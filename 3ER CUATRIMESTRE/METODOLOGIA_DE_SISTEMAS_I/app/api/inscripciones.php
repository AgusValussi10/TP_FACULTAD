<?php

/**
 * GET ?alumno_id= → inscripciones del alumno y opciones (RFGm09/10/11):
 * deportes (horario, profesor, valor, cupo, si puede inscribirse y por qué no),
 * reservas de comedor y transporte, meses que todavía se pueden reservar y recorridos con valor.
 * Lo ve el padre (de sus hijos) y el alumno (lo suyo); solo el padre modifica.
 */

require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../lib/inscripciones.php';

requerir_metodo('GET');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';
$alumno_id = api_alumno_permitido($conn, $sesion, $_GET['alumno_id'] ?? 0);

$tarifas = tarifas_valores($conn);
responder_json(200, true, [
    'puede_modificar' => $sesion['rol'] === 'padre',
    'max_deportes'    => MAX_DEPORTES_SIMULTANEOS,
    'deportes'        => deportes_para_alumno($conn, $alumno_id),
    'servicios'       => servicios_de_alumno($conn, $alumno_id),
    'meses'           => meses_reservables(),
    'recorridos'      => recorridos_con_valor($conn),
    'valor_comedor'   => $tarifas['comedor'] ?? null,
    'dia_limite'      => DIA_LIMITE_RESERVA_SERVICIOS,
]);
$conn->close();
