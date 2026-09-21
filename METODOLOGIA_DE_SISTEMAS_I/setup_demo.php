<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host    = getenv('MYSQLHOST')     ?: 'localhost';
$dbname  = getenv('MYSQLDATABASE') ?: 'educar_db';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_port = (int)(getenv('MYSQLPORT') ?: 3306);

echo "<h2>Variables de entorno</h2><pre>";
echo "MYSQLHOST="     . $host    . "\n";
echo "MYSQLDATABASE=" . $dbname  . "\n";
echo "MYSQLUSER="     . $db_user . "\n";
echo "MYSQLPASSWORD=" . ($db_pass ? '(tiene valor)' : '(vacío)') . "\n";
echo "MYSQLPORT="     . $db_port . "\n";
echo "</pre>";
flush();

echo "<p>Intentando conectar...</p>";
flush();

$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$ok = $conn->real_connect($host, $db_user, $db_pass, $dbname, $db_port);

if (!$ok || $conn->connect_error) {
    echo "<p style='color:red'><strong>Error de conexion:</strong> " . htmlspecialchars($conn->connect_error) . "</p>";
    exit;
}

echo "<p style='color:green'><strong>Conexion OK</strong></p>";
flush();

// ── TABLAS ──────────────────────────────────────────────────────────────────

