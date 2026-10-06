using Dapper;
using MySqlConnector;

namespace UnionDelSur.API.Data;

public static class InitDb
{
    public static async Task InitializeAsync(IConfiguration config)
    {
        var connStr = config.GetConnectionString("Default")!;

        // Primero conectar sin especificar la BD para poder crearla si no existe
        var builder = new MySqlConnectionStringBuilder(connStr);
        var dbName = builder.Database;
        builder.Database = "";
        await using var rootConn = new MySqlConnection(builder.ConnectionString);
        await rootConn.OpenAsync();
        await rootConn.ExecuteAsync($"CREATE DATABASE IF NOT EXISTS `{dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        await rootConn.CloseAsync();

        await using var conn = new MySqlConnection(connStr);
        await conn.OpenAsync();

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(200) NOT NULL,
                email VARCHAR(200) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                rol ENUM('admin','entrenador','familia') NOT NULL DEFAULT 'familia',
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS categorias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                anio_nacimiento_desde INT NOT NULL,
                anio_nacimiento_hasta INT NOT NULL,
                entrenador_id INT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS grupos_familiares (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre_contacto VARCHAR(200) NOT NULL,
                telefono VARCHAR(50) NULL,
                email VARCHAR(200) NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS jugadores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                apellido VARCHAR(100) NOT NULL,
                dni VARCHAR(20) UNIQUE NOT NULL,
                fecha_nacimiento DATE NOT NULL,
                categoria_id INT NOT NULL,
                grupo_familiar_id INT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS cuotas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                jugador_id INT NOT NULL,
                periodo_mes TINYINT NOT NULL,
                periodo_anio SMALLINT NOT NULL,
                monto DECIMAL(10,2) NOT NULL,
                descuento_hermanos DECIMAL(10,2) NOT NULL DEFAULT 0,
                estado ENUM('pendiente','pagada','vencida') NOT NULL DEFAULT 'pendiente',
                fecha_vencimiento DATE NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_cuota_jugador_periodo (jugador_id, periodo_mes, periodo_anio)
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS pagos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cuota_id INT NOT NULL,
                monto_pagado DECIMAL(10,2) NOT NULL,
                fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                metodo_pago VARCHAR(50) NOT NULL DEFAULT 'Efectivo',
                registrado_por_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS seguros (
                id INT AUTO_INCREMENT PRIMARY KEY,
                jugador_id INT NOT NULL,
                numero_poliza VARCHAR(100) NOT NULL,
                vigente_desde DATE NOT NULL,
                vigente_hasta DATE NOT NULL,
                estado ENUM('vigente','vencido','pendiente') NOT NULL DEFAULT 'vigente'
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS carnets_liga (
                id INT AUTO_INCREMENT PRIMARY KEY,
                jugador_id INT NOT NULL,
                numero_carnet VARCHAR(100) NOT NULL,
                temporada VARCHAR(20) NOT NULL,
                estado ENUM('activo','vencido','pendiente') NOT NULL DEFAULT 'pendiente',
                fecha_emision DATE NULL,
                fecha_vencimiento DATE NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS asistencias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                jugador_id INT NOT NULL,
                categoria_id INT NOT NULL,
                fecha DATE NOT NULL,
                presente TINYINT(1) NOT NULL DEFAULT 0,
                observaciones VARCHAR(500) NULL,
                UNIQUE KEY uq_asistencia (jugador_id, fecha)
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS partidos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                categoria_id INT NOT NULL,
                rival VARCHAR(200) NOT NULL,
                fecha DATE NOT NULL,
                hora VARCHAR(10) NOT NULL,
                lugar ENUM('local','visitante') NOT NULL DEFAULT 'local',
                resultado_local INT NULL,
                resultado_visitante INT NULL,
                estado ENUM('programado','jugado','suspendido') NOT NULL DEFAULT 'programado',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );");

        await conn.ExecuteAsync(@"
            CREATE TABLE IF NOT EXISTS goleadores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                partido_id INT NOT NULL,
                jugador_id INT NOT NULL,
                cantidad INT NOT NULL DEFAULT 1
            );");

        // Seed admin user
        var adminExists = await conn.ExecuteScalarAsync<int>(
            "SELECT COUNT(*) FROM usuarios WHERE email = 'admin@uniondelsur.com'");
        if (adminExists == 0)
        {
            var hash = BCrypt.Net.BCrypt.HashPassword("Admin123!");
            await conn.ExecuteAsync(
                "INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES ('Administrador', 'admin@uniondelsur.com', @hash, 'admin')",
                new { hash });
        }

        // Seed demo categories
        var categoriasCount = await conn.ExecuteScalarAsync<int>("SELECT COUNT(*) FROM categorias");
        if (categoriasCount == 0)
        {
            await conn.ExecuteAsync(@"
                INSERT INTO categorias (nombre, anio_nacimiento_desde, anio_nacimiento_hasta) VALUES
                ('Sub-8', 2018, 2019),
                ('Sub-10', 2016, 2017),
                ('Sub-12', 2014, 2015),
                ('Sub-14', 2012, 2013),
                ('Sub-16', 2010, 2011),
                ('Sub-18', 2008, 2009);");
        }

        // Seed demo players — INSERT IGNORE para no duplicar si ya existen por DNI
        var cats = (await conn.QueryAsync<(int id, int anioDesde)>(
            "SELECT id, anio_nacimiento_desde AS anioDesde FROM categorias WHERE activo = 1 ORDER BY anio_nacimiento_desde DESC")).ToList();

        var seedJugadores = new[]
        {
            // (nombre, apellido, dni, dia, mes, offset_año_dentro_categoria, idx_categoria)
            ("Tomás",      "González",   "45000001", 15, 3,  0, 0),
            ("Mateo",      "Rodríguez",  "45000002",  8, 7,  1, 0),
            ("Santiago",   "López",      "45000003", 22,11,  0, 0),
            ("Facundo",    "Martínez",   "45000004",  5, 2,  1, 0),
            ("Nicolás",    "García",     "45000005", 17, 9,  0, 0),

            ("Luca",       "Fernández",  "45000006", 10, 4,  0, 1),
            ("Benjamín",   "Pérez",      "45000007",  3, 8,  1, 1),
            ("Agustín",    "Díaz",       "45000008", 28, 1,  0, 1),
            ("Thiago",     "Sánchez",    "45000009", 14, 6,  1, 1),
            ("Franco",     "Romero",     "45000010", 20,10,  0, 1),

            ("Julián",     "Torres",     "45000011",  6, 5,  0, 2),
            ("Ezequiel",   "Acosta",     "45000012", 19,12,  1, 2),
            ("Rodrigo",    "Ríos",       "45000013",  1, 3,  0, 2),
            ("Leandro",    "Medina",     "45000014", 25, 7,  1, 2),
            ("Matías",     "Herrera",    "45000015", 11, 9,  0, 2),

            ("Alexis",     "Castro",     "45000016", 30, 4,  0, 3),
            ("Emanuel",    "Vargas",     "45000017",  7, 2,  1, 3),
            ("Iván",       "Flores",     "45000018", 16, 8,  0, 3),
            ("Gastón",     "Morales",    "45000019", 23, 6,  1, 3),
            ("Diego",      "Ortiz",      "45000020",  9,11,  0, 3),

            ("Alan",       "Núñez",      "45000021", 13, 1,  0, 4),
            ("Braian",     "Vega",       "45000022", 27, 5,  1, 4),
            ("Cristian",   "Ramos",      "45000023",  4, 9,  0, 4),
            ("Darío",      "Suárez",     "45000024", 18, 3,  1, 4),
            ("Esteban",    "Silva",      "45000025",  2, 7,  0, 4),

            ("Fabián",     "Rojas",      "45000026", 21, 2,  0, 5),
            ("Germán",     "Navarro",    "45000027",  8, 6,  1, 5),
            ("Hernán",     "Reyes",      "45000028", 14,10,  0, 5),
            ("Ignacio",    "Guzmán",     "45000029", 26, 4,  1, 5),
            ("Jonathan",   "Molina",     "45000030",  3, 8,  0, 5),
        };

        foreach (var (nombre, apellido, dni, dia, mes, offsetAnio, catIdx) in seedJugadores)
        {
            if (catIdx >= cats.Count) continue;
            var cat = cats[catIdx];
            var anio = cat.anioDesde + offsetAnio;
            var nacimiento = new DateTime(anio, mes, dia);
            await conn.ExecuteAsync(@"
                INSERT IGNORE INTO jugadores (nombre, apellido, dni, fecha_nacimiento, categoria_id)
                VALUES (@nombre, @apellido, @dni, @nacimiento, @catId)",
                new { nombre, apellido, dni, nacimiento, catId = cat.id });
        }
    }
}
