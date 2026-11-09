<?php

use PHPUnit\Framework\TestCase;

/**
 * Parte 3 — App móvil: ingreso, cierre de sesión y cambio de contraseña (RFGm01/RFGm02).
 *
 * La app es solo para padre y alumno. laura.martinez tiene dos hijos en la demo
 * (ana.garcia y matias.vega, seed_parte3.php); carlos.lopez es de otra familia.
 * bruno.flores se suspende durante un test y se reactiva al terminar.
 */
class AppAuthTest extends TestCase
{
    private const API = '/app/api/';

    public static function tearDownAfterClass(): void
    {
        Db::exec("UPDATE usuarios SET activo = 1 WHERE usuario = 'bruno.flores'");
        // Por si el test de contraseña cortó a mitad de camino.
        $hash = password_hash('alumno456', PASSWORD_DEFAULT);
        $stmt = Db::conn()->prepare("UPDATE usuarios SET password_hash = ? WHERE usuario = 'carlos.lopez'");
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->close();
    }

    // ── Ingreso ─────────────────────────────────────────────────────────

    public function testElPadreIngresaYVeASusDosHijos(): void
    {
        $c = new HttpClient();
        $r = $this->login($c, 'laura.martinez', 'padre123');

        $this->assertSame(200, $r['status'], $r['body']);
        $this->assertTrue($r['json']['success']);
        $this->assertSame('padre', $r['json']['data']['usuario']['rol']);
        $nombres = array_column($r['json']['data']['alumnos'], 'id');
        $this->assertEqualsCanonicalizing([$this->idDe('ana.garcia'), $this->idDe('matias.vega')], $nombres);

        $sesion = $c->get(self::API . 'sesion.php');
        $this->assertSame(200, $sesion['status']);
        $this->assertSame('laura.martinez', Db::valor('SELECT usuario FROM usuarios WHERE id = ?', 'i', $sesion['json']['data']['usuario']['id']));
    }

    public function testElAlumnoIngresaYSoloSeVeASiMismo(): void
    {
        $c = new HttpClient();
        $r = $this->login($c, 'ana.garcia', 'alumno123');

        $this->assertSame(200, $r['status'], $r['body']);
        $this->assertSame('alumno', $r['json']['data']['usuario']['rol']);
        $this->assertSame([$this->idDe('ana.garcia')], array_column($r['json']['data']['alumnos'], 'id'));
    }

    public function testDocenteYAdminSonRechazadosConMensajeClaro(): void
    {
        foreach ([['maria.rodriguez', 'docente123'], ['admin', 'admin'], ['sandra.benitez', 'enfermeria123']] as [$usuario, $pass]) {
            $c = new HttpClient();
            $r = $this->login($c, $usuario, $pass);
            $this->assertSame(403, $r['status'], $usuario);
            $this->assertStringContainsString('familias y alumnos', $r['json']['message']);
            $this->assertSame(401, $c->get(self::API . 'sesion.php')['status'], "$usuario no queda logueado");
        }
    }

    public function testLaContrasenaIncorrectaEsRechazada(): void
    {
        $c = new HttpClient();
        $r = $this->login($c, 'laura.martinez', 'otra-cosa');
        $this->assertSame(401, $r['status']);
        $this->assertSame('Usuario o contraseña incorrectos.', $r['json']['message']);

        // Un usuario inexistente da el mismo mensaje (no se revela qué usuarios existen).
        $this->assertSame('Usuario o contraseña incorrectos.', $this->login(new HttpClient(), 'no.existe', 'x12345678')['json']['message']);
    }

    public function testUnUsuarioSuspendidoEsRechazado(): void
    {
        Db::exec("UPDATE usuarios SET activo = 0 WHERE usuario = 'bruno.flores'");
        $r = $this->login(new HttpClient(), 'bruno.flores', 'alumno444');
        Db::exec("UPDATE usuarios SET activo = 1 WHERE usuario = 'bruno.flores'");

        $this->assertSame(403, $r['status']);
        $this->assertStringContainsString('suspendido', $r['json']['message']);
    }

    public function testLaWebSigueIgual(): void
    {
        // Misma verificación compartida: la web acepta y rechaza como antes.
        $ok = (new HttpClient())->login('maria.rodriguez', 'docente123', 'Docentes / Personal / Autoridades');
        $this->assertTrue($ok['success']);
        $mal = (new HttpClient())->login('laura.martinez', 'padre123', 'Alumnos');
        $this->assertFalse($mal['success']);
        $this->assertSame('Usuario o contraseña incorrectos.', $mal['message']);
    }

    // ── Sesión ──────────────────────────────────────────────────────────

