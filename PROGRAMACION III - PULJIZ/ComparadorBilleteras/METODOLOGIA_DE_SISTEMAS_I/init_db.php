<?php
$host    = getenv('MYSQLHOST')     ?: 'localhost';
$dbname  = getenv('MYSQLDATABASE') ?: 'educar_db';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_port = (int)(getenv('MYSQLPORT') ?: 3306);

$conn = new mysqli($host, $db_user, $db_pass, $dbname, $db_port);
if ($conn->connect_error) {
    fwrite(STDERR, "DB connection failed: " . $conn->connect_error . PHP_EOL);
    exit(1);
}

$schema = file_get_contents(__DIR__ . '/database/schema.sql');
if ($conn->multi_query($schema)) {
    do {
        if ($res = $conn->store_result()) $res->free();
    } while ($conn->more_results() && $conn->next_result());
}

if ($conn->errno) {
    fwrite(STDERR, "Schema error: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "DB schema OK" . PHP_EOL;
$conn->close();
