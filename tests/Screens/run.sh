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

# the port must be free: a worker of the run before (stuck on a slow page)
# can still hold it, and then this server cannot start
free_port() {
  fuser -k "$port/tcp" 2>/dev/null || true
  pkill -f "127.0.0.1:$port" 2>/dev/null || true
}
free_port; sleep 2
# several workers (one slow page does not block the others); started again
# if it stops, so one crash does not end the run
( while true; do
    PHP_CLI_SERVER_WORKERS=4 php artisan serve --no-reload --host=127.0.0.1 --port="$port" >> "/tmp/serve-$(basename "$dir").log" 2>&1
    echo "server stopped, starting again" >> "/tmp/serve-$(basename "$dir").log"; sleep 1
  done ) &
server=$!
up=0
for i in $(seq 1 60); do
  if curl -s -o /dev/null "http://127.0.0.1:$port/login"; then up=1; break; fi
  sleep 1
done
if [ "$up" != 1 ]; then
  echo "The server did not start on port $port:"
  tail -n 40 "/tmp/serve-$(basename "$dir").log" 2>/dev/null || true
  exit 1
fi

node "$GITHUB_WORKSPACE/tools/shoot.mjs" "http://127.0.0.1:$port" storage/screens/urls.json "$out"

# both runs use the same port (the URL shows on some pages)
kill $server 2>/dev/null || true; pkill -f "artisan serve" || true; free_port; sleep 2
