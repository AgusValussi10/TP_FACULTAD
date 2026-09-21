<?php
/**
 * seed_datos.php — Datos de demostración masivos para todas las tablas.
 *
 * Cómo impactarlo en la BDD:
 *   Acceder desde el navegador a:
 *   https://educar-para-transformar-nw1k.onrender.com/database/seed_datos.php
 *
 * Es idempotente: usa INSERT IGNORE / ON DUPLICATE KEY UPDATE.
 * Eliminá este archivo después de ejecutarlo.
 */
require_once 'db_config.php';
$conn->set_charset('utf8');

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Seed</title><style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:24px;line-height:1.6}
h2{color:#f97316;margin:24px 0 6px}
ul{margin:0 0 8px;padding-left:20px}
.ok{color:#4ade80}.skip{color:#64748b}.err{color:#f87171}
table{border-collapse:collapse;margin-top:8px}
th,td{border:1px solid #334155;padding:4px 12px;text-align:left}
th{background:#1e293b;color:#f97316}
code{background:#1e293b;padding:1px 5px;border-radius:3px}
</style></head><body>';

// ── helpers ──────────────────────────────────────────────────────────────────

function uid(mysqli $c, string $usuario): int {
    $r = $c->query("SELECT id FROM usuarios WHERE usuario = '" . $c->real_escape_string($usuario) . "'");
    return (int)($r ? $r->fetch_assoc()['id'] ?? 0 : 0);
}
function cid(mysqli $c, string $nombre): int {
    $r = $c->query("SELECT id FROM cursos WHERE nombre = '" . $c->real_escape_string($nombre) . "'");
    return (int)($r ? $r->fetch_assoc()['id'] ?? 0 : 0);
}
function mid(mysqli $c, string $nombre, int $curso_id): int {
    $r = $c->query("SELECT id FROM materias WHERE nombre = '" . $c->real_escape_string($nombre) . "' AND curso_id = $curso_id");
    return (int)($r ? $r->fetch_assoc()['id'] ?? 0 : 0);
}
function row(string $class, string $icon, string $text): void {
    echo "<li class='$class'>$icon $text</li>";
}

// ── 1. USUARIOS ───────────────────────────────────────────────────────────────
echo '<h2>1. Usuarios</h2><ul>';

$nuevos = [
    // 8 alumnos nuevos
    ['nombre' => 'Sofía Herrera',    'usuario' => 'sofia.herrera',    'pass' => 'alumno789',   'rol' => 'alumno'],
    ['nombre' => 'Miguel Torres',    'usuario' => 'miguel.torres',    'pass' => 'alumno321',   'rol' => 'alumno'],
    ['nombre' => 'Valentina Paz',    'usuario' => 'valentina.paz',    'pass' => 'alumno654',   'rol' => 'alumno'],
    ['nombre' => 'Ezequiel Romero',  'usuario' => 'ezequiel.romero',  'pass' => 'alumno987',   'rol' => 'alumno'],
    ['nombre' => 'Luciana Campos',   'usuario' => 'luciana.campos',   'pass' => 'alumno111',   'rol' => 'alumno'],
    ['nombre' => 'Matías Vega',      'usuario' => 'matias.vega',      'pass' => 'alumno222',   'rol' => 'alumno'],
    ['nombre' => 'Agustina Molina',  'usuario' => 'agustina.molina',  'pass' => 'alumno333',   'rol' => 'alumno'],
    ['nombre' => 'Bruno Flores',     'usuario' => 'bruno.flores',     'pass' => 'alumno444',   'rol' => 'alumno'],
    // 2 docentes nuevos
    ['nombre' => 'Patricia Aguirre', 'usuario' => 'patricia.aguirre', 'pass' => 'docente789',  'rol' => 'docente'],
    ['nombre' => 'Ernesto Castillo', 'usuario' => 'ernesto.castillo', 'pass' => 'docente321',  'rol' => 'docente'],
    // 3 padres nuevos
    ['nombre' => 'Carlos Herrera',   'usuario' => 'carlos.herrera',   'pass' => 'padre789',    'rol' => 'padre'],
    ['nombre' => 'Marta Torres',     'usuario' => 'marta.torres',     'pass' => 'padre321',    'rol' => 'padre'],
    ['nombre' => 'Fernando Paz',     'usuario' => 'fernando.paz',     'pass' => 'padre654',    'rol' => 'padre'],
];

$stmt = $conn->prepare("INSERT IGNORE INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)");
foreach ($nuevos as $u) {
    $hash = password_hash($u['pass'], PASSWORD_BCRYPT);
    $stmt->bind_param('ssss', $u['nombre'], $u['usuario'], $hash, $u['rol']);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        row('ok', '✅', "{$u['usuario']} ({$u['rol']}) — pass: <code>{$u['pass']}</code>");
    } else {
        row('skip', '—', "{$u['usuario']} ya existe");
    }
}
$stmt->close();
echo '</ul>';

// ── 2. CURSOS ─────────────────────────────────────────────────────────────────
echo '<h2>2. Cursos</h2><ul>';

$cursos = [
    ['nombre' => '2°B',   'nivel' => 'Secundario'],
    ['nombre' => '1°A',   'nivel' => 'Primario'],
    ['nombre' => 'Sala 4','nivel' => 'Inicial'],
    ['nombre' => '4°C',   'nivel' => 'Secundario'],
];
$stmt = $conn->prepare("INSERT IGNORE INTO cursos (nombre, nivel_educativo) VALUES (?, ?)");
foreach ($cursos as $cur) {
    $stmt->bind_param('ss', $cur['nombre'], $cur['nivel']);
    $stmt->execute();
    $icon = $stmt->affected_rows > 0 ? '✅' : '—';
    $class = $stmt->affected_rows > 0 ? 'ok' : 'skip';
    row($class, $icon, "{$cur['nombre']} ({$cur['nivel']})");
}
$stmt->close();
echo '</ul>';

// ── 3. MATERIAS ───────────────────────────────────────────────────────────────
echo '<h2>3. Materias</h2><ul>';

$c3a  = cid($conn, '3°A');
$c2b  = cid($conn, '2°B');
$c1a  = cid($conn, '1°A');
$cs4  = cid($conn, 'Sala 4');
$c4c  = cid($conn, '4°C');

$maria    = uid($conn, 'maria.rodriguez');
$roberto  = uid($conn, 'roberto.silva');
$patricia = uid($conn, 'patricia.aguirre');
$ernesto  = uid($conn, 'ernesto.castillo');

$materias = [
    // 3°A (ya tienen Matemática y Lengua — agregamos Historia, Física, Inglés)
    ['nombre' => 'Historia',            'curso_id' => $c3a, 'docente_id' => $roberto],
    ['nombre' => 'Física',              'curso_id' => $c3a, 'docente_id' => $roberto],
    ['nombre' => 'Inglés',              'curso_id' => $c3a, 'docente_id' => $patricia],
    // 2°B
    ['nombre' => 'Matemática',          'curso_id' => $c2b, 'docente_id' => $maria],
    ['nombre' => 'Lengua y Literatura', 'curso_id' => $c2b, 'docente_id' => $maria],
    ['nombre' => 'Geografía',           'curso_id' => $c2b, 'docente_id' => $patricia],
    ['nombre' => 'Química',             'curso_id' => $c2b, 'docente_id' => $ernesto],
    ['nombre' => 'Inglés',              'curso_id' => $c2b, 'docente_id' => $patricia],
    // 1°A
    ['nombre' => 'Matemática',          'curso_id' => $c1a, 'docente_id' => $ernesto],
    ['nombre' => 'Lengua',              'curso_id' => $c1a, 'docente_id' => $ernesto],
    ['nombre' => 'Ciencias Naturales',  'curso_id' => $c1a, 'docente_id' => $patricia],
    ['nombre' => 'Ciencias Sociales',   'curso_id' => $c1a, 'docente_id' => $patricia],
    // Sala 4
    ['nombre' => 'Actividades Lúdicas', 'curso_id' => $cs4, 'docente_id' => $roberto],
    ['nombre' => 'Iniciación Musical',  'curso_id' => $cs4, 'docente_id' => $roberto],
    // 4°C
    ['nombre' => 'Matemática',          'curso_id' => $c4c, 'docente_id' => $roberto],
    ['nombre' => 'Física',              'curso_id' => $c4c, 'docente_id' => $roberto],
    ['nombre' => 'Química',             'curso_id' => $c4c, 'docente_id' => $ernesto],
    ['nombre' => 'Inglés',              'curso_id' => $c4c, 'docente_id' => $patricia],
];

$stmt = $conn->prepare("INSERT IGNORE INTO materias (nombre, curso_id, docente_id) VALUES (?, ?, ?)");
foreach ($materias as $m) {
    $stmt->bind_param('sii', $m['nombre'], $m['curso_id'], $m['docente_id']);
    $stmt->execute();
    $icon  = $stmt->affected_rows > 0 ? '✅' : '—';
    $class = $stmt->affected_rows > 0 ? 'ok' : 'skip';
    row($class, $icon, "{$m['nombre']} (curso {$m['curso_id']})");
}
$stmt->close();
echo '</ul>';

// ── 4. MATRICULACIÓN ──────────────────────────────────────────────────────────
echo '<h2>4. Matriculación alumno_curso</h2><ul>';

$matriculas = [
    ['sofia.herrera',   '3°A'],
    ['miguel.torres',   '3°A'],
    ['valentina.paz',   '2°B'],
    ['ezequiel.romero', '2°B'],
    ['luciana.campos',  '1°A'],
    ['matias.vega',     '1°A'],
    ['agustina.molina', 'Sala 4'],
    ['bruno.flores',    'Sala 4'],
];
foreach ($matriculas as [$uname, $cnombre]) {
    $aid  = uid($conn, $uname);
    $curid = cid($conn, $cnombre);
    $conn->query("INSERT IGNORE INTO alumno_curso (alumno_id, curso_id) VALUES ($aid, $curid)");
    $icon  = $conn->affected_rows > 0 ? '✅' : '—';
    $class = $conn->affected_rows > 0 ? 'ok' : 'skip';
    row($class, $icon, "$uname → $cnombre");
}
echo '</ul>';

// ── 5. VÍNCULOS PADRE-ALUMNO ──────────────────────────────────────────────────
echo '<h2>5. Vínculos padre-alumno</h2><ul>';

$vinculos = [
    ['carlos.herrera', 'sofia.herrera'],
    ['marta.torres',   'miguel.torres'],
    ['fernando.paz',   'valentina.paz'],
];
foreach ($vinculos as [$padre, $alumno]) {
    $p = uid($conn, $padre); $a = uid($conn, $alumno);
    $conn->query("INSERT IGNORE INTO padre_alumno (padre_id, alumno_id) VALUES ($p, $a)");
    $icon  = $conn->affected_rows > 0 ? '✅' : '—';
    $class = $conn->affected_rows > 0 ? 'ok' : 'skip';
    row($class, $icon, "$padre → $alumno");
}
echo '</ul>';

// ── 6. CALIFICACIONES ─────────────────────────────────────────────────────────
// boletin_listar.php agrupa por mes: 3-5 → 1er Trim, 6-8 → 2do Trim, 9-12 → 3er Trim
echo '<h2>6. Calificaciones</h2><ul>';

// materia IDs 3°A
$m3a = [
    'mat' => mid($conn, 'Matemática',          $c3a),
    'len' => mid($conn, 'Lengua y Literatura',  $c3a),
    'his' => mid($conn, 'Historia',             $c3a),
    'fis' => mid($conn, 'Física',               $c3a),
    'ing' => mid($conn, 'Inglés',               $c3a),
];
// materia IDs 2°B
$m2b = [
    'mat' => mid($conn, 'Matemática',          $c2b),
    'len' => mid($conn, 'Lengua y Literatura',  $c2b),
    'geo' => mid($conn, 'Geografía',            $c2b),
    'qui' => mid($conn, 'Química',              $c2b),
    'ing' => mid($conn, 'Inglés',               $c2b),
];
// materia IDs 1°A
$m1a = [
    'mat' => mid($conn, 'Matemática',         $c1a),
    'len' => mid($conn, 'Lengua',             $c1a),
    'cn'  => mid($conn, 'Ciencias Naturales', $c1a),
    'cs'  => mid($conn, 'Ciencias Sociales',  $c1a),
];

// [evaluacion_suffix, fecha] por trimestre × instancia
$instancias = [
    // 1er Trim — meses 3-5
    ['1T-Parcial 1',    '2026-04-10'],
    ['1T-TP',           '2026-04-28'],
    ['1T-Parcial 2',    '2026-05-20'],
    // 2do Trim — meses 6-8
    ['2T-Parcial 1',    '2026-06-18'],
    ['2T-TP',           '2026-07-09'],
    ['2T-Parcial 2',    '2026-08-06'],
    // 3er Trim — meses 9-12
    ['3T-Parcial 1',    '2026-09-04'],
    ['3T-TP',           '2026-09-18'],
];

// Notas por alumno/materia (8 instancias: 3+3+2)
// Patrón: buen alumno > 6, alumno débil < 6 en varias materias
$datos_califs = [
    // ── 3°A ──
    'ana.garcia' => ['mids' => $m3a, 'notas' => [
        'mat' => [8,7,8, 9,7,8, 8,9],
        'len' => [9,8,9, 8,9,9, 9,8],
        'his' => [8,7,8, 7,8,8, 8,7],
        'fis' => [6,7,7, 6,7,6, 7,7],
        'ing' => [9,9,8, 8,9,9, 9,8],
    ]],
    'carlos.lopez' => ['mids' => $m3a, 'notas' => [
        'mat' => [4,3,4, 5,4,3, 4,3],   // promedio < 6 → recuperatorio
        'len' => [6,5,6, 5,6,5, 6,5],
        'his' => [3,4,3, 4,3,4, 3,4],   // promedio < 6 → recuperatorio
        'fis' => [3,4,3, 4,3,3, 4,3],   // promedio < 6 → recuperatorio
        'ing' => [7,6,7, 6,7,6, 7,6],
    ]],
    'sofia.herrera' => ['mids' => $m3a, 'notas' => [
        'mat' => [8,7,8, 7,8,8, 8,7],
        'len' => [9,8,9, 8,9,8, 9,8],
        'his' => [9,8,9, 9,8,9, 8,9],
        'fis' => [5,4,5, 5,4,5, 5,4],   // promedio < 6 → recuperatorio
        'ing' => [9,8,9, 8,9,9, 8,9],
    ]],
    'miguel.torres' => ['mids' => $m3a, 'notas' => [
        'mat' => [5,5,6, 5,6,5, 5,6],   // borderline
        'len' => [7,6,7, 6,7,7, 6,7],
        'his' => [6,7,6, 7,6,6, 7,6],
        'fis' => [3,4,3, 4,3,4, 3,4],   // promedio < 6 → recuperatorio
        'ing' => [6,5,6, 6,5,6, 5,6],
    ]],
    // ── 2°B ──
    'valentina.paz' => ['mids' => $m2b, 'notas' => [
        'mat' => [9,9,8, 9,8,9, 8,9],
        'len' => [8,9,9, 8,9,8, 9,8],
        'geo' => [10,9,9, 9,10,9, 9,10],
        'qui' => [7,8,7, 8,7,8, 7,8],
        'ing' => [9,8,9, 9,8,9, 8,9],
    ]],
    'ezequiel.romero' => ['mids' => $m2b, 'notas' => [
        'mat' => [3,4,3, 4,3,4, 3,4],   // promedio < 6
        'len' => [5,4,5, 5,4,5, 4,5],
        'geo' => [6,5,6, 5,6,5, 5,6],
        'qui' => [3,2,3, 3,2,3, 2,3],   // promedio < 6
        'ing' => [5,4,5, 4,5,4, 5,4],
    ]],
    // ── 1°A ──
    'luciana.campos' => ['mids' => $m1a, 'notas' => [
        'mat' => [8,9,8, 9,8,9, 8,9],
        'len' => [7,8,8, 8,7,8, 8,7],
        'cn'  => [8,7,8, 8,7,8, 7,8],
        'cs'  => [9,8,9, 8,9,8, 9,8],
    ]],
    'matias.vega' => ['mids' => $m1a, 'notas' => [
        'mat' => [5,4,5, 4,5,5, 4,5],   // borderline bajo
        'len' => [6,5,6, 5,6,5, 6,5],
        'cn'  => [4,5,4, 5,4,5, 4,5],   // promedio < 6
        'cs'  => [7,6,7, 6,7,6, 7,6],
    ]],
];

$stmt = $conn->prepare(
    "INSERT IGNORE INTO calificaciones (alumno_id, materia_id, evaluacion, nota, fecha_evaluacion)
     VALUES (?, ?, ?, ?, ?)"
);
foreach ($datos_califs as $uname => $info) {
    $aid  = uid($conn, $uname);
    $ins  = 0;
    foreach ($info['notas'] as $mkey => $notas_arr) {
        $mid_val = $info['mids'][$mkey];
        foreach ($notas_arr as $idx => $nota) {
            [$eval_suf, $fecha] = $instancias[$idx];
            $eval = "$eval_suf"; // ya es único por diseño
            $stmt->bind_param('iisis', $aid, $mid_val, $eval, $nota, $fecha);
            $stmt->execute();
            $ins += $stmt->affected_rows;
        }
    }
    row('ok', '✅', "$uname — $ins registros insertados");
}
$stmt->close();
echo '</ul>';

// ── 7. ASISTENCIAS ────────────────────────────────────────────────────────────
echo '<h2>7. Asistencias</h2><ul>';

// 36 días lectivos representativos del año
$dias = [
    '2026-03-10','2026-03-12','2026-03-17','2026-03-24','2026-03-26',
    '2026-04-07','2026-04-09','2026-04-14','2026-04-21','2026-04-28',
    '2026-05-05','2026-05-07','2026-05-12','2026-05-19','2026-05-26',
    '2026-06-02','2026-06-09','2026-06-11','2026-06-16','2026-06-23',
    '2026-07-07','2026-07-14','2026-07-21','2026-07-28',
    '2026-08-04','2026-08-11','2026-08-18','2026-08-25',
    '2026-09-01','2026-09-03','2026-09-08','2026-09-10','2026-09-15','2026-09-17','2026-09-21',
];

// Patrón de asistencia: se cicla sobre el array (P=presente, A=ausente, T=tarde)
$patrones = [
    'ana.garcia'      => ['P','P','P','P','P','P','P','P','P','T'],  // 90% presente
    'carlos.lopez'    => ['P','A','P','P','A','P','T','A','P','P'],  // 30% ausente (muchas faltas)
    'sofia.herrera'   => ['P','P','P','P','P','T','P','P','P','P'],  // 95% presente
    'miguel.torres'   => ['P','P','A','P','P','P','T','P','A','P'],  // 80% presente
    'valentina.paz'   => ['P','P','P','P','P','P','P','T','P','P'],  // 95%
    'ezequiel.romero' => ['P','A','A','P','A','P','T','A','P','A'],  // 50% presente
    'luciana.campos'  => ['P','P','P','P','T','P','P','P','P','P'],  // 95%
    'matias.vega'     => ['P','P','A','P','P','T','P','P','A','P'],  // 80%
];

$asist_materias = [
    'ana.garcia'      => [$m3a['mat'], $m3a['len'], $m3a['his']],
    'carlos.lopez'    => [$m3a['mat'], $m3a['len'], $m3a['his']],
    'sofia.herrera'   => [$m3a['mat'], $m3a['len'], $m3a['fis']],
    'miguel.torres'   => [$m3a['mat'], $m3a['fis'], $m3a['ing']],
    'valentina.paz'   => [$m2b['mat'], $m2b['len'], $m2b['geo']],
    'ezequiel.romero' => [$m2b['mat'], $m2b['qui'], $m2b['ing']],
    'luciana.campos'  => [$m1a['mat'], $m1a['len'], $m1a['cn']],
    'matias.vega'     => [$m1a['mat'], $m1a['cn'],  $m1a['cs']],
];

$estados_map = ['P' => 'presente', 'A' => 'ausente', 'T' => 'tarde'];
$stmt = $conn->prepare(
    "INSERT IGNORE INTO asistencias (alumno_id, materia_id, fecha, estado) VALUES (?, ?, ?, ?)"
);
$total_asist = 0;
foreach ($patrones as $uname => $patron) {
    $aid  = uid($conn, $uname);
    $mids_list = $asist_materias[$uname];
    foreach ($mids_list as $mid_val) {
        foreach ($dias as $i => $fecha) {
            $estado = $estados_map[$patron[$i % count($patron)]];
            $stmt->bind_param('iiss', $aid, $mid_val, $fecha, $estado);
            $stmt->execute();
            $total_asist += $stmt->affected_rows;
        }
    }
}
$stmt->close();
row('ok', '✅', "$total_asist registros de asistencia insertados (8 alumnos × 3 materias × 35 días)");
echo '</ul>';

// ── 8. PLANIFICACIONES ────────────────────────────────────────────────────────
echo '<h2>8. Planificaciones</h2><ul>';

$plans = [
    [$m3a['mat'], 2026,
     'Unidad 1: Álgebra y expresiones algebraicas. Unidad 2: Funciones lineales y cuadráticas. ' .
     'Unidad 3: Geometría analítica y trigonometría básica. Unidad 4: Estadística descriptiva. ' .
     'Unidad 5: Probabilidad y combinatoria.',
     'Se priorizará la resolución de problemas contextualizados. Evaluación continua con TPs quincenales.'],
    [$m3a['len'], 2026,
     'Unidad 1: Narrativa contemporánea latinoamericana. Unidad 2: Poesía y recursos literarios. ' .
     'Unidad 3: El texto argumentativo. Unidad 4: Literatura regional chaqueña. ' .
     'Unidad 5: Producción y revisión de textos expositivos.',
     'Se incorporarán autores del NEA. Proyecto final: antología de cuentos breves del aula.'],
    [$m3a['his'], 2026,
     'Unidad 1: Primera y Segunda Guerra Mundial. Unidad 2: La Guerra Fría. ' .
     'Unidad 3: Descolonización y Tercer Mundo. Unidad 4: Historia argentina siglo XX. ' .
     'Unidad 5: Democracia y derechos humanos en Argentina.',
     'Énfasis en análisis de fuentes primarias y documentales históricos.'],
    [$m3a['fis'], 2026,
     'Unidad 1: Cinemática — movimiento uniforme y uniformemente acelerado. Unidad 2: Dinámica y leyes de Newton. ' .
     'Unidad 3: Trabajo, energía y potencia. Unidad 4: Termodinámica básica. ' .
     'Unidad 5: Ondas mecánicas y sonido.',
     'Prácticas de laboratorio mensuales. Alumnos con dificultades en dinámica — reforzar antes del 2do parcial.'],
    [$m3a['ing'], 2026,
     'Unit 1: Present Perfect and Past Simple review. Unit 2: Conditionals (0, 1 and 2). ' .
     'Unit 3: Passive Voice. Unit 4: Reported Speech. Unit 5: Academic writing — essay structure.',
     null],
    [$m2b['mat'], 2026,
     'Unidad 1: Números racionales e irracionales. Unidad 2: Ecuaciones e inecuaciones de 1° y 2° grado. ' .
     'Unidad 3: Figuras y cuerpos geométricos. Unidad 4: Proporcionalidad directa e inversa. ' .
     'Unidad 5: Introducción a la estadística — tablas y gráficos.',
     null],
    [$m2b['qui'], 2026,
     'Unidad 1: Materia y sus propiedades. Unidad 2: Tabla periódica y tendencias. ' .
     'Unidad 3: Enlace químico — iónico, covalente y metálico. Unidad 4: Reacciones químicas — tipos y balanceo. ' .
     'Unidad 5: Química orgánica — introducción a los hidrocarburos.',
     'Clases de laboratorio bimestrales. Normas de seguridad obligatorias antes de cada práctica.'],
    [$m1a['mat'], 2026,
     'Unidad 1: Números naturales hasta 1000 — lectura y escritura. Unidad 2: Adición y sustracción con reagrupación. ' .
     'Unidad 3: Multiplicación y división básica. Unidad 4: Fracciones simples. ' .
     'Unidad 5: Medidas de longitud, peso y tiempo.',
     'Uso de materiales manipulativos (regletas, fichas de conteo, balanza de platillos).'],
    [$m1a['cn'], 2026,
     'Unidad 1: El cuerpo humano — funciones básicas. Unidad 2: Los seres vivos del Chaco y su clasificación. ' .
     'Unidad 3: El agua y el ciclo hidrológico. Unidad 4: Los estados de la materia. ' .
     'Unidad 5: El sistema solar y los movimientos de la Tierra.',
     null],
    [$m1a['cs'], 2026,
     'Unidad 1: La familia y la comunidad. Unidad 2: El barrio y el municipio. ' .
     'Unidad 3: Trabajo y actividades económicas. Unidad 4: Historia local — Resistencia, Chaco. ' .
     'Unidad 5: Símbolos patrios y efemérides nacionales.',
     'Se realizará una salida educativa al Fogón de los Arrieros.'],
];

$stmt = $conn->prepare(
    "INSERT INTO planificaciones (materia_id, anio, contenidos, observaciones)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE contenidos = VALUES(contenidos), observaciones = VALUES(observaciones)"
);
foreach ($plans as $p) {
    $stmt->bind_param('iiss', $p[0], $p[1], $p[2], $p[3]);
    $stmt->execute();
    row('ok', '✅', "Planificación materia_id={$p[0]} año={$p[1]}");
}
$stmt->close();
echo '</ul>';

// ── 9. RECUPERATORIOS ─────────────────────────────────────────────────────────
echo '<h2>9. Recuperatorios</h2><ul>';

$carlos   = uid($conn, 'carlos.lopez');
$sofia    = uid($conn, 'sofia.herrera');
$miguel   = uid($conn, 'miguel.torres');
$ezequiel = uid($conn, 'ezequiel.romero');
$matias   = uid($conn, 'matias.vega');

$recups = [
    // carlos.lopez — matemática, historia y física < 6
    [$carlos,   $m3a['mat'], '1er Trimestre', '2026-10-05', 'mañana', 'pendiente'],
    [$carlos,   $m3a['his'], '1er Trimestre', '2026-10-07', 'tarde',  'desaprobado'],
    [$carlos,   $m3a['fis'], '1er Trimestre', '2026-10-10', 'mañana', 'pendiente'],
    [$carlos,   $m3a['mat'], '2do Trimestre', '2026-10-14', 'tarde',  'pendiente'],
    [$carlos,   $m3a['fis'], '2do Trimestre', null,         null,     'pendiente'],
    // sofia.herrera — física < 6
    [$sofia,    $m3a['fis'], '1er Trimestre', '2026-10-06', 'mañana', 'aprobado'],
    [$sofia,    $m3a['fis'], '2do Trimestre', '2026-10-15', 'tarde',  'pendiente'],
    // miguel.torres — física < 6
    [$miguel,   $m3a['fis'], '1er Trimestre', '2026-10-08', 'mañana', 'aprobado'],
    [$miguel,   $m3a['fis'], '2do Trimestre', '2026-10-16', 'tarde',  'pendiente'],
    // ezequiel.romero — matemática y química < 6
    [$ezequiel, $m2b['mat'], '1er Trimestre', '2026-10-05', 'mañana', 'pendiente'],
    [$ezequiel, $m2b['qui'], '1er Trimestre', '2026-10-09', 'tarde',  'pendiente'],
    [$ezequiel, $m2b['mat'], '2do Trimestre', null,         null,     'pendiente'],
    [$ezequiel, $m2b['qui'], '2do Trimestre', '2026-10-17', 'mañana', 'desaprobado'],
    // matias.vega — ciencias naturales < 6
    [$matias,   $m1a['cn'],  '1er Trimestre', '2026-10-11', 'tarde',  'aprobado'],
    [$matias,   $m1a['mat'], '1er Trimestre', '2026-10-13', 'mañana', 'pendiente'],
    [$matias,   $m1a['cn'],  '2do Trimestre', null,         null,     'pendiente'],
];

$stmt = $conn->prepare(
    "INSERT IGNORE INTO recuperatorios (alumno_id, materia_id, periodo, fecha, turno, estado)
     VALUES (?, ?, ?, ?, ?, ?)"
);
foreach ($recups as $r) {
    $stmt->bind_param('iissss', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5]);
    $stmt->execute();
    $icon  = $stmt->affected_rows > 0 ? '✅' : '—';
    $class = $stmt->affected_rows > 0 ? 'ok' : 'skip';
    row($class, $icon, "alumno_id={$r[0]} materia_id={$r[1]} {$r[2]} — {$r[5]}");
}
$stmt->close();
echo '</ul>';

// ── 10. SANCIONES ─────────────────────────────────────────────────────────────
echo '<h2>10. Sanciones</h2><ul>';

$admin_id = uid($conn, 'admin');
$ana_id   = uid($conn, 'ana.garcia');

$sanciones = [
    [$carlos,   'apercibimiento', 'Comportamiento disruptivo durante clase de Matemática. Se notificó a los padres.',          '2026-04-15'],
    [$carlos,   'suspension',     'Reiteración de conducta inapropiada. Suspensión de 2 días. Se requiere entrevista con tutor.', '2026-06-03'],
    [$carlos,   'apercibimiento', 'Uso del celular durante evaluación escrita.',                                               '2026-08-20'],
    [$carlos,   'suspension',     'Falta de respeto hacia un docente frente al curso. Suspensión de 3 días.',                  '2026-09-08'],
    [$ezequiel, 'apercibimiento', 'Ausencias reiteradas sin justificación durante el mes de agosto.',                          '2026-08-25'],
    [$ezequiel, 'suspension',     'Agresión verbal hacia un compañero. Suspensión 1 día. Reunión con Departamento de Convivencia.', '2026-09-10'],
    [$ezequiel, 'apercibimiento', 'Deterioro intencional de material del aula.',                                               '2026-09-18'],
    [$miguel,   'apercibimiento', 'Llegadas tarde reiteradas en los últimos 15 días. Se notificó a la familia.',               '2026-09-05'],
    [$matias,   'apercibimiento', 'Pelea en el recreo — resuelto mediante mediación escolar.',                                 '2026-05-22'],
    [$matias,   'otra',           'Incidente con material escolar de un compañero. El alumno restituyó el material.',          '2026-07-14'],
];

$stmt = $conn->prepare(
    "INSERT INTO sanciones (alumno_id, tipo, descripcion, fecha, registrado_por) VALUES (?, ?, ?, ?, ?)"
);
foreach ($sanciones as $s) {
    $stmt->bind_param('isssi', $s[0], $s[1], $s[2], $s[3], $admin_id);
    $stmt->execute();
    row('ok', '✅', "Sanción {$s[1]} para alumno_id={$s[0]} ({$s[3]})");
}
$stmt->close();
echo '</ul>';

// ── 11. ATENCIONES DE ENFERMERÍA ──────────────────────────────────────────────
echo '<h2>11. Atenciones de enfermería</h2><ul>';

$sandra   = uid($conn, 'sandra.benitez');
$sofia_id = uid($conn, 'sofia.herrera');
$luciana  = uid($conn, 'luciana.campos');
$agus     = uid($conn, 'agustina.molina');

$atenciones = [
    [$ana_id,    'Dolor de cabeza intenso durante clase de Matemática',              '09:30:00', 'Se administró ibuprofeno 200mg. Se notificó a la familia. Alumna descansó 1 hora.',                      $sandra],
    [$carlos,    'Mareos y náuseas tras el recreo',                                  '10:45:00', 'Sin fiebre. Posible hipoglucemia. Se indicó merienda y reposo de 30 minutos.',                           $sandra],
    [$sofia_id,  'Golpe en rodilla derecha durante Educación Física',                '11:20:00', 'Sin herida abierta. Se aplicó hielo 15 min. Se recomendó reposo de la actividad por el resto del día.', $sandra],
    [$miguel,    'Fiebre 38.2°C',                                                    '08:50:00', 'Se comunicó a los padres. El alumno fue retirado por su madre a las 10:00hs.',                           $sandra],
    [$ezequiel,  'Herida cortante superficial en la palma de la mano',               '13:10:00', 'Limpieza con agua y jabón, curación con antiséptico y apósito. Sin necesidad de puntos.',               $sandra],
    [$luciana,   'Alergia ocular intensa — lagrimeo y enrojecimiento',               '09:15:00', 'Antecedente de rinitis alérgica. Se aplicó suero fisiológico. Se informó a la docente.',                $sandra],
    [$matias,    'Dolor abdominal leve — posible cólico',                            '10:00:00', 'Sin fiebre ni síntomas febriles. Reposo 30 minutos. El alumno mejoró y regresó al aula.',               $sandra],
    [$agus,      'Caída en el patio — golpe en el codo izquierdo',                   '15:30:00', 'Sin lesión grave. Se aplicó hielo. Se tranquilizó a la alumna y se avisó a la familia.',                $sandra],
    [$ana_id,    'Mareos tras exposición solar prolongada en recreo',                '12:45:00', 'Se indicó hidratación y reposo en un lugar fresco. Mejoró en 20 minutos.',                              $sandra],
    [$carlos,    'Golpe en la cabeza durante clase de Educación Física',             '14:00:00', 'Sin pérdida de conciencia. Se evaluó y se realizó seguimiento de 60 min. Familia notificada.',          $sandra],
];

$stmt = $conn->prepare(
    "INSERT INTO atenciones_enfermeria (alumno_id, motivo, hora, observaciones, atendido_por) VALUES (?, ?, ?, ?, ?)"
);
foreach ($atenciones as $a) {
    $stmt->bind_param('isssi', $a[0], $a[1], $a[2], $a[3], $a[4]);
    $stmt->execute();
    row('ok', '✅', "Atención alumno_id={$a[0]} — {$a[1]}");
}
$stmt->close();
echo '</ul>';

// ── 12. RESERVAS DE SERVICIOS ─────────────────────────────────────────────────
echo '<h2>12. Reservas de servicios</h2><ul>';

$valentina = uid($conn, 'valentina.paz');
$bruno     = uid($conn, 'bruno.flores');

$reservas = [
    [$ana_id,    'comedor',    7, 2026],
    [$ana_id,    'comedor',    8, 2026],
    [$ana_id,    'comedor',    9, 2026],
    [$ana_id,    'transporte', 7, 2026],
    [$ana_id,    'transporte', 8, 2026],
    [$carlos,    'comedor',    8, 2026],
    [$sofia_id,  'comedor',    7, 2026],
    [$sofia_id,  'comedor',    8, 2026],
    [$sofia_id,  'comedor',    9, 2026],
    [$sofia_id,  'transporte', 7, 2026],
    [$sofia_id,  'transporte', 8, 2026],
    [$sofia_id,  'transporte', 9, 2026],
    [$valentina, 'comedor',    6, 2026],
    [$valentina, 'comedor',    7, 2026],
    [$valentina, 'comedor',    8, 2026],
    [$valentina, 'comedor',    9, 2026],
    [$luciana,   'transporte', 8, 2026],
    [$luciana,   'transporte', 9, 2026],
    [$matias,    'comedor',    9, 2026],
    [$bruno,     'comedor',    9, 2026],
];

$stmt = $conn->prepare(
    "INSERT IGNORE INTO reservas_servicios (alumno_id, servicio, mes, anio) VALUES (?, ?, ?, ?)"
);
foreach ($reservas as $r) {
    $stmt->bind_param('isii', $r[0], $r[1], $r[2], $r[3]);
    $stmt->execute();
    $icon  = $stmt->affected_rows > 0 ? '✅' : '—';
    $class = $stmt->affected_rows > 0 ? 'ok' : 'skip';
    row($class, $icon, "alumno_id={$r[0]} {$r[1]} mes {$r[2]}/{$r[3]}");
}
$stmt->close();
echo '</ul>';

// ── 13. SOLICITUDES DE INSCRIPCIÓN ────────────────────────────────────────────
echo '<h2>13. Solicitudes de inscripción</h2><ul>';

$solicitudes = [
    ['Tomás',     'Aguirre',  '2015-03-12', 'Primario',   'Jorge Aguirre',    '3624112233', 'tomas.aguirre@gmail.com',    'Viene de escuela pública cercana.',                              'pendiente'],
    ['Camila',    'Ríos',     '2018-07-22', 'Inicial',    'Paola Ríos',       '3624445566', 'paola.rios@hotmail.com',     null,                                                             'pendiente'],
    ['Joaquín',   'Méndez',   '2011-11-05', 'Secundario', 'Carmen Méndez',    '3624778899', 'carmen.mendez@gmail.com',    'El alumno tiene especial interés en el área de ciencias.',       'contactado'],
    ['Florencia', 'Gómez',    '2016-09-18', 'Primario',   'Roberto Gómez',    '3624001122', 'roberto.gomez@yahoo.com',    null,                                                             'contactado'],
    ['Sebastián', 'Alvarado', '2013-04-30', 'Primario',   'Marcela Alvarado', '3624334455', 'marcela.alvarado@gmail.com', 'Alumno con necesidades educativas especiales — pedir más info.', 'pendiente'],
    ['Martina',   'Juárez',   '2009-08-14', 'Secundario', 'Luis Juárez',      '3624667788', 'luis.juarez@outlook.com',    null,                                                             'admitido'],
    ['Nicolás',   'Paredes',  '2017-02-28', 'Inicial',    'Silvia Paredes',   '3624990011', 'silvia.paredes@gmail.com',   null,                                                             'pendiente'],
    ['Valentina', 'Ibáñez',   '2014-06-07', 'Primario',   'Daniel Ibáñez',    '3624223344', 'daniel.ibanez@gmail.com',    'Familia que se muda desde Buenos Aires.',                        'rechazado'],
    ['Agustín',   'Soria',    '2010-12-20', 'Secundario', 'Roxana Soria',     '3624556677', 'roxana.soria@gmail.com',     null,                                                             'pendiente'],
    ['Emma',      'Delgado',  '2019-01-15', 'Inicial',    'Víctor Delgado',   '3624889900', 'victor.delgado@hotmail.com', null,                                                             'contactado'],
];

$stmt = $conn->prepare(
    "INSERT INTO solicitudes_inscripcion
     (nombre_alumno, apellido_alumno, fecha_nacimiento, nivel_educativo, nombre_tutor, telefono, email, comentarios, estado)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($solicitudes as $s) {
    $stmt->bind_param('sssssssss', $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8]);
    $stmt->execute();
    row('ok', '✅', "{$s[0]} {$s[1]} — {$s[3]} ({$s[8]})");
}
$stmt->close();
echo '</ul>';

// ── 14. NOTIFICACIONES ────────────────────────────────────────────────────────
echo '<h2>14. Notificaciones</h2><ul>';

$laura     = uid($conn, 'laura.martinez');   // padre de ana.garcia
$diego     = uid($conn, 'diego.fernandez');  // padre de carlos.lopez
$c_herrera = uid($conn, 'carlos.herrera');   // padre de sofia.herrera
$m_torres  = uid($conn, 'marta.torres');     // padre de miguel.torres

$notifs = [
    [$laura,     $ana_id,   'enfermeria',    'Ana fue atendida en enfermería por dolor de cabeza. Ya se encuentra bien y regresó al aula.'],
    [$laura,     $ana_id,   'calificacion',  'Se cargaron calificaciones del 2do trimestre. Promedio en Matemática: 8. Ver boletín completo en el portal.'],
    [$diego,     $carlos,   'enfermeria',    'Carlos fue atendido en enfermería por mareos. Se recomienda que lleve colación todos los días.'],
    [$diego,     $carlos,   'asistencia',    'Carlos registra 8 ausencias en el mes de agosto. El límite por cuatrimestre es 15. Por favor regularizar.'],
    [$diego,     $carlos,   'sancion',       'Carlos recibió una sanción disciplinaria el 20/08. Ver detalle en el portal o comunicarse con Secretaría.'],
    [$diego,     $carlos,   'recuperatorio', 'Se programaron exámenes recuperatorios para Carlos. Consulte las fechas y turnos en el portal.'],
    [$c_herrera, $sofia_id, 'enfermeria',    'Sofía fue atendida en enfermería por un golpe leve en la rodilla durante Educación Física. Sin complicaciones.'],
    [$c_herrera, $sofia_id, 'recuperatorio', 'Se programó recuperatorio de Física para Sofía el 06/10/2026 en turno mañana.'],
    [$m_torres,  $miguel,   'enfermeria',    'Miguel presentó fiebre de 38.2°C y fue retirado. Por favor manténgase en contacto con la escuela.'],
    [$m_torres,  $miguel,   'recuperatorio', 'Se programó recuperatorio de Física para Miguel el 08/10/2026 en turno mañana. Estado: aprobado.'],
];

$stmt = $conn->prepare(
    "INSERT INTO notificaciones (padre_id, alumno_id, tipo, mensaje) VALUES (?, ?, ?, ?)"
);
foreach ($notifs as $n) {
    $stmt->bind_param('iiss', $n[0], $n[1], $n[2], $n[3]);
    $stmt->execute();
    row('ok', '✅', "Notif '{$n[2]}' para padre_id={$n[0]}");
}
$stmt->close();
echo '</ul>';

// ── 15. NOTICIAS EXTRA ────────────────────────────────────────────────────────
echo '<h2>15. Noticias</h2><ul>';

$noticias = [
    ['Inicio del ciclo lectivo 2026', 'La institución abre sus puertas con renovadas aulas y nuevos recursos pedagógicos para todos los niveles.',
     '<p>Con gran entusiasmo, la comunidad educativa inició el ciclo lectivo 2026. Las obras de refacción en el ala sur fueron concluidas durante enero, sumando tres nuevas aulas con equipamiento tecnológico de punta.</p><p>La Rectora destacó la incorporación de tres nuevos docentes en las áreas de Ciencias y Lenguas Extranjeras.</p>',
     'institucional', 'assets/edificio.avif', 'publicada', '2026-03-02'],
    ['Torneo Interescolar de Fútbol 2026', 'Nuestro equipo avanzó a cuartos de final en el torneo regional disputado este mes.',
     '<p>El equipo de fútbol del nivel secundario logró una destacada actuación en el Torneo Interescolar Regional, venciendo en los primeros tres encuentros con solvencia.</p><p>El próximo partido se disputará el sábado 28 de septiembre en nuestro campo de juego.</p>',
     'deportiva', 'assets/campofutbol.avif', 'publicada', '2026-09-15'],
    ['Apertura de inscripciones 2027', 'Ya están abiertas las preinscripciones para el ciclo lectivo 2027 en todos los niveles educativos.',
     '<p>Informamos que desde el 15 de septiembre se encuentran abiertas las preinscripciones para el ciclo 2027. Los interesados podrán completar el formulario en la sección Inscripciones de este sitio web o acercarse personalmente a Secretaría.</p><p>Cupos limitados. Se recomienda gestionar la documentación con anticipación.</p>',
     'institucional', 'assets/inscripciones2027.avif', 'publicada', '2026-09-15'],
    ['Nuevos laboratorios de Ciencias', 'La institución inaugura dos laboratorios completamente equipados para Física, Química y Biología.',
     '<p>Gracias al programa de inversión en infraestructura educativa, se inauguraron dos modernos laboratorios de ciencias naturales. Cada laboratorio cuenta con 16 mesadas individuales, campana de extracción y equipos de microscopía digital.</p><p>Los docentes recibirán capacitación durante el mes de octubre.</p>',
     'academica', 'assets/laboratorio.avif', 'publicada', '2026-08-20'],
    ['Olimpiadas de Matemática — resultados', 'Tres alumnos del nivel secundario clasificaron a la instancia provincial de las Olimpiadas de Matemática.',
     '<p>Con orgullo, la institución anuncia que Ana García (3°A), Valentina Paz (2°B) y otra alumna de 4°C clasificaron a la etapa provincial de las Olimpiadas Nacionales de Matemática.</p><p>La competencia provincial se realizará en noviembre en Resistencia.</p>',
     'academica', 'assets/salacomputacion.avif', 'publicada', '2026-07-10'],
    ['Semana de la Educación Física', 'Del 14 al 18 de octubre se realizará la Semana de la Educación Física con jornadas deportivas y recreativas.',
     '<p>Se convoca a toda la comunidad a participar de la Semana de la Educación Física. Habrá competencias de atletismo, natación, básquet y handball, más una jornada de juegos recreativos para nivel inicial y primario.</p>',
     'deportiva', 'assets/gimnasio.avif', 'publicada', '2026-09-01'],
    ['Taller de Orientación Vocacional', 'Alumnos de 5° y 6° año participaron del taller de orientación vocacional dictado por profesionales del CONICET.',
     '<p>Durante dos semanas, los alumnos del ciclo superior participaron de talleres grupales y entrevistas individuales orientadas a la toma de decisiones sobre el futuro académico y laboral.</p><p>El material de trabajo quedará disponible en la biblioteca institucional.</p>',
     'academica', 'assets/salacomputacion.avif', 'publicada', '2026-06-05'],
    ['Festival Cultural de Fin de Año', 'Reservá la fecha: el 12 de diciembre se realizará el Festival Cultural anual con muestras, teatro y música en vivo.',
     '<p>El Festival Cultural 2026 reunirá producciones de todos los niveles. Habrá exposición de trabajos plásticos, obra de teatro del elenco de 4° año, presentación del coro institucional y números musicales del taller de Iniciación Musical de Sala 4.</p>',
     'cultural', 'assets/piscina.avif', 'publicada', '2026-09-18'],
];

$stmt = $conn->prepare(
    "INSERT INTO noticias (titulo, resumen, contenido, categoria, imagen_url, estado, fecha_pub)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
foreach ($noticias as $n) {
    $stmt->bind_param('sssssss', $n[0], $n[1], $n[2], $n[3], $n[4], $n[5], $n[6]);
    $stmt->execute();
    row('ok', '✅', $n[0]);
}
$stmt->close();
echo '</ul>';

// ── 16. OPINIONES ─────────────────────────────────────────────────────────────
echo '<h2>16. Opiniones</h2><ul>';

$opiniones = [
    ['Laura M.',        'Excelente atención de los docentes. Mi hija avanzó muchísimo este año en Matemática.', 9, 2026, 'aprobado'],
    ['Diego F.',        'Muy buena comunicación con las familias. El portal de padres es muy útil para seguir el progreso.', 8, 2026, 'aprobado'],
    ['Carlos H.',       'Las instalaciones deportivas son fantásticas. El campo de fútbol está en condiciones impecables.', 9, 2026, 'aprobado'],
    ['Marta T.',        'Los docentes son muy comprometidos. Mi hijo mejoró notablemente desde que entró a la institución.', 8, 2026, 'aprobado'],
    ['Anónimo',         'El servicio de comedor es muy bueno. Las porciones son adecuadas y la comida está bien.', 7, 2026, 'aprobado'],
    ['Roxana S.',       'Muy conforme con el nivel académico. Los laboratorios nuevos son un lujo.', 9, 2026, 'aprobado'],
    ['Jorge A.',        'Buena comunicación institucional. Siempre recibimos respuesta rápida ante cualquier consulta.', 8, 2026, 'aprobado'],
    ['Anónimo',         'El transporte escolar funciona muy bien, puntual y seguro.', 8, 2026, 'aprobado'],
    ['Patricia V.',     'Me gustaría que extendieran el horario de la biblioteca. Por lo demás, todo excelente.', 7, 2026, 'pendiente'],
    ['Marcelo R.',      'Extraordinaria propuesta pedagógica. Se nota que el equipo docente está muy actualizado.', 10, 2026, 'aprobado'],
];

$stmt = $conn->prepare(
    "INSERT INTO opiniones (nombre, texto, mes, anio, estado) VALUES (?, ?, ?, ?, ?)"
);
foreach ($opiniones as $o) {
    $stmt->bind_param('ssiss', $o[0], $o[1], $o[2], $o[3], $o[4]);
    $stmt->execute();
    row('ok', '✅', "{$o[0]} — {$o[4]}");
}
$stmt->close();
echo '</ul>';

// ── 17. PUESTOS VACANTES + POSTULACIONES ──────────────────────────────────────
echo '<h2>17. Puestos vacantes</h2><ul>';

$puestos = [
    ['Docente de Inglés — nivel secundario', 'Se busca docente graduado con experiencia mínima de 2 años en nivel secundario. Disponibilidad horaria tarde. Certificado Cambridge (B2+) excluyente.', 'Tiempo completo', 1],
    ['Auxiliar de laboratorio', 'Se incorpora auxiliar para los laboratorios de Física y Química. Requisito: estudios en curso o completos en Ciencias Naturales, Química o afines.', 'Medio tiempo', 0],
    ['Preceptor/a — turno tarde', 'Se requiere preceptor con experiencia en gestión de grupos de adolescentes. Buen manejo de herramientas digitales. Disponibilidad inmediata.', 'Tiempo completo', 1],
    ['Docente de Educación Física', 'Se busca profesor/a de Educación Física con orientación en deportes acuáticos. Manejo de pileta reglamentaria. Experiencia en nivel primario y/o secundario.', 'Tiempo completo', 0],
];

$stmt = $conn->prepare(
    "INSERT INTO puestos_vacantes (titulo, descripcion, tipo, urgente) VALUES (?, ?, ?, ?)"
);
$puesto_ids = [];
foreach ($puestos as $p) {
    $stmt->bind_param('sssi', $p[0], $p[1], $p[2], $p[3]);
    $stmt->execute();
    $puesto_ids[] = $conn->insert_id;
    row('ok', '✅', $p[0]);
}
$stmt->close();
echo '</ul>';

if (count($puesto_ids) >= 3) {
    echo '<h2>17b. Postulaciones</h2><ul>';

    $postulaciones = [
        [$puesto_ids[0], 'Lucía',   'Fernández', '28345678', 'luci.fern@gmail.com',    '3624100200', 3, 'Tres años en escuelas secundarias del Gran Chaco. Nivel C1 en inglés, certificado CAE 2023.', 'pendiente'],
        [$puesto_ids[0], 'Gonzalo', 'Ponce',     '34567890', 'gponce.eng@outlook.com', '3624300400', 5, 'Docente de inglés en colegios bilingües en Resistencia desde 2019. IELTS Band 7.5.', 'revisado'],
        [$puesto_ids[1], 'Romina',  'Álvarez',   '37890123', 'romina.alv@hotmail.com', '3624500600', 1, 'Estudiante avanzada de Química en UNNE. Experiencia en laboratorio de síntesis orgánica.', 'pendiente'],
        [$puesto_ids[2], 'Marcos',  'Díaz',      '31234567', 'marcosdiaz@gmail.com',   '3624700800', 4, 'Cuatro años como preceptor en escuela secundaria pública. Buenas referencias comprobables.', 'seleccionado'],
        [$puesto_ids[2], 'Sofía',   'Luna',      '39012345', 'sofia.luna@gmail.com',   '3624900000', 2, 'Dos años como auxiliar docente y preceptora suplente en nivel secundario.', 'revisado'],
        [$puesto_ids[3], 'Damián',  'Cáceres',   '26789012', 'damian.caceres@gmail.com','3624010203', 6, 'Profesor de Educación Física con especialización en natación y entrenamiento deportivo.', 'pendiente'],
    ];

    $stmt = $conn->prepare(
        "INSERT INTO postulaciones
         (puesto_id, nombre, apellido, dni, email, telefono, experiencia_anios, experiencia_descripcion, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($postulaciones as $p) {
        $stmt->bind_param('isssssiss', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8]);
        $stmt->execute();
        row('ok', '✅', "{$p[1]} {$p[2]} para puesto_id={$p[0]} — {$p[8]}");
    }
    $stmt->close();
    echo '</ul>';
}

// ── 18. CONSULTAS DE CONTACTO ─────────────────────────────────────────────────
echo '<h2>18. Consultas de contacto</h2><ul>';

$consultas = [
    ['Jorge Aguirre',    'jorge.aguirre@gmail.com',    '¿Cuáles son los horarios de inscripción?',             'Quería saber si las inscripciones para el ciclo 2027 se hacen en persona o exclusivamente de manera online.',               'pendiente'],
    ['Carmen Méndez',    'carmen.mendez@gmail.com',    'Consulta sobre traslado de legajo',                    'Mi hijo viene de otra institución y necesito saber qué documentación debo presentar para el traslado del legajo.',         'leida'],
    ['Marcela Alvarado', 'marcela.alv@hotmail.com',    'Necesidades educativas especiales',                    'Mi hijo tiene diagnóstico de TDAH. Quisiera saber si la institución cuenta con equipo de orientación o apoyo pedagógico.', 'respondida'],
    ['Víctor Delgado',   'victor.delgado@hotmail.com', 'Información sobre nivel inicial',                      'Queremos anotar a nuestra hija Emma en Sala de 4 años. ¿Cuáles son los requisitos y si hay vacantes disponibles?',         'pendiente'],
    ['Roxana Soria',     'roxana.soria@gmail.com',     'Beca o ayuda económica',                               'Somos una familia con dificultades económicas. ¿La institución cuenta con algún programa de becas o aranceles diferenciados?', 'pendiente'],
    ['Roberto Gómez',    'roberto.gomez@yahoo.com',    'Visita a las instalaciones',                           'Estamos considerando inscribir a Florencia. ¿Podríamos coordinar una visita guiada a las instalaciones antes de decidir?', 'leida'],
    ['Luis Juárez',      'luis.juarez@outlook.com',    'Agradecimiento por la atención recibida',              'Quiero expresar mi agradecimiento al equipo directivo y a la Sra. Secretaria por la excelente atención durante el proceso de inscripción de Martina.', 'archivada'],
    ['Silvia Paredes',   'silvia.paredes@gmail.com',   'Horario de atención a familias',                       '¿En qué horario pueden atendernos para consultas sobre el progreso de nuestro hijo en el nivel inicial?',                  'pendiente'],
];

$stmt = $conn->prepare(
    "INSERT INTO consultas (nombre, email, asunto, mensaje, estado) VALUES (?, ?, ?, ?, ?)"
);
foreach ($consultas as $c) {
    $stmt->bind_param('sssss', $c[0], $c[1], $c[2], $c[3], $c[4]);
    $stmt->execute();
    row('ok', '✅', "{$c[0]} — {$c[4]}");
}
$stmt->close();
echo '</ul>';

// ─────────────────────────────────────────────────────────────────────────────
$conn->close();

echo '<hr><h2 style="color:#4ade80">✅ Seed completado</h2>';
echo '<h3 style="color:#f97316">Credenciales nuevas</h3>';
echo '<table>
<tr><th>Usuario</th><th>Contraseña</th><th>Rol</th></tr>
<tr><td>sofia.herrera</td><td><code>alumno789</code></td><td>alumno</td></tr>
<tr><td>miguel.torres</td><td><code>alumno321</code></td><td>alumno</td></tr>
<tr><td>valentina.paz</td><td><code>alumno654</code></td><td>alumno</td></tr>
<tr><td>ezequiel.romero</td><td><code>alumno987</code></td><td>alumno</td></tr>
<tr><td>luciana.campos</td><td><code>alumno111</code></td><td>alumno</td></tr>
<tr><td>matias.vega</td><td><code>alumno222</code></td><td>alumno</td></tr>
<tr><td>agustina.molina</td><td><code>alumno333</code></td><td>alumno</td></tr>
<tr><td>bruno.flores</td><td><code>alumno444</code></td><td>alumno</td></tr>
<tr><td>patricia.aguirre</td><td><code>docente789</code></td><td>docente</td></tr>
<tr><td>ernesto.castillo</td><td><code>docente321</code></td><td>docente</td></tr>
<tr><td>carlos.herrera</td><td><code>padre789</code></td><td>padre</td></tr>
<tr><td>marta.torres</td><td><code>padre321</code></td><td>padre</td></tr>
<tr><td>fernando.paz</td><td><code>padre654</code></td><td>padre</td></tr>
</table>
<br><strong style="color:#f87171">⚠️ Eliminá este archivo después de ejecutarlo.</strong>
</body></html>';
