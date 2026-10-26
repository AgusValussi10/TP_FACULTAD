<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['padre', 'docente']);
errores_como_json();

require_once __DIR__ . '/../database/db_config.php';

$rol        = $_SESSION['rol'];
$usuario_id = (int) $_SESSION['usuario_id'];

$select = "SELECT cn.id, cn.calificacion_id, cn.mensaje, cn.respuesta, cn.estado,
                  DATE_FORMAT(cn.created_at, '%d/%m/%Y %H:%i') AS fecha,
                  DATE_FORMAT(cn.respondida_at, '%d/%m/%Y %H:%i') AS fecha_respuesta,
                  u.nombre AS alumno, m.nombre AS materia, c.evaluacion, c.nota
           FROM consultas_notas cn
           JOIN calificaciones c ON c.id = cn.calificacion_id
           JOIN materias m       ON m.id = c.materia_id
           JOIN usuarios u       ON u.id = cn.alumno_id";

if ($rol === 'padre') {
    // Sus propias consultas, opcionalmente filtradas por hijo.
    $alumno_id = (int) ($_GET['alumno_id'] ?? 0);
    if ($alumno_id > 0) {
        if (!alumno_pertenece_a_padre($conn, $alumno_id, $usuario_id)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }
        $stmt = $conn->prepare("$select WHERE cn.padre_id = ? AND cn.alumno_id = ? ORDER BY cn.created_at DESC");
        $stmt->bind_param('ii', $usuario_id, $alumno_id);
    } else {
        $stmt = $conn->prepare("$select WHERE cn.padre_id = ? ORDER BY cn.created_at DESC");
        $stmt->bind_param('i', $usuario_id);
    }
} else {
    // Docente: solo consultas sobre notas de sus materias; pendientes primero.
    $stmt = $conn->prepare("$select WHERE m.docente_id = ? ORDER BY cn.estado = 'pendiente' DESC, cn.created_at DESC");
    $stmt->bind_param('i', $usuario_id);
}

$stmt->execute();
$consultas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'consultas' => $consultas]);
