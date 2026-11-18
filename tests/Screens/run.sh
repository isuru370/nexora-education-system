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
# several workers (one slow page does not block the others); started again
# if it stops, so one crash does not end the run
( while true; do
    PHP_CLI_SERVER_WORKERS=4 php artisan serve --no-reload --host=127.0.0.1 --port="$port" >> "/tmp/serve-$(basename "$dir").log" 2>&1
    echo "server stopped, starting again" >> "/tmp/serve-$(basename "$dir").log"; sleep 1
  done ) &
server=$!
for i in $(seq 1 30); do curl -s -o /dev/null "http://127.0.0.1:$port/login" && break; sleep 1; done

node "$GITHUB_WORKSPACE/tools/shoot.mjs" "http://127.0.0.1:$port" storage/screens/urls.json "$out"

# both runs use the same port (the URL shows on some pages)
kill $server 2>/dev/null || true; pkill -f "artisan serve" || true; pkill -f "php -S 127.0.0.1:$port" || true; sleep 1
