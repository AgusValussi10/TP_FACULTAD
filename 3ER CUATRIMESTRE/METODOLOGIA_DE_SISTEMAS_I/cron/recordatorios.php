<?php

/**
 * Cron de mails a las familias (RFGm07/RFGm08). Lo llama GitHub Actions todos los días
 * (.github/workflows/recordatorios.yml) con ?tipo=fin_de_mes | dia_20 y el token en el
 * header X-Cron-Token. Este endpoint decide si HOY corresponde mandar ese tipo.
 *
 * Protegido con la variable de entorno CRON_TOKEN (nunca en el código): sin variable
 * configurada o con un token distinto, responde 403.
 */

require_once __DIR__ . '/../lib/recordatorios.php';

errores_como_json();
$esperado = (string) getenv('CRON_TOKEN');
$recibido = (string) ($_SERVER['HTTP_X_CRON_TOKEN'] ?? '');
if ($esperado === '' || !hash_equals($esperado, $recibido)) {
    responder_json(403, false, null, 'Token inválido.');
    exit;
}

require_once __DIR__ . '/../database/db_config.php';
$tipo = (string) ($_GET['tipo'] ?? '');
try {
    $r = generar_recordatorios($conn, $tipo, date('Y-m-d'));
    $mensaje = $r['corresponde'] ? "Generados: {$r['generados']} (modo simulación)." : 'Hoy no corresponde enviar este tipo de mail.';
    responder_json(200, true, $r, $mensaje);
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
