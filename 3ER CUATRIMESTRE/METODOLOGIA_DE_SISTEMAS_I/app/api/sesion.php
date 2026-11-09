<?php

/** GET → usuario de la sesión y sus alumnos (hijos del padre, o el propio alumno). 401 si no hay sesión. */

require_once __DIR__ . '/_base.php';

requerir_metodo('GET');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';

responder_json(200, true, ['usuario' => $sesion, 'alumnos' => api_alumnos($conn, $sesion)]);
$conn->close();
