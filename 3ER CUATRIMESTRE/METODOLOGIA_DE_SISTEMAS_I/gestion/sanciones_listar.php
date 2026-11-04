<?php

// GET → sanciones con filtros (RFG14). Admin: todas. Docente: solo alumnos de los cursos donde dicta.
// Filtros opcionales: curso_id, tipo, estado (vigente|anulada), desde, hasta (Y-m-d).
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin', 'docente']);
errores_como_json();

require_once __DIR__ . '/../database/db_config.php';

$es_admin   = $_SESSION['rol'] === 'admin';
$docente_id = $es_admin ? 0 : (int) $_SESSION['usuario_id'];
$curso_id   = (int) ($_GET['curso_id'] ?? 0);
$tipo       = isset(TIPOS_SANCION[$_GET['tipo'] ?? '']) ? $_GET['tipo'] : '';
$estado     = in_array($_GET['estado'] ?? '', ['vigente', 'anulada'], true) ? $_GET['estado'] : '';
$fecha_ok   = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v ?? '') ? $v : '';
$desde      = $fecha_ok($_GET['desde'] ?? '');
$hasta      = $fecha_ok($_GET['hasta'] ?? '');
$anulada    = $estado === 'anulada' ? 1 : 0;

// Cursos visibles: el docente solo ve los cursos donde dicta alguna materia.
$cond_curso_docente = "(? = 0 OR EXISTS (SELECT 1 FROM materias md WHERE md.curso_id = c.id AND md.docente_id = ?))";

$stmt = $conn->prepare(
    "SELECT s.id, s.alumno_id, u.nombre AS alumno, c.nombre AS curso, s.tipo, s.descripcion, s.fecha,
            DATE_FORMAT(s.fecha, '%d/%m/%Y') AS fecha_ar, s.dias_suspension, s.sin_contacto, s.anulada,
            s.motivo_anulacion, DATE_FORMAT(s.anulada_en, '%d/%m/%Y') AS anulada_en,
            r.nombre AS registrado_por, a.nombre AS anulada_por
     FROM sanciones s
     JOIN usuarios u ON u.id = s.alumno_id
     LEFT JOIN alumno_curso ac ON ac.alumno_id = s.alumno_id
     LEFT JOIN cursos c ON c.id = ac.curso_id
     LEFT JOIN usuarios r ON r.id = s.registrado_por
     LEFT JOIN usuarios a ON a.id = s.anulada_por
     WHERE $cond_curso_docente
       AND (? = 0 OR c.id = ?)
       AND (? = '' OR s.tipo = ?)
       AND (? = '' OR s.anulada = ?)
       AND (? = '' OR s.fecha >= ?)
       AND (? = '' OR s.fecha <= ?)
     ORDER BY s.fecha DESC, s.id DESC"
);
$stmt->bind_param(
    'iiiisssissss',
    $docente_id,
    $docente_id,
    $curso_id,
    $curso_id,
    $tipo,
    $tipo,
    $estado,
    $anulada,
    $desde,
    $desde,
    $hasta,
    $hasta
);
$stmt->execute();
$sanciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT c.id, c.nombre FROM cursos c WHERE $cond_curso_docente ORDER BY c.nivel_educativo, c.nombre");
$stmt->bind_param('ii', $docente_id, $docente_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

foreach ($sanciones as &$s) {
    $s['tipo_texto']   = TIPOS_SANCION[$s['tipo']];
    $s['sin_contacto'] = (bool) $s['sin_contacto'];
    $s['anulada']      = (bool) $s['anulada'];
}
unset($s);

echo json_encode(['success' => true, 'es_admin' => $es_admin, 'sanciones' => $sanciones, 'cursos' => $cursos]);
