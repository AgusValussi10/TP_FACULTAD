<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin', 'docente']);
errores_como_json();

require_once __DIR__ . '/../database/db_config.php';

$es_admin  = ($_SESSION['rol'] ?? '') === 'admin';
$alumno_id = (int) ($_GET['alumno_id'] ?? 0);
if ($alumno_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetro alumno_id requerido.']);
    exit;
}

// Un docente solo puede ver el legajo de alumnos en cursos donde dicta alguna materia.
if (!$es_admin && !alumno_pertenece_a_docente($conn, $alumno_id, (int) $_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tenés acceso al legajo de ese alumno.']);
    $conn->close();
    exit;
}

/** Ejecuta una consulta preparada con un único parámetro entero y devuelve todas las filas. */
function filas(mysqli $conn, string $sql, int $id): array
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ── Identificación ──────────────────────────────────────────────
$alumno = filas($conn,
    "SELECT id, nombre, usuario, activo, DATE_FORMAT(created_at,'%d/%m/%Y') AS alta
     FROM usuarios WHERE id = ? AND rol = 'alumno'", $alumno_id)[0] ?? null;
if (!$alumno) {
    echo json_encode(['success' => false, 'message' => 'Alumno no encontrado.']);
    exit;
}
$alumno['activo'] = (int) $alumno['activo'];

// Datos personales (tabla alumnos_datos; puede no existir la fila si el alumno es viejo).
$datos = filas($conn,
    "SELECT d.dni, d.fecha_nacimiento, DATE_FORMAT(d.fecha_nacimiento,'%d/%m/%Y') AS fecha_nacimiento_ar,
            d.domicilio, d.telefono, d.email, d.tutor_nombre, d.contacto_emergencia,
            d.obra_social, d.observaciones_salud, d.solicitud_id,
            DATE_FORMAT(s.created_at,'%d/%m/%Y') AS fecha_solicitud, s.nivel_educativo AS nivel_solicitado,
            DATE_FORMAT(d.actualizado_en,'%d/%m/%Y') AS actualizado
     FROM alumnos_datos d
     LEFT JOIN solicitudes_inscripcion s ON s.id = d.solicitud_id
     WHERE d.alumno_id = ?", $alumno_id)[0] ?? null;
if ($datos && $datos['fecha_nacimiento']) {
    $datos['edad'] = edad_desde_fecha_nacimiento($datos['fecha_nacimiento']);
}

$curso = filas($conn,
    "SELECT c.id, c.nombre AS curso, c.nivel_educativo, ac.condicion
     FROM alumno_curso ac JOIN cursos c ON c.id = ac.curso_id WHERE ac.alumno_id = ?", $alumno_id)[0] ?? null;

$tutores = filas($conn,
    "SELECT u.nombre, u.usuario, u.activo
     FROM padre_alumno pa JOIN usuarios u ON u.id = pa.padre_id
     WHERE pa.alumno_id = ? ORDER BY u.nombre", $alumno_id);

// ── Académico ──────────────────────────────────────────────────
$calificaciones = filas($conn,
    "SELECT m.nombre AS materia, c.evaluacion, c.nota, c.fecha_evaluacion,
            DATE_FORMAT(c.fecha_evaluacion,'%d/%m/%Y') AS fecha
     FROM calificaciones c JOIN materias m ON m.id = c.materia_id
     WHERE c.alumno_id = ? ORDER BY m.nombre, c.fecha_evaluacion", $alumno_id);
foreach ($calificaciones as &$c) {
    $c['nota']      = (int) $c['nota'];
    $c['trimestre'] = periodo_desde_fecha($c['fecha_evaluacion']);
    unset($c['fecha_evaluacion']);
}
unset($c);

$asistencia = filas($conn,
    "SELECT m.nombre AS materia,
            SUM(a.estado = 'presente') AS presentes,
            SUM(a.estado = 'tarde')    AS tardes,
            SUM(a.estado = 'ausente')  AS faltas,
            COUNT(a.id)                AS total
     FROM asistencias a JOIN materias m ON m.id = a.materia_id
     WHERE a.alumno_id = ?
     GROUP BY m.nombre ORDER BY m.nombre", $alumno_id);
foreach ($asistencia as &$a) {
    foreach (['presentes', 'tardes', 'faltas', 'total'] as $k) {
        $a[$k] = (int) $a[$k];
    }
    $a['porcentaje'] = $a['total'] ? round(($a['presentes'] + $a['tardes']) * 100 / $a['total'], 1) : null;
    $a['semaforo']   = calcular_semaforo($a['faltas'])['label'];
}
unset($a);

$recuperatorios = filas($conn,
    "SELECT m.nombre AS materia, r.periodo, DATE_FORMAT(r.fecha,'%d/%m/%Y') AS fecha, r.turno, r.estado, r.nota
     FROM recuperatorios r JOIN materias m ON m.id = r.materia_id
     WHERE r.alumno_id = ? ORDER BY r.fecha IS NULL, r.fecha DESC", $alumno_id);

// Resumen por materia del curso actual: docente, promedio, faltas, recuperatorios.
$materias = [];
if ($curso) {
    $materias = filas($conn,
        "SELECT m.nombre AS materia, d.nombre AS docente,
                (SELECT ROUND(AVG(c.nota),2) FROM calificaciones c WHERE c.materia_id = m.id AND c.alumno_id = ac.alumno_id) AS promedio,
                (SELECT COUNT(*) FROM calificaciones c WHERE c.materia_id = m.id AND c.alumno_id = ac.alumno_id) AS evaluaciones,
                (SELECT COUNT(*) FROM asistencias a WHERE a.materia_id = m.id AND a.alumno_id = ac.alumno_id AND a.estado = 'ausente') AS faltas,
                (SELECT COUNT(*) FROM recuperatorios r WHERE r.materia_id = m.id AND r.alumno_id = ac.alumno_id AND r.estado = 'pendiente') AS recups_pendientes
         FROM alumno_curso ac
         JOIN materias m ON m.curso_id = ac.curso_id
         LEFT JOIN usuarios d ON d.id = m.docente_id
         WHERE ac.alumno_id = ? ORDER BY m.nombre", $alumno_id);
    foreach ($materias as &$m) {
        $m['promedio']          = $m['promedio'] !== null ? (float) $m['promedio'] : null;
        $m['evaluaciones']      = (int) $m['evaluaciones'];
        $m['faltas']            = (int) $m['faltas'];
        $m['recups_pendientes'] = (int) $m['recups_pendientes'];
        $m['situacion']         = $m['promedio'] === null ? 'Sin notas'
                                : ($m['promedio'] >= NOTA_APROBACION ? 'Aprobando' : 'En riesgo');
    }
    unset($m);
}

// ── Convivencia, salud y servicios ─────────────────────────────
$sanciones = filas($conn,
    "SELECT s.tipo, s.descripcion, DATE_FORMAT(s.fecha,'%d/%m/%Y') AS fecha, u.nombre AS registrado_por
     FROM sanciones s JOIN usuarios u ON u.id = s.registrado_por
     WHERE s.alumno_id = ? ORDER BY s.fecha DESC", $alumno_id);

$enfermeria = filas($conn,
    "SELECT ae.motivo, TIME_FORMAT(ae.hora,'%H:%i') AS hora, ae.observaciones,
            DATE_FORMAT(ae.created_at,'%d/%m/%Y') AS fecha, u.nombre AS atendido_por
     FROM atenciones_enfermeria ae JOIN usuarios u ON u.id = ae.atendido_por
     WHERE ae.alumno_id = ? ORDER BY ae.created_at DESC", $alumno_id);

$servicios = filas($conn,
    "SELECT servicio, mes, anio, estado FROM reservas_servicios WHERE alumno_id = ? ORDER BY anio DESC, mes DESC", $alumno_id);

$deportes = filas($conn,
    "SELECT d.nombre AS deporte, d.horario, DATE_FORMAT(i.created_at,'%d/%m/%Y') AS desde
     FROM inscripciones_deportivas i JOIN deportes d ON d.id = i.deporte_id
     WHERE i.alumno_id = ? ORDER BY d.nombre", $alumno_id);

// Comunicación con la familia
$consultas = filas($conn,
    "SELECT m.nombre AS materia, c.evaluacion, c.nota, p.nombre AS padre, q.mensaje, q.respuesta, q.estado,
            DATE_FORMAT(q.created_at,'%d/%m/%Y') AS fecha
     FROM consultas_notas q
     JOIN calificaciones c ON c.id = q.calificacion_id
     JOIN materias m ON m.id = c.materia_id
     JOIN usuarios p ON p.id = q.padre_id
     WHERE q.alumno_id = ? ORDER BY q.created_at DESC", $alumno_id);

$notificaciones = filas($conn,
    "SELECT tipo, mensaje, DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha
     FROM notificaciones WHERE alumno_id = ? ORDER BY created_at DESC LIMIT 15", $alumno_id);

// RFG12/RFG16: situación económica. Solo la ve el admin; el docente no recibe datos de pagos.
$cuotas = null;       // null = sin acceso (el front no muestra la sección)
$deuda_total = null;
if ($es_admin) {
    $cuotas = filas($conn,
        "SELECT concepto, mes, anio, importe, recargo, estado, metodo_pago,
                DATE_FORMAT(fecha_vencimiento,'%d/%m/%Y') AS vencimiento,
                DATE_FORMAT(fecha_pago,'%d/%m/%Y') AS fecha_pago,
                (estado = 'pendiente' AND fecha_vencimiento < CURDATE()) AS vencida
         FROM cuotas WHERE alumno_id = ? ORDER BY anio DESC, mes DESC, concepto", $alumno_id);
    $deuda_total = 0.0;
    foreach ($cuotas as &$q) {
        $q['vencida'] = (bool) $q['vencida'];
        $q['metodo_pago'] = $q['metodo_pago'] ? (METODOS_PAGO[$q['metodo_pago']] ?? $q['metodo_pago']) : null;
        if ($q['estado'] === 'pendiente') {
            $deuda_total += (float) $q['importe'] + (float) $q['recargo'];
        }
    }
    unset($q);
}
$conn->close();

// ── Indicadores para la cabecera del legajo ────────────────────
$notas     = array_column($calificaciones, 'nota');
$tot_asist = array_sum(array_column($asistencia, 'total'));
$ok_asist  = array_sum(array_column($asistencia, 'presentes')) + array_sum(array_column($asistencia, 'tardes'));
$resumen = [
    'promedio_general'  => $notas ? round(array_sum($notas) / count($notas), 2) : null,
    'asistencia_pct'    => $tot_asist ? round($ok_asist * 100 / $tot_asist, 1) : null,
    'faltas_total'      => array_sum(array_column($asistencia, 'faltas')),
    'materias_riesgo'   => count(array_filter($materias, fn($m) => $m['situacion'] === 'En riesgo')),
    'materias_total'    => count($materias),
    'recups_pendientes' => count(array_filter($recuperatorios, fn($r) => $r['estado'] === 'pendiente')),
    'sanciones'         => count($sanciones),
    'nota_aprobacion'   => NOTA_APROBACION,
    'limite_faltas'     => LIMITE_FALTAS,
];

echo json_encode([
    'success'        => true,
    'es_admin'       => $es_admin,
    'generado'       => date('d/m/Y H:i'),
    'alumno'         => $alumno,
    'datos'          => $datos,
    'curso'          => $curso,
    'tutores'        => $tutores,
    'resumen'        => $resumen,
    'materias'       => $materias,
    'calificaciones' => $calificaciones,
    'asistencia'     => $asistencia,
    'recuperatorios' => $recuperatorios,
    'sanciones'      => $sanciones,
    'enfermeria'     => $enfermeria,
    'servicios'      => $servicios,
    'deportes'       => $deportes,
    'consultas'      => $consultas,
    'notificaciones' => $notificaciones,
    'cuotas'         => $cuotas,
    'deuda_total'    => $deuda_total,
]);
