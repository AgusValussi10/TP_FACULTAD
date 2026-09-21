<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['alumno', 'padre', 'admin']);

require_once __DIR__ . '/../database/db_config.php';

$rol        = $_SESSION['rol'];
$usuario_id = (int) $_SESSION['usuario_id'];
$alumno_id  = (int) ($_GET['alumno_id'] ?? 0);

// Validar acceso según rol
if ($rol === 'alumno') {
    $alumno_id = $usuario_id;
} elseif ($rol === 'padre') {
    if ($alumno_id <= 0 || !alumno_pertenece_a_padre($conn, $alumno_id, $usuario_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
        exit;
    }
} elseif ($alumno_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetro alumno_id requerido.']);
    exit;
}

// Nombre del alumno
$stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ? AND rol = 'alumno'");
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$alumno = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$alumno) {
    echo json_encode(['success' => false, 'message' => 'Alumno no encontrado.']);
    exit;
}

// Calificaciones del alumno agrupadas por materia y período (según mes de fecha_evaluacion)
$stmt = $conn->prepare(
    "SELECT m.nombre AS materia, c.evaluacion, c.nota,
            MONTH(c.fecha_evaluacion) AS mes,
            DATE_FORMAT(c.fecha_evaluacion, '%d/%m/%Y') AS fecha
     FROM calificaciones c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.alumno_id = ?
     ORDER BY m.nombre, c.fecha_evaluacion"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Agrupar por materia y trimestre (mes 3-5 = 1er Trim, 6-8 = 2do, 9-12 = 3er)
function mes_a_trimestre(int $mes): string {
    if ($mes >= 3 && $mes <= 5) return '1er Trimestre';
    if ($mes >= 6 && $mes <= 8) return '2do Trimestre';
    return '3er Trimestre';
}

$materias = [];
foreach ($rows as $r) {
    $mat  = $r['materia'];
    $trim = mes_a_trimestre((int) $r['mes']);
    if (!isset($materias[$mat])) {
        $materias[$mat] = [
            '1er Trimestre' => [],
            '2do Trimestre' => [],
            '3er Trimestre' => [],
        ];
    }
    $materias[$mat][$trim][] = [
        'evaluacion' => $r['evaluacion'],
        'nota'       => (int) $r['nota'],
        'fecha'      => $r['fecha'],
    ];
}

// Calcular promedios por trimestre y anual
$resultado = [];
foreach ($materias as $nombre => $trimestres) {
    $todas_las_notas = [];
    $periodos = [];
    foreach ($trimestres as $periodo => $cals) {
        $notas = array_column($cals, 'nota');
        $prom  = count($notas) > 0 ? round(array_sum($notas) / count($notas), 2) : null;
        $periodos[] = [
            'periodo'        => $periodo,
            'calificaciones' => $cals,
            'promedio'       => $prom,
        ];
        $todas_las_notas = array_merge($todas_las_notas, $notas);
    }
    $prom_anual = count($todas_las_notas) > 0
        ? round(array_sum($todas_las_notas) / count($todas_las_notas), 2)
        : null;

    $resultado[] = [
        'materia'       => $nombre,
        'periodos'      => $periodos,
        'promedio_anual'=> $prom_anual,
        'aprobada'      => $prom_anual !== null && $prom_anual >= NOTA_APROBACION,
    ];
}

echo json_encode([
    'success' => true,
    'alumno'  => htmlspecialchars($alumno['nombre']),
    'materias'=> $resultado,
]);
