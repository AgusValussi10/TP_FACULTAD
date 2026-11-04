<?php

// POST → registra una sanción disciplinaria y avisa a la familia (RFG14 / RN13).
// Admin: a cualquier alumno. Docente: solo a alumnos de los cursos donde dicta.
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin', 'docente']);
errores_como_json();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

function fallar(string $mensaje, int $http = 200): void
{
    http_response_code($http);
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

$alumno_id   = (int) ($_POST['alumno_id'] ?? 0);
$tipo        = trim($_POST['tipo'] ?? '');
$fecha       = trim($_POST['fecha'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$dias_txt    = trim($_POST['dias_suspension'] ?? '');
$usuario_id  = (int) $_SESSION['usuario_id'];

if (!isset(TIPOS_SANCION[$tipo])) {
    fallar('Elegí un tipo de sanción válido.');
}
$f = DateTime::createFromFormat('!Y-m-d', $fecha);
if (!$f || $f->format('Y-m-d') !== $fecha) {
    fallar('Fecha inválida.');
}
$hoy = new DateTime('today');
if ($f > $hoy) {
    fallar('La fecha de la sanción no puede ser futura.');
}
if ($f < (clone $hoy)->modify('-' . SANCION_DIAS_ATRAS_MAX . ' days')) {
    fallar('Solo se pueden registrar sanciones de los últimos ' . SANCION_DIAS_ATRAS_MAX . ' días.');
}
$largo = mb_strlen($descripcion);
if ($largo < 10 || $largo > 1000) {
    fallar('La descripción debe tener entre 10 y 1000 caracteres.');
}
$dias = null;
if ($tipo === 'suspension') {
    if (!ctype_digit($dias_txt) || (int) $dias_txt < 1 || (int) $dias_txt > SUSPENSION_DIAS_MAX) {
        fallar('Indicá los días de suspensión (entre 1 y ' . SUSPENSION_DIAS_MAX . ').');
    }
    $dias = (int) $dias_txt;
}

$stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ? AND rol = 'alumno'");
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$alumno = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$alumno) {
    fallar('Elegí un alumno.');
}
if ($_SESSION['rol'] === 'docente' && !alumno_pertenece_a_docente($conn, $alumno_id, $usuario_id)) {
    fallar('Solo podés sancionar a alumnos de tus cursos.', 403);
}

$stmt = $conn->prepare("SELECT padre_id FROM padre_alumno WHERE alumno_id = ?");
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$padres = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'padre_id');
$stmt->close();
// Flujo alternativo (como el 6.A de enfermería): sin padre vinculado se guarda igual
// y queda marcada para que administración avise a la familia por otro medio.
$sin_contacto = empty($padres) ? 1 : 0;

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO sanciones (alumno_id, tipo, descripcion, fecha, registrado_por, dias_suspension, sin_contacto)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssiii', $alumno_id, $tipo, $descripcion, $fecha, $usuario_id, $dias, $sin_contacto);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();

    $detalle = TIPOS_SANCION[$tipo] . ($dias ? " ($dias " . ($dias === 1 ? 'día' : 'días') . ')' : '');
    $mensaje = "Se registró una sanción a {$alumno['nombre']} el " . $f->format('d/m/Y') . ": $detalle. $descripcion";
    foreach ($padres as $padre_id) {
        crear_notificacion($conn, (int) $padre_id, $alumno_id, 'sancion', $mensaje);
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
$conn->close();

echo json_encode([
    'success'      => true,
    'id'           => $id,
    'sin_contacto' => (bool) $sin_contacto,
    'message'      => $sin_contacto
        ? 'Sanción registrada. El alumno no tiene padre/tutor vinculado: administración debe avisar a la familia.'
        : 'Sanción registrada y notificada a la familia.',
]);
