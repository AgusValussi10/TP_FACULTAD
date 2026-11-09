<?php

/**
 * Verificación de usuario y contraseña, compartida por la web (auth/login.php)
 * y la app móvil (app/api/login.php). No toca la sesión.
 *
 * Devuelve ['estado' => ..., 'usuario' => fila|null] con estado:
 * - 'credenciales': el usuario no existe o la contraseña no coincide;
 * - 'rol':          la contraseña es correcta pero el rol no puede entrar por ese acceso;
 * - 'suspendido':   la contraseña es correcta pero el usuario está suspendido (activo = 0);
 * - 'ok'.
 * Rol y suspensión solo se informan con la contraseña correcta, para no revelar usuarios.
 */
function autenticar_usuario(mysqli $conn, string $usuario, string $password, array $roles_permitidos): array
{
    $stmt = $conn->prepare("SELECT id, nombre, password_hash, rol, activo FROM usuarios WHERE usuario = ?");
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fila || !password_verify($password, $fila['password_hash'])) {
        return ['estado' => 'credenciales', 'usuario' => null];
    }
    unset($fila['password_hash']);
    if (!in_array($fila['rol'], $roles_permitidos, true)) {
        return ['estado' => 'rol', 'usuario' => $fila];
    }
    if ((int) $fila['activo'] !== 1) {
        return ['estado' => 'suspendido', 'usuario' => $fila];
    }
    return ['estado' => 'ok', 'usuario' => $fila];
}
