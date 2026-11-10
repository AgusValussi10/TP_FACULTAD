<?php

use PHPUnit\Framework\TestCase;

/**
 * Parte 3 — App: pagar ítems y cargar comprobantes de transferencia (RFGm05/RFGm06).
 *
 * Usa julio del año que viene (período sin datos de demo): genera esa facturación y la
 * limpia al final. ana.garcia es hija de laura.martinez (Natación y Vóleibol);
 * diego.fernandez es de otra familia.
 */
class AppPagosTest extends TestCase
{
    private const API = '/app/api/';
    private const MES = 7;

    private static HttpClient $padre;
    private static HttpClient $ajeno;
    private static HttpClient $alumno;
    private static HttpClient $admin;
    private static int $anio;
    private static int $ana;
    private static int $factura;
    private static string $png;

    public static function setUpBeforeClass(): void
    {
        self::$anio = (int) date('Y') + 1;
        self::$ana  = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'ana.garcia'");
        self::limpiar();
        // Comedor y transporte reservados para ese mes: la factura queda con 5 ítems.
        Db::exec("INSERT INTO reservas_servicios (alumno_id, servicio, mes, anio, estado, recorrido_id) VALUES
            (" . self::$ana . ", 'comedor', " . self::MES . ", " . self::$anio . ", 'confirmada', NULL),
            (" . self::$ana . ", 'transporte', " . self::MES . ", " . self::$anio . ", 'confirmada', 2)");
        generar_facturacion(Db::conn(), self::MES, self::$anio);
        self::$factura = (int) Db::valor('SELECT id FROM facturas WHERE alumno_id = ? AND mes = ? AND anio = ?', 'iii', self::$ana, self::MES, self::$anio);

        self::$padre = self::cliente('laura.martinez', 'padre123');
        self::$ajeno = self::cliente('diego.fernandez', 'padre456');
        self::$alumno = self::cliente('ana.garcia', 'alumno123');
        self::$admin = new HttpClient();
        self::$admin->login('admin', 'admin', 'Docentes / Personal / Autoridades');

        self::$png = tempnam(sys_get_temp_dir(), 'png');
        file_put_contents(self::$png, self::pngMinimo());
    }

    /** Cada test arranca con la factura de prueba sin pagos: todos los ítems y la cuota pendientes. */
    protected function setUp(): void
    {
        $f = self::$factura;
        Db::exec("DELETE FROM pagos WHERE factura_id = $f");
        Db::exec("UPDATE factura_items SET estado = 'pendiente' WHERE factura_id = $f");
        Db::exec("UPDATE cuotas SET estado = 'pendiente', fecha_pago = NULL, metodo_pago = NULL, referencia_pago = NULL, registrado_por = NULL
                  WHERE id IN (SELECT cuota_id FROM factura_items WHERE factura_id = $f AND cuota_id IS NOT NULL)");
        recalcular_factura(Db::conn(), $f);
    }

    public static function tearDownAfterClass(): void
    {
        self::limpiar();
        @unlink(self::$png);
    }

    // ── Flujo completo ──────────────────────────────────────────────────

    public function testFlujoCompletoHastaLaAprobacionDelAdmin(): void
    {
        $items = $this->itemsPendientes(['cuota', 'deporte']);
        $r = self::$padre->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura, 'items' => array_keys($items)]);
        $this->assertSame(200, $r['status'], $r['body']);
        $pago = $r['json']['data'];
        $this->assertSame('pendiente_transferencia', $pago['estado']);
        $this->assertEqualsWithDelta(array_sum($items), $pago['total'], 0.001);
        $this->assertNotEmpty($pago['transferencia']['cbu']);
        $this->assertNotEmpty($pago['transferencia']['alias']);

        $pdf = self::$padre->get(self::API . 'pago_pdf.php?pago_id=' . $pago['id']);
        $this->assertSame(200, $pdf['status']);
        $this->assertStringStartsWith('%PDF', $pdf['body']);

        $subida = $this->subir(self::$padre, $pago['id'], $pago['total']);
        $this->assertSame(200, $subida['status'], $subida['body']);
        $this->assertSame('en_revision', $subida['json']['data']['estado']);

        $aprobado = self::$admin->post('/gestion/pago_revisar.php', ['pago_id' => $pago['id'], 'accion' => 'aprobar']);
        $this->assertTrue($aprobado['json']['success'] ?? false, $aprobado['body']);

        $visto = self::$padre->get(self::API . 'pago.php?pago_id=' . $pago['id'])['json']['data'];
        $this->assertSame('aprobado', $visto['estado']);
        $this->assertFalse($visto['admite_comprobantes']);
        $cuota = (int) Db::valor("SELECT cuota_id FROM factura_items WHERE factura_id = ? AND tipo = 'cuota'", 'i', self::$factura);
        $this->assertSame('pagada', Db::valor('SELECT estado FROM cuotas WHERE id = ?', 'i', $cuota), 'La web ve la cuota pagada');
        $this->assertSame(0, (int) Db::valor(
            "SELECT COUNT(*) FROM factura_items WHERE id IN (" . implode(',', array_keys($items)) . ") AND estado <> 'pagado'"
        ));
        $this->assertSame(1, (int) Db::valor(
            "SELECT COUNT(*) FROM notificaciones WHERE tipo = 'pago' AND mensaje LIKE ?",
            's',
            '%Aprobamos tu pago ' . $pago['numero'] . '%'
        ));
    }

