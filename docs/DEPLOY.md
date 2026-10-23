# Deploy NexoraEDU

## Every deploy (first install and updates)

```bash
git pull                                   # or upload the new files
composer install --no-dev --optimize-autoloader
php artisan nexora:deploy
```

`nexora:deploy` does everything and ends with either

```
 ✔  READY FOR PRODUCTION
```

or `✖ NOT READY FOR PRODUCTION` with a list of what to fix. Exit code is 0 / 1, so it can also be used in a CI script.

| Step | What it does |
|---|---|
| 1. Server and .env | PHP version + extensions, APP_KEY, APP_ENV=production, APP_DEBUG=false, APP_URL (https), writable folders, database connection, mail, `public/storage` link (created if missing) |
| 2. Database tables | Runs only the new migrations (`migrate --force`) and lists them |
| 3. Basic data | First install only: roles, branch, super admin, grades, banks, class categories |
| 4. Health checks | Core tables, roles, an active admin, a branch, every route → controller method exists, every controller starts, all views compile, storage + cache work, `/` and `/login` load, API returns 401 without login |
| 5. Caches | `config:cache`, `route:cache`, `view:cache` |

### First install

Put these in `.env` before the first run:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.lk
SUPER_ADMIN_USERNAME=admin@your-domain.lk
SUPER_ADMIN_PASSWORD=a-strong-password
```

then `php artisan key:generate` (only if `APP_KEY` is empty) and `php artisan nexora:deploy`.

### Options

| Option | Use |
|---|---|
| `--check` | Only check, change nothing (good before an update) |
| `--maintenance` | Show the maintenance page while migrating |
| `--seed` | Run the seeders again (resets the super admin password to the `.env` value) |
| `--no-optimize` | Skip the cache rebuild |

### Cron (once per server)

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Runs the daily backup, class status updates and expired-student checks.

### Features for one institute only

Every institute runs the same code. Optional features (listed in `config/features.php`) are **off** until switched on in that institute's server:

```bash
php artisan nexora:feature                 # list: which are ON here
php artisan nexora:feature courses on
php artisan nexora:feature courses off
```

There is no web page for this, so an institute cannot switch a feature on by itself. `nexora:deploy` shows which features are on.

For developers: `feature('courses')` in PHP, `@feature('courses') ... @endfeature` in Blade, `->middleware('feature:courses')` on routes (404 when off). The mobile apps read `GET /api/features` (also in the login and `/api/me` responses as `features`).

### Never on a live server

`php artisan migrate:fresh` — deletes every table and all data.
