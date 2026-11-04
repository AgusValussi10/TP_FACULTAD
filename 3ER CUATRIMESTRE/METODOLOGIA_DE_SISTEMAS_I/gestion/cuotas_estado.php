<?php

// GET → estado de las cuotas (RFG19).
//  - padre: ?alumno_id= de un hijo → cuotas con importes, recargo, total y resumen de deuda.
//  - admin: ?alumno_id= de cualquier alumno → lo mismo.
//  - docente: sin parámetros → alumnos de sus cursos con el estado de cada cuota del año (más las
//    impagas de años anteriores), SIN importes: decisión de diseño por RN14 (cobrar y ver montos
//    es tarea administrativa).
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['padre', 'admin', 'docente']);
errores_como_json();

require_once __DIR__ . '/../database/db_config.php';

$rol        = $_SESSION['rol'];
$usuario_id = (int) $_SESSION['usuario_id'];

if ($rol === 'docente') {
    $anio = (int) date('Y');
    $stmt = $conn->prepare(
        "SELECT u.id AS alumno_id, u.nombre AS alumno, c.nombre AS curso,
                cu.mes, cu.anio, cu.concepto, cu.estado, cu.fecha_vencimiento
         FROM usuarios u
         JOIN alumno_curso ac ON ac.alumno_id = u.id
         JOIN cursos c ON c.id = ac.curso_id
         LEFT JOIN cuotas cu ON cu.alumno_id = u.id AND (cu.anio = ? OR cu.estado = 'pendiente')
         WHERE u.rol = 'alumno' AND u.activo = 1
           AND EXISTS (SELECT 1 FROM materias m WHERE m.curso_id = c.id AND m.docente_id = ?)
         ORDER BY c.nombre, u.nombre, cu.fecha_vencimiento"
    );
    $stmt->bind_param('ii', $anio, $usuario_id);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    $alumnos = [];
    foreach ($filas as $f) {
        $id = (int) $f['alumno_id'];
        $alumnos[$id] ??= ['alumno_id' => $id, 'alumno' => $f['alumno'], 'curso' => $f['curso'], 'cuotas' => [], 'impagas' => 0, 'proximas_a_vencer' => 0];
        if ($f['mes'] === null) {
            continue;
        }
        $estado = estado_visual_cuota($f['estado'], $f['fecha_vencimiento']);
        $alumnos[$id]['cuotas'][] = [
            'periodo'       => ($f['concepto'] === 'matricula' ? 'Matrícula ' : '') . MESES[(int) $f['mes'] - 1]
                . ((int) $f['anio'] !== $anio ? ' ' . $f['anio'] : ''),
            'estado_visual' => $estado,
            'estado_texto'  => ESTADOS_CUOTA[$estado],
        ];
        $alumnos[$id]['impagas']           += $estado === 'impaga' ? 1 : 0;
        $alumnos[$id]['proximas_a_vencer'] += $estado === 'proxima_a_vencer' ? 1 : 0;
    }
    echo json_encode(['success' => true, 'anio' => $anio, 'alumnos' => array_values($alumnos)]);
    exit;
}

$alumno_id = (int) ($_GET['alumno_id'] ?? 0);
if ($alumno_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetro alumno_id requerido.']);
    exit;
}
if ($rol === 'padre' && !alumno_pertenece_a_padre($conn, $alumno_id, $usuario_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solo podés ver las cuotas de tus hijos.']);
    exit;
}

$datos = cuotas_de_alumno($conn, $alumno_id);
$conn->close();

echo json_encode(['success' => true, 'dias_aviso' => DIAS_AVISO_VENCIMIENTO] + $datos);
