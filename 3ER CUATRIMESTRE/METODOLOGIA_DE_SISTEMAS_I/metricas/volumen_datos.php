<?php
/**
 * Agrega volumen de datos "de un ciclo lectivo" a la base de prueba para que
 * las mediciones de rendimiento no se hagan con 10 alumnos de demo.
 * NO correr contra producción.
 *
 * Uso: php metricas/volumen_datos.php [alumnos=300] [consultas=1500]
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(
    getenv('MYSQLHOST') ?: '127.0.0.1', getenv('MYSQLUSER') ?: 'root',
    getenv('MYSQLPASSWORD') ?: '', getenv('MYSQLDATABASE') ?: 'educar_db', (int) (getenv('MYSQLPORT') ?: 3306)
);
$conn->set_charset('utf8');
if (str_contains(getenv('MYSQLHOST') ?: '', 'filess.io')) {
    exit("Esto es la base de producción: no se carga volumen ahí.\n");
}

$nAlumnos   = (int) ($argv[1] ?? 300);
$nConsultas = (int) ($argv[2] ?? 1500);
mt_srand(42); // misma carga en cada corrida → mediciones comparables

$hashAlumno = password_hash('alumno123', PASSWORD_BCRYPT);
$hashPadre  = password_hash('padre123', PASSWORD_BCRYPT);
$cursos = $conn->query('SELECT id FROM cursos')->fetch_all(MYSQLI_ASSOC);
$materiasPorCurso = [];
foreach ($conn->query('SELECT id, curso_id FROM materias') as $m) {
    $materiasPorCurso[$m['curso_id']][] = (int) $m['id'];
}
$evaluaciones = ['Parcial 1', 'Parcial 2', 'TP 1', 'TP 2', 'Oral', 'Integrador', 'Lección', 'Recuperatorio'];
$meses        = [4, 5, 6, 7, 8, 9, 10, 11];

$conn->begin_transaction();
$insUsuario = $conn->prepare('INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)');
$insCurso   = $conn->prepare('INSERT INTO alumno_curso (alumno_id, curso_id) VALUES (?, ?)');
$insPadre   = $conn->prepare('INSERT INTO padre_alumno (padre_id, alumno_id) VALUES (?, ?)');
$insCal     = $conn->prepare('INSERT INTO calificaciones (alumno_id, materia_id, evaluacion, nota, fecha_evaluacion) VALUES (?, ?, ?, ?, ?)');

$calificaciones = [];
$padreId = 0;
for ($i = 1; $i <= $nAlumnos; $i++) {
    $nombre = "Alumno Carga $i"; $usuario = "carga.alumno$i"; $rol = 'alumno';
    $insUsuario->bind_param('ssss', $nombre, $usuario, $hashAlumno, $rol);
    $insUsuario->execute();
    $alumnoId = $conn->insert_id;

    $cursoId = (int) $cursos[$i % count($cursos)]['id'];
    $insCurso->bind_param('ii', $alumnoId, $cursoId);
    $insCurso->execute();

    if ($i % 2 === 1) { // un padre cada dos alumnos (familias con 2 hijos)
        $nombre = 'Tutor Carga ' . (($i + 1) / 2); $usuario = 'carga.padre' . (($i + 1) / 2); $rol = 'padre';
        $insUsuario->bind_param('ssss', $nombre, $usuario, $hashPadre, $rol);
        $insUsuario->execute();
        $padreId = $conn->insert_id;
    }
    $insPadre->bind_param('ii', $padreId, $alumnoId);
    $insPadre->execute();

    foreach ($materiasPorCurso[$cursoId] ?? [] as $materiaId) {
        foreach ($evaluaciones as $k => $ev) {
            $nota  = mt_rand(2, 10);
            $fecha = sprintf('2026-%02d-%02d', $meses[$k], mt_rand(1, 28));
            $insCal->bind_param('iisis', $alumnoId, $materiaId, $ev, $nota, $fecha);
            $insCal->execute();
            $calificaciones[] = [$conn->insert_id, $alumnoId, $padreId];
        }
    }
}

$insConsulta = $conn->prepare(
    "INSERT INTO consultas_notas (calificacion_id, padre_id, alumno_id, mensaje, respuesta, estado, created_at, respondida_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
for ($i = 0; $i < $nConsultas; $i++) {
    [$calId, $alumnoId, $pId] = $calificaciones[mt_rand(0, count($calificaciones) - 1)];
    $respondida = mt_rand(1, 100) <= 60;
    $mensaje    = 'Consulta de carga sobre la evaluación, número ' . $i;
    $respuesta  = $respondida ? 'Respuesta del docente a la consulta ' . $i : null;
    $estado     = $respondida ? 'respondida' : 'pendiente';
    $creada     = sprintf('2026-%02d-%02d 10:%02d:00', mt_rand(4, 10), mt_rand(1, 28), mt_rand(0, 59));
    $resp       = $respondida ? $creada : null;
    $insConsulta->bind_param('iiisssss', $calId, $pId, $alumnoId, $mensaje, $respuesta, $estado, $creada, $resp);
    $insConsulta->execute();
}
$conn->commit();

foreach (['usuarios', 'calificaciones', 'consultas_notas', 'padre_alumno'] as $t) {
    echo str_pad($t, 16) . $conn->query("SELECT COUNT(*) FROM $t")->fetch_row()[0] . PHP_EOL;
}
