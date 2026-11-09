#!/bin/bash
# Arma una base de datos de prueba desde cero, en el mismo orden que
# documenta CLAUDE.md ("Orden de ejecución de schemas en BD nueva").
# Lo usa el pipeline de CI y también sirve para correr los tests en local.
#
# Variables (las mismas que lee database/db_config.php):
#   MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
set -euo pipefail

cd "$(dirname "$0")/.."

HOST="${MYSQLHOST:-127.0.0.1}"
PORT="${MYSQLPORT:-3306}"
USER="${MYSQLUSER:-root}"
PASS="${MYSQLPASSWORD:-}"
DB="${MYSQLDATABASE:-educar_db}"

MYSQL=(mysql --default-character-set=utf8 -h "$HOST" -P "$PORT" -u "$USER")
[ -n "$PASS" ] && MYSQL+=("-p$PASS")

echo "==> Recreando base $DB en $HOST:$PORT"
"${MYSQL[@]}" -e "DROP DATABASE IF EXISTS \`$DB\`; CREATE DATABASE \`$DB\` CHARACTER SET utf8;"

correr_sql() {
    # Los schema_sprint*.sql traen "USE educar_db;" para usarlos a mano: se quita
    # esa línea para que se apliquen siempre sobre $DB (p. ej. educar_test en local).
    echo "    SQL  $1"
    sed -E '/^[[:space:]]*USE[[:space:]]+[^;]+;[[:space:]]*$/Id' "$1" | "${MYSQL[@]}" "$DB"
}
correr_php() {
    # Los scripts de seed se pensaron para el navegador: se ejecutan por CLI
    # desde database/ (usan require_once 'db_config.php').
    echo "    PHP  $1"
    (cd database && php "$1" > /dev/null)
}

correr_sql database/schema.sql
correr_sql database/schema_sprint2.sql      # 1ra pasada: agrega rol 'enfermeria'
correr_php setup_demo.php
correr_sql database/schema_gestion.sql
correr_sql database/schema_sprint2.sql      # 2da pasada: seed padre_alumno
correr_sql database/schema_sprint3.sql
correr_sql database/schema_sprint4.sql
correr_php seed_datos.php
correr_sql database/schema_sprint5.sql
correr_sql database/schema_sprint6.sql      # RFG14: columnas nuevas de sanciones
correr_sql database/schema_parte3.sql       # Parte 3: tarifas, recorridos, facturas, pagos, comprobantes
echo "    PHP  init_db.php"
php init_db.php
correr_php seed_legajo.php                  # datos personales del legajo (necesita alumnos_datos de init_db)
correr_php seed_cuotas.php                  # RFG19: cuotas de demo con vencimientos relativos a hoy
correr_php seed_parte3.php                  # Parte 3: tarifas, facturas y pagos de demo (usa las cuotas de arriba)

echo "==> Base lista"
