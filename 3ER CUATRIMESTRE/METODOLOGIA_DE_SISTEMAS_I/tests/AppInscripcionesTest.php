<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../lib/inscripciones.php';

/**
 * Parte 3 — App: inscripciones a deportes (RFGm09), comedor (RFGm10) y transporte por
 * recorrido (RFGm11). Mismas reglas en la web (RFG11/RFG09): lib/inscripciones.php.
 *
 * Datos de seed_parte3.php: ana.garcia está en Natación (mar/jue 15–16) y Vóleibol
 * (mié/vie 16–17:30), o sea, ya tiene 2; matias.vega está en Fútbol (lun/mié/vie 17–18:30).
 * Atletismo (lun/mié 16–17:30) choca con Fútbol; Danza (lun/jue 15:30–16:30) no.
 * El test deja todo como estaba (inscripciones, reservas, cupo y facturas del mes siguiente).
 */
class AppInscripcionesTest extends TestCase
{
    private const API = '/app/api/';

    private static HttpClient $padre;
    private static HttpClient $ajeno;
    private static HttpClient $alumno;
    private static int $ana;
    private static int $matias;
    /** @var array{mes:int, anio:int} primer mes con reservas abiertas */
    private static array $mesAbierto;

    public static function setUpBeforeClass(): void
    {
        self::$ana    = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'ana.garcia'");
        self::$matias = (int) Db::valor("SELECT id FROM usuarios WHERE usuario = 'matias.vega'");
        self::$mesAbierto = meses_reservables()[0];
        self::$padre  = self::cliente('laura.martinez', 'padre123');
        self::$ajeno  = self::cliente('diego.fernandez', 'padre456');
        self::$alumno = self::cliente('ana.garcia', 'alumno123');
    }

    public static function tearDownAfterClass(): void
    {
        $m = self::$matias;
        Db::exec("DELETE FROM inscripciones_deportivas WHERE alumno_id = $m AND deporte_id <> " . self::idDeporte('Fútbol'));
        Db::exec("DELETE FROM reservas_servicios WHERE alumno_id = $m AND servicio = 'transporte'");
        Db::exec("UPDATE deportes SET cupo_maximo = 18 WHERE nombre = 'Artes Marciales'");
        ['mes' => $mes, 'anio' => $anio] = self::$mesAbierto;
        Db::exec("DELETE FROM facturas WHERE mes = $mes AND anio = $anio");
    }

    // ── Deportes (RFGm09) ───────────────────────────────────────────────

    public function testUnTercerDeporteEsRechazado(): void
    {
        $r = $this->deporte(self::$ana, 'Artes Marciales', 'inscribir');
        $this->assertSame(409, $r['status']);
        $this->assertStringContainsString('hasta 2', $r['json']['message']);
        $this->assertSame(2, $this->cantidadDeportes(self::$ana));
    }

    public function testUnDeporteSinCupoEsRechazado(): void
    {
        Db::exec("UPDATE deportes SET cupo_maximo = 0 WHERE nombre = 'Artes Marciales'");
        $r = $this->deporte(self::$matias, 'Artes Marciales', 'inscribir');
        Db::exec("UPDATE deportes SET cupo_maximo = 18 WHERE nombre = 'Artes Marciales'");
        $this->assertSame(409, $r['status']);
        $this->assertStringContainsString('cupo', $r['json']['message']);
    }

    public function testUnDeporteConChoqueDeHorarioEsRechazado(): void
    {
        $r = $this->deporte(self::$matias, 'Atletismo', 'inscribir');
        $this->assertSame(409, $r['status']);
        $this->assertStringContainsString('se superpone en horario con Fútbol', $r['json']['message']);

        // La app lo anticipa en el listado.
        $lista = self::$padre->get(self::API . 'inscripciones.php?alumno_id=' . self::$matias)['json']['data']['deportes'];
        $atletismo = array_values(array_filter($lista, fn ($d) => $d['nombre'] === 'Atletismo'))[0];
        $this->assertFalse($atletismo['puede']);
        $this->assertStringContainsString('Fútbol', $atletismo['motivo']);
    }

