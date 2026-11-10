<?php

/**
 * Base de la API JSON de la app móvil (PWA). Mismo origen que la web: usa la
 * misma sesión PHP, helpers.php y la base; no hay tokens ni CORS.
 *
 * - Toda respuesta es {success, data, message} con el código HTTP correcto
 *   (401 sin sesión, 403 sin permiso, 405 método, 409/422 reglas de negocio).
 * - La app es solo para los roles padre y alumno.
 * - La cookie de sesión se renueva en cada pedido de la API (vence a los 30 min
 *   SIN USO, no a los 30 min del login). La web no cambia.
 * - Nada de esto se cachea (datos personales): Cache-Control no-store.
 */

require_once __DIR__ . '/../../lib/facturacion.php'; // incluye helpers.php, que abre la sesión
require_once __DIR__ . '/../../auth/autenticar.php';

const ROLES_APP = ['padre', 'alumno'];
const SESION_SEGUNDOS = 1800;
const PASSWORD_MIN = 8;
const PASSWORD_MAX = 72; // límite de bcrypt

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(404); // php -S sirve toda la carpeta: este archivo no es un endpoint
    exit;
}

header('Content-Type: application/json; charset=utf-8'); // los que devuelven PDF lo cambian
header('Cache-Control: no-store');
errores_como_json();
renovar_cookie_sesion();

/** Reenvía la cookie de sesión con 30 min más de vida (idle timeout). */
function renovar_cookie_sesion(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'; // Render termina el TLS en su proxy
    setcookie(session_name(), session_id(), [
        'expires'  => time() + SESION_SEGUNDOS,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Usuario de la sesión, o corta con 401 (sin sesión) / 403 (rol que no usa la app).
 * Libera el bloqueo de la sesión: los endpoints solo la leen.
 *
 * @return array{id:int, rol:string, nombre:string}
 */
function api_sesion(): array
{
    $id  = (int) ($_SESSION['usuario_id'] ?? 0);
    $rol = (string) ($_SESSION['rol'] ?? '');
    if ($id <= 0) {
        responder_json(401, false, null, 'Tu sesión venció. Volvé a ingresar.');
        exit;
    }
    if (!in_array($rol, ROLES_APP, true)) {
        responder_json(403, false, null, 'La app es para familias y alumnos.');
        exit;
    }
    $sesion = ['id' => $id, 'rol' => $rol, 'nombre' => (string) ($_SESSION['nombre'] ?? '')];
    session_write_close();
    return $sesion;
}

/** Los pagos los hace la familia: el alumno recibe 403. */
function api_solo_padre(array $sesion): void
{
    if ($sesion['rol'] !== 'padre') {
        responder_json(403, false, null, 'Los pagos los hace la familia desde su cuenta.');
        exit;
    }
}

/** Ejecuta $fn y responde su resultado; una ReglaNegocioException se responde con su código y mensaje. */
function api_responder(callable $fn, string $mensaje = ''): void
{
    try {
        responder_json(200, true, $fn(), $mensaje);
    } catch (ReglaNegocioException $e) {
        responder_json($e->getCode(), false, null, $e->getMessage());
    }
}

/** Hijos del padre, o el propio alumno, con curso y nivel. */
function api_alumnos(mysqli $conn, array $sesion): array
{
    $sql = "SELECT u.id, u.nombre, c.nombre AS curso, c.nivel_educativo AS nivel
            FROM usuarios u
            LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id
            LEFT JOIN cursos c ON c.id = ac.curso_id
            WHERE " . ($sesion['rol'] === 'padre'
                ? 'u.id IN (SELECT alumno_id FROM padre_alumno WHERE padre_id = ?)'
                : 'u.id = ?') . "
            ORDER BY u.nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $sesion['id']);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return array_map(fn ($a) => [
        'id' => (int) $a['id'], 'nombre' => $a['nombre'], 'curso' => $a['curso'], 'nivel' => $a['nivel'],
    ], $filas);
}

/**
 * alumno_id con el que opera el pedido. El alumno solo puede ser él mismo; el
 * padre, solo uno de sus hijos. Si no, 403 (exista o no el alumno pedido).
 */
function api_alumno_permitido(mysqli $conn, array $sesion, $alumno_id): int
{
    $alumno_id = (int) $alumno_id;
    if ($sesion['rol'] === 'alumno') {
        if ($alumno_id !== 0 && $alumno_id !== $sesion['id']) {
            responder_json(403, false, null, 'Solo podés ver tu propia información.');
            exit;
        }
        return $sesion['id'];
    }
    if ($alumno_id <= 0 || !alumno_pertenece_a_padre($conn, $alumno_id, $sesion['id'])) {
        responder_json(403, false, null, 'El alumno no está vinculado a tu cuenta.');
        exit;
    }
    return $alumno_id;
}
