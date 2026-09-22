<?php
require_once 'database/db_config.php';

// Mostrar registro actual
$r = $conn->query("SELECT id, usuario, rol, activo, LEFT(password_hash,20) as hash_preview FROM usuarios WHERE usuario = 'sandra.benitez'");
$row = $r->fetch_assoc();
echo "<pre>Registro actual:\n";
print_r($row);
echo "</pre>";

// Actualizar hash y rol
$hash = password_hash('enfermeria123', PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE usuarios SET password_hash = ?, rol = 'enfermeria' WHERE usuario = 'sandra.benitez'");
$stmt->bind_param('s', $hash);
$stmt->execute();
echo "Filas afectadas: " . $stmt->affected_rows . "<br>";

// Verificar que el nuevo hash funciona
$r2 = $conn->query("SELECT password_hash FROM usuarios WHERE usuario = 'sandra.benitez'");
$row2 = $r2->fetch_assoc();
$ok = password_verify('enfermeria123', $row2['password_hash']);
echo "Verificación del nuevo hash: " . ($ok ? "<b style='color:green'>OK</b>" : "<b style='color:red'>FALLO</b>") . "<br>";

$stmt->close();
$conn->close();
