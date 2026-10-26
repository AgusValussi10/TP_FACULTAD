#!/bin/bash
# Corre en la máquina local las mismas etapas que el pipeline de GitHub
# Actions (lint → base de prueba → tests) y mide cuánto tarda cada una.
# Uso:  MYSQLHOST=127.0.0.1 MYSQLUSER=root bash ci/pipeline_local.sh
set -uo pipefail
cd "$(dirname "$0")/.."

ahora() { date +%s.%N; }
dur()   { awk -v a="$1" -v b="$2" 'BEGIN{printf "%.2f", b-a}'; }

t0=$(ahora)

# 1. Lint
errores=0; total=0
while IFS= read -r -d '' f; do
    total=$((total+1))
    php -l "$f" > /dev/null 2>&1 || { echo "  ✗ $f"; errores=$((errores+1)); }
done < <(find . -name '*.php' -not -path './lib/fpdf/*' -print0)
t1=$(ahora)
echo "1 · Lint:   $total archivos, $errores con errores ($(dur "$t0" "$t1") s)"
[ "$errores" -eq 0 ] || { echo "PIPELINE FALLÓ en Lint"; exit 1; }

# 2. Base de prueba
bash ci/preparar_bd.sh > /tmp/preparar_bd.log 2>&1 || { echo "PIPELINE FALLÓ preparando la base (ver /tmp/preparar_bd.log)"; exit 1; }
t2=$(ahora)
echo "2a· Base:   lista ($(dur "$t1" "$t2") s)"

# 3. Tests
phpunit --log-junit reporte-tests.xml > /tmp/phpunit.log 2>&1
rc=$?
t3=$(ahora)
resumen=$(grep -E "^(OK|Tests:|FAILURES)" /tmp/phpunit.log | tail -1)
echo "2b· Tests:  $resumen ($(dur "$t2" "$t3") s)"
echo "Total: $(dur "$t0" "$t3") s"
if [ $rc -ne 0 ]; then
    grep -E "^[0-9]+\) " /tmp/phpunit.log | sed 's/^/  ✗ /'
    echo "PIPELINE FALLÓ en Tests"
    exit 1
fi
echo "PIPELINE OK"
