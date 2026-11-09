<?php

/** POST (actual, nueva, confirmacion) → cambia la contraseña del usuario de la sesión (RFGm02). */

require_once __DIR__ . '/_base.php';

requerir_metodo('POST');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';

$actual = (string) ($_POST['actual'] ?? '');
$nueva  = (string) ($_POST['nueva'] ?? '');
$conf   = (string) ($_POST['confirmacion'] ?? '');

$error = null;
if ($actual === '' || $nueva === '') {
    $error = 'Completá la contraseña actual y la nueva.';
} elseif (strlen($nueva) < PASSWORD_MIN || strlen($nueva) > PASSWORD_MAX) {
    $error = 'La contraseña nueva tiene que tener entre ' . PASSWORD_MIN . ' y ' . PASSWORD_MAX . ' caracteres.';
} elseif ($nueva !== $conf) {
    $error = 'La confirmación no coincide con la contraseña nueva.';
} elseif ($nueva === $actual) {
    $error = 'La contraseña nueva tiene que ser distinta de la actual.';
}
if ($error) {
    responder_json(422, false, null, $error);
    exit;
}

$stmt = $conn->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $sesion['id']);
$stmt->execute();
$hash = (string) ($stmt->get_result()->fetch_row()[0] ?? '');
$stmt->close();
if (!password_verify($actual, $hash)) {
    responder_json(422, false, null, 'La contraseña actual no es correcta.');
    exit;
}

$nuevo_hash = password_hash($nueva, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
$stmt->bind_param('si', $nuevo_hash, $sesion['id']);
$stmt->execute();
$stmt->close();
$conn->close();

responder_json(200, true, null, 'Listo, cambiaste tu contraseña.');
