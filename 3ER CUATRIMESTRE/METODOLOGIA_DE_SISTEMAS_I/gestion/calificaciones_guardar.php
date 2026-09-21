<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$materia_id       = (int) ($_POST['materia_id'] ?? 0);
$evaluacion       = trim($_POST['evaluacion'] ?? '');
$fecha_evaluacion = trim($_POST['fecha_evaluacion'] ?? '');
$notas            = json_decode($_POST['notas'] ?? '', true);

if ($materia_id <= 0 || $evaluacion === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_evaluacion)
    || !is_array($notas) || empty($notas)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$hoy = date('Y-m-d');

if ($fecha_evaluacion > $hoy) {
    echo json_encode(['success' => false, 'message' => 'La fecha de evaluación no puede ser futura.']);
    exit;
}

if (dias_habiles_entre($fecha_evaluacion, $hoy) > PLAZO_CARGA_NOTA_DIAS_HABILES) {
    echo json_encode([
        'success' => false,
        'message' => 'Venció el plazo de ' . PLAZO_CARGA_NOTA_DIAS_HABILES . ' días hábiles para cargar notas de esta evaluación.',
    ]);
    exit;
}

$docente_id = (int) $_SESSION['usuario_id'];
$materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
if (!$materia) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La materia no pertenece al docente.']);
    exit;
}

$alumnos_curso = [];
$stmt = $conn->prepare("SELECT alumno_id FROM alumno_curso WHERE curso_id = ?");
$stmt->bind_param('i', $materia['curso_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alumnos_curso[(int) $row['alumno_id']] = true;
}
$stmt->close();

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO calificaciones (alumno_id, materia_id, evaluacion, nota, fecha_evaluacion)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE nota = VALUES(nota), fecha_evaluacion = VALUES(fecha_evaluacion)"
    );

    $guardados = 0;
    $omitidos  = 0;
    foreach ($notas as $alumno_id => $nota) {
        $alumno_id = (int) $alumno_id;
        if (!isset($alumnos_curso[$alumno_id])) {
            continue;
        }

        if (!is_numeric($nota)) {
            $omitidos++;
            continue;
        }
        $notaInt = (int) $nota;
        if ($notaInt != $nota || $notaInt < 0 || $notaInt > 10) {
            $omitidos++;
            continue;
        }

        $stmt->bind_param('iisis', $alumno_id, $materia_id, $evaluacion, $notaInt, $fecha_evaluacion);
        $stmt->execute();
        $guardados++;
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'guardados' => $guardados, 'omitidos' => $omitidos]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al guardar las calificaciones.']);
}

$conn->close();
