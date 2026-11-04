<?php

use PHPUnit\Framework\TestCase;

/**
 * RFG16 — Legajo completo del alumno.
 *
 * El legajo junta datos personales (tabla alumnos_datos), académicos y, solo
 * para el admin, la situación económica. Los datos personales se cargan desde
 * el ABM de alumnos o se copian de la solicitud al admitir.
 *
 * Datos del seed: carlos.lopez es de 3°A (maria.rodriguez dicta ahí) y tiene
 * DNI cargado por seed_legajo.php; luciana.campos es de 1°A, donde
 * maria.rodriguez no dicta ninguna materia.
 */
class LegajoTest extends TestCase
{
    private const DOCENTES = 'Docentes / Personal / Autoridades';

    private static HttpClient $admin;
    private static HttpClient $docente;
    private static int $carlosId;

    public static function setUpBeforeClass(): void
    {
        self::$carlosId = self::idDe('carlos.lopez');

        self::$admin = new HttpClient();
        $login = self::$admin->login('admin', 'admin', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del admin: ' . json_encode($login));

        self::$docente = new HttpClient();
        $login = self::$docente->login('maria.rodriguez', 'docente123', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del docente: ' . json_encode($login));
    }

    // ── Lectura del legajo ───────────────────────────────────────────────

    public function testElAdminVeDatosPersonalesYSituacionEconomica(): void
    {
        $r = self::$admin->get('/gestion/legajo_listar.php?alumno_id=' . self::$carlosId);

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertTrue($r['json']['es_admin']);
        $this->assertSame('carlos.lopez', $r['json']['alumno']['usuario']);

        $dni = Db::valor('SELECT dni FROM alumnos_datos WHERE alumno_id = ?', 'i', self::$carlosId);
        $this->assertNotNull($dni, 'El seed no cargó el DNI de carlos.lopez (seed_legajo.php)');
        $this->assertSame($dni, $r['json']['datos']['dni']);

        $this->assertIsArray($r['json']['cuotas'], 'El admin tiene que recibir la situación económica');
        $this->assertNotNull($r['json']['deuda_total']);
    }

    public function testElDocenteNoRecibeLaSituacionEconomica(): void
    {
        $r = self::$docente->get('/gestion/legajo_listar.php?alumno_id=' . self::$carlosId);

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertFalse($r['json']['es_admin']);
        $this->assertArrayHasKey('cuotas', $r['json']);
        $this->assertNull($r['json']['cuotas']);
        $this->assertNull($r['json']['deuda_total']);
    }

    public function testElDocenteNoVeAlumnosDeCursosAjenos(): void
    {
        $r = self::$docente->get('/gestion/legajo_listar.php?alumno_id=' . self::idDe('luciana.campos'));

        $this->assertSame(403, $r['status']);
        $this->assertFalse($r['json']['success']);
    }

    // ── ABM de alumnos ───────────────────────────────────────────────────

    public function testElAbmRechazaUnDniRepetido(): void
    {
        $dni   = (string) Db::valor('SELECT dni FROM alumnos_datos WHERE alumno_id = ?', 'i', self::$carlosId);
        $datos = $this->nuevoAlumno(['dni' => $dni]);

        $r = self::$admin->post('/gestion/alumno_guardar.php', $datos);

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('DNI', $r['json']['message']);
        $this->assertSame(0, $this->usuariosLlamados($datos['usuario']), 'Con el DNI repetido no se debe crear el usuario');
    }

    public function testEditarOtroAlumnoConElDniDeCarlosNoModificaANinguno(): void
    {
        $anaId       = self::idDe('ana.garcia');
        $anaAntes    = $this->snapshot($anaId);
        $carlosAntes = $this->snapshot(self::$carlosId);

        $datos = ['dni' => $carlosAntes['dni']] + $this->formularioEdicion($anaId);
        $r = self::$admin->post('/gestion/alumno_guardar.php', $datos);

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('DNI', $r['json']['message']);
        $this->assertSame($anaAntes, $this->snapshot($anaId), 'Se modificó el alumno editado');
        $this->assertSame($carlosAntes, $this->snapshot(self::$carlosId), 'Se modificó el alumno dueño del DNI');
    }

    public function testEditarACarlosSinCambiosFunciona(): void
    {
        $antes = $this->snapshot(self::$carlosId);

        $r = self::$admin->post('/gestion/alumno_guardar.php', $this->formularioEdicion(self::$carlosId));

        $this->assertTrue($r['json']['success'], $r['body']);
        $despues = $this->snapshot(self::$carlosId);
        unset($antes['actualizado_en'], $despues['actualizado_en']);
        $this->assertSame($antes, $despues);
    }

    public function testElAbmRechazaUnEmailInvalido(): void
    {
        $datos = $this->nuevoAlumno(['email' => 'no-es-un-email']);

        $r = self::$admin->post('/gestion/alumno_guardar.php', $datos);

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('email', $r['json']['message']);
        $this->assertSame(0, $this->usuariosLlamados($datos['usuario']));
    }

    // ── Admisión → legajo ────────────────────────────────────────────────

    public function testAdmitirCopiaLosDatosDeLaSolicitudAlLegajo(): void
    {
        $hoy        = new DateTime('today', new DateTimeZone('America/Argentina/Buenos_Aires'));
        $nacimiento = $hoy->modify('-8 years')->format('Y-m-d');
        $telefono   = '3624' . random_int(100000, 999999);
        $email      = 'legajo.' . uniqid() . '@test.com';
        $tutor      = 'Tutora Legajo PHPUnit';

        $conn = Db::conn();
        $stmt = $conn->prepare(
            "INSERT INTO solicitudes_inscripcion
                (nombre_alumno, apellido_alumno, fecha_nacimiento, nivel_educativo,
                 nombre_tutor, telefono, email, comentarios)
             VALUES ('Alumno', 'Legajo', ?, 'Primario', ?, ?, ?, 'Creada por PHPUnit')"
        );
        $stmt->bind_param('ssss', $nacimiento, $tutor, $telefono, $email);
        $stmt->execute();
        $solicitudId = $conn->insert_id;
        $stmt->close();

        $usuario = 'phpunit.' . uniqid();
        $r = self::$admin->post('/inscripciones/admitir.php', [
            'id'                        => $solicitudId,
            'usuario'                   => $usuario,
            'password'                  => 'clave123',
            'nombre'                    => 'Alumno Legajo',
            'nivel_anterior_confirmado' => '1',
            'curso_id'                  => 0,
        ]);
        $this->assertTrue($r['json']['success'], $r['body']);

        $alumnoId = self::idDe($usuario);
        $stmt = $conn->prepare(
            'SELECT fecha_nacimiento, telefono, email, tutor_nombre, solicitud_id FROM alumnos_datos WHERE alumno_id = ?'
        );
        $stmt->bind_param('i', $alumnoId);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertNotNull($fila, 'Admitir no creó la fila en alumnos_datos');
        $this->assertSame($nacimiento, $fila['fecha_nacimiento']);
        $this->assertSame($telefono, $fila['telefono']);
        $this->assertSame($email, $fila['email']);
        $this->assertSame($tutor, $fila['tutor_nombre']);
        $this->assertSame($solicitudId, (int) $fila['solicitud_id']);
    }

    // ── Auxiliares ───────────────────────────────────────────────────────

    /** Alta como la hace el modal "Nuevo alumno", con usuario único. */
    private function nuevoAlumno(array $extra): array
    {
        return $extra + [
            'id'       => 0,
            'nombre'   => 'Alumno ABM PHPUnit',
            'usuario'  => 'phpunit.abm.' . uniqid(),
            'password' => 'clave123',
            'curso_id' => 0,
        ];
    }

    /** Usuario, curso y datos personales de un alumno, para comparar antes y después. */
    private function snapshot(int $alumnoId): array
    {
        $stmt = Db::conn()->prepare(
            "SELECT u.nombre, u.usuario, ac.curso_id, d.dni, d.fecha_nacimiento, d.domicilio, d.telefono,
                    d.email, d.tutor_nombre, d.contacto_emergencia, d.obra_social, d.observaciones_salud,
                    d.actualizado_en
             FROM usuarios u
             LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id
             LEFT JOIN alumnos_datos d ON d.alumno_id = u.id
             WHERE u.id = ?"
        );
        $stmt->bind_param('i', $alumnoId);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila;
    }

    /** Lo que envía el modal "Editar datos" con los valores actuales del alumno (sin cambiar la contraseña). */
    private function formularioEdicion(int $alumnoId): array
    {
        $s = $this->snapshot($alumnoId);
        unset($s['actualizado_en']);
        $s['curso_id'] = (int) $s['curso_id'];
        return ['id' => $alumnoId, 'password' => ''] + array_map(fn($v) => $v ?? '', $s);
    }

    private function usuariosLlamados(string $usuario): int
    {
        return (int) Db::valor('SELECT COUNT(*) FROM usuarios WHERE usuario = ?', 's', $usuario);
    }

    private static function idDe(string $usuario): int
    {
        return (int) Db::valor('SELECT id FROM usuarios WHERE usuario = ?', 's', $usuario);
    }
}
