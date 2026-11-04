<?php

use PHPUnit\Framework\TestCase;

/**
 * RFG06 — Exámenes recuperatorios.
 *
 * El docente programa, edita (carga la nota) y elimina recuperatorios de sus
 * materias; el admin solo los consulta. Reglas verificadas (asumidas, ver
 * CLAUDE.md): lunes a viernes, sin fechas pasadas al programar, la nota exige
 * un examen ya tomado, el estado sale de la nota y hay uno solo por
 * alumno/materia/período. Al cargar la nota se avisa al padre.
 *
 * Datos del seed: Matemática 3°A la dicta maria.rodriguez; miguel.torres y
 * ana.garcia son de 3°A (miguel es hijo de marta.torres); valentina.paz es de
 * 2°B; roberto.silva no dicta Matemática de 3°A. El seed no tiene
 * recuperatorios "Anual", así que los tests usan ese período.
 */
class RecuperatoriosTest extends TestCase
{
    private const DOCENTES = 'Docentes / Personal / Autoridades';
    private const PERIODO  = 'Anual';

    private static HttpClient $docente;
    private static HttpClient $admin;
    private static int $materiaId;
    private static int $miguelId;
    private static int $anaId;

    public static function setUpBeforeClass(): void
    {
        self::$materiaId = (int) Db::valor(
            "SELECT m.id FROM materias m JOIN cursos c ON c.id = m.curso_id
             JOIN usuarios d ON d.id = m.docente_id
             WHERE m.nombre = 'Matemática' AND c.nombre = '3°A' AND d.usuario = 'maria.rodriguez'"
        );
        self::$miguelId = self::idDe('miguel.torres');
        self::$anaId    = self::idDe('ana.garcia');

        // Cada corrida arranca sin los recuperatorios ni avisos que crean estos tests.
        Db::exec("DELETE FROM recuperatorios WHERE periodo = '" . self::PERIODO . "'");
        Db::exec("DELETE FROM notificaciones WHERE tipo = 'recuperatorio' AND mensaje LIKE '%(" . self::PERIODO . ")%'");

        self::$docente = new HttpClient();
        $login = self::$docente->login('maria.rodriguez', 'docente123', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del docente: ' . json_encode($login));

        self::$admin = new HttpClient();
        $login = self::$admin->login('admin', 'admin', self::DOCENTES);
        self::assertTrue($login['success'] ?? false, 'Login del admin: ' . json_encode($login));
    }

    // ── Flujo principal ───────────────────────────────────────────────────

    public function testElDocenteProgramaUnRecuperatorio(): int
    {
        $r = self::$docente->post('/gestion/recuperatorio_guardar.php', $this->datos(self::$miguelId, $this->dia('next monday')));

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertSame('pendiente', $r['json']['estado']);

        $id = (int) Db::valor(
            'SELECT id FROM recuperatorios WHERE alumno_id = ? AND materia_id = ? AND periodo = ?',
            'iis',
            self::$miguelId,
            self::$materiaId,
            self::PERIODO
        );
        $this->assertGreaterThan(0, $id);
        return $id;
    }

    /** @depends testElDocenteProgramaUnRecuperatorio */
    public function testRechazaDuplicadoDelMismoAlumnoMateriaYPeriodo(): void
    {
        $r = self::$docente->post('/gestion/recuperatorio_guardar.php', $this->datos(self::$miguelId, $this->dia('next tuesday')));

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('ya tiene un recuperatorio', $r['json']['message']);
        $this->assertSame(1, $this->recuperatoriosDe(self::$miguelId));
    }

    /** @depends testElDocenteProgramaUnRecuperatorio */
    public function testCargarNotaSieteLoApruebaYAvisaAlPadre(int $id): void
    {
        $datos = $this->datos(self::$miguelId, $this->dia('last monday'), '7') + ['id' => $id];
        $r = self::$docente->post('/gestion/recuperatorio_guardar.php', $datos);

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertSame('aprobado', $r['json']['estado']);
        $this->assertSame('aprobado', Db::valor('SELECT estado FROM recuperatorios WHERE id = ?', 'i', $id));
        $this->assertSame(1, $this->recuperatoriosDe(self::$miguelId), 'Editar no debe crear otro registro');

        $this->assertSame(1, (int) Db::valor(
            "SELECT COUNT(*) FROM notificaciones n JOIN usuarios p ON p.id = n.padre_id
             WHERE p.usuario = 'marta.torres' AND n.alumno_id = ? AND n.tipo = 'recuperatorio'
               AND n.mensaje LIKE '%nota 7%'",
            'i',
            self::$miguelId
        ), 'No se creó la notificación del resultado para el padre');
    }

    /** @depends testElDocenteProgramaUnRecuperatorio */
    public function testOtroDocenteNoPuedeEliminarlo(int $id): void
    {
        $otro = new HttpClient();
        $otro->login('roberto.silva', 'docente456', self::DOCENTES);

        $r = $otro->post('/gestion/recuperatorio_eliminar.php', ['id' => $id]);

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertSame(1, (int) Db::valor('SELECT COUNT(*) FROM recuperatorios WHERE id = ?', 'i', $id));
    }

    /** @depends testCargarNotaSieteLoApruebaYAvisaAlPadre */
    public function testElAdminListaConFiltroDeEstado(): void
    {
        $r = self::$admin->get('/gestion/recuperatorio_listar.php?estado=aprobado');

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertNotEmpty($r['json']['recuperatorios']);
        foreach ($r['json']['recuperatorios'] as $rec) {
            $this->assertSame('aprobado', $rec['estado']);
        }
        $this->assertContains(self::PERIODO, array_column($r['json']['recuperatorios'], 'periodo'));
        $this->assertSame(
            count($r['json']['recuperatorios']),
            $r['json']['resumen']['aprobado'],
            'Sin filtro de curso, el listado de aprobados coincide con el total de aprobados'
        );
    }

    /** @depends testElDocenteProgramaUnRecuperatorio */
    public function testElDocentePuedeEliminarElSuyo(int $id): void
    {
        $r = self::$docente->post('/gestion/recuperatorio_eliminar.php', ['id' => $id]);

        $this->assertTrue($r['json']['success'], $r['body']);
        $this->assertSame(0, (int) Db::valor('SELECT COUNT(*) FROM recuperatorios WHERE id = ?', 'i', $id));
    }

    // ── Validaciones ─────────────────────────────────────────────────────

    public function testRechazaSabado(): void
    {
        $r = self::$docente->post('/gestion/recuperatorio_guardar.php', $this->datos(self::$anaId, $this->dia('next saturday')));

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('lunes a viernes', $r['json']['message']);
        $this->assertSame(0, $this->recuperatoriosDe(self::$anaId));
    }

    public function testRechazaDomingo(): void
    {
        $r = self::$docente->post('/gestion/recuperatorio_guardar.php', $this->datos(self::$anaId, $this->dia('next sunday')));

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('lunes a viernes', $r['json']['message']);
    }

    public function testRechazaFechaPasadaAlProgramar(): void
    {
        $r = self::$docente->post('/gestion/recuperatorio_guardar.php', $this->datos(self::$anaId, $this->dia('last monday')));

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('anterior a hoy', $r['json']['message']);
        $this->assertSame(0, $this->recuperatoriosDe(self::$anaId));
    }

    public function testRechazaNotaDeUnExamenConFechaFutura(): void
    {
        $r = self::$docente->post('/gestion/recuperatorio_guardar.php', $this->datos(self::$anaId, $this->dia('next monday'), '7'));

        $this->assertFalse($r['json']['success'], $r['body']);
        $this->assertStringContainsString('todavía no se tomó', $r['json']['message']);
        $this->assertSame(0, $this->recuperatoriosDe(self::$anaId));
    }

    public function testRechazaAlumnoDeOtroCurso(): void
    {
        $valentina = self::idDe('valentina.paz');   // 2°B, la materia es de 3°A

        $r = self::$docente->post('/gestion/recuperatorio_guardar.php', $this->datos($valentina, $this->dia('next monday')));

        $this->assertSame(403, $r['status']);
        $this->assertSame(0, $this->recuperatoriosDe($valentina));
    }

    public function testElAdminNoPuedeGuardar(): void
    {
        $r = self::$admin->post('/gestion/recuperatorio_guardar.php', $this->datos(self::$anaId, $this->dia('next monday')));

        $this->assertSame(403, $r['status']);
        $this->assertSame(0, $this->recuperatoriosDe(self::$anaId));
    }

    // ── Auxiliares ───────────────────────────────────────────────────────

    /**
     * Fecha relativa a hoy en la zona horaria del sistema (la misma con la que
     * recuperatorio_guardar.php calcula "hoy"), para que los tests no venzan.
     */
    private function dia(string $relativo): string
    {
        $hoy = new DateTime('today', new DateTimeZone('America/Argentina/Buenos_Aires'));
        return $hoy->modify($relativo)->format('Y-m-d');
    }

    /** Lo mismo que envía el formulario "Programar / Editar" del docente. */
    private function datos(int $alumnoId, string $fecha, string $nota = ''): array
    {
        return [
            'alumno_id'  => $alumnoId,
            'materia_id' => self::$materiaId,
            'periodo'    => self::PERIODO,
            'fecha'      => $fecha,
            'turno'      => 'mañana',
            'nota'       => $nota,
        ];
    }

    private function recuperatoriosDe(int $alumnoId): int
    {
        return (int) Db::valor(
            'SELECT COUNT(*) FROM recuperatorios WHERE alumno_id = ? AND periodo = ?',
            'is',
            $alumnoId,
            self::PERIODO
        );
    }

    private static function idDe(string $usuario): int
    {
        return (int) Db::valor('SELECT id FROM usuarios WHERE usuario = ?', 's', $usuario);
    }
}
