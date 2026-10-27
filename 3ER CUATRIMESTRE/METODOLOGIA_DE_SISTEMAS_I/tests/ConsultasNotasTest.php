<?php
use PHPUnit\Framework\TestCase;

/**
 * RFG07 — Consultas del padre/tutor sobre una calificación.
 *
 * Recorre el flujo completo como lo hace la UI:
 *   padre ve el boletín → consulta una nota → docente la ve y responde →
 *   el padre ve la respuesta y recibe la notificación.
 * Y verifica las reglas de negocio y de acceso de cada endpoint.
 *
 * Datos del seed (ci/preparar_bd.sh): laura.martinez es madre de ana.garcia;
 * Matemática la dicta maria.rodriguez; roberto.silva no dicta Matemática;
 * diego.fernandez es padre de otro alumno (carlos.lopez).
 */
class ConsultasNotasTest extends TestCase
{
    private const PADRES   = 'Padres / Tutores';
    private const DOCENTES = 'Docentes / Personal / Autoridades';

    private static HttpClient $padre;
    private static HttpClient $docente;
    private static int $hijoId;
    private static int $calificacionId;

    public static function setUpBeforeClass(): void
    {
        // Cada corrida arranca sin consultas previas.
        Db::exec('DELETE FROM consultas_notas');
        Db::exec("DELETE FROM notificaciones WHERE tipo = 'consulta_nota'");

        self::$padre = new HttpClient();
        $login = self::$padre->login('laura.martinez', 'padre123', self::PADRES);
        self::assertTrue($login['success'] ?? false, 'Login del padre: ' . json_encode($login));

        self::$docente = new HttpClient();
        $login = self::$docente->login('maria.rodriguez', 'docente123', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del docente: ' . json_encode($login));

        self::$hijoId = (int) Db::valor(
            "SELECT pa.alumno_id FROM padre_alumno pa
             JOIN usuarios p ON p.id = pa.padre_id WHERE p.usuario = 'laura.martinez' LIMIT 1"
        );
    }

    // ── Acceso ────────────────────────────────────────────────────────────

    public function testSinSesionNoSePuedeConsultar(): void
    {
        $anonimo = new HttpClient();
        $r = $anonimo->post('/gestion/consulta_nota_guardar.php', ['calificacion_id' => 1, 'mensaje' => 'Hola, quería consultar']);
        $this->assertSame(403, $r['status']);
        $this->assertFalse($r['json']['success']);
    }

    public function testGuardarSoloAceptaPost(): void
    {
        $r = self::$padre->get('/gestion/consulta_nota_guardar.php');
        $this->assertSame(405, $r['status']);
    }

    // ── Flujo principal ───────────────────────────────────────────────────

    public function testElBoletinDevuelveLosIdsDeLasCalificaciones(): int
    {
        $r = self::$padre->get('/gestion/boletin_listar.php?alumno_id=' . self::$hijoId);
        $this->assertSame(200, $r['status']);
        $this->assertTrue($r['json']['success'], $r['body']);

        $id = $this->buscarCalificacion($r['json'], 'Matemática');
        $this->assertGreaterThan(0, $id, 'El boletín no trae una nota de Matemática con id');
        self::$calificacionId = $id;
        return $id;
    }

    /** @depends testElBoletinDevuelveLosIdsDeLasCalificaciones */
    public function testElPadreEnviaUnaConsulta(int $calId): void
    {
        $r = self::$padre->post('/gestion/consulta_nota_guardar.php', [
            'calificacion_id' => $calId,
            'mensaje'         => '¿Podría explicarme qué temas entraban en esta evaluación?',
        ]);
        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertSame(1, (int) Db::valor(
            "SELECT COUNT(*) FROM consultas_notas WHERE calificacion_id = ? AND estado = 'pendiente'", 'i', $calId
        ));
    }

    /** @depends testElPadreEnviaUnaConsulta */
    public function testNoPermiteDosConsultasPendientesSobreLaMismaNota(): void
    {
        $r = self::$padre->post('/gestion/consulta_nota_guardar.php', [
            'calificacion_id' => self::$calificacionId,
            'mensaje'         => 'Insisto con la consulta anterior, gracias.',
        ]);
        $this->assertFalse($r['json']['success']);
        $this->assertStringContainsString('pendiente', $r['json']['message']);
    }

    /** @depends testElPadreEnviaUnaConsulta */
    public function testElDocenteDeLaMateriaVeLaConsultaPendiente(): int
    {
        $r = self::$docente->get('/gestion/consulta_nota_listar.php');
        $this->assertTrue($r['json']['success'], $r['body']);

        $consulta = $this->buscarConsulta($r['json']['consultas'], self::$calificacionId);
        $this->assertNotNull($consulta, 'El docente no ve la consulta sobre su materia');
        $this->assertSame('pendiente', $consulta['estado']);
        return (int) $consulta['id'];
    }

    /** @depends testElDocenteDeLaMateriaVeLaConsultaPendiente */
    public function testOtroDocenteNoPuedeResponder(int $consultaId): void
    {
        $otro = new HttpClient();
        $otro->login('roberto.silva', 'docente456', self::DOCENTES);
        $r = $otro->post('/gestion/consulta_nota_responder.php', ['id' => $consultaId, 'respuesta' => 'No es mi materia']);
        $this->assertSame(403, $r['status']);
    }

    /** @depends testElDocenteDeLaMateriaVeLaConsultaPendiente */
    public function testElDocenteRespondeYElPadreRecibeLaRespuesta(int $consultaId): void
    {
        $r = self::$docente->post('/gestion/consulta_nota_responder.php', [
            'id'        => $consultaId,
            'respuesta' => 'Entraban ecuaciones de primer grado y problemas de proporcionalidad.',
        ]);
        $this->assertTrue($r['json']['success'], $r['body']);

        $r = self::$padre->get('/gestion/consulta_nota_listar.php?alumno_id=' . self::$hijoId);
        $consulta = $this->buscarConsulta($r['json']['consultas'], self::$calificacionId);
        $this->assertSame('respondida', $consulta['estado']);
        $this->assertStringContainsString('ecuaciones', $consulta['respuesta']);

        $this->assertSame(1, (int) Db::valor(
            "SELECT COUNT(*) FROM notificaciones n JOIN usuarios u ON u.id = n.padre_id
             WHERE u.usuario = 'laura.martinez' AND n.tipo = 'consulta_nota'"
        ), 'No se creó la notificación para el padre');
    }

    // ── Validaciones ─────────────────────────────────────────────────────

    /** @depends testElBoletinDevuelveLosIdsDeLasCalificaciones */
    public function testRechazaMensajesDemasiadoCortos(int $calId): void
    {
        $r = self::$padre->post('/gestion/consulta_nota_guardar.php', ['calificacion_id' => $calId, 'mensaje' => 'Hola']);
        $this->assertFalse($r['json']['success']);
        $this->assertStringContainsString('entre 10 y 1000', $r['json']['message']);
    }

    /** @depends testElBoletinDevuelveLosIdsDeLasCalificaciones */
    public function testOtroPadreNoPuedeConsultarNotasQueNoSonDeSuHijo(int $calId): void
    {
        $otroPadre = new HttpClient();
        $otroPadre->login('diego.fernandez', 'padre456', self::PADRES);
        $r = $otroPadre->post('/gestion/consulta_nota_guardar.php', [
            'calificacion_id' => $calId,
            'mensaje'         => 'Quiero consultar una nota que no es de mi hijo.',
        ]);
        $this->assertSame(403, $r['status']);
    }

    // ── Auxiliares ───────────────────────────────────────────────────────

    private function buscarCalificacion(array $boletin, string $materia): int
    {
        // Estructura: materias[] → periodos[] → calificaciones[] (cada una con id y nota).
        foreach ($boletin['materias'] ?? [] as $m) {
            if ($m['materia'] !== $materia) {
                continue;
            }
            foreach ($m['periodos'] as $p) {
                foreach ($p['calificaciones'] as $c) {
                    return (int) $c['id'];
                }
            }
        }
        return 0;
    }

    private function buscarConsulta(array $consultas, int $calificacionId): ?array
    {
        foreach ($consultas as $c) {
            if ((int) $c['calificacion_id'] === $calificacionId) {
                return $c;
            }
        }
        return null;
    }
}
