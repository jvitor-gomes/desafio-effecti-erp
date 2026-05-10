#!/bin/sh
set -e

echo "Instalando dependencias PHP..."
composer install --no-interaction --prefer-dist

if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
  echo "Executando migrations..."
  vendor/bin/phinx migrate -c phinx.php
fi

if [ "${AUTO_SEED:-true}" = "true" ]; then
  echo "Executando seed..."
  vendor/bin/phinx seed:run -c phinx.php
fi

echo "Iniciando PHP-FPM..."
exec "$@"
