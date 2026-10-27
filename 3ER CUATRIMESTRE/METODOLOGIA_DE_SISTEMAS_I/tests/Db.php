<?php

/** Acceso directo a la base de prueba, solo para preparar y verificar datos. */
class Db
{
    private static ?mysqli $conn = null;

    public static function conn(): mysqli
    {
        if (self::$conn === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            self::$conn = new mysqli(
                getenv('MYSQLHOST') ?: '127.0.0.1',
                getenv('MYSQLUSER') ?: 'root',
                getenv('MYSQLPASSWORD') ?: '',
                getenv('MYSQLDATABASE') ?: 'educar_db',
                (int) (getenv('MYSQLPORT') ?: 3306)
            );
            self::$conn->set_charset('utf8');
        }
        return self::$conn;
    }

    /** Devuelve el primer valor de la primera fila (o null). */
    public static function valor(string $sql, string $tipos = '', ...$params)
    {
        $stmt = self::conn()->prepare($sql);
        if ($tipos !== '') {
            $stmt->bind_param($tipos, ...$params);
        }
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $fila[0] ?? null;
    }

    public static function exec(string $sql): void
    {
        self::conn()->query($sql);
    }
}
