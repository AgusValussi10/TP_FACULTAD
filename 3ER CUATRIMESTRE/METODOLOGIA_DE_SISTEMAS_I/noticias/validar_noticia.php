<?php
/**
 * Valida y normaliza los datos de una noticia (usado por guardar.php y actualizar.php).
 * Devuelve ['error' => string] si algún dato es inválido, o
 * ['error' => null, 'categoria' => ..., 'estado' => ..., 'fecha_pub_value' => ...] si es válido.
 */
function validarNoticia(array $datos): array
{
    $titulo     = $datos['titulo']     ?? '';
    $contenido  = $datos['contenido']  ?? '';
    $categoria  = $datos['categoria']  ?? 'general';
    $estado     = $datos['estado']     ?? 'borrador';
    $imagen_url = $datos['imagen_url'] ?? '';
    $fecha_pub  = $datos['fecha_pub']  ?? '';

    if (mb_strlen($titulo) < 3 || mb_strlen($titulo) > 200) {
        return ['error' => 'El título debe tener entre 3 y 200 caracteres.'];
    }
    if (mb_strlen($contenido) < 10) {
        return ['error' => 'El contenido debe tener al menos 10 caracteres.'];
    }

    $categorias_validas = ['institucional', 'academica', 'deportiva', 'cultural', 'general'];
    if (!in_array($categoria, $categorias_validas)) $categoria = 'general';

    $estados_validos = ['borrador', 'publicada', 'archivada'];
    if (!in_array($estado, $estados_validos)) $estado = 'borrador';

    if ($imagen_url !== '' && !filter_var($imagen_url, FILTER_VALIDATE_URL) && !preg_match('/^assets\/[a-zA-Z0-9._-]+$/', $imagen_url)) {
        return ['error' => 'La URL de imagen no es válida.'];
    }

    $fecha_pub_value = null;
    if ($fecha_pub !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $fecha_pub);
        if ($d && $d->format('Y-m-d') === $fecha_pub) {
            $fecha_pub_value = $fecha_pub;
        }
    }

    return [
        'error'           => null,
        'categoria'       => $categoria,
        'estado'          => $estado,
        'fecha_pub_value' => $fecha_pub_value,
    ];
}
