<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../lib/recordatorios.php';

/**
 * Parte 3 — Mails automáticos a las familias en modo simulación (RFGm07/RFGm08).
 *
 * Las generaciones usan fechas dentro de 3 años (sin otros mails) y se limpian al final.
 * La deuda que se reclama es la de los datos de demo (ítems pendientes de las facturas).
 */
class RecordatoriosTest extends TestCase
{
    private static int $anio;
    private static int $laura;

    public static function setUpBeforeClass(): void
    {
        self::$anio  = (int) date('Y') + 3;
        self::$laura = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'laura.martinez'");
        self::limpiar();
    }

    public static function tearDownAfterClass(): void
    {
        self::limpiar();
    }

    // ── Calendario ──────────────────────────────────────────────────────

    public function testUltimoDiaHabilCuandoElMesTerminaEnFinDeSemana(): void
    {
        $this->assertSame('2026-10-30', ultimo_dia_habil(10, 2026), 'Octubre 2026 termina sábado');
        $this->assertSame('2027-01-29', ultimo_dia_habil(1, 2027), 'Enero 2027 termina domingo');
        $this->assertSame('2026-11-30', ultimo_dia_habil(11, 2026), 'Noviembre 2026 termina lunes');
    }

    public function testQueDiaCorrespondeCadaMail(): void
    {
        $this->assertTrue(corresponde_enviar('fin_de_mes', '2026-10-30'));
        $this->assertFalse(corresponde_enviar('fin_de_mes', '2026-10-31'), 'Sábado: no');
        $this->assertFalse(corresponde_enviar('fin_de_mes', '2026-10-29'));
        $this->assertTrue(corresponde_enviar('dia_20', '2026-11-20'));
        $this->assertFalse(corresponde_enviar('dia_20', '2026-11-21'));
    }

    // ── Generación (modo simulación) ────────────────────────────────────

    public function testFinDeMesComponeElTotalDelPadreYAdjuntaLasFacturas(): void
    {
        $fecha = ultimo_dia_habil(3, self::$anio);
        $r = generar_recordatorios(Db::conn(), 'fin_de_mes', $fecha);
        $this->assertTrue($r['corresponde']);
        $this->assertGreaterThan(0, $r['generados']);

        // Total esperado: los ítems pendientes de los hijos de laura (sin los que están en revisión).
        $esperado = (float) Db::valor(
            "SELECT SUM(fi.importe) FROM padre_alumno pa JOIN facturas f ON f.alumno_id = pa.alumno_id
             JOIN factura_items fi ON fi.factura_id = f.id WHERE pa.padre_id = ? AND fi.estado = 'pendiente'",
            'i',
            self::$laura
        );
        $mail = Db::conn()->query(
            "SELECT * FROM mails_enviados WHERE padre_id = " . self::$laura . " AND tipo = 'fin_de_mes' AND mes = 3 AND anio = " . self::$anio
        )->fetch_assoc();
        $this->assertEqualsWithDelta($esperado, (float) $mail['total'], 0.001);
        $this->assertSame('simulado', $mail['estado'], 'Sin SMTP: modo simulación');
        $this->assertNotEmpty($mail['adjuntos'], 'Lleva las facturas con saldo adjuntas');
        $this->assertStringContainsString('Ana García', $mail['cuerpo_html']);
        $this->assertStringContainsString('Matías Vega', $mail['cuerpo_html']);
        $this->assertStringContainsString(formato_pesos($esperado), $mail['cuerpo_html']);
    }

    public function testNoSeMandaDosVecesElMismoTipoYMes(): void
    {
        $fecha = sprintf('%04d-04-20', self::$anio);
        $primera = generar_recordatorios(Db::conn(), 'dia_20', $fecha);
        $segunda = generar_recordatorios(Db::conn(), 'dia_20', $fecha);
        $this->assertGreaterThan(0, $primera['generados']);
        $this->assertSame(0, $segunda['generados']);
        $this->assertSame($primera['generados'], $segunda['ya_enviados']);
        $this->assertSame($primera['generados'], (int) Db::valor(
            "SELECT COUNT(*) FROM mails_enviados WHERE tipo = 'dia_20' AND mes = 4 AND anio = ?",
            'i',
            self::$anio
        ));
    }

    public function testUnaFechaQueNoCorrespondeNoGeneraSalvoQueSeFuerce(): void
    {
        $fecha = sprintf('%04d-05-15', self::$anio);
        $this->assertSame(0, generar_recordatorios(Db::conn(), 'dia_20', $fecha)['generados']);
        $this->assertGreaterThan(0, generar_recordatorios(Db::conn(), 'dia_20', $fecha, true)['generados']);
    }

    // ── Endpoint del cron y pantalla del admin ──────────────────────────

    public function testElCronSinTokenOConTokenIncorrectoDa403(): void
    {
        $c = new HttpClient();
        $this->assertSame(403, $c->get('/cron/recordatorios.php?tipo=dia_20')['status']);
        $this->assertSame(403, $c->get('/cron/recordatorios.php?tipo=dia_20', ['X-Cron-Token: otro'])['status']);
        $ok = $c->get('/cron/recordatorios.php?tipo=dia_20', ['X-Cron-Token: ' . TEST_CRON_TOKEN]);
        $this->assertSame(200, $ok['status'], $ok['body']);
        $this->assertArrayHasKey('corresponde', $ok['json']['data']);
    }

    public function testSoloElAdminEjecutaLaPruebaYVeLosMails(): void
    {
        $padre = new HttpClient();
        $padre->login('laura.martinez', 'padre123', 'Padres / Tutores');
        $datos = ['tipo' => 'dia_20', 'fecha' => sprintf('%04d-06-20', self::$anio)];
        $this->assertSame(403, $padre->post('/gestion/mails_ejecutar.php', $datos)['status']);
        $this->assertSame(403, $padre->get('/gestion/mails_listar.php')['status']);

        $admin = new HttpClient();
        $admin->login('admin', 'admin', 'Docentes / Personal / Autoridades');
        $r = $admin->post('/gestion/mails_ejecutar.php', $datos);
        $this->assertSame(200, $r['status'], $r['body']);
        $this->assertGreaterThan(0, $r['json']['data']['generados']);
        $lista = $admin->get('/gestion/mails_listar.php')['json']['data'];
        $this->assertNotEmpty($lista);
        $vista = $admin->get('/gestion/mail_ver.php?id=' . $lista[0]['id']);
        $this->assertSame(200, $vista['status']);
        $this->assertStringContainsString('Total:', $vista['body']);
    }

    private static function limpiar(): void
    {
        Db::exec('DELETE FROM mails_enviados WHERE anio = ' . self::$anio);
    }
}
