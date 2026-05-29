<?php
$host    = getenv('MYSQLHOST')     ?: 'localhost';
$dbname  = getenv('MYSQLDATABASE') ?: 'educar_db';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_port = (int)(getenv('MYSQLPORT') ?: 3306);

echo "<h2>Variables de entorno</h2><pre>";
echo "MYSQLHOST="     . ($host    ?: '(vacío)') . "\n";
echo "MYSQLDATABASE=" . ($dbname  ?: '(vacío)') . "\n";
echo "MYSQLUSER="     . ($db_user ?: '(vacío)') . "\n";
echo "MYSQLPASSWORD=" . ($db_pass ? '(tiene valor)' : '(vacío)') . "\n";
echo "MYSQLPORT="     . $db_port . "\n";
echo "</pre>";

$conn = new mysqli($host, $db_user, $db_pass, $dbname, $db_port);
if ($conn->connect_error) {
    echo "<p style='color:red'><strong>Error de conexion:</strong> " . htmlspecialchars($conn->connect_error) . "</p>";
    exit;
}

echo "<p style='color:green'>Conexion OK</p>";

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

if (!$stmt) {
    echo "<p style='color:red'>Error preparando query: " . htmlspecialchars($conn->error) . "</p>";
    exit;
}

echo '<h2>Creando usuarios...</h2><ul>';
foreach ($usuarios_demo as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt->bind_param('ssss', $u['nombre'], $u['usuario'], $hash, $u['rol']);
    if ($stmt->execute()) {
        echo "<li>OK: <strong>{$u['usuario']}</strong> ({$u['rol']}) — password: <code>{$u['password']}</code></li>";
    } else {
        echo "<li>Ya existe o error: {$u['usuario']}</li>";
    }
}
echo '</ul>';
$stmt->close();
$conn->close();
echo '<br><strong>Listo. <a href="/">Ir a la landing</a></strong>';
