<?php

/** POST → cierra la sesión de la app (RFGm01). */

require_once __DIR__ . '/_base.php';

requerir_metodo('POST');

$_SESSION = [];
session_destroy();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);

responder_json(200, true, null, 'Cerraste sesión.');
