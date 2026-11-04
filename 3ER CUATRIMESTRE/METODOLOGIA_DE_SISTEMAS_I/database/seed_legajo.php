<?php

/**
 * seed_legajo.php — Datos personales de demostración para el legajo (tabla alumnos_datos).
 *
 * Requiere que exista la tabla alumnos_datos (la crea init_db.php en cada deploy,
 * o database/schema_legajo.sql en local).
 *
 * Cómo impactarlo en la BDD:
 *   - Producción: abrir https://educar-para-transformar-nw1k.onrender.com/database/seed_legajo.php
 *   - Local/CI:   cd database && php seed_legajo.php
 *
 * Es idempotente: solo completa los alumnos que todavía no tienen datos cargados
 * (INSERT IGNORE), así que no pisa lo que se haya editado desde el ABM.
 * Todos los datos son ficticios.
 */

require_once 'db_config.php';
$conn->set_charset('utf8');

// usuario => [dni, nacimiento, domicilio, teléfono, email, tutor, contacto de emergencia, obra social, salud]
$datos = [
    'ana.garcia'      => ['49812345', '2011-04-12', 'Av. Sarmiento 1450, Resistencia',      '362 4512345', 'familia.garcia@mail.com',   'Laura Martínez',   'Laura Martínez (madre) · 362 4512345',  'OSDE',            null],
    'carlos.lopez'    => ['49934567', '2011-08-03', 'Juan B. Justo 820, Resistencia',      '362 4623456', 'diego.fernandez@mail.com',  'Diego Fernández',  'Diego Fernández (tutor) · 362 4623456', 'IOSFA',           'Asma leve: usa inhalador (salbutamol) ante esfuerzo intenso.'],
    'sofia.herrera'   => ['49756789', '2011-01-27', 'French 330, Resistencia',             '362 4734567', 'carlos.herrera@mail.com',   'Carlos Herrera',   'Carlos Herrera (padre) · 362 4734567',  'InSSSeP',         null],
    'miguel.torres'   => ['50012678', '2011-11-15', 'Av. 25 de Mayo 2100, Resistencia',    '362 4845678', 'marta.torres@mail.com',     'Marta Torres',     'Marta Torres (madre) · 362 4845678',    'Swiss Medical',   'Alergia a la penicilina.'],
    'valentina.paz'   => ['50345123', '2012-06-09', 'Güemes 455, Resistencia',             '362 4956789', 'fernando.paz@mail.com',     'Fernando Paz',     'Fernando Paz (padre) · 362 4956789',    'OSDE',            null],
    'ezequiel.romero' => ['50398765', '2012-09-21', 'Av. Alberdi 1780, Resistencia',       '362 4067890', 'familia.romero@mail.com',   'Silvia Romero',    'Silvia Romero (madre) · 362 4067890',   'PAMI (a cargo)',  null],
    'luciana.campos'  => ['50876543', '2013-03-30', 'Mitre 960, Resistencia',              '362 4178901', 'familia.campos@mail.com',   'Gustavo Campos',   'Gustavo Campos (padre) · 362 4178901',  'InSSSeP',         'Celiaquía: comedor sin TACC.'],
    'matias.vega'     => ['50912345', '2013-07-18', 'Necochea 1210, Resistencia',          '362 4289012', 'familia.vega@mail.com',     'Romina Vega',      'Romina Vega (madre) · 362 4289012',     'Sin obra social', null],
    'agustina.molina' => ['56123456', '2022-02-14', 'Av. Castelli 3400, Resistencia',      '362 4390123', 'familia.molina@mail.com',   'Paula Molina',     'Paula Molina (madre) · 362 4390123',    'OSDE',            null],
    'bruno.flores'    => ['56234567', '2022-05-06', 'Santa María de Oro 780, Resistencia', '362 4401234', 'familia.flores@mail.com',   'Javier Flores',    'Javier Flores (padre) · 362 4401234',   'Galeno',          'Intolerancia a la lactosa.'],
];

$buscar = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND rol = 'alumno'");
$insert = $conn->prepare(
    "INSERT IGNORE INTO alumnos_datos
        (alumno_id, dni, fecha_nacimiento, domicilio, telefono, email, tutor_nombre, contacto_emergencia, obra_social, observaciones_salud)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$cargados = 0;
$salida = [];
foreach ($datos as $usuario => $d) {
    $buscar->bind_param('s', $usuario);
    $buscar->execute();
    $fila = $buscar->get_result()->fetch_assoc();
    if (!$fila) {
        $salida[] = "omitido  $usuario (no existe el usuario)";
        continue;
    }
    $id = (int) $fila['id'];
    $insert->bind_param('isssssssss', $id, ...$d);
    $insert->execute();
    if ($insert->affected_rows > 0) {
        $cargados++;
        $salida[] = "cargado  $usuario";
    } else {
        $salida[] = "ya tenía $usuario";
    }
}
$buscar->close();
$insert->close();
$conn->close();

$texto = implode(PHP_EOL, $salida) . PHP_EOL . "Listo: $cargados alumnos con datos nuevos." . PHP_EOL;
if (PHP_SAPI === 'cli') {
    echo $texto;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $texto;
}
