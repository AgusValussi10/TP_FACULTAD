<?php
require_once __DIR__ . '/database/db_config.php';

$usuarios_demo = [
    ['nombre' => 'Ana García',       'usuario' => 'ana.garcia',       'password' => 'alumno123',  'rol' => 'alumno'],
    ['nombre' => 'Carlos López',     'usuario' => 'carlos.lopez',     'password' => 'alumno456',  'rol' => 'alumno'],
    ['nombre' => 'María Rodríguez',  'usuario' => 'maria.rodriguez',  'password' => 'docente123', 'rol' => 'docente'],
    ['nombre' => 'Roberto Silva',    'usuario' => 'roberto.silva',    'password' => 'docente456', 'rol' => 'docente'],
    ['nombre' => 'Laura Martínez',   'usuario' => 'laura.martinez',   'password' => 'padre123',   'rol' => 'padre'],
    ['nombre' => 'Diego Fernández',  'usuario' => 'diego.fernandez',  'password' => 'padre456',   'rol' => 'padre'],
];

$stmt = $conn->prepare(
    "INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)"
);

echo '<h2>Creando usuarios de demo...</h2><ul>';
foreach ($usuarios_demo as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt->bind_param('ssss', $u['nombre'], $u['usuario'], $hash, $u['rol']);
    if ($stmt->execute()) {
        echo "<li>OK: <strong>{$u['usuario']}</strong> ({$u['rol']}) — password: <code>{$u['password']}</code></li>";
    } else {
        echo "<li>Ya existe: {$u['usuario']}</li>";
    }
}
echo '</ul>';
$stmt->close();
$conn->close();
echo '<br><strong>Listo. Ahora podes iniciar sesion en <a href="/">la landing</a>.</strong>';
