<?php

use PHPUnit\Framework\TestCase;

/**
 * RFG14 (RN13) — Sanciones disciplinarias.
 *
 * Admin y docente registran sanciones; se notifica a cada padre vinculado y,
 * si el alumno no tiene, queda marcada "sin contacto".
 *
 * Datos del seed: maria.rodriguez dicta en 3°A y 2°B; carlos.lopez es de 3°A
 * (padre diego.fernandez); luciana.campos es de 1°A y no tiene padre vinculado.
 * Las sanciones de los tests llevan "[PHPUnit]" en la descripción y se borran al empezar.
 */
class SancionesTest extends TestCase
{
    private const DOCENTES = 'Docentes / Personal / Autoridades';
    private const MARCA    = '[PHPUnit]';

    private static HttpClient $admin;
    private static HttpClient $docente;
    private static int $carlosId;
    private static int $lucianaId;

    public static function setUpBeforeClass(): void
    {
        Db::exec("DELETE FROM sanciones WHERE descripcion LIKE '" . self::MARCA . "%'");
        Db::exec("DELETE FROM notificaciones WHERE tipo = 'sancion' AND mensaje LIKE '%" . self::MARCA . "%'");
        self::$carlosId  = self::idDe('carlos.lopez');
        self::$lucianaId = self::idDe('luciana.campos');

        self::$admin = new HttpClient();
        $login = self::$admin->login('admin', 'admin', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del admin: ' . json_encode($login));

        self::$docente = new HttpClient();
        $login = self::$docente->login('maria.rodriguez', 'docente123', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del docente: ' . json_encode($login));
    }

    // ── Registro ─────────────────────────────────────────────────────────

    public function testElDocenteSancionaASuAlumnoYSeNotificaAlPadre(): void
    {
        $r = self::$docente->post('/gestion/sanciones_guardar.php', $this->datos(self::$carlosId, 'apercibimiento'));

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertFalse($r['json']['sin_contacto']);
        $this->assertSame(1, (int) Db::valor('SELECT COUNT(*) FROM sanciones WHERE id = ?', 'i', (int) $r['json']['id']));
        $this->assertSame(1, $this->notificacionesDeDiego('Se registró una sanción'), 'No se notificó al padre');
    }

    public function testUnaSuspensionGuardaLosDias(): void
    {
        $r = self::$docente->post('/gestion/sanciones_guardar.php', $this->datos(self::$carlosId, 'suspension', ['dias_suspension' => '3']));

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertSame(3, (int) Db::valor('SELECT dias_suspension FROM sanciones WHERE id = ?', 'i', (int) $r['json']['id']));
    }

    public function testElAdminSancionaAUnAlumnoSinPadreYQuedaSinContacto(): void
    {
        $r = self::$admin->post('/gestion/sanciones_guardar.php', $this->datos(self::$lucianaId, 'otra'));

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertTrue($r['json']['sin_contacto']);
        $this->assertStringContainsString('avisar a la familia', $r['json']['message']);
        $this->assertSame(1, (int) Db::valor('SELECT sin_contacto FROM sanciones WHERE id = ?', 'i', (int) $r['json']['id']));
    }

    // ── Validaciones ─────────────────────────────────────────────────────

    public function testRechazaFechaFutura(): void
    {
        $r = self::$docente->post('/gestion/sanciones_guardar.php', $this->datos(self::$carlosId, 'otra', ['fecha' => $this->dia('+1 day')]));
        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('futura', $r['json']['message']);
    }

    public function testRechazaFechaDeHaceMasDeTreintaDias(): void
    {
        $r = self::$docente->post('/gestion/sanciones_guardar.php', $this->datos(self::$carlosId, 'otra', ['fecha' => $this->dia('-31 days')]));
        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('últimos 30 días', $r['json']['message']);
    }

    public function testRechazaDescripcionCorta(): void
    {
        $r = self::$docente->post('/gestion/sanciones_guardar.php', $this->datos(self::$carlosId, 'otra', ['descripcion' => 'Corta']));
        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('entre 10 y 1000', $r['json']['message']);
    }

    public function testRechazaSuspensionSinDiasValidos(): void
    {
        foreach (['', '0', '31'] as $dias) {
            $r = self::$docente->post('/gestion/sanciones_guardar.php', $this->datos(self::$carlosId, 'suspension', ['dias_suspension' => $dias]));
            $this->assertFalse($r['json']['success'], "días '$dias': " . $r['body']);
            $this->assertStringContainsString('días de suspensión', $r['json']['message']);
        }
    }

    // ── Acceso (RN14) ────────────────────────────────────────────────────

    public function testElDocenteNoPuedeSancionarAUnAlumnoAjeno(): void
    {
        $r = self::$docente->post('/gestion/sanciones_guardar.php', $this->datos(self::$lucianaId, 'otra'));
        $this->assertSame(403, $r['status']);
    }

    public function testAlumnoYPadreNoPuedenRegistrar(): void
    {
        foreach ([['ana.garcia', 'alumno123', 'Alumnos'], ['diego.fernandez', 'padre456', 'Padres / Tutores']] as [$u, $p, $rol]) {
            $cliente = new HttpClient();
            $cliente->login($u, $p, $rol);
            $r = $cliente->post('/gestion/sanciones_guardar.php', $this->datos(self::$carlosId, 'otra'));
            $this->assertSame(403, $r['status'], "$u pudo registrar una sanción");
        }
    }

    public function testElDocenteSoloListaSancionesDeSusCursos(): void
    {
        $r = self::$docente->get('/gestion/sanciones_listar.php');

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertNotContains(self::$lucianaId, array_map('intval', array_column($r['json']['sanciones'], 'alumno_id')));
        $this->assertContains(self::$carlosId, array_map('intval', array_column($r['json']['sanciones'], 'alumno_id')));
    }

    // ── Auxiliares ───────────────────────────────────────────────────────

    private function datos(int $alumnoId, string $tipo, array $extra = []): array
    {
        return $extra + [
            'alumno_id'       => $alumnoId,
            'tipo'            => $tipo,
            'fecha'           => $this->dia('today'),
            'descripcion'     => self::MARCA . ' Falta de respeto a un compañero durante el recreo.',
            'dias_suspension' => '',
        ];
    }

    /** Fecha relativa a hoy, en la zona horaria del sistema. */
    private function dia(string $relativo): string
    {
        return (new DateTime('today', new DateTimeZone('America/Argentina/Buenos_Aires')))->modify($relativo)->format('Y-m-d');
    }

    private function notificacionesDeDiego(string $inicio): int
    {
        return (int) Db::valor(
            "SELECT COUNT(*) FROM notificaciones n JOIN usuarios p ON p.id = n.padre_id
             WHERE p.usuario = 'diego.fernandez' AND n.tipo = 'sancion' AND n.mensaje LIKE ? AND n.mensaje LIKE ?",
            'ss',
            $inicio . '%',
            '%' . self::MARCA . '%'
        );
    }

    private static function idDe(string $usuario): int
    {
        return (int) Db::valor('SELECT id FROM usuarios WHERE usuario = ?', 's', $usuario);
    }
}
