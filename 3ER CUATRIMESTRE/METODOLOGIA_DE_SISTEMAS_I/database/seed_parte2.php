<?php
/**
 * seed_parte2.php — Continuación del seed desde la sección 11 en adelante.
 * Ejecutar en: https://educar-para-transformar-nw1k.onrender.com/database/seed_parte2.php
 */
require_once 'db_config.php';
$conn->set_charset('utf8');

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Seed Parte 2</title><style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:24px;line-height:1.6}
h2{color:#f97316;margin:24px 0 6px}
ul{margin:0 0 8px;padding-left:20px}
.ok{color:#4ade80}.skip{color:#64748b}.err{color:#f87171}
table{border-collapse:collapse;margin-top:8px}
th,td{border:1px solid #334155;padding:4px 12px;text-align:left}
th{background:#1e293b;color:#f97316}
code{background:#1e293b;padding:1px 5px;border-radius:3px}
</style></head><body>';

function uid(mysqli $c, string $usuario): int {
    $r = $c->query("SELECT id FROM usuarios WHERE usuario = '" . $c->real_escape_string($usuario) . "'");
    return (int)($r ? $r->fetch_assoc()['id'] ?? 0 : 0);
}
function row(string $class, string $icon, string $text): void {
    echo "<li class='$class'>$icon $text</li>";
}

// ── PASO PREVIO: garantizar que sandra.benitez exista ─────────────────────────
echo '<h2>0. Verificar usuarios necesarios</h2><ul>';

$usuarios_req = [
    ['nombre' => 'Sandra Benítez', 'usuario' => 'sandra.benitez', 'pass' => 'enfermeria123', 'rol' => 'enfermeria'],
    // usuarios de alumnos que se usan en secciones 11-12
    ['nombre' => 'Ana García',      'usuario' => 'ana.garcia',      'pass' => 'alumno123',  'rol' => 'alumno'],
    ['nombre' => 'Carlos López',    'usuario' => 'carlos.lopez',    'pass' => 'alumno456',  'rol' => 'alumno'],
    ['nombre' => 'Sofía Herrera',   'usuario' => 'sofia.herrera',   'pass' => 'alumno789',  'rol' => 'alumno'],
    ['nombre' => 'Miguel Torres',   'usuario' => 'miguel.torres',   'pass' => 'alumno321',  'rol' => 'alumno'],
    ['nombre' => 'Ezequiel Romero', 'usuario' => 'ezequiel.romero', 'pass' => 'alumno987',  'rol' => 'alumno'],
    ['nombre' => 'Luciana Campos',  'usuario' => 'luciana.campos',  'pass' => 'alumno111',  'rol' => 'alumno'],
    ['nombre' => 'Matías Vega',     'usuario' => 'matias.vega',     'pass' => 'alumno222',  'rol' => 'alumno'],
    ['nombre' => 'Agustina Molina', 'usuario' => 'agustina.molina', 'pass' => 'alumno333',  'rol' => 'alumno'],
    ['nombre' => 'Bruno Flores',    'usuario' => 'bruno.flores',    'pass' => 'alumno444',  'rol' => 'alumno'],
    ['nombre' => 'Valentina Paz',   'usuario' => 'valentina.paz',   'pass' => 'alumno654',  'rol' => 'alumno'],
];

$stmt = $conn->prepare("INSERT IGNORE INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)");
foreach ($usuarios_req as $u) {
    $hash = password_hash($u['pass'], PASSWORD_BCRYPT);
    $stmt->bind_param('ssss', $u['nombre'], $u['usuario'], $hash, $u['rol']);
    $stmt->execute();
    $icon  = $stmt->affected_rows > 0 ? '✅' : '—';
    $class = $stmt->affected_rows > 0 ? 'ok' : 'skip';
    row($class, $icon, "{$u['usuario']} ({$u['rol']})");
}
$stmt->close();
echo '</ul>';

// Cargar IDs
$sandra   = uid($conn, 'sandra.benitez');
$ana      = uid($conn, 'ana.garcia');
$carlos   = uid($conn, 'carlos.lopez');
$sofia    = uid($conn, 'sofia.herrera');
$miguel   = uid($conn, 'miguel.torres');
$ezequiel = uid($conn, 'ezequiel.romero');
$luciana  = uid($conn, 'luciana.campos');
$matias   = uid($conn, 'matias.vega');
$agus     = uid($conn, 'agustina.molina');
$valentina = uid($conn, 'valentina.paz');
$bruno    = uid($conn, 'bruno.flores');

if (!$sandra) {
    echo "<p class='err'>❌ No se pudo obtener el ID de sandra.benitez. Verificá que el ENUM de usuarios.rol incluye 'enfermeria' (ejecutar schema_sprint2.sql primero).</p>";
    $conn->close(); exit;
}

// ── 11. ATENCIONES DE ENFERMERÍA ──────────────────────────────────────────────
echo '<h2>11. Atenciones de enfermería</h2><ul>';

$atenciones = [
    [$ana,      'Dolor de cabeza intenso durante clase de Matemática',              '09:30:00', 'Se administró ibuprofeno 200mg. Se notificó a la familia. Alumna descansó 1 hora.',                      $sandra],
    [$carlos,   'Mareos y náuseas tras el recreo',                                  '10:45:00', 'Sin fiebre. Posible hipoglucemia. Se indicó merienda y reposo de 30 minutos.',                           $sandra],
    [$sofia,    'Golpe en rodilla derecha durante Educación Física',                '11:20:00', 'Sin herida abierta. Se aplicó hielo 15 min. Se recomendó reposo de la actividad por el resto del día.', $sandra],
    [$miguel,   'Fiebre 38.2°C',                                                    '08:50:00', 'Se comunicó a los padres. El alumno fue retirado por su madre a las 10:00hs.',                           $sandra],
    [$ezequiel, 'Herida cortante superficial en la palma de la mano',               '13:10:00', 'Limpieza con agua y jabón, curación con antiséptico y apósito. Sin necesidad de puntos.',               $sandra],
    [$luciana,  'Alergia ocular intensa — lagrimeo y enrojecimiento',               '09:15:00', 'Antecedente de rinitis alérgica. Se aplicó suero fisiológico. Se informó a la docente.',                $sandra],
    [$matias,   'Dolor abdominal leve — posible cólico',                            '10:00:00', 'Sin fiebre ni síntomas febriles. Reposo 30 minutos. El alumno mejoró y regresó al aula.',               $sandra],
    [$agus,     'Caída en el patio — golpe en el codo izquierdo',                   '15:30:00', 'Sin lesión grave. Se aplicó hielo. Se tranquilizó a la alumna y se avisó a la familia.',                $sandra],
    [$ana,      'Mareos tras exposición solar prolongada en recreo',                '12:45:00', 'Se indicó hidratación y reposo en un lugar fresco. Mejoró en 20 minutos.',                              $sandra],
    [$carlos,   'Golpe en la cabeza durante clase de Educación Física',             '14:00:00', 'Sin pérdida de conciencia. Se evaluó y se realizó seguimiento de 60 min. Familia notificada.',          $sandra],
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

$reservas = [
    [$ana,       'comedor',    7, 2026],
    [$ana,       'comedor',    8, 2026],
    [$ana,       'comedor',    9, 2026],
    [$ana,       'transporte', 7, 2026],
    [$ana,       'transporte', 8, 2026],
    [$carlos,    'comedor',    8, 2026],
    [$sofia,     'comedor',    7, 2026],
    [$sofia,     'comedor',    8, 2026],
    [$sofia,     'comedor',    9, 2026],
    [$sofia,     'transporte', 7, 2026],
    [$sofia,     'transporte', 8, 2026],
    [$sofia,     'transporte', 9, 2026],
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

$laura     = uid($conn, 'laura.martinez');
$diego     = uid($conn, 'diego.fernandez');
$c_herrera = uid($conn, 'carlos.herrera');
$m_torres  = uid($conn, 'marta.torres');

$notifs = [
    [$laura,     $ana,    'enfermeria',    'Ana fue atendida en enfermería por dolor de cabeza. Ya se encuentra bien y regresó al aula.'],
    [$laura,     $ana,    'calificacion',  'Se cargaron calificaciones del 2do trimestre. Promedio en Matemática: 8. Ver boletín completo en el portal.'],
    [$diego,     $carlos, 'enfermeria',    'Carlos fue atendido en enfermería por mareos. Se recomienda que lleve colación todos los días.'],
    [$diego,     $carlos, 'asistencia',    'Carlos registra 8 ausencias en el mes de agosto. El límite por cuatrimestre es 15. Por favor regularizar.'],
    [$diego,     $carlos, 'sancion',       'Carlos recibió una sanción disciplinaria el 20/08. Ver detalle en el portal o comunicarse con Secretaría.'],
    [$diego,     $carlos, 'recuperatorio', 'Se programaron exámenes recuperatorios para Carlos. Consulte las fechas y turnos en el portal.'],
    [$c_herrera, $sofia,  'enfermeria',    'Sofía fue atendida en enfermería por un golpe leve en la rodilla durante Educación Física. Sin complicaciones.'],
    [$c_herrera, $sofia,  'recuperatorio', 'Se programó recuperatorio de Física para Sofía el 06/10/2026 en turno mañana.'],
    [$m_torres,  $miguel, 'enfermeria',    'Miguel presentó fiebre de 38.2°C y fue retirado. Por favor manténgase en contacto con la escuela.'],
    [$m_torres,  $miguel, 'recuperatorio', 'Se programó recuperatorio de Física para Miguel el 08/10/2026 en turno mañana. Estado: aprobado.'],
];

$stmt = $conn->prepare(
    "INSERT INTO notificaciones (padre_id, alumno_id, tipo, mensaje) VALUES (?, ?, ?, ?)"
);
foreach ($notifs as $n) {
    $stmt->bind_param('iiss', $n[0], $n[1], $n[2], $n[3]);
    $stmt->execute();
    row('ok', '✅', "Notif '{$n[2]}' padre_id={$n[0]}");
}
$stmt->close();
echo '</ul>';

// ── 15. NOTICIAS ──────────────────────────────────────────────────────────────
echo '<h2>15. Noticias</h2><ul>';

$noticias = [
    ['Inicio del ciclo lectivo 2026', 'La institución abre sus puertas con renovadas aulas y nuevos recursos pedagógicos para todos los niveles.',
     '<p>Con gran entusiasmo, la comunidad educativa inició el ciclo lectivo 2026. Las obras de refacción en el ala sur fueron concluidas durante enero, sumando tres nuevas aulas con equipamiento tecnológico de punta.</p><p>La Rectora destacó la incorporación de tres nuevos docentes en las áreas de Ciencias y Lenguas Extranjeras.</p>',
     'institucional', 'assets/edificio.avif', 'publicada', '2026-03-02'],
    ['Torneo Interescolar de Fútbol 2026', 'Nuestro equipo avanzó a cuartos de final en el torneo regional disputado este mes.',
     '<p>El equipo de fútbol del nivel secundario logró una destacada actuación en el Torneo Interescolar Regional, venciendo en los primeros tres encuentros con solvencia.</p><p>El próximo partido se disputará el sábado 28 de septiembre en nuestro campo de juego.</p>',
     'deportiva', 'assets/campofutbol.avif', 'publicada', '2026-09-15'],
    ['Apertura de inscripciones 2027', 'Ya están abiertas las preinscripciones para el ciclo lectivo 2027 en todos los niveles educativos.',
     '<p>Informamos que desde el 15 de septiembre se encuentran abiertas las preinscripciones para el ciclo 2027. Los interesados podrán completar el formulario en la sección Inscripciones de este sitio web o acercarse personalmente a Secretaría.</p>',
     'institucional', 'assets/inscripciones2027.avif', 'publicada', '2026-09-15'],
    ['Nuevos laboratorios de Ciencias', 'La institución inaugura dos laboratorios completamente equipados para Física, Química y Biología.',
     '<p>Gracias al programa de inversión en infraestructura educativa, se inauguraron dos modernos laboratorios de ciencias naturales. Cada laboratorio cuenta con 16 mesadas individuales, campana de extracción y equipos de microscopía digital.</p>',
     'academica', 'assets/laboratorio.avif', 'publicada', '2026-08-20'],
    ['Olimpiadas de Matemática — resultados', 'Tres alumnos del nivel secundario clasificaron a la instancia provincial.',
     '<p>Con orgullo, la institución anuncia que Ana García (3°A), Valentina Paz (2°B) y una alumna de 4°C clasificaron a la etapa provincial de las Olimpiadas Nacionales de Matemática.</p><p>La competencia provincial se realizará en noviembre en Resistencia.</p>',
     'academica', 'assets/salacomputacion.avif', 'publicada', '2026-07-10'],
    ['Semana de la Educación Física', 'Del 14 al 18 de octubre se realizará la Semana de la Educación Física con jornadas deportivas.',
     '<p>Se convoca a toda la comunidad a participar. Habrá competencias de atletismo, natación, básquet y handball, más una jornada de juegos recreativos para nivel inicial y primario.</p>',
     'deportiva', 'assets/gimnasio.avif', 'publicada', '2026-09-01'],
    ['Taller de Orientación Vocacional', 'Alumnos de 5° y 6° año participaron del taller dictado por profesionales del CONICET.',
     '<p>Durante dos semanas, los alumnos del ciclo superior participaron de talleres grupales y entrevistas individuales orientadas a la toma de decisiones sobre el futuro académico y laboral.</p>',
     'academica', 'assets/salacomputacion.avif', 'publicada', '2026-06-05'],
    ['Festival Cultural de Fin de Año', 'Reservá la fecha: el 12 de diciembre se realizará el Festival Cultural anual.',
     '<p>El Festival Cultural 2026 reunirá producciones de todos los niveles: exposición de trabajos plásticos, obra de teatro del elenco de 4° año, presentación del coro institucional y números musicales del taller de Iniciación Musical de Sala 4.</p>',
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
    ['Laura M.',    'Excelente atención de los docentes. Mi hija avanzó muchísimo este año en Matemática.', 9, 2026, 'aprobado'],
    ['Diego F.',    'Muy buena comunicación con las familias. El portal de padres es muy útil para seguir el progreso.', 8, 2026, 'aprobado'],
    ['Carlos H.',   'Las instalaciones deportivas son fantásticas. El campo de fútbol está en condiciones impecables.', 9, 2026, 'aprobado'],
    ['Marta T.',    'Los docentes son muy comprometidos. Mi hijo mejoró notablemente desde que entró a la institución.', 8, 2026, 'aprobado'],
    ['Anónimo',     'El servicio de comedor es muy bueno. Las porciones son adecuadas y la comida está bien.', 7, 2026, 'aprobado'],
    ['Roxana S.',   'Muy conforme con el nivel académico. Los laboratorios nuevos son un lujo.', 9, 2026, 'aprobado'],
    ['Jorge A.',    'Buena comunicación institucional. Siempre recibimos respuesta rápida ante cualquier consulta.', 8, 2026, 'aprobado'],
    ['Anónimo',     'El transporte escolar funciona muy bien, puntual y seguro.', 8, 2026, 'aprobado'],
    ['Patricia V.', 'Me gustaría que extendieran el horario de la biblioteca. Por lo demás, todo excelente.', 7, 2026, 'pendiente'],
    ['Marcelo R.',  'Extraordinaria propuesta pedagógica. Se nota que el equipo docente está muy actualizado.', 10, 2026, 'aprobado'],
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

echo '<h2>17b. Postulaciones</h2><ul>';
$postulaciones = [
    [$puesto_ids[0], 'Lucía',   'Fernández', '28345678', 'luci.fern@gmail.com',     '3624100200', 3, 'Tres años en escuelas secundarias del Gran Chaco. Nivel C1 en inglés, certificado CAE 2023.', 'pendiente'],
    [$puesto_ids[0], 'Gonzalo', 'Ponce',     '34567890', 'gponce.eng@outlook.com',  '3624300400', 5, 'Docente de inglés en colegios bilingües en Resistencia desde 2019. IELTS Band 7.5.', 'revisado'],
    [$puesto_ids[1], 'Romina',  'Álvarez',   '37890123', 'romina.alv@hotmail.com',  '3624500600', 1, 'Estudiante avanzada de Química en UNNE. Experiencia en laboratorio de síntesis orgánica.', 'pendiente'],
    [$puesto_ids[2], 'Marcos',  'Díaz',      '31234567', 'marcosdiaz@gmail.com',    '3624700800', 4, 'Cuatro años como preceptor en escuela secundaria pública. Buenas referencias comprobables.', 'seleccionado'],
    [$puesto_ids[2], 'Sofía',   'Luna',      '39012345', 'sofia.luna@gmail.com',    '3624900000', 2, 'Dos años como auxiliar docente y preceptora suplente en nivel secundario.', 'revisado'],
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

// ── 18. CONSULTAS DE CONTACTO ─────────────────────────────────────────────────
echo '<h2>18. Consultas de contacto</h2><ul>';

$consultas = [
    ['Jorge Aguirre',    'jorge.aguirre@gmail.com',    '¿Cuáles son los horarios de inscripción?',        'Quería saber si las inscripciones para el ciclo 2027 se hacen en persona o exclusivamente de manera online.',               'pendiente'],
    ['Carmen Méndez',    'carmen.mendez@gmail.com',    'Consulta sobre traslado de legajo',               'Mi hijo viene de otra institución y necesito saber qué documentación debo presentar para el traslado del legajo.',         'leida'],
    ['Marcela Alvarado', 'marcela.alv@hotmail.com',    'Necesidades educativas especiales',               'Mi hijo tiene diagnóstico de TDAH. ¿La institución cuenta con equipo de orientación o apoyo pedagógico?',                  'respondida'],
    ['Víctor Delgado',   'victor.delgado@hotmail.com', 'Información sobre nivel inicial',                 'Queremos anotar a nuestra hija Emma en Sala de 4 años. ¿Cuáles son los requisitos y si hay vacantes disponibles?',         'pendiente'],
    ['Roxana Soria',     'roxana.soria@gmail.com',     'Beca o ayuda económica',                          '¿La institución cuenta con algún programa de becas o aranceles diferenciados para familias con dificultades económicas?',  'pendiente'],
    ['Roberto Gómez',    'roberto.gomez@yahoo.com',    'Visita a las instalaciones',                      '¿Podríamos coordinar una visita guiada a las instalaciones antes de decidir la inscripción de Florencia?',                 'leida'],
    ['Luis Juárez',      'luis.juarez@outlook.com',    'Agradecimiento por la atención recibida',         'Quiero expresar mi agradecimiento al equipo directivo por la excelente atención durante el proceso de inscripción.',       'archivada'],
    ['Silvia Paredes',   'silvia.paredes@gmail.com',   'Horario de atención a familias',                  '¿En qué horario pueden atendernos para consultas sobre el progreso de nuestro hijo en el nivel inicial?',                 'pendiente'],
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

$conn->close();

echo '<hr><h2 style="color:#4ade80">✅ Parte 2 completada (secciones 11–18)</h2>';
echo '<p style="color:#f87171"><strong>⚠️ Eliminá este archivo después de ejecutarlo.</strong></p>';
echo '</body></html>';
