<?php

use PHPUnit\Framework\TestCase;

/**
 * Parte 3 — Reportes de pagos del admin (RFGm12), con valores verificados a mano.
 *
 * Escenario propio en enero y febrero de dentro de 2 años (sin otros datos), ambos alumnos de Secundario:
 *   ana    ene: Natación 30 (pagado) + Recorrido 2 70 (pagado)        → total 100, pagado 100 (pagada)
 *   ana    feb: Natación 20 (pagado) + Recorrido 2 30 (pendiente)     → total  50, pagado  20 (parcial)
 *   carlos ene: Danza 80 (pendiente)                                  → total  80, pagado   0 (pendiente)
 *   pagos: aprobado 100 (15/ene), aprobado 20 (03/feb), rechazado 999 (20/ene, no cuenta)
 */
class ReportesPagosTest extends TestCase
{
    private static HttpClient $admin;
    private static int $anio;

    public static function setUpBeforeClass(): void
    {
        self::$anio = (int) date('Y') + 2;
        self::limpiar();
        $ana    = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'ana.garcia'");
        $carlos = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'carlos.lopez'");
        $laura  = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'laura.martinez'");
        $natacion = (int) Db::valor("SELECT id FROM deportes WHERE nombre = 'Natación'");
        $danza    = (int) Db::valor("SELECT id FROM deportes WHERE nombre = 'Danza'");

        $anaEne = self::factura($ana, 1, 100, 100, 'pagada');
        $anaFeb = self::factura($ana, 2, 50, 20, 'parcial');
        $carEne = self::factura($carlos, 1, 80, 0, 'pendiente');
        self::item($anaEne, 'deporte', 30, 'pagado', $natacion, null);
        self::item($anaEne, 'transporte', 70, 'pagado', null, 2);
        self::item($anaFeb, 'deporte', 20, 'pagado', $natacion, null);
        self::item($anaFeb, 'transporte', 30, 'pendiente', null, 2);
        self::item($carEne, 'deporte', 80, 'pendiente', $danza, null);

        $a = self::$anio;
        Db::exec("INSERT INTO pagos (factura_id, padre_id, total, estado, revisado_en) VALUES
            ($anaEne, $laura, 100, 'aprobado', '$a-01-15 10:00:00'),
            ($anaFeb, $laura, 20, 'aprobado', '$a-02-03 10:00:00'),
            ($anaEne, $laura, 999, 'rechazado', '$a-01-20 10:00:00')");

        self::$admin = new HttpClient();
        self::$admin->login('admin', 'admin', 'Docentes / Personal / Autoridades');
    }

    public static function tearDownAfterClass(): void
    {
        self::limpiar();
    }

    public function testIngresosPorPeriodoSoloCuentanLosAprobados(): void
    {
        $d = $this->reporte('ingresos', '01', '02');
        $this->assertSame([['Enero ' . self::$anio, 1, 100], ['Febrero ' . self::$anio, 1, 20]], $this->numeros($d['filas']));
        $this->assertEquals(120, $d['totales'][2]);
    }

    public function testPagosCompletosEIncompletosPorAnioYAlumno(): void
    {
        $completos = $this->reporte('completos', '01', '02');
        $this->assertSame([[self::$anio, 'Ana García', 1, 100]], $this->numeros($completos['filas']));

        $incompletos = $this->reporte('incompletos', '01', '02');
        $this->assertSame(
            [[self::$anio, 'Ana García', 1, 50, 20, 30], [self::$anio, 'Carlos López', 1, 80, 0, 80]],
            $this->numeros($incompletos['filas'])
        );
        $this->assertEquals(110, $incompletos['totales'][5]);

        // El período filtra por mes facturado: solo enero → solo la de Carlos queda incompleta.
        $soloEnero = $this->reporte('incompletos', '01', '01');
        $this->assertSame([[self::$anio, 'Carlos López', 1, 80, 0, 80]], $this->numeros($soloEnero['filas']));
    }

    public function testPagosPorDeporteNivelHorarioYProfesor(): void
    {
        $d = $this->reporte('deportes', '01', '02');
        $porDeporte = [];
        foreach ($d['filas'] as [$deporte, $nivel, $horario, $profesor, $cuotas, $facturado, $pagado]) {
            $porDeporte[$deporte] = [$nivel, $cuotas, (int) $facturado, (int) $pagado];
            $this->assertNotEmpty($horario);
            $this->assertNotEmpty($profesor);
        }
        $this->assertSame(['Danza' => ['Secundario', 1, 80, 0], 'Natación' => ['Secundario', 2, 50, 50]], $porDeporte);
    }

    public function testPagosPorRecorridoYExportCsv(): void
    {
        $d = $this->reporte('transporte', '01', '02');
        $this->assertSame([['Recorrido 2 · Norte', 2, 100, 70]], $this->numeros($d['filas']));

        $a = self::$anio;
        $csv = self::$admin->get("/gestion/reportes_pagos_datos.php?reporte=transporte&desde=$a-01&hasta=$a-02&formato=csv");
        $this->assertSame(200, $csv['status']);
        $this->assertStringStartsWith("\xEF\xBB\xBF" . 'Recorrido;Cuotas;Facturado;Pagado', $csv['body'], 'UTF-8 con BOM y ";"');
        $this->assertStringContainsString('"Recorrido 2 · Norte";2;100,00;70,00', $csv['body']);
    }

    public function testSoloElAdminYPeriodoInvalido(): void
    {
        $a = self::$anio;
        $this->assertSame(422, self::$admin->get("/gestion/reportes_pagos_datos.php?reporte=ingresos&desde=$a-03&hasta=$a-01")['status']);
        $padre = new HttpClient();
        $padre->login('laura.martinez', 'padre123', 'Padres / Tutores');
        $this->assertSame(403, $padre->get("/gestion/reportes_pagos_datos.php?reporte=ingresos&desde=$a-01&hasta=$a-02")['status']);
        $this->assertSame(302, $padre->get('/gestion/reportes_pagos.php')['status']);
    }

    // ── Auxiliares ──────────────────────────────────────────────────────

    private function reporte(string $reporte, string $desde, string $hasta): array
    {
        $a = self::$anio;
        $r = self::$admin->get("/gestion/reportes_pagos_datos.php?reporte=$reporte&desde=$a-$desde&hasta=$a-$hasta");
        $this->assertSame(200, $r['status'], $r['body']);
        return $r['json']['data'];
    }

    /** Los importes llegan como float en JSON (100.0): se pasan a int para comparar con los valores a mano. */
    private function numeros(array $filas): array
    {
        return array_map(fn ($f) => array_map(fn ($v) => is_float($v) ? (int) round($v) : $v, $f), $filas);
    }

    private static function factura(int $alumno, int $mes, float $total, float $pagado, string $estado): int
    {
        $a = self::$anio;
        Db::exec("INSERT INTO facturas (alumno_id, mes, anio, fecha_emision, fecha_vencimiento, total, pagado, estado)
                  VALUES ($alumno, $mes, $a, '$a-0$mes-01', '$a-0$mes-10', $total, $pagado, '$estado')");
        return (int) Db::conn()->insert_id;
    }

    private static function item(int $factura, string $tipo, float $importe, string $estado, ?int $deporte, ?int $recorrido): void
    {
        $d = $deporte ?? 'NULL';
        $r = $recorrido ?? 'NULL';
        Db::exec("INSERT INTO factura_items (factura_id, tipo, clave, deporte_id, recorrido_id, descripcion, importe, estado)
                  VALUES ($factura, '$tipo', '$tipo:$d:$r', $d, $r, 'Ítem de prueba', $importe, '$estado')");
    }

    private static function limpiar(): void
    {
        Db::exec('DELETE FROM facturas WHERE anio = ' . self::$anio);
    }
}