    public function testUnaInscripcionDuplicadaEsRechazada(): void
    {
        $r = $this->deporte(self::$matias, 'Fútbol', 'inscribir');
        $this->assertSame(409, $r['status']);
        $this->assertStringContainsString('Ya está inscripto', $r['json']['message']);
    }

    public function testInscribirYDarDeBaja(): void
    {
        $this->assertSame(200, $this->deporte(self::$matias, 'Danza', 'inscribir')['status']);
        $this->assertSame(2, $this->cantidadDeportes(self::$matias));
        $this->assertSame(200, $this->deporte(self::$matias, 'Danza', 'baja')['status']);
        $this->assertSame(1, $this->cantidadDeportes(self::$matias));
        $this->assertSame(409, $this->deporte(self::$matias, 'Danza', 'baja')['status']);
    }

    public function testLaWebAplicaLasMismasReglas(): void
    {
        $web = new HttpClient();
        $web->login('laura.martinez', 'padre123', 'Padres / Tutores');
        $r = $web->post('/gestion/deportes_guardar.php', ['alumno_id' => self::$ana, 'deportes' => [self::idDeporte('Artes Marciales')]]);
        $this->assertSame([], $r['json']['inscriptos']);
        $this->assertNotEmpty($r['json']['rechazados'], 'RFG11 ahora también limita a 2 deportes');
        $this->assertSame(2, $this->cantidadDeportes(self::$ana));
    }

    // ── Comedor y transporte (RFGm10/RFGm11) ───────────────────────────

    public function testTransportePorRecorridoQueSePuedeCambiarYCancelar(): void
    {
        $sin = $this->servicio('transporte', 'reservar', []);
        $this->assertSame(422, $sin['status'], 'El transporte exige recorrido');

        $this->assertSame(200, $this->servicio('transporte', 'reservar', ['recorrido_id' => 3])['status']);
        $this->assertSame(3, (int) $this->recorridoReservado());
        $this->assertSame(200, $this->servicio('transporte', 'reservar', ['recorrido_id' => 4])['status']);
        $this->assertSame(4, (int) $this->recorridoReservado(), 'Cambiar de recorrido actualiza la misma reserva');
        $this->assertSame(1, (int) Db::valor(
            "SELECT COUNT(*) FROM reservas_servicios WHERE alumno_id = ? AND servicio = 'transporte' AND mes = ? AND anio = ?",
            'iii',
            self::$matias,
            self::$mesAbierto['mes'],
            self::$mesAbierto['anio']
        ));

        $this->assertSame(200, $this->servicio('transporte', 'cancelar', [])['status']);
        $this->assertNull($this->recorridoReservado());
    }

    public function testComedorFueraDePlazoEsRechazado(): void
    {
        $hoy = new DateTime('first day of this month'); // el plazo del mes en curso venció el día 25 del anterior
        $r = self::$padre->post(self::API . 'servicio.php', [
            'alumno_id' => self::$matias, 'servicio' => 'comedor', 'accion' => 'reservar',
            'mes' => (int) $hoy->format('n'), 'anio' => (int) $hoy->format('Y'),
        ]);
        $this->assertSame(409, $r['status']);
        $this->assertStringContainsString('plazo', $r['json']['message']);
    }

