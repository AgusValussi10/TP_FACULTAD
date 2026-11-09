<?php

use PHPUnit\Framework\TestCase;

/**
 * Parte 3 (App móvil) — Facturación mensual y revisión de pagos por transferencia (admin).
 *
 * Usa un período sin datos de demo (marzo del año que viene) y lo limpia antes y
 * después. ana.garcia (Secundario, hija de laura.martinez) está inscripta en
 * Natación y Vóleibol desde hace meses (seed_parte3.php). Los pagos que en la app
 * crea el padre se arman acá por SQL: la API del padre se prueba en su propio test.
 */
class FacturacionTest extends TestCase
{
    private const DOCENTES = 'Docentes / Personal / Autoridades';
    private const PADRES   = 'Padres / Tutores';
    private const MES      = 3;

    private static HttpClient $admin;
    private static HttpClient $padre;
    private static HttpClient $docente;
    private static int $anio;
    private static int $anaId;
    private static int $lauraId;

    public static function setUpBeforeClass(): void
    {
        self::$anio    = (int) date('Y') + 1;
        self::$anaId   = self::idDe('ana.garcia');
        self::$lauraId = self::idDe('laura.martinez');
        self::limpiarPeriodo();

        self::$admin = new HttpClient();
        self::assertTrue(self::$admin->login('admin', 'admin', self::DOCENTES)['success'] ?? false, 'Login admin');
        self::$padre = new HttpClient();
        self::assertTrue(self::$padre->login('laura.martinez', 'padre123', self::PADRES)['success'] ?? false, 'Login padre');
        self::$docente = new HttpClient();
        self::assertTrue(self::$docente->login('maria.rodriguez', 'docente123', self::DOCENTES)['success'] ?? false, 'Login docente');
    }

    public static function tearDownAfterClass(): void
    {
        self::limpiarPeriodo();
    }

    // ── Generación ──────────────────────────────────────────────────────

    public function testGenerarDosVecesNoDuplica(): void
    {
        $primera = $this->generar();
        $this->assertTrue($primera['success'], json_encode($primera));
        $this->assertGreaterThan(0, $primera['data']['creadas']);

        $segunda = $this->generar();
        $this->assertTrue($segunda['success']);
        $this->assertSame(0, $segunda['data']['creadas']);
        $this->assertSame($primera['data']['creadas'], $segunda['data']['existentes']);

        $facturas = (int) Db::valor('SELECT COUNT(*) FROM facturas WHERE mes = ? AND anio = ?', 'ii', self::MES, self::$anio);
        $this->assertSame($primera['data']['creadas'], $facturas);
        $cuotas = (int) Db::valor(
            "SELECT COUNT(*) FROM cuotas WHERE alumno_id = ? AND concepto = 'cuota' AND mes = ? AND anio = ?",
            'iii',
            self::$anaId,
            self::MES,
            self::$anio
        );
        $this->assertSame(1, $cuotas, 'La cuota del mes se crea una sola vez');
    }

