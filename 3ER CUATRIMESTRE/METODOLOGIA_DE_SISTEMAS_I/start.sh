#!/bin/bash
set -e

LISTEN_PORT="${PORT:-8080}"

echo "==> Initializing database schema..."
for i in 1 2 3 4 5 6 7 8 9 10; do
    php init_db.php && break || true
    echo "    Retry $i/10 in 3s..."
    sleep 3
done

echo "==> Starting PHP on port $LISTEN_PORT"
exec php -S 0.0.0.0:$LISTEN_PORT
