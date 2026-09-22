<?php
require_once 'database/db_config.php';
$hash = password_hash('enfermeria123', PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE usuario = 'sandra.benitez'");
$stmt->bind_param('s', $hash);
$stmt->execute();
echo "Listo. Filas afectadas: " . $stmt->affected_rows;
$stmt->close();
$conn->close();