    /** @depends testGenerarDosVecesNoDuplica */
    public function testLaFacturaSumaSusItemsConLasTarifasVigentes(): void
    {
        $facturaId = $this->facturaDeAna();
        $total     = (float) Db::valor('SELECT total FROM facturas WHERE id = ?', 'i', $facturaId);
        $suma      = (float) Db::valor('SELECT SUM(importe) FROM factura_items WHERE factura_id = ?', 'i', $facturaId);
        $this->assertEqualsWithDelta($suma, $total, 0.001);

        // Cuota de Secundario + Natación + Vóleibol (sin comedor ni transporte reservados para ese mes),
        // más el recargo de cuotas anteriores vencidas que haya (otros tests le dejan alguna impaga).
        $esperado = (float) Db::valor(
            "SELECT SUM(t.valor) FROM tarifas t
             WHERE t.clave = 'cuota:Secundario'
                OR t.clave IN (SELECT CONCAT('deporte:', id) FROM deportes WHERE nombre IN ('Natación', 'Vóleibol'))"
        );
        $recargos = (float) Db::valor(
            "SELECT COALESCE(SUM(fi.importe), 0) FROM factura_items fi JOIN cuotas c ON c.id = fi.cuota_id
             WHERE fi.factura_id = ? AND fi.tipo = 'recargo' AND fi.importe = c.recargo",
            'i',
            $facturaId
        );
        $this->assertEqualsWithDelta($esperado + $recargos, $total, 0.001);
        $sinRecargo = (int) Db::valor("SELECT COUNT(*) FROM factura_items WHERE factura_id = ? AND tipo NOT IN ('cuota', 'deporte', 'recargo')", 'i', $facturaId);
        $this->assertSame(0, $sinRecargo, 'No hay comedor ni transporte reservados para ese mes');
        $this->assertSame('pendiente', Db::valor('SELECT estado FROM facturas WHERE id = ?', 'i', $facturaId));
    }

    public function testPeriodoInvalidoDa422(): void
    {
        $r = self::$admin->post('/gestion/facturacion_generar.php', ['mes' => 13, 'anio' => self::$anio]);
        $this->assertSame(422, $r['status']);
    }

    // ── Revisión de pagos ───────────────────────────────────────────────

    /** @depends testGenerarDosVecesNoDuplica */
    public function testAprobarMarcaLosItemsLaFacturaYLaCuota(): void
    {
        $facturaId = $this->facturaDeAna();
        $itemCuota = (int) Db::valor("SELECT id FROM factura_items WHERE factura_id = ? AND tipo = 'cuota'", 'i', $facturaId);
        $cuotaId   = (int) Db::valor('SELECT cuota_id FROM factura_items WHERE id = ?', 'i', $itemCuota);
        $pagoId    = $this->pagoEnRevision($facturaId, [$itemCuota]);

        $r = self::$admin->post('/gestion/pago_revisar.php', ['pago_id' => $pagoId, 'accion' => 'aprobar']);
        $this->assertTrue($r['json']['success'] ?? false, $r['body']);

        $this->assertSame('aprobado', Db::valor('SELECT estado FROM pagos WHERE id = ?', 'i', $pagoId));
        $this->assertSame('pagado', Db::valor('SELECT estado FROM factura_items WHERE id = ?', 'i', $itemCuota));
        $this->assertSame('parcial', Db::valor('SELECT estado FROM facturas WHERE id = ?', 'i', $facturaId));
        $this->assertSame('pagada', Db::valor('SELECT estado FROM cuotas WHERE id = ?', 'i', $cuotaId));
        $this->assertSame('transferencia', Db::valor('SELECT metodo_pago FROM cuotas WHERE id = ?', 'i', $cuotaId));
        $this->assertSame(sprintf('P-%06d', $pagoId), Db::valor('SELECT referencia_pago FROM cuotas WHERE id = ?', 'i', $cuotaId));
        $avisos = (int) Db::valor(
            "SELECT COUNT(*) FROM notificaciones WHERE padre_id = ? AND tipo = 'pago' AND mensaje LIKE ?",
            'is',
            self::$lauraId,
            '%' . sprintf('P-%06d', $pagoId) . '%'
        );
        $this->assertSame(1, $avisos, 'Se notifica al padre');

        // No se puede revisar dos veces.
        $otra = self::$admin->post('/gestion/pago_revisar.php', ['pago_id' => $pagoId, 'accion' => 'aprobar']);
        $this->assertSame(409, $otra['status']);
    }

