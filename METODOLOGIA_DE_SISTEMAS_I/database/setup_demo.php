<?php
/**
 * setup_demo.php
 * Crea usuarios de prueba con contraseñas encriptadas.
 * Ejecutar UNA SOLA VEZ desde el navegador: http://localhost/tp-facultad/setup_demo.php
 * ELIMINAR este archivo después de usarlo.
 */
require_once 'db_config.php';

$usuarios_demo = [
    // ALUMNOS
    ['nombre' => 'Ana García',       'usuario' => 'ana.garcia',       'password' => 'alumno123',  'rol' => 'alumno'],
    ['nombre' => 'Carlos López',     'usuario' => 'carlos.lopez',     'password' => 'alumno456',  'rol' => 'alumno'],

    // DOCENTES
    ['nombre' => 'María Rodríguez',  'usuario' => 'maria.rodriguez',  'password' => 'docente123', 'rol' => 'docente'],
    ['nombre' => 'Roberto Silva',    'usuario' => 'roberto.silva',    'password' => 'docente456', 'rol' => 'docente'],

    // PADRES
    ['nombre' => 'Laura Martínez',   'usuario' => 'laura.martinez',   'password' => 'padre123',   'rol' => 'padre'],
    ['nombre' => 'Diego Fernández',  'usuario' => 'diego.fernandez',  'password' => 'padre456',   'rol' => 'padre'],

    // ADMINISTRADOR
    ['nombre' => 'Administrador',    'usuario' => 'admin',            'password' => 'admin',      'rol' => 'admin'],
];

$stmt = $conn->prepare(
    "INSERT IGNORE INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)"
);

echo '<h2>Creando usuarios de demo...</h2><ul>';
foreach ($usuarios_demo as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt->bind_param('ssss', $u['nombre'], $u['usuario'], $hash, $u['rol']);
    if ($stmt->execute()) {
        echo "<li>✅ <strong>{$u['usuario']}</strong> ({$u['rol']}) — contraseña: <code>{$u['password']}</code></li>";
    } else {
        echo "<li>⚠️ {$u['usuario']} ya existe o hubo un error.</li>";
    }
}
echo '</ul>';

$stmt->close();
$conn->close();

echo '<br><strong style="color:green">¡Listo! Podés iniciar sesión con los usuarios de arriba.</strong><br>';
echo '<strong style="color:red">⚠️ IMPORTANTE: Eliminá este archivo (setup_demo.php) después de usarlo.</strong>';