    public function testLoInscriptoSeFacturaElMesSiguiente(): void
    {
        $this->deporte(self::$matias, 'Danza', 'inscribir');
        $this->servicio('transporte', 'reservar', ['recorrido_id' => 3]);
        ['mes' => $mes, 'anio' => $anio] = self::$mesAbierto;
        Db::exec("DELETE FROM facturas WHERE alumno_id = " . self::$matias . " AND mes = $mes AND anio = $anio");
        generar_facturacion(Db::conn(), $mes, $anio);

        $factura = (int) Db::valor('SELECT id FROM facturas WHERE alumno_id = ? AND mes = ? AND anio = ?', 'iii', self::$matias, $mes, $anio);
        $tipos = array_column(Db::conn()->query("SELECT tipo, deporte_id, recorrido_id FROM factura_items WHERE factura_id = $factura")->fetch_all(MYSQLI_ASSOC), 'recorrido_id', 'deporte_id');
        $this->assertArrayHasKey(self::idDeporte('Danza'), $tipos, 'Danza se cobra en la factura del mes siguiente');
        $this->assertSame(3, (int) Db::valor("SELECT recorrido_id FROM factura_items WHERE factura_id = ? AND tipo = 'transporte'", 'i', $factura));

        // Con la factura emitida, ese mes ya no se puede cambiar.
        $this->assertSame(409, $this->servicio('transporte', 'cancelar', [])['status']);
        Db::exec("DELETE FROM facturas WHERE id = $factura");
        $this->deporte(self::$matias, 'Danza', 'baja');
        $this->servicio('transporte', 'cancelar', []);
    }

    // ── Permisos ────────────────────────────────────────────────────────

    public function testElPadreAjenoYElAlumnoNoModifican(): void
    {
        $this->assertSame(403, self::$ajeno->get(self::API . 'inscripciones.php?alumno_id=' . self::$ana)['status']);
        $this->assertSame(403, self::$ajeno->post(self::API . 'deporte.php', [
            'alumno_id' => self::$ana, 'deporte_id' => self::idDeporte('Danza'), 'accion' => 'baja',
        ])['status']);
        $this->assertSame(403, self::$ajeno->post(self::API . 'servicio.php', [
            'alumno_id' => self::$ana, 'servicio' => 'comedor', 'accion' => 'cancelar',
            'mes' => self::$mesAbierto['mes'], 'anio' => self::$mesAbierto['anio'],
        ])['status']);

        $propio = self::$alumno->get(self::API . 'inscripciones.php');
        $this->assertSame(200, $propio['status']);
        $this->assertFalse($propio['json']['data']['puede_modificar']);
        $this->assertSame(403, self::$alumno->post(self::API . 'deporte.php', [
            'alumno_id' => self::$ana, 'deporte_id' => self::idDeporte('Natación'), 'accion' => 'baja',
        ])['status']);
        $this->assertSame(2, $this->cantidadDeportes(self::$ana));
    }

    // ── Auxiliares ──────────────────────────────────────────────────────

    private function deporte(int $alumno, string $nombre, string $accion): array
    {
        return self::$padre->post(self::API . 'deporte.php', ['alumno_id' => $alumno, 'deporte_id' => self::idDeporte($nombre), 'accion' => $accion]);
    }

    private function servicio(string $servicio, string $accion, array $extra): array
    {
        return self::$padre->post(self::API . 'servicio.php', [
            'alumno_id' => self::$matias, 'servicio' => $servicio, 'accion' => $accion,
            'mes' => self::$mesAbierto['mes'], 'anio' => self::$mesAbierto['anio'],
        ] + $extra);
    }

    private function recorridoReservado()
    {
        return Db::valor(
            "SELECT recorrido_id FROM reservas_servicios WHERE alumno_id = ? AND servicio = 'transporte' AND mes = ? AND anio = ?",
            'iii',
            self::$matias,
            self::$mesAbierto['mes'],
            self::$mesAbierto['anio']
        );
    }

    private function cantidadDeportes(int $alumno): int
    {
        return (int) Db::valor('SELECT COUNT(*) FROM inscripciones_deportivas WHERE alumno_id = ?', 'i', $alumno);
    }

    private static function idDeporte(string $nombre): int
    {
        return (int) Db::valor('SELECT id FROM deportes WHERE nombre = ?', 's', $nombre);
    }

    private static function cliente(string $usuario, string $password): HttpClient
    {
        $c = new HttpClient();
        $c->post(self::API . 'login.php', ['usuario' => $usuario, 'password' => $password]);
        return $c;
    }
}
