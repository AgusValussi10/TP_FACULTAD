<?php
require_once '../auth/session.php';
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// En PHP 8.1+ mysqli lanza excepciones ante errores de SQL; sin esto PHP
// responde HTML y el front solo ve "No se pudo conectar con el servidor".
set_exception_handler(function (Throwable $e) {
    global $conn;
    if ($conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignorado) {}
    }
    error_log('admitir.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al admitir: ' . $e->getMessage()]);
});

require_once '../database/db_config.php';
require_once '../gestion/helpers.php';

/**
 * Crea el usuario del alumno admitido, validando que el nombre de usuario no
 * exista todavía. Devuelve ['error' => null] si se creó correctamente, o
 * ['error' => string] con el motivo si algo falló.
 */
function crearUsuarioAlumno(mysqli $conn, string $usuario, string $password, string $nombre): array
{
    $check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $check->bind_param('s', $usuario);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        return ['error' => 'El nombre de usuario ya existe. Elegí otro.'];
    }
    $check->close();

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $rol  = 'alumno';
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $nombre, $usuario, $hash, $rol);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['error' => 'Error al crear el usuario.'];
    }
    $stmt->close();

    return ['error' => null];
}

/** Marca la solicitud de inscripción como admitida. */
function marcarSolicitudAdmitida(mysqli $conn, int $id): void
{
    $upd = $conn->prepare("UPDATE solicitudes_inscripcion SET estado = 'admitido' WHERE id = ?");
    $upd->bind_param('i', $id);
    $upd->execute();
    $upd->close();
}

$id       = (int)($_POST['id']      ?? 0);
$usuario  = trim($_POST['usuario']  ?? '');
$password = trim($_POST['password'] ?? '');
$nombre   = trim($_POST['nombre']   ?? '');
$curso_id = (int)($_POST['curso_id'] ?? 0);

if (!$id || !$usuario || !$password || !$nombre) {
    echo json_encode(['success' => false, 'message' => 'Completá todos los campos.']);
    exit;
}

if (strlen($password) < 4) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres.']);
    exit;
}

// RN03: validar requisitos académicos antes de admitir (RFG04).
$sol = $conn->prepare("SELECT nivel_educativo, fecha_nacimiento FROM solicitudes_inscripcion WHERE id = ?");
$sol->bind_param('i', $id);
$sol->execute();
$solicitud = $sol->get_result()->fetch_assoc();
$sol->close();

if (!$solicitud) {
    echo json_encode(['success' => false, 'message' => 'La solicitud no existe.']);
    $conn->close();
    exit;
}

$edad = edad_desde_fecha_nacimiento($solicitud['fecha_nacimiento']);
$nivel_anterior_confirmado = ($_POST['nivel_anterior_confirmado'] ?? '') === '1';

if ($solicitud['nivel_educativo'] === 'Inicial') {
    if ($edad < EDAD_MINIMA_INICIAL) {
        echo json_encode(['success' => false, 'message' =>
            "No cumple la edad mínima para Nivel Inicial (tiene {$edad} años, se requieren " . EDAD_MINIMA_INICIAL . ")."]);
        $conn->close();
        exit;
    }
} elseif (!$nivel_anterior_confirmado) {
    echo json_encode(['success' => false, 'message' =>
        'Falta confirmar que el alumno aprobó el nivel anterior antes de admitir.']);
    $conn->close();
    exit;
}

/** Corta la admisión deshaciendo todo lo hecho dentro de la transacción. */
function abortarAdmision(mysqli $conn, string $mensaje): void
{
    $conn->rollback();
    $conn->close();
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

// Todo en una transacción: si la inscripción en el curso falla, no queda
// un usuario creado ni la solicitud marcada como admitida.
$conn->begin_transaction();

// RFG08: validar disponibilidad de vacantes si se indica un curso de destino.
// FOR UPDATE bloquea la fila del curso hasta el commit, así dos admisiones
// simultáneas no pueden ocupar la misma última vacante.
$vacantesRestantes = null;
if ($curso_id > 0) {
    $cur = $conn->prepare("SELECT capacidad FROM cursos WHERE id = ? FOR UPDATE");
    $cur->bind_param('i', $curso_id);
    $cur->execute();
    $rowCurso = $cur->get_result()->fetch_assoc();
    $cur->close();
    if (!$rowCurso) {
        abortarAdmision($conn, 'El curso seleccionado no existe.');
    }

    $cnt = $conn->prepare("SELECT COUNT(*) AS inscriptos FROM alumno_curso WHERE curso_id = ?");
    $cnt->bind_param('i', $curso_id);
    $cnt->execute();
    $inscriptos = (int)$cnt->get_result()->fetch_assoc()['inscriptos'];
    $cnt->close();

    $disponibles = (int)$rowCurso['capacidad'] - $inscriptos;
    if ($disponibles <= 0) {
        abortarAdmision($conn, 'El curso seleccionado no tiene vacantes disponibles.');
    }
    $vacantesRestantes = $disponibles - 1;
}

$resultadoUsuario = crearUsuarioAlumno($conn, $usuario, $password, $nombre);
if ($resultadoUsuario['error']) {
    abortarAdmision($conn, $resultadoUsuario['error']);
}

$nuevoAlumnoId = $conn->insert_id;

marcarSolicitudAdmitida($conn, $id);

if ($solicitud['nivel_educativo'] !== 'Inicial' && $nivel_anterior_confirmado) {
    $upd2 = $conn->prepare("UPDATE solicitudes_inscripcion SET nivel_anterior_aprobado = 1 WHERE id = ?");
    $upd2->bind_param('i', $id);
    $upd2->execute();
    $upd2->close();
}

// RFG08: inscribir al alumno en el curso indicado (si se especificó uno).
// Al sumarse a alumno_curso, la vacante queda descontada automáticamente
// (vacantes = capacidad - inscriptos).
if ($curso_id > 0) {
    $ins = $conn->prepare("INSERT INTO alumno_curso (alumno_id, curso_id) VALUES (?, ?)");
    $ins->bind_param('ii', $nuevoAlumnoId, $curso_id);
    $ok = $ins->execute();
    $ins->close();
    if (!$ok) {
        abortarAdmision($conn, 'No se pudo inscribir al alumno en el curso.');
    }
}

$conn->commit();
$conn->close();

echo json_encode([
    'success'            => true,
    'usuario'            => $usuario,
    'vacantes_restantes' => $vacantesRestantes,
]);
