<?php
/**
 * Arranque de los tests de integración.
 *
 * Los endpoints del sistema son scripts PHP sueltos que leen la sesión, así que
 * se prueban como los usa el navegador: por HTTP, con cookies de sesión.
 * Si no se pasa TEST_BASE_URL, se levanta el servidor embebido de PHP sobre la
 * carpeta del proyecto y se apaga al terminar.
 *
 * Requiere una base ya preparada con ci/preparar_bd.sh.
 */

require_once __DIR__ . '/HttpClient.php';
require_once __DIR__ . '/Db.php';

$baseUrl = getenv('TEST_BASE_URL') ?: '';

if ($baseUrl === '') {
    $puerto  = (int) (getenv('TEST_PORT') ?: 8099);
    $docroot = realpath(__DIR__ . '/..');
    $log     = sys_get_temp_dir() . '/php-server-tests.log';

    $proceso = proc_open(
        [PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', $docroot],
        [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']],
        $pipes,
        $docroot
    );
    if (!is_resource($proceso)) {
        fwrite(STDERR, "No se pudo iniciar el servidor PHP\n");
        exit(1);
    }

    // Espera a que el puerto responda (máx. 10 s).
    $listo = false;
    for ($i = 0; $i < 100; $i++) {
        $s = @fsockopen('127.0.0.1', $puerto);
        if ($s) {
            fclose($s);
            $listo = true;
            break;
        }
        usleep(100000);
    }
    if (!$listo) {
        fwrite(STDERR, "El servidor PHP no respondió en el puerto $puerto (ver $log)\n");
        exit(1);
    }

    register_shutdown_function(function () use ($proceso) {
        proc_terminate($proceso);
        proc_close($proceso);
    });

    $baseUrl = "http://127.0.0.1:$puerto";
}

define('TEST_BASE_URL', rtrim($baseUrl, '/'));