    public function testSinSesionLaApiResponde401(): void
    {
        $c = new HttpClient();
        $this->assertSame(401, $c->get(self::API . 'sesion.php')['status']);
        $this->assertSame(401, $c->get(self::API . 'inicio.php')['status']);
    }

    public function testLaApiRenuevaLaCookieDeSesionEnCadaPedido(): void
    {
        $c = new HttpClient();
        $this->login($c, 'ana.garcia', 'alumno123');
        $r = $c->get(self::API . 'sesion.php');
        $cookie = array_values(array_filter($r['headers'], fn ($h) => stripos($h, 'Set-Cookie: PHPSESSID=') === 0));
        $this->assertNotEmpty($cookie, 'La respuesta reenvía la cookie de sesión');
        $this->assertStringContainsStringIgnoringCase('Max-Age=1800', end($cookie));
        $this->assertStringContainsStringIgnoringCase('HttpOnly', end($cookie));
    }

    public function testLogoutCierraLaSesion(): void
    {
        $c = new HttpClient();
        $this->login($c, 'laura.martinez', 'padre123');
        $this->assertSame(200, $c->get(self::API . 'sesion.php')['status']);

        $r = $c->post(self::API . 'logout.php', []);
        $this->assertSame(200, $r['status']);
        $this->assertSame(401, $c->get(self::API . 'sesion.php')['status']);
    }

    // ── Inicio y permisos ───────────────────────────────────────────────

    public function testInicioDelHijoYNoDeOtroAlumno(): void
    {
        $padre = new HttpClient();
        $this->login($padre, 'laura.martinez', 'padre123');
        $r = $padre->get(self::API . 'inicio.php?alumno_id=' . $this->idDe('ana.garcia'));
        $this->assertSame(200, $r['status'], $r['body']);
        $this->assertArrayHasKey('deuda', $r['json']['data']);
        $this->assertNotEmpty($r['json']['data']['deportes'], 'ana tiene deportes en la demo');

        $this->assertSame(403, $padre->get(self::API . 'inicio.php?alumno_id=' . $this->idDe('carlos.lopez'))['status']);
        $this->assertSame(403, $padre->get(self::API . 'inicio.php')['status'], 'El padre tiene que indicar el hijo');

        $alumno = new HttpClient();
        $this->login($alumno, 'ana.garcia', 'alumno123');
        $this->assertSame(200, $alumno->get(self::API . 'inicio.php')['status']);
        $this->assertSame(403, $alumno->get(self::API . 'inicio.php?alumno_id=' . $this->idDe('carlos.lopez'))['status']);
    }

    // ── Cambio de contraseña ────────────────────────────────────────────

    public function testCambiarLaContrasena(): void
    {
        $c = new HttpClient();
        $this->login($c, 'carlos.lopez', 'alumno456');
        $url = self::API . 'password.php';

        $this->assertSame(422, $c->post($url, ['actual' => 'incorrecta', 'nueva' => 'NuevaClave1', 'confirmacion' => 'NuevaClave1'])['status']);
        $this->assertSame(422, $c->post($url, ['actual' => 'alumno456', 'nueva' => 'corta', 'confirmacion' => 'corta'])['status']);
        $this->assertSame(422, $c->post($url, ['actual' => 'alumno456', 'nueva' => 'NuevaClave1', 'confirmacion' => 'OtraClave1'])['status']);

        $ok = $c->post($url, ['actual' => 'alumno456', 'nueva' => 'NuevaClave1', 'confirmacion' => 'NuevaClave1']);
        $this->assertSame(200, $ok['status'], $ok['body']);
        $this->assertSame(401, $this->login(new HttpClient(), 'carlos.lopez', 'alumno456')['status'], 'La vieja ya no sirve');
        $this->assertSame(200, $this->login(new HttpClient(), 'carlos.lopez', 'NuevaClave1')['status'], 'La nueva sí');

        // Se restaura la contraseña de demo por el mismo endpoint.
        $vuelta = $c->post($url, ['actual' => 'NuevaClave1', 'nueva' => 'alumno456', 'confirmacion' => 'alumno456']);
        $this->assertSame(200, $vuelta['status'], $vuelta['body']);
    }

    public function testCambiarLaContrasenaSinSesionDa401(): void
    {
        $r = (new HttpClient())->post(self::API . 'password.php', ['actual' => 'a', 'nueva' => 'b', 'confirmacion' => 'b']);
        $this->assertSame(401, $r['status']);
    }

    // ── Auxiliares ──────────────────────────────────────────────────────

    private function login(HttpClient $c, string $usuario, string $password): array
    {
        return $c->post(self::API . 'login.php', ['usuario' => $usuario, 'password' => $password]);
    }

    private function idDe(string $usuario): int
    {
        return (int) Db::valor('SELECT id FROM usuarios WHERE usuario = ?', 's', $usuario);
    }
}
