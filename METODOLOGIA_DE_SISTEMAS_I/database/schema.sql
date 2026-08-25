CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    usuario       VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('alumno', 'docente', 'padre', 'admin') NOT NULL,
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS solicitudes_inscripcion (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre_alumno    VARCHAR(100) NOT NULL,
    apellido_alumno  VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE         NOT NULL,
    nivel_educativo  ENUM('Inicial','Primario','Secundario') NOT NULL,
    nombre_tutor     VARCHAR(100) NOT NULL,
    telefono         VARCHAR(30)  NOT NULL,
    email            VARCHAR(150) NOT NULL,
    comentarios      TEXT,
    estado           ENUM('pendiente','contactado','admitido','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS puestos_vacantes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    tipo        VARCHAR(50)  NOT NULL DEFAULT 'Tiempo completo',
    urgente     TINYINT(1)   NOT NULL DEFAULT 0,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS postulaciones (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    puesto_id               INT NOT NULL,
    nombre                  VARCHAR(100) NOT NULL,
    apellido                VARCHAR(100) NOT NULL,
    dni                     VARCHAR(20)  NOT NULL,
    email                   VARCHAR(150) NOT NULL,
    telefono                VARCHAR(30)  NOT NULL,
    experiencia_anios       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    experiencia_descripcion TEXT NOT NULL,
    estado                  ENUM('pendiente','revisado','seleccionado','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (puesto_id) REFERENCES puestos_vacantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS opiniones (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL DEFAULT 'Anónimo',
    texto      TEXT NOT NULL,
    mes        TINYINT UNSIGNED NOT NULL,
    anio       SMALLINT UNSIGNED NOT NULL,
    estado     ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS consultas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    asunto     VARCHAR(200) NOT NULL DEFAULT '',
    mensaje    TEXT NOT NULL,
    estado     ENUM('pendiente','leida','respondida','archivada') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS noticias (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(200)  NOT NULL,
    resumen     VARCHAR(400)  NOT NULL DEFAULT '',
    contenido   TEXT          NOT NULL,
    categoria   ENUM('institucional','academica','deportiva','cultural','general') NOT NULL DEFAULT 'general',
    imagen_url  VARCHAR(300)  NOT NULL DEFAULT '',
    estado      ENUM('borrador','publicada','archivada') NOT NULL DEFAULT 'borrador',
    fecha_pub   DATE,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO noticias (titulo, resumen, contenido, categoria, imagen_url, estado, fecha_pub)
SELECT 'Remodelación del Edificio Principal','Renovamos nuestras instalaciones para brindar un mejor ambiente de aprendizaje a toda la comunidad educativa.','<p>Con una inversión significativa, el Centro Educativo completó la remodelación integral del edificio principal. Las obras incluyeron pintura general, nuevos pisos, modernización de aulas y mejora del sistema eléctrico e iluminación LED en todos los espacios.</p><p>Este proyecto fue posible gracias al esfuerzo conjunto de la institución y la colaboración de la comunidad educativa.</p>','institucional','assets/edificio.avif','publicada','2026-05-10'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM noticias WHERE titulo='Remodelación del Edificio Principal');

INSERT INTO noticias (titulo, resumen, contenido, categoria, imagen_url, estado, fecha_pub)
SELECT 'Nuevo Laboratorio de Ciencias Naturales','Incorporamos equipamiento de última generación para fortalecer la enseñanza experimental en los niveles Primario y Secundario.','<p>El nuevo laboratorio cuenta con 20 puestos de trabajo individuales, microscopios digitales, equipos de química básica y un proyector interactivo para demostraciones en clase.</p><p>Los estudiantes podrán realizar experiencias prácticas en biología, química y física, potenciando su aprendizaje a través de la experimentación directa.</p>','academica','assets/laboratorio.avif','publicada','2026-04-22'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM noticias WHERE titulo='Nuevo Laboratorio de Ciencias Naturales');

INSERT INTO noticias (titulo, resumen, contenido, categoria, imagen_url, estado, fecha_pub)
SELECT 'Torneo Intercolegial de Fútbol 2026','Nuestro equipo clasificó a la final del torneo intercolegial regional tras una destacada participación en todas las etapas.','<p>Con gran entusiasmo y dedicación, el equipo de fútbol del Centro Educativo superó a 8 instituciones de la región y llegó a la gran final del Torneo Intercolegial 2026.</p><p>Los partidos se disputarán en nuestra cancha oficial los días 20 y 27 de junio. ¡Los invitamos a acompañar y alentar a nuestros jugadores!</p>','deportiva','assets/campofutbol.avif','publicada','2026-06-01'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM noticias WHERE titulo='Torneo Intercolegial de Fútbol 2026');

INSERT INTO noticias (titulo, resumen, contenido, categoria, imagen_url, estado, fecha_pub)
SELECT 'Inauguración del Natatorio Institucional','El Centro Educativo suma un espacio acuático moderno para la práctica deportiva y el bienestar de alumnos y familias.','<p>Con gran alegría anunciamos la inauguración del natatorio institucional, un proyecto largamente esperado por toda la comunidad. La pileta cuenta con 4 calles de 25 metros, vestuarios, gradas para espectadores y sistema de filtrado de última generación.</p><p>Las clases de natación estarán disponibles para todos los niveles a partir del próximo ciclo lectivo. Habrá turnos mañana, tarde y noche.</p>','cultural','assets/piscina.avif','publicada','2026-03-15'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM noticias WHERE titulo='Inauguración del Natatorio Institucional');

INSERT INTO noticias (titulo, resumen, contenido, categoria, imagen_url, estado, fecha_pub)
SELECT 'Ampliación del Área de Educación Física','Renovamos y ampliamos el gimnasio cubierto con nuevos equipos de musculación y espacios multiuso para actividades deportivas.','<p>El gimnasio cubierto fue completamente renovado e incorpora nuevos equipos de musculación, una sala de usos múltiples para yoga y artes marciales, y nuevo parquet en la cancha principal.</p><p>Estas mejoras benefician directamente a los más de 500 alumnos que realizan actividad física en la institución, reforzando nuestro compromiso con la salud y el deporte.</p>','general','assets/gimnasio.avif','publicada','2026-02-20'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM noticias WHERE titulo='Ampliación del Área de Educación Física');