    /** @depends testGenerarDosVecesNoDuplica */
    public function testRechazarNoMarcaNadaYLiberaLosItems(): void
    {
        $facturaId = $this->facturaDeAna();
        $items     = $this->idsDeItems($facturaId, 'deporte');
        $pagado    = (float) Db::valor('SELECT pagado FROM facturas WHERE id = ?', 'i', $facturaId);
        $pagoId    = $this->pagoEnRevision($facturaId, $items);

        $sinMotivo = self::$admin->post('/gestion/pago_revisar.php', ['pago_id' => $pagoId, 'accion' => 'rechazar', 'motivo' => '']);
        $this->assertSame(422, $sinMotivo['status']);

        $r = self::$admin->post('/gestion/pago_revisar.php', [
            'pago_id' => $pagoId, 'accion' => 'rechazar', 'motivo' => 'El monto no coincide con la transferencia.',
        ]);
        $this->assertTrue($r['json']['success'] ?? false, $r['body']);

        $this->assertSame('rechazado', Db::valor('SELECT estado FROM pagos WHERE id = ?', 'i', $pagoId));
        foreach ($items as $itemId) {
            $this->assertSame('pendiente', Db::valor('SELECT estado FROM factura_items WHERE id = ?', 'i', $itemId));
        }
        $this->assertEqualsWithDelta($pagado, (float) Db::valor('SELECT pagado FROM facturas WHERE id = ?', 'i', $facturaId), 0.001);
    }

    // ── Cobro en la web sincronizado con la factura ─────────────────────

    /** @depends testGenerarDosVecesNoDuplica */
    public function testElCobroDeLaWebMarcaElItemDeLaFacturaYNoAceptaEfectivo(): void
    {
        $alumnoId  = self::idDe('carlos.lopez');
        $facturaId = (int) Db::valor('SELECT id FROM facturas WHERE alumno_id = ? AND mes = ? AND anio = ?', 'iii', $alumnoId, self::MES, self::$anio);
        $cuotaId   = (int) Db::valor("SELECT cuota_id FROM factura_items WHERE factura_id = ? AND tipo = 'cuota'", 'i', $facturaId);

        $efectivo = self::$admin->post('/gestion/cuotas_pagar.php', ['cuota_id' => $cuotaId, 'metodo_pago' => 'efectivo']);
        $this->assertFalse($efectivo['json']['success']);

        $r = self::$admin->post('/gestion/cuotas_pagar.php', ['cuota_id' => $cuotaId, 'metodo_pago' => 'transferencia']);
        $this->assertTrue($r['json']['success'] ?? false, $r['body']);
        $this->assertSame('pagado', Db::valor("SELECT estado FROM factura_items WHERE factura_id = ? AND tipo = 'cuota'", 'i', $facturaId));
        $this->assertNotSame('pendiente', Db::valor('SELECT estado FROM facturas WHERE id = ?', 'i', $facturaId));
    }

    // ── Permisos ────────────────────────────────────────────────────────

    public function testSoloElAdminUsaLosEndpoints(): void
    {
        $gets  = ['/gestion/tarifas_listar.php', '/gestion/facturas_listar.php', '/gestion/pagos_listar.php'];
        $posts = [
            '/gestion/tarifas_guardar.php'     => ['clave' => 'comedor', 'valor' => 1],
            '/gestion/facturacion_generar.php' => ['mes' => self::MES, 'anio' => self::$anio],
            '/gestion/pago_revisar.php'        => ['pago_id' => 1, 'accion' => 'aprobar'],
        ];
        foreach ([self::$padre, self::$docente, new HttpClient()] as $cliente) {
            foreach ($gets as $ruta) {
                $this->assertSame(403, $cliente->get($ruta)['status'], $ruta);
            }
            foreach ($posts as $ruta => $datos) {
                $this->assertSame(403, $cliente->post($ruta, $datos)['status'], $ruta);
            }
        }
        $this->assertNotEquals(1.0, (float) Db::valor("SELECT valor FROM tarifas WHERE clave = 'comedor'"));
    }

    public function testLasPantallasRedirigenSiNoEsAdmin(): void
    {
        foreach (['tarifas', 'facturacion', 'pagos_revisar'] as $pantalla) {
            $this->assertSame(302, self::$padre->get("/gestion/$pantalla.php")['status'], $pantalla);
            $this->assertSame(200, self::$admin->get("/gestion/$pantalla.php")['status'], $pantalla);
        }
    }

