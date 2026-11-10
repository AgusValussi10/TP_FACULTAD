<?php

use PHPUnit\Framework\TestCase;

/**
 * Parte 3 — App: cuotas, facturas, historial de deuda por concepto y factura en PDF (RFGm03/RFGm04).
 *
 * Datos de seed_parte3.php: ana.garcia y matias.vega son hijos de laura.martinez y tienen
 * facturas de mes-2, mes-1 y el mes actual; carlos.lopez es de otra familia (diego.fernandez).
 * Los valores esperados se calculan con SQL directo sobre la base para no depender de importes fijos.
 */
class AppCuentaTest extends TestCase
{
    private const API = '/app/api/';

    private static HttpClient $padre;
    private static HttpClient $alumno;
    private static int $ana;
    private static int $carlos;

    public static function setUpBeforeClass(): void
    {
        self::$ana    = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'ana.garcia'");
        self::$carlos = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'carlos.lopez'");
        self::$padre  = new HttpClient();
        self::$padre->post(self::API . 'login.php', ['usuario' => 'laura.martinez', 'password' => 'padre123']);
        self::$alumno = new HttpClient();
        self::$alumno->post(self::API . 'login.php', ['usuario' => 'ana.garcia', 'password' => 'alumno123']);
    }

    // ── Cuotas (RFGm03) ─────────────────────────────────────────────────

    public function testElPadreVeLasCuotasDeSuHijoConEstado(): void
    {
        $r = self::$padre->get(self::API . 'cuotas.php?alumno_id=' . self::$ana);
        $this->assertSame(200, $r['status'], $r['body']);
        $cuotas = $r['json']['data']['cuotas'];
        $this->assertCount((int) Db::valor('SELECT COUNT(*) FROM cuotas WHERE alumno_id = ?', 'i', self::$ana), $cuotas);
        foreach ($cuotas as $c) {
            $this->assertContains($c['estado_visual'], ['pagada', 'impaga', 'proxima_a_vencer', 'pendiente']);
            $this->assertEqualsWithDelta($c['importe'] + $c['recargo'], $c['total'], 0.001);
        }
        $this->assertArrayHasKey('total_adeudado', $r['json']['data']['resumen']);
    }

    public function testCuotasDeOtroAlumnoDan403(): void
    {
        $this->assertSame(403, self::$padre->get(self::API . 'cuotas.php?alumno_id=' . self::$carlos)['status']);
        $this->assertSame(403, self::$alumno->get(self::API . 'cuotas.php?alumno_id=' . self::$carlos)['status']);
        $this->assertSame(200, self::$alumno->get(self::API . 'cuotas.php')['status'], 'El alumno ve las suyas sin indicar id');
        $this->assertSame(401, (new HttpClient())->get(self::API . 'cuotas.php')['status']);
    }

    // ── Facturas (RFGm04) ───────────────────────────────────────────────

    public function testLasFacturasSeFiltranPorPeriodoYSumanSusItems(): void
    {
        [$desde, $hasta, $minimo, $maximo] = $this->rangoUltimosDosMeses();
        $r = self::$padre->get(self::API . "facturas.php?alumno_id=" . self::$ana . "&desde=$desde&hasta=$hasta");
        $this->assertSame(200, $r['status'], $r['body']);
        $facturas = $r['json']['data'];

        $esperadas = (int) Db::valor(
            'SELECT COUNT(*) FROM facturas WHERE alumno_id = ? AND (anio * 12 + mes) BETWEEN ? AND ?',
            'iii',
            self::$ana,
            $minimo,
            $maximo
        );
        $this->assertCount($esperadas, $facturas);
        $this->assertGreaterThan(0, $esperadas, 'El seed deja facturas de los últimos meses');
        foreach ($facturas as $f) {
            $suma = array_sum(array_column($f['items'], 'importe'));
            $this->assertEqualsWithDelta($suma, $f['total'], 0.001, $f['numero']);
            $this->assertEqualsWithDelta($f['total'], $f['pagado'] + $f['en_revision'] + $f['saldo'], 0.001, $f['numero']);
        }
    }

