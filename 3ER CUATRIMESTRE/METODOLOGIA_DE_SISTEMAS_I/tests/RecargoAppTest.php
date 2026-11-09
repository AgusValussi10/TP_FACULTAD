<?php

use PHPUnit\Framework\TestCase;

/**
 * Parte 3 — RN11 al pagar por la app: una cuota vencida se paga con su recargo,
 * que se calcula una sola vez (calcular_recargo de Sprint 5), se guarda en
 * cuotas.recargo, entra como ítem a la misma factura y la facturación siguiente
 * no lo repite.
 *
 * Usa abril y mayo del año que viene (períodos sin datos de demo) y los limpia.
 * La cuota de abril se crea con vencimiento AYER para simular que está vencida.
 */
class RecargoAppTest extends TestCase
{
    private const IMPORTE = 40000.0;

    private static int $anio;
    private static int $anaId;
    private static int $lauraId;
    private static int $adminId;
    private static int $cuotaId;
    private static int $facturaId;

    public static function setUpBeforeClass(): void
    {
        self::$anio    = (int) date('Y') + 1;
        self::$anaId   = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'ana.garcia'");
        self::$lauraId = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'laura.martinez'");
        self::$adminId = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'admin'");
        self::limpiar();

        $conn = Db::conn();
        $ayer = date('Y-m-d', strtotime('-1 day'));
        $anio = self::$anio;
        $conn->query(
            "INSERT INTO cuotas (alumno_id, concepto, mes, anio, importe, fecha_vencimiento)
             VALUES (" . self::$anaId . ", 'cuota', 4, $anio, " . self::IMPORTE . ", '$ayer')"
        );
        self::$cuotaId = (int) $conn->insert_id;
        generar_facturacion($conn, 4, $anio);
        self::$facturaId = (int) Db::valor('SELECT id FROM facturas WHERE alumno_id = ? AND mes = 4 AND anio = ?', 'ii', self::$anaId, $anio);
    }

    public static function tearDownAfterClass(): void
    {
        self::limpiar();
    }

    public function testPagarUnaCuotaVencidaSumaElRecargoALaMismaFactura(): void
    {
        $this->assertSame(0, $this->recargosDeLaCuota(), 'Antes de pagar no hay recargo facturado');

        $itemCuota = (int) Db::valor("SELECT id FROM factura_items WHERE factura_id = ? AND tipo = 'cuota'", 'i', self::$facturaId);
        $pagoId    = crear_pago(Db::conn(), self::$facturaId, self::$lauraId, [$itemCuota]);

        $recargo = round(self::IMPORTE * PORCENTAJE_RECARGO_MORA / 100, 2);
        $this->assertEqualsWithDelta(self::IMPORTE + $recargo, (float) Db::valor('SELECT total FROM pagos WHERE id = ?', 'i', $pagoId), 0.001);
        $this->assertEqualsWithDelta($recargo, (float) Db::valor('SELECT recargo FROM cuotas WHERE id = ?', 'i', self::$cuotaId), 0.001);
        $this->assertSame(1, $this->recargosDeLaCuota());
        $this->assertSame(
            'en_revision',
            Db::valor("SELECT estado FROM factura_items WHERE cuota_id = ? AND tipo = 'recargo'", 'i', self::$cuotaId)
        );
        $this->assertSame(2, (int) Db::valor('SELECT COUNT(*) FROM pago_items WHERE pago_id = ?', 'i', $pagoId));

        // El admin aprueba: la cuota queda pagada con ese mismo recargo (no se recalcula).
        registrar_comprobante(Db::conn(), $pagoId, self::$lauraId, 'test.pdf', 'application/pdf', '%PDF-1.4', self::IMPORTE + $recargo, date('Y-m-d'));
        aprobar_pago(Db::conn(), $pagoId, self::$adminId);
        $this->assertSame('pagada', Db::valor('SELECT estado FROM cuotas WHERE id = ?', 'i', self::$cuotaId));
        $this->assertEqualsWithDelta($recargo, (float) Db::valor('SELECT recargo FROM cuotas WHERE id = ?', 'i', self::$cuotaId), 0.001);
        // Quedan los deportes de ana sin pagar: la factura queda parcial.
        $this->assertSame('parcial', Db::valor('SELECT estado FROM facturas WHERE id = ?', 'i', self::$facturaId));
        $this->assertSame('pagado', Db::valor("SELECT estado FROM factura_items WHERE cuota_id = ? AND tipo = 'recargo'", 'i', self::$cuotaId));
    }

    /** @depends testPagarUnaCuotaVencidaSumaElRecargoALaMismaFactura */
    public function testLaFacturacionSiguienteNoRepiteElRecargo(): void
    {
        generar_facturacion(Db::conn(), 5, self::$anio);
        $this->assertSame(1, $this->recargosDeLaCuota(), 'Un solo ítem de recargo por cuota');
        $this->assertNull(agregar_recargo_a_factura(Db::conn(), self::$facturaId, self::$cuotaId));
    }

    public function testUnaCuotaQueNoVencioNoLlevaRecargo(): void
    {
        $conn = Db::conn();
        $anio = self::$anio;
        $conn->query(
            "INSERT INTO cuotas (alumno_id, concepto, mes, anio, importe, fecha_vencimiento)
             VALUES (" . self::$anaId . ", 'cuota', 6, $anio, " . self::IMPORTE . ", '$anio-06-10')"
        );
        $this->assertNull(agregar_recargo_a_factura($conn, self::$facturaId, (int) $conn->insert_id));
    }

    private function recargosDeLaCuota(): int
    {
        return (int) Db::valor("SELECT COUNT(*) FROM factura_items WHERE tipo = 'recargo' AND cuota_id = ?", 'i', self::$cuotaId);
    }

    private static function limpiar(): void
    {
        $anio = self::$anio;
        Db::exec("DELETE FROM facturas WHERE mes IN (4, 5) AND anio = $anio");
        Db::exec("DELETE FROM cuotas WHERE mes IN (4, 5, 6) AND anio = $anio");
        Db::exec("DELETE FROM notificaciones WHERE tipo = 'pago' AND mensaje LIKE '%Abril $anio%'");
    }
}
