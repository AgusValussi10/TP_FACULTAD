<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin', 'docente']);

require_once __DIR__ . '/../database/db_config.php';

$alumno_id = (int) ($_GET['alumno_id'] ?? 0);
if ($alumno_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetro alumno_id requerido.']);
    exit;
}

// Un docente solo puede ver el legajo de alumnos en cursos donde dicta alguna materia.
if (($_SESSION['rol'] ?? '') === 'docente' && !alumno_pertenece_a_docente($conn, $alumno_id, (int) $_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tenés acceso al legajo de ese alumno.']);
    $conn->close();
    exit;
}

// Datos personales
$stmt = $conn->prepare("SELECT id, nombre, usuario, activo, DATE_FORMAT(created_at,'%d/%m/%Y') AS alta FROM usuarios WHERE id = ? AND rol = 'alumno'");
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$alumno = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$alumno) {
    echo json_encode(['success' => false, 'message' => 'Alumno no encontrado.']);
    exit;
}

// Curso actual
$stmt = $conn->prepare(
    "SELECT c.nombre AS curso, c.nivel_educativo FROM alumno_curso ac JOIN cursos c ON c.id = ac.curso_id WHERE ac.alumno_id = ?"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$curso = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Sprint 5 (RFG12): cuotas — tabla no creada aún.
$cuotas_pendientes = [];

// Calificaciones
$stmt = $conn->prepare(
    "SELECT m.nombre AS materia, c.evaluacion, c.nota, DATE_FORMAT(c.fecha_evaluacion,'%d/%m/%Y') AS fecha
     FROM calificaciones c JOIN materias m ON m.id = c.materia_id
     WHERE c.alumno_id = ? ORDER BY m.nombre, c.fecha_evaluacion"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$calificaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Asistencia resumida
$stmt = $conn->prepare(
    "SELECT m.nombre AS materia,
            SUM(a.estado = 'ausente') AS faltas,
            COUNT(a.id) AS total
     FROM asistencias a JOIN materias m ON m.id = a.materia_id
     WHERE a.alumno_id = ?
     GROUP BY m.nombre ORDER BY m.nombre"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$asistencia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recuperatorios
$stmt = $conn->prepare(
    "SELECT m.nombre AS materia, r.periodo, DATE_FORMAT(r.fecha,'%d/%m/%Y') AS fecha, r.turno, r.estado
     FROM recuperatorios r JOIN materias m ON m.id = r.materia_id
     WHERE r.alumno_id = ? ORDER BY r.created_at DESC"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$recuperatorios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sanciones
$stmt = $conn->prepare(
    "SELECT s.tipo, s.descripcion, DATE_FORMAT(s.fecha,'%d/%m/%Y') AS fecha, u.nombre AS registrado_por
     FROM sanciones s JOIN usuarios u ON u.id = s.registrado_por
     WHERE s.alumno_id = ? ORDER BY s.fecha DESC"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$sanciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Atenciones de enfermería
$stmt = $conn->prepare(
    "SELECT ae.motivo, ae.hora, ae.observaciones, DATE_FORMAT(ae.created_at,'%d/%m/%Y') AS fecha, u.nombre AS atendido_por
     FROM atenciones_enfermeria ae JOIN usuarios u ON u.id = ae.atendido_por
     WHERE ae.alumno_id = ? ORDER BY ae.created_at DESC"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$enfermeria = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Servicios reservados
$stmt = $conn->prepare(
    "SELECT servicio, mes, anio, estado FROM reservas_servicios WHERE alumno_id = ? ORDER BY anio DESC, mes DESC"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$servicios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

echo json_encode([
    'success'           => true,
    'alumno'            => $alumno,
    'curso'             => $curso,
    'calificaciones'    => $calificaciones,
    'asistencia'        => $asistencia,
    'recuperatorios'    => $recuperatorios,
    'sanciones'         => $sanciones,
    'enfermeria'        => $enfermeria,
    'servicios'         => $servicios,
    'cuotas_pendientes' => $cuotas_pendientes,
]);
