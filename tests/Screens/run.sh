#!/bin/bash
# Builds one checkout, fills the screens database, serves it and takes the
# screenshots. usage: run.sh <checkout dir> <port> <out dir>
set -e
dir=$(cd "$1" && pwd); port=$2; out=$3
cd "$dir"

composer install --no-interaction --no-progress --prefer-dist -q

cp .env.example .env
db="$dir/database/screens.sqlite"
rm -f "$db"; touch "$db"
{
  echo ""
  echo "APP_ENV=local"
  echo "APP_DEBUG=true"
  echo "APP_URL=http://127.0.0.1:$port"
  echo "DB_CONNECTION=sqlite"
  echo "DB_DATABASE=$db"
  echo "SESSION_DRIVER=file"
  echo "CACHE_DRIVER=file"
  echo "CACHE_STORE=file"
  echo "QUEUE_CONNECTION=sync"
  echo "MAIL_MAILER=array"
} >> .env
php artisan key:generate --force -q
php artisan storage:link -q || true

php vendor/bin/phpunit -c tests/Screens/phpunit.xml
php artisan serve --host=127.0.0.1 --port="$port" > /tmp/serve-$port.log 2>&1 &
sleep 3

node "$GITHUB_WORKSPACE/tools/shoot.mjs" "http://127.0.0.1:$port" storage/screens/urls.json "$out"