$tablas = [
    "usuarios" => "CREATE TABLE IF NOT EXISTS usuarios (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        nombre        VARCHAR(100) NOT NULL,
        usuario       VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        rol           ENUM('alumno','docente','padre','admin','enfermeria') NOT NULL,
        activo        TINYINT(1) NOT NULL DEFAULT 1,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "solicitudes_inscripcion" => "CREATE TABLE IF NOT EXISTS solicitudes_inscripcion (
        id                       INT AUTO_INCREMENT PRIMARY KEY,
        nombre_alumno            VARCHAR(100) NOT NULL,
        apellido_alumno          VARCHAR(100) NOT NULL,
        fecha_nacimiento         DATE NOT NULL,
        nivel_educativo          ENUM('Inicial','Primario','Secundario') NOT NULL,
        nombre_tutor             VARCHAR(100) NOT NULL,
        telefono                 VARCHAR(30) NOT NULL,
        email                    VARCHAR(150) NOT NULL,
        comentarios              TEXT,
        nivel_anterior_aprobado  TINYINT(1) NULL DEFAULT NULL,
        estado                   ENUM('pendiente','contactado','admitido','rechazado') NOT NULL DEFAULT 'pendiente',
        created_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "noticias" => "CREATE TABLE IF NOT EXISTS noticias (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        titulo     VARCHAR(200) NOT NULL,
        resumen    VARCHAR(400),
        contenido  TEXT,
        categoria  ENUM('institucional','academica','deportiva','cultural','general') NOT NULL DEFAULT 'general',
        imagen_url VARCHAR(300),
        estado     ENUM('borrador','publicada','archivada') NOT NULL DEFAULT 'borrador',
        fecha_pub  DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "opiniones" => "CREATE TABLE IF NOT EXISTS opiniones (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        nombre     VARCHAR(100) NOT NULL DEFAULT 'Anónimo',
        texto      TEXT NOT NULL,
        mes        TINYINT UNSIGNED NOT NULL,
        anio       SMALLINT UNSIGNED NOT NULL,
        estado     ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "consultas" => "CREATE TABLE IF NOT EXISTS consultas (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        nombre     VARCHAR(100) NOT NULL,
        email      VARCHAR(150) NOT NULL,
        asunto     VARCHAR(200) NOT NULL,
        mensaje    TEXT NOT NULL,
        estado     ENUM('pendiente','leida','respondida','archivada') NOT NULL DEFAULT 'pendiente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "puestos_vacantes" => "CREATE TABLE IF NOT EXISTS puestos_vacantes (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        titulo      VARCHAR(150) NOT NULL,
        descripcion TEXT,
        tipo        VARCHAR(50),
        urgente     TINYINT(1) NOT NULL DEFAULT 0,
        activo      TINYINT(1) NOT NULL DEFAULT 1,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "postulaciones" => "CREATE TABLE IF NOT EXISTS postulaciones (
        id                      INT AUTO_INCREMENT PRIMARY KEY,
        puesto_id               INT NOT NULL,
        nombre                  VARCHAR(100) NOT NULL,
        apellido                VARCHAR(100) NOT NULL,
        dni                     VARCHAR(20) NOT NULL,
        email                   VARCHAR(150) NOT NULL,
        telefono                VARCHAR(30) NOT NULL,
        experiencia_anios       TINYINT UNSIGNED NOT NULL DEFAULT 0,
        experiencia_descripcion TEXT,
        estado                  ENUM('pendiente','revisado','seleccionado','rechazado') NOT NULL DEFAULT 'pendiente',
        created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (puesto_id) REFERENCES puestos_vacantes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "cursos" => "CREATE TABLE IF NOT EXISTS cursos (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        nombre          VARCHAR(20) NOT NULL,
        nivel_educativo ENUM('Inicial','Primario','Secundario') NOT NULL,
        UNIQUE (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "materias" => "CREATE TABLE IF NOT EXISTS materias (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        nombre     VARCHAR(100) NOT NULL,
        curso_id   INT NOT NULL,
        docente_id INT NULL,
        FOREIGN KEY (curso_id)   REFERENCES cursos(id)   ON DELETE CASCADE,
        FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "alumno_curso" => "CREATE TABLE IF NOT EXISTS alumno_curso (
        alumno_id INT NOT NULL PRIMARY KEY,
        curso_id  INT NOT NULL,
        FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (curso_id)  REFERENCES cursos(id)   ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "asistencias" => "CREATE TABLE IF NOT EXISTS asistencias (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        alumno_id  INT NOT NULL,
        materia_id INT NOT NULL,
        fecha      DATE NOT NULL,
        estado     ENUM('presente','ausente','tarde') NOT NULL,
        UNIQUE (alumno_id, materia_id, fecha),
        FOREIGN KEY (alumno_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
        FOREIGN KEY (materia_id) REFERENCES materias(id)  ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "calificaciones" => "CREATE TABLE IF NOT EXISTS calificaciones (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        alumno_id        INT NOT NULL,
        materia_id       INT NOT NULL,
        evaluacion       VARCHAR(150) NOT NULL,
        nota             TINYINT UNSIGNED NOT NULL,
        fecha_evaluacion DATE NOT NULL,
        UNIQUE (alumno_id, materia_id, evaluacion),
        FOREIGN KEY (alumno_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
        FOREIGN KEY (materia_id) REFERENCES materias(id)  ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "padre_alumno" => "CREATE TABLE IF NOT EXISTS padre_alumno (
        padre_id  INT NOT NULL,
        alumno_id INT NOT NULL,
        PRIMARY KEY (padre_id, alumno_id),
        FOREIGN KEY (padre_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "reservas_servicios" => "CREATE TABLE IF NOT EXISTS reservas_servicios (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        alumno_id  INT NOT NULL,
        servicio   ENUM('comedor','transporte') NOT NULL,
        mes        TINYINT UNSIGNED NOT NULL,
        anio       SMALLINT UNSIGNED NOT NULL,
        estado     ENUM('confirmada') NOT NULL DEFAULT 'confirmada',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_reserva (alumno_id, servicio, mes, anio),
        FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "atenciones_enfermeria" => "CREATE TABLE IF NOT EXISTS atenciones_enfermeria (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        alumno_id     INT NOT NULL,
        motivo        VARCHAR(200) NOT NULL,
        hora          TIME NOT NULL,
        observaciones TEXT,
        atendido_por  INT NOT NULL,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (alumno_id)    REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (atendido_por) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "notificaciones" => "CREATE TABLE IF NOT EXISTS notificaciones (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        padre_id   INT NULL,
        alumno_id  INT NOT NULL,
        tipo       VARCHAR(50) NOT NULL,
        mensaje    TEXT NOT NULL,
        leida      TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (padre_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "planificaciones" => "CREATE TABLE IF NOT EXISTS planificaciones (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        materia_id    INT NOT NULL,
        anio          SMALLINT UNSIGNED NOT NULL,
        contenidos    TEXT NOT NULL,
        observaciones TEXT,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_plan (materia_id, anio),
        FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

    "recuperatorios" => "CREATE TABLE IF NOT EXISTS recuperatorios (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        alumno_id  INT NOT NULL,
        materia_id INT NOT NULL,
        periodo    ENUM('1er Trimestre','2do Trimestre','3er Trimestre','Anual') NOT NULL DEFAULT 'Anual',
        fecha      DATE NULL,
        turno      ENUM('mañana','tarde') NULL,
        estado     ENUM('pendiente','aprobado','desaprobado') NOT NULL DEFAULT 'pendiente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_recup (alumno_id, materia_id, periodo),
        FOREIGN KEY (alumno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
];

echo '<h2>Creando tablas...</h2><ul>';
foreach ($tablas as $nombre => $sql) {
    if ($conn->query($sql)) {
        echo "<li style='color:green'>OK: <strong>$nombre</strong></li>";
    } else {
        echo "<li style='color:red'>Error en $nombre: " . htmlspecialchars($conn->error) . "</li>";
    }
}
echo '</ul>';
flush();

// Agregar columna nivel_anterior_aprobado si no existe (idempotente)
$conn->query("ALTER TABLE solicitudes_inscripcion ADD COLUMN IF NOT EXISTS nivel_anterior_aprobado TINYINT(1) NULL DEFAULT NULL AFTER comentarios");

// Ampliar ENUM de rol para incluir 'enfermeria' si no estaba
$conn->query("ALTER TABLE usuarios MODIFY COLUMN rol ENUM('alumno','docente','padre','admin','enfermeria') NOT NULL");

// ── USUARIOS DEMO ────────────────────────────────────────────────────────────

$usuarios_demo = [
    ['nombre' => 'Administrador',    'usuario' => 'admin',            'password' => 'admin',      'rol' => 'admin'],
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
    try {
        $stmt->execute();
        echo "<li style='color:green'>OK: <strong>{$u['usuario']}</strong> ({$u['rol']}) — password: <code>{$u['password']}</code></li>";
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            echo "<li style='color:orange'>Ya existe: <strong>{$u['usuario']}</strong> (omitido)</li>";
        } else {
            echo "<li style='color:red'>Error en {$u['usuario']}: " . htmlspecialchars($e->getMessage()) . "</li>";
        }
    }
}
echo '</ul>';
$stmt->close();
flush();

// ── SEED: curso, materias, alumnos matriculados ──────────────────────────────

echo '<h2>Seed de cursos y materias...</h2><ul>';

$conn->query("INSERT INTO cursos (nombre, nivel_educativo)
    SELECT '3°A', 'Secundario' FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM cursos WHERE nombre = '3°A')");
echo "<li>Curso 3°A: " . ($conn->affected_rows >= 0 ? 'OK' : htmlspecialchars($conn->error)) . "</li>";

$conn->query("INSERT INTO materias (nombre, curso_id, docente_id)
    SELECT 'Matemática', c.id, u.id
    FROM cursos c JOIN usuarios u ON u.usuario = 'maria.rodriguez'
    WHERE c.nombre = '3°A'
      AND NOT EXISTS (SELECT 1 FROM materias WHERE nombre = 'Matemática' AND curso_id = c.id)");
echo "<li>Materia Matemática: OK</li>";

$conn->query("INSERT INTO materias (nombre, curso_id, docente_id)
    SELECT 'Lengua y Literatura', c.id, u.id
    FROM cursos c JOIN usuarios u ON u.usuario = 'maria.rodriguez'
    WHERE c.nombre = '3°A'
      AND NOT EXISTS (SELECT 1 FROM materias WHERE nombre = 'Lengua y Literatura' AND curso_id = c.id)");
echo "<li>Materia Lengua y Literatura: OK</li>";

$conn->query("INSERT INTO alumno_curso (alumno_id, curso_id)
    SELECT u.id, c.id FROM usuarios u JOIN cursos c ON c.nombre = '3°A'
    WHERE u.usuario = 'ana.garcia'
      AND NOT EXISTS (SELECT 1 FROM alumno_curso WHERE alumno_id = u.id)");
echo "<li>Matrícula ana.garcia: OK</li>";

$conn->query("INSERT INTO alumno_curso (alumno_id, curso_id)
    SELECT u.id, c.id FROM usuarios u JOIN cursos c ON c.nombre = '3°A'
    WHERE u.usuario = 'carlos.lopez'
      AND NOT EXISTS (SELECT 1 FROM alumno_curso WHERE alumno_id = u.id)");
echo "<li>Matrícula carlos.lopez: OK</li>";

$conn->query("INSERT IGNORE INTO padre_alumno (padre_id, alumno_id)
    SELECT p.id, a.id FROM usuarios p, usuarios a
    WHERE p.usuario = 'laura.martinez' AND a.usuario = 'ana.garcia'");
echo "<li>Vínculo laura.martinez → ana.garcia: OK</li>";

$conn->query("INSERT IGNORE INTO padre_alumno (padre_id, alumno_id)
    SELECT p.id, a.id FROM usuarios p, usuarios a
    WHERE p.usuario = 'diego.fernandez' AND a.usuario = 'carlos.lopez'");
echo "<li>Vínculo diego.fernandez → carlos.lopez: OK</li>";

echo '</ul>';

$conn->close();
echo '<br><strong>Todo listo. <a href="/">Ir a la landing</a></strong>';
