<?php

use PHPUnit\Framework\TestCase;

/**
 * RFG04 — Admisión de alumnos con validación de requisitos académicos (RN03).
 *
 * Prueba el endpoint inscripciones/admitir.php como lo usa el portal del
 * administrador, por HTTP y con sesión real. Cada test crea su propia
 * solicitud en la base de prueba, así no depende del seed ni de otro test.
 *
 * Reglas verificadas:
 *   - Solo el rol admin puede admitir.
 *   - Nivel Inicial: edad mínima de 3 años (se prueban los dos lados del límite).
 *   - Otros niveles: hay que confirmar que aprobó el nivel anterior.
 *   - Si se rechaza, no se crea el usuario ni cambia el estado de la solicitud.
 */
class AdmisionTest extends TestCase
{
    private const DOCENTES = 'Docentes / Personal / Autoridades';

    private static HttpClient $admin;

    public static function setUpBeforeClass(): void
    {
        self::$admin = new HttpClient();
        $login = self::$admin->login('admin', 'admin', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del admin: ' . json_encode($login));
    }

    // ── Acceso ────────────────────────────────────────────────────────────

    public function testSinSesionDeAdminNoSePuedeAdmitir(): void
    {
        $id = $this->crearSolicitud($this->fechaHace('-4 years'), 'Inicial');

        $anonimo = new HttpClient();
        $r = $anonimo->post('/inscripciones/admitir.php', $this->datosAdmision($id));

        $this->assertSame(403, $r['status']);
        $this->assertSame('pendiente', $this->estadoDe($id));
    }

    // ── Nivel Inicial: edad mínima (criterios 1, 2 y 3 de la HU) ─────────

    public function testRechazaInicialConDosAnios(): void
    {
        $id = $this->crearSolicitud($this->fechaHace('-2 years -1 month'), 'Inicial');
        $datos = $this->datosAdmision($id);

        $r = self::$admin->post('/inscripciones/admitir.php', $datos);

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('edad mínima', $r['json']['message']);
        $this->assertSame('pendiente', $this->estadoDe($id), 'La solicitud no debe cambiar de estado');
        $this->assertSame(0, $this->usuariosLlamados($datos['usuario']), 'No se debe crear el usuario');
    }

    public function testRechazaInicialUnDiaAntesDeCumplirTres(): void
    {
        $id = $this->crearSolicitud($this->fechaHace('-3 years +1 day'), 'Inicial');

        $r = self::$admin->post('/inscripciones/admitir.php', $this->datosAdmision($id));

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('edad mínima', $r['json']['message']);
    }

    public function testAdmiteInicialElDiaQueCumpleTres(): void
    {
        $id = $this->crearSolicitud($this->fechaHace('-3 years'), 'Inicial');
        $datos = $this->datosAdmision($id);

        $r = self::$admin->post('/inscripciones/admitir.php', $datos);

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertSame('admitido', $this->estadoDe($id));
        $this->assertSame(1, $this->usuariosLlamados($datos['usuario']), 'Se debe crear el usuario alumno');
    }

    // ── Otros niveles: nivel anterior aprobado (criterios 4 y 5) ─────────

    public function testRechazaPrimarioSinConfirmarNivelAnterior(): void
    {
        $id = $this->crearSolicitud($this->fechaHace('-7 years'), 'Primario');

        $r = self::$admin->post('/inscripciones/admitir.php', $this->datosAdmision($id, '0'));

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('nivel anterior', $r['json']['message']);
        $this->assertSame('pendiente', $this->estadoDe($id));
    }

    public function testAdmitePrimarioConNivelAnteriorConfirmado(): void
    {
        $id = $this->crearSolicitud($this->fechaHace('-7 years'), 'Primario');

        $r = self::$admin->post('/inscripciones/admitir.php', $this->datosAdmision($id, '1'));

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertSame('admitido', $this->estadoDe($id));
        $this->assertSame(1, (int) Db::valor(
            'SELECT nivel_anterior_aprobado FROM solicitudes_inscripcion WHERE id = ?',
            'i',
            $id
        ));
    }

    // ── Auxiliares ───────────────────────────────────────────────────────

    /**
     * Fecha de nacimiento relativa a hoy, para que los tests no venzan con el tiempo.
     * "Hoy" se toma en la zona horaria del sistema (Argentina), la misma con la que
     * admitir.php calcula la edad: si el test usara otra zona, cerca de la medianoche
     * "hoy" sería otro día y el caso límite (cumple 3 hoy) daría mal.
     */
    private function fechaHace(string $intervalo): string
    {
        $hoy = new DateTime('today', new DateTimeZone('America/Argentina/Buenos_Aires'));
        return $hoy->modify($intervalo)->format('Y-m-d');
    }

    /** Condición previa: una solicitud pendiente. Devuelve su id. */
    private function crearSolicitud(string $fechaNacimiento, string $nivel): int
    {
        $conn = Db::conn();
        $stmt = $conn->prepare(
            "INSERT INTO solicitudes_inscripcion
                (nombre_alumno, apellido_alumno, fecha_nacimiento, nivel_educativo,
                 nombre_tutor, telefono, email, comentarios)
             VALUES ('Alumno', 'PHPUnit', ?, ?, 'Tutor PHPUnit', '3624000000', 'phpunit@test.com', 'Creada por PHPUnit')"
        );
        $stmt->bind_param('ss', $fechaNacimiento, $nivel);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
        return $id;
    }

    /** Lo mismo que envía el modal "Admitir", con un usuario único. */
    private function datosAdmision(int $id, string $nivelAnteriorConfirmado = '0'): array
    {
        return [
            'id'                        => $id,
            'usuario'                   => 'phpunit.' . uniqid(),
            'password'                  => 'clave123',
            'nombre'                    => 'Alumno PHPUnit',
            'nivel_anterior_confirmado' => $nivelAnteriorConfirmado,
            'curso_id'                  => 0,
        ];
    }

    private function estadoDe(int $id): string
    {
        return (string) Db::valor('SELECT estado FROM solicitudes_inscripcion WHERE id = ?', 'i', $id);
    }

    private function usuariosLlamados(string $usuario): int
    {
        return (int) Db::valor('SELECT COUNT(*) FROM usuarios WHERE usuario = ?', 's', $usuario);
    }
}