    public function testLaFacturaMuestraSusPagosYComprobantes(): void
    {
        $r = self::$padre->get(self::API . 'facturas.php?alumno_id=' . self::$ana);
        $pagos = array_merge(...array_column($r['json']['data'], 'pagos'));
        $this->assertNotEmpty($pagos, 'ana tiene pagos de demo');
        $conComprobante = array_filter($pagos, fn ($p) => $p['comprobantes']);
        $this->assertNotEmpty($conComprobante);
        $this->assertArrayNotHasKey('archivo', reset($conComprobante)['comprobantes'][0], 'El archivo no viaja en el listado');
    }

    public function testUnPeriodoInvalidoDa422(): void
    {
        $this->assertSame(422, self::$padre->get(self::API . 'facturas.php?alumno_id=' . self::$ana . '&desde=2026-13')['status']);
        $this->assertSame(422, self::$padre->get(self::API . 'deuda.php?alumno_id=' . self::$ana . '&hasta=noviembre')['status']);
    }

    // ── Historial de deuda por concepto (RFGm04) ────────────────────────

    public function testLaDeudaPorConceptoCuadraConLosItems(): void
    {
        $r = self::$padre->get(self::API . 'deuda.php?alumno_id=' . self::$ana);
        $this->assertSame(200, $r['status'], $r['body']);
        $totales = $r['json']['data']['totales'];

        $sql = "SELECT COALESCE(SUM(fi.importe), 0) FROM factura_items fi JOIN facturas f ON f.id = fi.factura_id WHERE f.alumno_id = ?";
        $this->assertEqualsWithDelta((float) Db::valor($sql, 'i', self::$ana), $totales['facturado'], 0.001);
        $this->assertEqualsWithDelta((float) Db::valor($sql . " AND fi.estado = 'pagado'", 'i', self::$ana), $totales['pagado'], 0.001);
        $this->assertEqualsWithDelta((float) Db::valor($sql . " AND fi.estado = 'pendiente'", 'i', self::$ana), $totales['adeudado'], 0.001);

        $conceptos = array_column($r['json']['data']['conceptos'], 'concepto');
        $this->assertContains('Deporte · Natación', $conceptos, 'Cada deporte es un concepto aparte');
        $this->assertContains('Comedor', $conceptos);
        foreach ($r['json']['data']['conceptos'] as $c) {
            $this->assertEqualsWithDelta($c['facturado'], array_sum(array_column($c['meses'], 'facturado')), 0.001, $c['concepto']);
        }
    }

    public function testDeudaDeOtroAlumnoDa403(): void
    {
        $this->assertSame(403, self::$padre->get(self::API . 'deuda.php?alumno_id=' . self::$carlos)['status']);
    }

    // ── Factura en PDF ──────────────────────────────────────────────────

    public function testElPadreYElAlumnoBajanLaFacturaEnPdf(): void
    {
        $id = (int) Db::valor('SELECT id FROM facturas WHERE alumno_id = ? ORDER BY id DESC LIMIT 1', 'i', self::$ana);
        foreach ([self::$padre, self::$alumno] as $cliente) {
            $r = $cliente->get(self::API . "factura_pdf.php?factura_id=$id");
            $this->assertSame(200, $r['status']);
            $this->assertStringStartsWith('%PDF', $r['body']);
        }
    }

    public function testLaFacturaAjenaOInexistenteDa403(): void
    {
        $ajena = (int) Db::valor('SELECT id FROM facturas WHERE alumno_id = ? LIMIT 1', 'i', self::$carlos);
        $this->assertSame(403, self::$padre->get(self::API . "factura_pdf.php?factura_id=$ajena")['status']);
        $this->assertSame(403, self::$alumno->get(self::API . "factura_pdf.php?factura_id=$ajena")['status']);
        $this->assertSame(403, self::$padre->get(self::API . 'factura_pdf.php?factura_id=999999')['status'], 'No revela si existe');
    }

    // ── Auxiliares ──────────────────────────────────────────────────────

    /** Mes anterior y actual como 'AAAA-MM' y como índice (anio * 12 + mes). */
    private function rangoUltimosDosMeses(): array
    {
        $ant = new DateTime('first day of last month');
        $act = new DateTime('first day of this month');
        return [
            $ant->format('Y-m'), $act->format('Y-m'),
            (int) $ant->format('Y') * 12 + (int) $ant->format('n'), (int) $act->format('Y') * 12 + (int) $act->format('n'),
        ];
    }
}
