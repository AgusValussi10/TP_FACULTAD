<?php

/** GET admin → catálogo de tarifas con su valor actual (null si falta cargarlo). Parte 3. */

require_once __DIR__ . '/../lib/facturacion.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('GET');
require_once __DIR__ . '/../database/db_config.php';

responder_json(200, true, tarifas_catalogo($conn));
$conn->close();
