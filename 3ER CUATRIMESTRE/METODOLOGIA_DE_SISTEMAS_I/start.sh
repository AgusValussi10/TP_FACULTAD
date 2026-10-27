#!/bin/bash
set -e

LISTEN_PORT="${PORT:-8080}"

echo "==> Initializing database schema..."
for i in 1 2 3 4 5 6 7 8 9 10; do
    php init_db.php && break || true
    echo "    Retry $i/10 in 3s..."
    sleep 3
done

# El servidor embebido de PHP atiende de a UN pedido por vez: con varios
# usuarios los pedidos hacen fila mientras se espera a la base remota.
# Con workers atiende varios en paralelo (Linux, PHP >= 7.4). Se puede
# ajustar con la variable de entorno PHP_CLI_SERVER_WORKERS en Render.
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

echo "==> Starting PHP on port $LISTEN_PORT ($PHP_CLI_SERVER_WORKERS workers)"
exec php -S 0.0.0.0:$LISTEN_PORT
