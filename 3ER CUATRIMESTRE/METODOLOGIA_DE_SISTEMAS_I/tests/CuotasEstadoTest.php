<?php

use PHPUnit\Framework\TestCase;

/**
 * RFG19 (desafío) — Estado de cada cuota para el padre, el docente y el admin.
 *
 * El estado visual sale de estado + vencimiento: pagada / impaga (vencida) /
 * próxima a vencer (vence en DIAS_AVISO_VENCIMIENTO días o menos) / pendiente.
 * El padre ve las de sus hijos con importes y puede bajar el recibo de las
 * pagadas; el docente ve solo el estado, sin importes, de los alumnos de sus
 * cursos (decisión de diseño, RN14); el admin ve y registra.
 *
 * Datos del seed: ana.garcia (3°A) es hija de laura.martinez; carlos.lopez (3°A)
 * es de otra familia; luciana.campos es de 1°A, donde maria.rodriguez no dicta.
 * El test borra las cuotas de ana.garcia y le crea las suyas.
 */
class CuotasEstadoTest extends TestCase
{
    private const DOCENTES = 'Docentes / Personal / Autoridades';
    private const PADRES   = 'Padres / Tutores';
    private const IMPORTE  = 10000.0;

    private static HttpClient $padre;
    private static int $anaId;
    /** @var array<string,int> estado esperado => id de cuota */
    private static array $ids = [];

    public static function setUpBeforeClass(): void
    {
        self::$anaId = self::idDe('ana.garcia');
        Db::exec('DELETE FROM cuotas WHERE alumno_id = ' . self::$anaId);

        // Meses distintos (clave única alumno/concepto/mes/año); el vencimiento es relativo a hoy.
        self::$ids['impaga']           = self::crearCuota(1, '-5 days', 'pendiente');
        self::$ids['proxima_a_vencer'] = self::crearCuota(2, '+3 days', 'pendiente');
        self::$ids['pendiente']        = self::crearCuota(3, '+20 days', 'pendiente');
        self::$ids['pagada']           = self::crearCuota(4, '-30 days', 'pagada');

        self::$padre = new HttpClient();
        $login = self::$padre->login('laura.martinez', 'padre123', self::PADRES);
        self::assertTrue($login['success'] ?? false, 'Login del padre: ' . json_encode($login));
    }

    // ── Padre ────────────────────────────────────────────────────────────

    public function testElPadreVeLasCuotasDeSuHijoConSuEstado(): void
    {
        $r = self::$padre->get('/gestion/cuotas_estado.php?alumno_id=' . self::$anaId);

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertCount(4, $r['json']['cuotas']);
        $porId = array_column($r['json']['cuotas'], 'estado_visual', 'id');
        foreach (self::$ids as $esperado => $id) {
            $this->assertSame($esperado, $porId[$id], "Cuota $id");
        }
    }

    public function testElResumenSumaLaDeudaConElRecargoDeLaVencida(): void
    {
        $r = self::$padre->get('/gestion/cuotas_estado.php?alumno_id=' . self::$anaId);
        $resumen = $r['json']['resumen'];

        // Impaga con 10 % de recargo + próxima + pendiente; la pagada no suma.
        $this->assertEqualsWithDelta(self::IMPORTE * 1.10 + self::IMPORTE * 2, $resumen['total_adeudado'], 0.01);
        $this->assertEqualsWithDelta(self::IMPORTE * 1.10, $resumen['total_vencido'], 0.01);
        $this->assertSame(1, $resumen['impagas']);
        $this->assertSame(1, $resumen['proximas_a_vencer']);
        $this->assertSame($this->dia('+3 days'), $resumen['proximo_vencimiento']);
    }

    public function testElPadreNoVeLasCuotasDeOtroAlumno(): void
    {
        $r = self::$padre->get('/gestion/cuotas_estado.php?alumno_id=' . self::idDe('carlos.lopez'));
        $this->assertSame(403, $r['status']);
    }

    public function testElPadreBajaElReciboDeUnaCuotaPagadaDeSuHijo(): void
    {
        $r = self::$padre->get('/gestion/recibo_pdf.php?cuota_id=' . self::$ids['pagada']);
        $this->assertSame(200, $r['status']);
        $this->assertStringStartsWith('%PDF', $r['body']);
    }

    public function testElPadreNoBajaElReciboDeUnaCuotaImpaga(): void
    {
        $r = self::$padre->get('/gestion/recibo_pdf.php?cuota_id=' . self::$ids['impaga']);
        $this->assertSame(409, $r['status']);
    }

