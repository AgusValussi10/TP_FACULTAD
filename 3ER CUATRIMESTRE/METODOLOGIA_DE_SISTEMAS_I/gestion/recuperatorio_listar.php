<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['docente', 'admin']);
errores_como_json();

require_once __DIR__ . '/../database/db_config.php';

$rol = $_SESSION['rol'];

// RFG06 — Admin: todos los recuperatorios de la institución, con filtros.
if ($rol === 'admin') {
    $curso_id = (int) ($_GET['curso_id'] ?? 0);
    $estado   = $_GET['estado'] ?? '';
    if (!in_array($estado, ['', 'pendiente', 'aprobado', 'desaprobado'], true)) {
        $estado = '';
    }

    $stmt = $conn->prepare(
        "SELECT r.id, u.nombre AS alumno, m.nombre AS materia, c.nombre AS curso,
                d.nombre AS docente, r.periodo, r.fecha,
                DATE_FORMAT(r.fecha, '%d/%m/%Y') AS fecha_ar, r.turno, r.estado, r.nota
         FROM recuperatorios r
         JOIN usuarios u ON u.id = r.alumno_id
         JOIN materias m ON m.id = r.materia_id
         JOIN cursos   c ON c.id = m.curso_id
         LEFT JOIN usuarios d ON d.id = m.docente_id
         WHERE (? = 0 OR c.id = ?) AND (? = '' OR r.estado = ?)
         ORDER BY r.fecha IS NULL, r.fecha, c.nombre, m.nombre, u.nombre"
    );
    $stmt->bind_param('iiss', $curso_id, $curso_id, $estado, $estado);
    $stmt->execute();
    $recuperatorios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $cursos = $conn->query("SELECT id, nombre FROM cursos ORDER BY nivel_educativo, nombre")->fetch_all(MYSQLI_ASSOC);
    $totales = $conn->query("SELECT estado, COUNT(*) AS n FROM recuperatorios GROUP BY estado")->fetch_all(MYSQLI_ASSOC);
    $conn->close();

    $resumen = ['pendiente' => 0, 'aprobado' => 0, 'desaprobado' => 0];
    foreach ($totales as $t) {
        $resumen[$t['estado']] = (int) $t['n'];
    }
    foreach ($recuperatorios as &$r) {
        $r['nota'] = $r['nota'] !== null ? (int) $r['nota'] : null;
    }
    unset($r);

    echo json_encode([
        'success'        => true,
        'recuperatorios' => $recuperatorios,
        'cursos'         => $cursos,
        'resumen'        => $resumen,
        'hoy'            => date('Y-m-d'),
    ]);
    exit;
}

// Docente: alumnos del curso de una materia propia + sus recuperatorios.
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

// Un renglón por alumno. El promedio va en subconsulta: con un JOIN directo a
// recuperatorios el alumno salía repetido y se duplicaba el conteo de notas.
$stmt = $conn->prepare(
    "SELECT u.id AS alumno_id, u.nombre,
            (SELECT ROUND(AVG(c.nota), 2) FROM calificaciones c WHERE c.alumno_id = u.id AND c.materia_id = ?) AS promedio,
            (SELECT COUNT(*)              FROM calificaciones c WHERE c.alumno_id = u.id AND c.materia_id = ?) AS evaluaciones
     FROM alumno_curso ac
     JOIN usuarios u ON u.id = ac.alumno_id
     WHERE ac.curso_id = ? AND u.activo = 1
     ORDER BY u.nombre"
);
$stmt->bind_param('iii', $materia_id, $materia_id, $materia['curso_id']);
$stmt->execute();
$alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
    "SELECT r.id, r.alumno_id, u.nombre AS alumno, r.periodo, r.fecha,
            DATE_FORMAT(r.fecha, '%d/%m/%Y') AS fecha_ar, r.turno, r.estado, r.nota
     FROM recuperatorios r
     JOIN usuarios u ON u.id = r.alumno_id
     WHERE r.materia_id = ?
     ORDER BY r.estado <> 'pendiente', r.fecha IS NULL, r.fecha, u.nombre"
);
$stmt->bind_param('i', $materia_id);
$stmt->execute();
$recuperatorios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$por_alumno = [];
foreach ($recuperatorios as &$r) {
    $r['nota'] = $r['nota'] !== null ? (int) $r['nota'] : null;
    $por_alumno[$r['alumno_id']][] = $r['estado'];
}
unset($r);

foreach ($alumnos as &$a) {
    $a['promedio']       = $a['promedio'] !== null ? (float) $a['promedio'] : null;
    $a['evaluaciones']   = (int) $a['evaluaciones'];
    $a['necesita_recup'] = $a['promedio'] !== null && $a['promedio'] < NOTA_APROBACION;
    $estados             = $por_alumno[$a['alumno_id']] ?? [];
    $a['recups_pendientes'] = count(array_filter($estados, fn($e) => $e === 'pendiente'));
    $a['recups_total']      = count($estados);
}
unset($a);

echo json_encode([
    'success'         => true,
    'materia'         => $materia['nombre'],
    'curso'           => $materia['curso_nombre'],
    'alumnos'         => $alumnos,
    'recuperatorios'  => $recuperatorios,
    'nota_aprobacion' => NOTA_APROBACION,
    'periodo_actual'  => periodo_desde_fecha(date('Y-m-d')),
    'hoy'             => date('Y-m-d'),
]);