    public function testUnPagoAdmiteDosComprobantesYLaFacturaLosMuestra(): void
    {
        // Un primer pago con un comprobante (comedor) y otro con dos (transporte).
        $primero = self::$padre->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura, 'items' => array_keys($this->itemsPendientes(['comedor']))])['json']['data'];
        $this->assertSame(200, $this->subir(self::$padre, $primero['id'], $primero['total'])['status']);
        $pago = self::$padre->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura, 'items' => array_keys($this->itemsPendientes(['transporte']))])['json']['data'];

        $this->assertSame(200, $this->subir(self::$padre, $pago['id'], 100)['status']);
        $segunda = $this->subir(self::$padre, $pago['id'], $pago['total'] - 100);
        $this->assertSame(200, $segunda['status'], $segunda['body']);
        $this->assertCount(2, $segunda['json']['data']['comprobantes']);
        $this->assertEqualsWithDelta($pago['total'], $segunda['json']['data']['declarado'], 0.001);

        $facturas = self::$padre->get(self::API . 'facturas.php?alumno_id=' . self::$ana)['json']['data'];
        $factura = array_values(array_filter($facturas, fn ($f) => $f['id'] === self::$factura))[0];
        $total = array_sum(array_map(fn ($p) => count($p['comprobantes']), $factura['pagos']));
        $this->assertSame(3, $total, 'La factura tiene los comprobantes de sus dos pagos');
    }

    // ── Validaciones ────────────────────────────────────────────────────

    public function testUnItemYaEnProcesoNoSePuedeVolverAPagar(): void
    {
        $item = array_key_first($this->itemsPendientes(['comedor']));
        $primero = self::$padre->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura, 'items' => [$item]]);
        $this->assertSame(200, $primero['status'], $primero['body']);
        $segundo = self::$padre->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura, 'items' => [$item]]);
        $this->assertSame(409, $segundo['status']);

        // Anular libera el ítem; un pago ya anulado no se anula otra vez.
        $id = $primero['json']['data']['id'];
        $this->assertSame(200, self::$padre->post(self::API . 'pago_anular.php', ['pago_id' => $id])['status']);
        $this->assertSame('pendiente', Db::valor('SELECT estado FROM factura_items WHERE id = ?', 'i', $item));
        $this->assertSame(409, self::$padre->post(self::API . 'pago_anular.php', ['pago_id' => $id])['status']);
    }

    public function testUnArchivoNoPermitidoEsRechazado(): void
    {
        $pago = $this->pagoNuevo();
        $falso = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($falso, 'esto no es un PDF, aunque diga que sí');
        $r = self::$padre->postArchivo(self::API . 'comprobante_subir.php', $this->campos($pago['id'], 10), 'archivo', $falso, 'comprobante.pdf', 'application/pdf');
        $this->assertSame(422, $r['status']);
        $this->assertStringContainsString('JPG o PNG', $r['json']['message']);

        $grande = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($grande, "%PDF-1.4\n" . str_repeat('0', 2 * 1024 * 1024));
        $r = self::$padre->postArchivo(self::API . 'comprobante_subir.php', $this->campos($pago['id'], 10), 'archivo', $grande, 'grande.pdf', 'application/pdf');
        $this->assertSame(422, $r['status']);
        $this->assertStringContainsString('demasiado grande', $r['json']['message']);
        @unlink($falso);
        @unlink($grande);

        $this->assertSame(0, (int) Db::valor('SELECT COUNT(*) FROM comprobantes_transferencia WHERE pago_id = ?', 'i', $pago['id']));
    }

    public function testElNombreDelArchivoSeSanitiza(): void
    {
        $pago = $this->pagoNuevo();
        $r = self::$padre->postArchivo(
            self::API . 'comprobante_subir.php',
            $this->campos($pago['id'], 10),
            'archivo',
            self::$png,
            '../../<script>Transferencia "1".png',
            'image/png'
        );
        $this->assertSame(200, $r['status'], $r['body']);
        $nombre = Db::valor('SELECT nombre_archivo FROM comprobantes_transferencia WHERE pago_id = ?', 'i', $pago['id']);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+\.png$/', $nombre);
    }

    // ── Permisos ────────────────────────────────────────────────────────

    public function testElPadreAjenoRecibe403(): void
    {
        $item = array_key_first($this->itemsPendientes());
        $this->assertSame(403, self::$ajeno->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura, 'items' => [$item]])['status']);

        $pago = $this->pagoNuevo();
        $this->assertSame(403, self::$ajeno->get(self::API . 'pago.php?pago_id=' . $pago['id'])['status']);
        $this->assertSame(403, self::$ajeno->get(self::API . 'pago_pdf.php?pago_id=' . $pago['id'])['status']);
        $this->assertSame(403, $this->subir(self::$ajeno, $pago['id'], 10)['status']);
        $this->assertSame(403, self::$ajeno->post(self::API . 'pago_anular.php', ['pago_id' => $pago['id']])['status']);
    }

    public function testElAlumnoNoPuedePagar(): void
    {
        $item = array_key_first($this->itemsPendientes());
        $r = self::$alumno->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura, 'items' => [$item]]);
        $this->assertSame(403, $r['status']);
        $this->assertSame(401, (new HttpClient())->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura])['status']);
    }

    // ── Auxiliares ──────────────────────────────────────────────────────

    /** Pago nuevo sobre el primer ítem pendiente de la factura de prueba. */
    private function pagoNuevo(): array
    {
        $item = array_key_first($this->itemsPendientes());
        $r = self::$padre->post(self::API . 'pago_crear.php', ['factura_id' => self::$factura, 'items' => [$item]]);
        $this->assertSame(200, $r['status'], $r['body']);
        return $r['json']['data'];
    }

    /** id => importe de los ítems pendientes de la factura de prueba (opcionalmente de ciertos tipos). */
    private function itemsPendientes(array $tipos = []): array
    {
        $res = Db::conn()->query("SELECT id, tipo, importe FROM factura_items WHERE factura_id = " . self::$factura . " AND estado = 'pendiente' ORDER BY id");
        $items = [];
        foreach ($res->fetch_all(MYSQLI_ASSOC) as $it) {
            if (!$tipos || in_array($it['tipo'], $tipos, true)) {
                $items[(int) $it['id']] = (float) $it['importe'];
            }
        }
        return $items;
    }

    private function campos(int $pagoId, float $monto): array
    {
        return ['pago_id' => $pagoId, 'monto' => $monto, 'fecha' => date('Y-m-d')];
    }

    private function subir(HttpClient $cliente, int $pagoId, float $monto): array
    {
        return $cliente->postArchivo(self::API . 'comprobante_subir.php', $this->campos($pagoId, $monto), 'archivo', self::$png, 'transferencia.png', 'image/png');
    }

    private static function cliente(string $usuario, string $password): HttpClient
    {
        $c = new HttpClient();
        $c->post(self::API . 'login.php', ['usuario' => $usuario, 'password' => $password]);
        return $c;
    }

    /** PNG válido de 1×1 (sin depender de GD). */
    private static function pngMinimo(): string
    {
        $chunk = fn (string $tipo, string $datos) => pack('N', strlen($datos)) . $tipo . $datos . pack('N', crc32($tipo . $datos));
        return "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0))
            . $chunk('IDAT', gzcompress("\x00\xff\x00\x00")) . $chunk('IEND', '');
    }

    private static function limpiar(): void
    {
        $anio = self::$anio;
        Db::exec('DELETE FROM facturas WHERE mes = ' . self::MES . " AND anio = $anio");
        Db::exec('DELETE FROM cuotas WHERE mes = ' . self::MES . " AND anio = $anio");
        Db::exec("DELETE FROM notificaciones WHERE tipo = 'pago' AND mensaje LIKE '%Julio $anio%'");
        Db::exec('DELETE FROM reservas_servicios WHERE mes = ' . self::MES . " AND anio = $anio");
    }
}
