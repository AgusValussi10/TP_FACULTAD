<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['docente', 'admin']);
errores_como_json();

require_once __DIR__ . '/../database/db_config.php';

$rol  = $_SESSION['rol'];
$anio = (int) ($_GET['anio'] ?? date('Y'));
if ($anio < 2024 || $anio > 2100) {
    echo json_encode(['success' => false, 'message' => 'Año inválido.']);
    exit;
}

// RFG05 — Admin (coordinación): vista general de todas las materias del año,
// con o sin planificación cargada, para controlar quién falta entregar.
if ($rol === 'admin') {
    $curso_id = (int) ($_GET['curso_id'] ?? 0);

    $sql = "SELECT m.id AS materia_id, m.nombre AS materia, c.id AS curso_id, c.nombre AS curso,
                   c.nivel_educativo, u.nombre AS docente,
                   p.id AS plan_id, p.contenidos, p.observaciones,
                   DATE_FORMAT(p.created_at, '%d/%m/%Y') AS fecha_carga,
                   DATE_FORMAT(p.actualizado_en, '%d/%m/%Y') AS fecha_actualizacion
            FROM materias m
            JOIN cursos c ON c.id = m.curso_id
            LEFT JOIN usuarios u ON u.id = m.docente_id
            LEFT JOIN planificaciones p ON p.materia_id = m.id AND p.anio = ?
            WHERE (? = 0 OR c.id = ?)
            ORDER BY c.nivel_educativo, c.nombre, m.nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $anio, $curso_id, $curso_id);
    $stmt->execute();
    $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $cursos = $conn->query("SELECT id, nombre FROM cursos ORDER BY nivel_educativo, nombre")->fetch_all(MYSQLI_ASSOC);
    $conn->close();

    $cargadas = count(array_filter($materias, fn($m) => $m['plan_id'] !== null));
    echo json_encode([
        'success'  => true,
        'anio'     => $anio,
        'materias' => $materias,
        'cursos'   => $cursos,
        'resumen'  => ['total' => count($materias), 'cargadas' => $cargadas, 'pendientes' => count($materias) - $cargadas],
    ]);
    exit;
}

// Docente: la planificación de una de sus materias.
$materia_id = (int) ($_GET['materia_id'] ?? 0);
if ($materia_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Materia inválida.']);
    exit;
}

$docente_id = (int) $_SESSION['usuario_id'];
$materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
if (!$materia) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La materia no pertenece al docente.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT contenidos, observaciones,
            DATE_FORMAT(created_at, '%d/%m/%Y') AS fecha_carga,
            DATE_FORMAT(actualizado_en, '%d/%m/%Y') AS fecha_actualizacion
     FROM planificaciones WHERE materia_id = ? AND anio = ?"
);
$stmt->bind_param('ii', $materia_id, $anio);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'plan' => $plan]);
