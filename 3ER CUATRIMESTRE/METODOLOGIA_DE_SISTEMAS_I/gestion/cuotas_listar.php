<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('admin');

require_once __DIR__ . '/../database/db_config.php';

$alumno_id = (int) ($_GET['alumno_id'] ?? 0);

if ($alumno_id > 0) {
    $stmt = $conn->prepare(
        "SELECT id, concepto, mes, anio, importe, recargo,
                fecha_vencimiento AS fecha_vencimiento_iso,
                DATE_FORMAT(fecha_vencimiento,'%d/%m/%Y') AS fecha_vencimiento,
                estado, DATE_FORMAT(fecha_pago,'%d/%m/%Y') AS fecha_pago
         FROM cuotas
         WHERE alumno_id = ?
         ORDER BY anio DESC, mes DESC, created_at DESC"
    );
    $stmt->bind_param('i', $alumno_id);
    $stmt->execute();
    $cuotas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'cuotas' => $cuotas]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT u.id, u.nombre, c.nombre AS curso, ac.condicion,
            COALESCE((
                SELECT SUM(cu.importe + cu.recargo) FROM cuotas cu
                WHERE cu.alumno_id = u.id AND cu.estado = 'pendiente'
            ), 0) AS deuda_pendiente
     FROM usuarios u
     LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id
     LEFT JOIN cursos c ON c.id = ac.curso_id
     WHERE u.rol = 'alumno' AND u.activo = 1
     ORDER BY u.nombre"
);
$stmt->execute();
$res = $stmt->get_result();

$alumnos = [];
while ($row = $res->fetch_assoc()) {
    $row['condicion']       = $row['condicion'] ?? 'regular';
    $row['deuda_pendiente'] = (float) $row['deuda_pendiente'];
    $alumnos[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'alumnos' => $alumnos]);
