<?php

use PHPUnit\Framework\TestCase;

/**
 * RFG05 — Planificación anual por materia.
 *
 * El docente carga y edita la planificación de sus materias (una por
 * materia/año; al editar se registra la fecha en actualizado_en). El admin
 * controla todas las materias del año: cuáles tienen planificación y cuáles no.
 *
 * Datos del seed: Lengua y Literatura de 3°A la dicta maria.rodriguez.
 * Se usa el año actual, borrando antes la planificación de esa materia.
 */
class PlanificacionTest extends TestCase
{
    private const DOCENTES = 'Docentes / Personal / Autoridades';

    private static HttpClient $docente;
    private static HttpClient $admin;
    private static int $materiaId;
    private static int $anio;

    public static function setUpBeforeClass(): void
    {
        self::$anio = (int) (new DateTime('today', new DateTimeZone('America/Argentina/Buenos_Aires')))->format('Y');
        self::$materiaId = (int) Db::valor(
            "SELECT m.id FROM materias m JOIN cursos c ON c.id = m.curso_id
             JOIN usuarios d ON d.id = m.docente_id
             WHERE m.nombre = 'Lengua y Literatura' AND c.nombre = '3°A' AND d.usuario = 'maria.rodriguez'"
        );
        Db::exec('DELETE FROM planificaciones WHERE materia_id = ' . self::$materiaId . ' AND anio = ' . self::$anio);

        self::$docente = new HttpClient();
        $login = self::$docente->login('maria.rodriguez', 'docente123', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del docente: ' . json_encode($login));

        self::$admin = new HttpClient();
        $login = self::$admin->login('admin', 'admin', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del admin: ' . json_encode($login));
    }

    // ── Docente ──────────────────────────────────────────────────────────

    public function testElDocenteGuardaLaPlanificacion(): void
    {
        $r = self::$docente->post('/gestion/planificacion_guardar.php', $this->datos('Unidad 1: comprensión lectora y textos narrativos.'));

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertSame(1, (int) Db::valor(
            'SELECT COUNT(*) FROM planificaciones WHERE materia_id = ? AND anio = ? AND actualizado_en IS NULL',
            'ii',
            self::$materiaId,
            self::$anio
        ), 'Recién cargada no tiene fecha de edición');
    }

    /** @depends testElDocenteGuardaLaPlanificacion */
    public function testAlEditarlaSeRegistraLaFechaDeEdicion(): void
    {
        $r = self::$docente->post('/gestion/planificacion_guardar.php', $this->datos('Unidad 1 y 2: textos narrativos y argumentativos.'));
        $this->assertTrue($r['json']['success'], $r['body']);

        $this->assertSame(1, (int) Db::valor(
            'SELECT COUNT(*) FROM planificaciones WHERE materia_id = ? AND anio = ?',
            'ii',
            self::$materiaId,
            self::$anio
        ), 'Editar no debe crear otra planificación');

        $r = self::$docente->get('/gestion/planificacion_listar.php?materia_id=' . self::$materiaId . '&anio=' . self::$anio);
        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertStringContainsString('argumentativos', $r['json']['plan']['contenidos']);
        $this->assertNotNull($r['json']['plan']['fecha_actualizacion'], 'No se llenó actualizado_en');
    }

    public function testRechazaContenidosDemasiadoCortos(): void
    {
        $r = self::$docente->post('/gestion/planificacion_guardar.php', $this->datos('Corto'));

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('20 caracteres', $r['json']['message']);
    }

    // ── Admin ────────────────────────────────────────────────────────────

    /** @depends testElDocenteGuardaLaPlanificacion */
    public function testElAdminVeTodasLasMateriasDelAnioConElResumen(): void
    {
        $r = self::$admin->get('/gestion/planificacion_listar.php?anio=' . self::$anio);

        $this->assertTrue($r['json']['success'], $r['body']);
        $materias = $r['json']['materias'];
        $resumen  = $r['json']['resumen'];

        $this->assertSame((int) Db::valor('SELECT COUNT(*) FROM materias'), $resumen['total']);
        $this->assertCount($resumen['total'], $materias);
        $this->assertSame((int) Db::valor(
            'SELECT COUNT(*) FROM planificaciones p JOIN materias m ON m.id = p.materia_id WHERE p.anio = ?',
            'i',
            self::$anio
        ), $resumen['cargadas']);
        $this->assertSame($resumen['total'] - $resumen['cargadas'], $resumen['pendientes']);

        $fila = $this->buscarMateria($materias, self::$materiaId);
        $this->assertNotNull($fila, 'La materia del test no aparece en la vista del admin');
        $this->assertNotNull($fila['plan_id'], 'La materia figura como pendiente aunque tiene planificación');
    }

    public function testElAdminNoPuedeGuardar(): void
    {
        $r = self::$admin->post('/gestion/planificacion_guardar.php', $this->datos('Contenidos cargados por el administrador.'));
        $this->assertSame(403, $r['status']);
    }

    // ── Acceso ────────────────────────────────────────────────────────────

    public function testUnAlumnoRecibe403(): void
    {
        $alumno = new HttpClient();
        $alumno->login('ana.garcia', 'alumno123', 'Alumnos');

        $r = $alumno->get('/gestion/planificacion_listar.php?anio=' . self::$anio);
        $this->assertSame(403, $r['status']);

        $r = $alumno->post('/gestion/planificacion_guardar.php', $this->datos('Contenidos cargados por un alumno.'));
        $this->assertSame(403, $r['status']);
    }

    // ── Auxiliares ───────────────────────────────────────────────────────

    private function datos(string $contenidos): array
    {
        return [
            'materia_id'    => self::$materiaId,
            'anio'          => self::$anio,
            'contenidos'    => $contenidos,
            'observaciones' => '',
        ];
    }

    private function buscarMateria(array $materias, int $materiaId): ?array
    {
        foreach ($materias as $m) {
            if ((int) $m['materia_id'] === $materiaId) {
                return $m;
            }
        }
        return null;
    }
}