    public function testElPadreNoBajaElReciboDeOtroAlumno(): void
    {
        $ajena = (int) Db::valor(
            "SELECT id FROM cuotas WHERE alumno_id <> ? AND estado = 'pagada' LIMIT 1",
            'i',
            self::$anaId
        ) ?: self::crearCuota(5, '-30 days', 'pagada', self::idDe('carlos.lopez'));

        $r = self::$padre->get('/gestion/recibo_pdf.php?cuota_id=' . $ajena);
        $this->assertSame(403, $r['status']);
    }

    // ── Docente (opción a: solo estado, sin importes, sus cursos) ─────────

    public function testElDocenteVeElEstadoSinImportesSoloDeSusCursos(): void
    {
        $docente = new HttpClient();
        $docente->login('maria.rodriguez', 'docente123', self::DOCENTES);

        $r = $docente->get('/gestion/cuotas_estado.php?alumno_id=' . self::$anaId);

        $this->assertTrue($r['json']['success'], $r['body']);
        $alumnos = array_column($r['json']['alumnos'], null, 'alumno_id');
        $this->assertArrayHasKey(self::$anaId, $alumnos);
        $this->assertArrayNotHasKey(self::idDe('luciana.campos'), $alumnos, 'Ve alumnos de cursos ajenos');
        $this->assertSame(1, $alumnos[self::$anaId]['impagas']);
        foreach (['importe', 'recargo', 'total', 'total_adeudado'] as $clave) {
            $this->assertStringNotContainsString("\"$clave\"", $r['body'], "El docente recibe $clave");
        }
    }

    public function testElDocenteNoPuedeRegistrarPagosNiBajarRecibos(): void
    {
        $docente = new HttpClient();
        $docente->login('maria.rodriguez', 'docente123', self::DOCENTES);

        $r = $docente->post('/gestion/cuotas_pagar.php', ['cuota_id' => self::$ids['impaga'], 'metodo_pago' => 'efectivo']);
        $this->assertSame(403, $r['status']);
        $r = $docente->get('/gestion/recibo_pdf.php?cuota_id=' . self::$ids['pagada']);
        $this->assertSame(403, $r['status']);
    }

    // ── Admin y otros roles ──────────────────────────────────────────────

    public function testElAdminVeElEstadoVisualEnElDetalle(): void
    {
        $admin = new HttpClient();
        $admin->login('admin', 'admin', self::DOCENTES);

        $r = $admin->get('/gestion/cuotas_listar.php?alumno_id=' . self::$anaId);

        $this->assertTrue($r['json']['success'], $r['body']);
        $porId = array_column($r['json']['cuotas'], 'estado_visual', 'id');
        $this->assertSame('impaga', $porId[self::$ids['impaga']]);
        $this->assertSame('proxima_a_vencer', $porId[self::$ids['proxima_a_vencer']]);
    }

    public function testUnAlumnoRecibe403(): void
    {
        $alumno = new HttpClient();
        $alumno->login('ana.garcia', 'alumno123', 'Alumnos');
        $r = $alumno->get('/gestion/cuotas_estado.php?alumno_id=' . self::$anaId);
        $this->assertSame(403, $r['status']);
    }

    // ── Auxiliares ───────────────────────────────────────────────────────

    private static function crearCuota(int $mes, string $vence, string $estado, ?int $alumnoId = null): int
    {
        $alumnoId  = $alumnoId ?? self::$anaId;
        $anio      = 2000 + $mes;   // período ficticio: solo importa que no choque con otras cuotas
        $vencim    = (new DateTime('today', new DateTimeZone('America/Argentina/Buenos_Aires')))->modify($vence)->format('Y-m-d');
        $fechaPago = $estado === 'pagada' ? $vencim : null;
        $metodo    = $estado === 'pagada' ? 'efectivo' : null;
        $importe   = self::IMPORTE;

        $conn = Db::conn();
        $stmt = $conn->prepare(
            "INSERT INTO cuotas (alumno_id, concepto, mes, anio, importe, recargo, fecha_vencimiento, estado, fecha_pago, metodo_pago)
             VALUES (?, 'cuota', ?, ?, ?, 0, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iiidssss', $alumnoId, $mes, $anio, $importe, $vencim, $estado, $fechaPago, $metodo);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
        return $id;
    }

    private function dia(string $relativo): string
    {
        return (new DateTime('today', new DateTimeZone('America/Argentina/Buenos_Aires')))->modify($relativo)->format('Y-m-d');
    }

    private static function idDe(string $usuario): int
    {
        return (int) Db::valor('SELECT id FROM usuarios WHERE usuario = ?', 's', $usuario);
    }
}
