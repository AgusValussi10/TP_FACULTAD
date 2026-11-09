<?php

/**
 * POST (usuario, password) → inicia sesión en la app (RFGm01). Solo padre y alumno.
 * Reutiliza la verificación de la web (auth/autenticar.php).
 */

require_once __DIR__ . '/_base.php';

requerir_metodo('POST');
require_once __DIR__ . '/../../database/db_config.php';

$usuario  = trim((string) ($_POST['usuario'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
if ($usuario === '' || $password === '') {
    responder_json(422, false, null, 'Completá el usuario y la contraseña.');
    exit;
}

$auth = autenticar_usuario($conn, $usuario, $password, ROLES_APP);
$mensajes = [
    'credenciales' => [401, 'Usuario o contraseña incorrectos.'],
    'rol'          => [403, 'La app es para familias y alumnos. Docentes, enfermería y administración ingresan desde la web.'],
    'suspendido'   => [403, 'Tu usuario está suspendido. Comunicate con la administración de la escuela.'],
];
if ($auth['estado'] !== 'ok') {
    [$http, $mensaje] = $mensajes[$auth['estado']];
    responder_json($http, false, null, $mensaje);
    $conn->close();
    exit;
}

// Sesión nueva al autenticarse (evita fijación de sesión) y cookie renovada con el id nuevo.
session_regenerate_id(true);
$u = $auth['usuario'];
$_SESSION['usuario_id'] = (int) $u['id'];
$_SESSION['nombre']     = $u['nombre'];
$_SESSION['rol']        = $u['rol'];
renovar_cookie_sesion();

$sesion = ['id' => (int) $u['id'], 'rol' => $u['rol'], 'nombre' => $u['nombre']];
session_write_close();

responder_json(200, true, [
    'usuario' => $sesion,
    'alumnos' => api_alumnos($conn, $sesion),
], '¡Hola, ' . $u['nombre'] . '!');
$conn->close();
