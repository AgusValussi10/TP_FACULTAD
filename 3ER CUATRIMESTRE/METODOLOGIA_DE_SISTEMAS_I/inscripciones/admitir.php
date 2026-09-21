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

require_once '../database/db_config.php';

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

// Verificar que el usuario no exista
$check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$check->bind_param('s', $usuario);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya existe. Elegí otro.']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// RFG08: validar disponibilidad de vacantes si se indica un curso de destino.
if ($curso_id > 0) {
    $vac = $conn->prepare(
        "SELECT c.capacidad, COUNT(ac.alumno_id) AS inscriptos
         FROM cursos c LEFT JOIN alumno_curso ac ON ac.curso_id = c.id
         WHERE c.id = ? GROUP BY c.capacidad"
    );
    $vac->bind_param('i', $curso_id);
    $vac->execute();
    $rowVac = $vac->get_result()->fetch_assoc();
    $vac->close();
    if ($rowVac) {
        $disponibles = (int)$rowVac['capacidad'] - (int)$rowVac['inscriptos'];
        if ($disponibles <= 0) {
            echo json_encode(['success' => false, 'message' => 'El curso seleccionado no tiene vacantes disponibles.']);
            $conn->close();
            exit;
        }
    }
}

// Crear usuario con rol alumno
$hash = password_hash($password, PASSWORD_BCRYPT);
$rol  = 'alumno';
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $nombre, $usuario, $hash, $rol);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al crear el usuario.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Marcar solicitud como admitida
$upd = $conn->prepare("UPDATE solicitudes_inscripcion SET estado = 'admitido' WHERE id = ?");
$upd->bind_param('i', $id);
$upd->execute();
$upd->close();

// RFG08: inscribir al alumno en el curso indicado (si fue especificado).
if ($curso_id > 0) {
    $nuevoAlumnoId = $conn->insert_id;
    if (!$nuevoAlumnoId) {
        $sel = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $sel->bind_param('s', $usuario);
        $sel->execute();
        $nuevoAlumnoId = (int)($sel->get_result()->fetch_assoc()['id'] ?? 0);
        $sel->close();
    }
    if ($nuevoAlumnoId) {
        $ins = $conn->prepare("INSERT IGNORE INTO alumno_curso (alumno_id, curso_id) VALUES (?, ?)");
        $ins->bind_param('ii', $nuevoAlumnoId, $curso_id);
        $ins->execute();
        $ins->close();
    }
}

$conn->close();

echo json_encode(['success' => true, 'usuario' => $usuario]);
