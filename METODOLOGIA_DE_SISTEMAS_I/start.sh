#!/bin/bash
set -e

echo "==> Initializing database schema..."
for i in 1 2 3 4 5 6 7 8 9 10; do
    php -d extension=mysqli init_db.php && break || true
    echo "    Retry $i/10 in 3s..."
    sleep 3
done

echo "==> Starting PHP on port $PORT"
exec php -d extension=mysqli -S 0.0.0.0:$PORT