    public function testElAdminGuardaUnaTarifaYRechazaUnaClaveInvalida(): void
    {
        $original = (float) Db::valor("SELECT valor FROM tarifas WHERE clave = 'comedor'");
        $r = self::$admin->post('/gestion/tarifas_guardar.php', ['clave' => 'comedor', 'valor' => '33000,50']);
        $this->assertTrue($r['json']['success'] ?? false, $r['body']);
        $this->assertEqualsWithDelta(33000.5, (float) Db::valor("SELECT valor FROM tarifas WHERE clave = 'comedor'"), 0.001);
        Db::exec("UPDATE tarifas SET valor = $original WHERE clave = 'comedor'");

        $this->assertSame(422, self::$admin->post('/gestion/tarifas_guardar.php', ['clave' => 'deporte:9999', 'valor' => 10])['status']);
        $this->assertSame(422, self::$admin->post('/gestion/tarifas_guardar.php', ['clave' => 'comedor', 'valor' => 0])['status']);
    }

    // ── Auxiliares ──────────────────────────────────────────────────────

    private function generar(): array
    {
        return self::$admin->post('/gestion/facturacion_generar.php', ['mes' => self::MES, 'anio' => self::$anio])['json'] ?? [];
    }

    private function facturaDeAna(): int
    {
        return (int) Db::valor('SELECT id FROM facturas WHERE alumno_id = ? AND mes = ? AND anio = ?', 'iii', self::$anaId, self::MES, self::$anio);
    }

    private function idsDeItems(int $facturaId, string $tipo): array
    {
        $stmt = Db::conn()->prepare('SELECT id FROM factura_items WHERE factura_id = ? AND tipo = ?');
        $stmt->bind_param('is', $facturaId, $tipo);
        $stmt->execute();
        $ids = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id'));
        $stmt->close();
        return $ids;
    }

    /** Lo que hace el padre desde la app: pago con ítems en revisión y un comprobante cargado. */
    private function pagoEnRevision(int $facturaId, array $itemIds): int
    {
        $conn  = Db::conn();
        $lista = implode(',', array_map('intval', $itemIds));
        $total = (float) Db::valor("SELECT SUM(importe) FROM factura_items WHERE id IN ($lista)");
        $conn->query("INSERT INTO pagos (factura_id, padre_id, total, estado) VALUES ($facturaId, " . self::$lauraId . ", $total, 'en_revision')");
        $pagoId = (int) $conn->insert_id;
        $conn->query("INSERT INTO pago_items (pago_id, factura_item_id, importe) SELECT $pagoId, id, importe FROM factura_items WHERE id IN ($lista)");
        $conn->query("UPDATE factura_items SET estado = 'en_revision' WHERE id IN ($lista)");
        $conn->query(
            "INSERT INTO comprobantes_transferencia
                (pago_id, nombre_archivo, mime, tamanio, archivo, monto_declarado, fecha_transferencia, subido_por)
             VALUES ($pagoId, 'test.pdf', 'application/pdf', 8, '%PDF-1.4', $total, CURDATE(), " . self::$lauraId . ")"
        );
        return $pagoId;
    }

    private static function limpiarPeriodo(): void
    {
        $anio = self::$anio;
        Db::exec('DELETE FROM facturas WHERE mes = ' . self::MES . " AND anio = $anio");
        Db::exec('DELETE FROM cuotas WHERE mes = ' . self::MES . " AND anio = $anio");
        Db::exec("DELETE FROM notificaciones WHERE tipo = 'pago' AND mensaje LIKE '%Marzo $anio%'");
    }

    private static function idDe(string $usuario): int
    {
        return (int) Db::valor('SELECT id FROM usuarios WHERE usuario = ?', 's', $usuario);
    }
}
