<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin']);
errores_como_json();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
require_once __DIR__ . '/../database/db_config.php';

function responder(bool $ok, string $mensaje, array $extra = []): void
{
    echo json_encode(['success' => $ok, 'message' => $mensaje] + $extra);
    exit;
}

$id       = (int)($_POST['id'] ?? 0);
$nombre   = trim($_POST['nombre'] ?? '');
$usuario  = trim($_POST['usuario'] ?? '');
$password = trim($_POST['password'] ?? '');
$curso_id = (int)($_POST['curso_id'] ?? 0);

if (!$nombre || !$usuario) {
    responder(false, 'Nombre y usuario son obligatorios.');
}
if ($id === 0 && !$password) {
    responder(false, 'La contraseña es obligatoria para crear un alumno.');
}

// Datos personales del legajo (todos opcionales). Cadena vacía => NULL.
$campos = ['dni', 'fecha_nacimiento', 'domicilio', 'telefono', 'email', 'tutor_nombre',
           'contacto_emergencia', 'obra_social', 'observaciones_salud'];
$d = [];
foreach ($campos as $c) {
    $v = trim($_POST[$c] ?? '');
    $d[$c] = $v === '' ? null : $v;
}
if ($d['dni'] !== null) {
    $d['dni'] = str_replace(['.', ' '], '', $d['dni']);
    if (!preg_match('/^\d{7,9}$/', $d['dni'])) {
        responder(false, 'El DNI debe tener entre 7 y 9 números (sin puntos).');
    }
}
if ($d['fecha_nacimiento'] !== null) {
    $f = DateTime::createFromFormat('Y-m-d', $d['fecha_nacimiento']);
    if (!$f || $f->format('Y-m-d') !== $d['fecha_nacimiento'] || $f > new DateTime('today') || (int) $f->format('Y') < 1990) {
        responder(false, 'Fecha de nacimiento inválida.');
    }
}
if ($d['email'] !== null && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
    responder(false, 'El email no es válido.');
}
$largos = ['domicilio' => 200, 'telefono' => 30, 'email' => 150, 'tutor_nombre' => 100,
           'contacto_emergencia' => 150, 'obra_social' => 100, 'observaciones_salud' => 2000];
foreach ($largos as $c => $max) {
    if ($d[$c] !== null && mb_strlen($d[$c]) > $max) {
        responder(false, "El campo \"" . str_replace('_', ' ', $c) . "\" admite hasta $max caracteres.");
    }
}

$conn->begin_transaction();
try {
    if ($id === 0) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo) VALUES (?, ?, ?, 'alumno', 1)");
        $stmt->bind_param('sss', $nombre, $usuario, $hash);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
        $mensaje = 'Alumno creado correctamente.';
    } else {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, usuario=?, password_hash=? WHERE id=? AND rol='alumno'");
            $stmt->bind_param('sssi', $nombre, $usuario, $hash, $id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, usuario=? WHERE id=? AND rol='alumno'");
            $stmt->bind_param('ssi', $nombre, $usuario, $id);
        }
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM alumno_curso WHERE alumno_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = 'Alumno actualizado correctamente.';
    }

    if ($curso_id > 0) {
        $stmt = $conn->prepare("INSERT INTO alumno_curso (alumno_id, curso_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $id, $curso_id);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare(
        "INSERT INTO alumnos_datos
            (alumno_id, dni, fecha_nacimiento, domicilio, telefono, email, tutor_nombre,
             contacto_emergencia, obra_social, observaciones_salud, actualizado_en)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            dni = VALUES(dni), fecha_nacimiento = VALUES(fecha_nacimiento), domicilio = VALUES(domicilio),
            telefono = VALUES(telefono), email = VALUES(email), tutor_nombre = VALUES(tutor_nombre),
            contacto_emergencia = VALUES(contacto_emergencia), obra_social = VALUES(obra_social),
            observaciones_salud = VALUES(observaciones_salud), actualizado_en = NOW()"
    );
    $stmt->bind_param('isssssssss', $id, $d['dni'], $d['fecha_nacimiento'], $d['domicilio'], $d['telefono'],
                      $d['email'], $d['tutor_nombre'], $d['contacto_emergencia'], $d['obra_social'], $d['observaciones_salud']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    if ($e->getCode() === 1062) {
        $msg = str_contains($e->getMessage(), 'uq_alumno_dni')
            ? 'Ya hay otro alumno cargado con ese DNI.'
            : 'El nombre de usuario ya existe.';
        responder(false, $msg);
    }
    throw $e;
}
$conn->close();

responder(true, $mensaje, ['id' => $id]);
