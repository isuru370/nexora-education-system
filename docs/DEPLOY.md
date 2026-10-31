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
| 3. Basic data | First install only: roles, branch, super admin, grades, banks, class categories. **Every deploy:** adds missing banks (44) and bank branches (3,630, `database/seeders/data/bank_branches.csv`) |
| 4. Health checks | Core tables, features, **card mode** (warning until `nexora:card-mode` is run), roles, an active admin, a branch, every route → controller method exists, every controller starts, all views compile, storage + cache work, `/` and `/login` load, API returns 401 without login |
| 5. Caches | `config:cache`, `route:cache`, `view:cache` |
| 6. Parent Hub | Checks `PARENT_HUB_URL`, `PARENT_HUB_SECRET`, `INSTITUTE_CODE`, then sends this institute's details (code, name, `APP_URL`, contact email / mobile, logo, version) to the hub and confirms they were saved. Missing settings, a wrong secret or a hub that cannot be reached = **NOT READY** (parents could not log in). With `--check` nothing is sent. `php artisan parenthub:register` does only this step. |

### First install

Put these in `.env` before the first run:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.lk
SUPER_ADMIN_USERNAME=admin@your-domain.lk
SUPER_ADMIN_PASSWORD=a-strong-password

INSTITUTE_CODE=SHEI                      # unique per institute, never change it later
INSTITUTE_NAME="Savidya Higher Education Institute"
CONTACT_EMAIL=info@your-domain.lk
CONTACT_MOBILE=0771234567
INSTITUTE_LOGO=logo/logo.png             # under public/ (or a full URL)
PARENT_HUB_URL=https://hub.nexorait.lk/api/v1
PARENT_HUB_SECRET=same-as-the-hub
```

The `.env` is the master copy of the institute details: every deploy overwrites the name / contacts in the Parent Hub admin.

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

| Feature | Use |
|---|---|
| `courses` | Course management (only institutes that sell courses) |
| `welfare` | Teacher welfare: a monthly amount taken from teacher salaries (per branch) into the branch welfare fund (not institute income), its expenses and a PDF. Shown on the salary slip. |
| `landing_page` | **Only edu.nexorait.lk.** Shows the NexoraIT EDU product page on `/`. Off (every institute) = `/` opens the login page. |

There is no web page for this, so an institute cannot switch a feature on by itself. `nexora:deploy` shows which features are on.

For developers: `feature('courses')` in PHP, `@feature('courses') ... @endfeature` in Blade, `->middleware('feature:courses')` on routes (404 when off). The mobile apps read `GET /api/features` (also in the login and `/api/me` responses as `features`).

### Student ID card mode (per institute)

Each institute registers students **one** way. The developer chooses it on that institute's server (default `permanent`):

```bash
php artisan nexora:card-mode              # show the current mode
php artisan nexora:card-mode permanent    # register with a permanent ID card straight away
php artisan nexora:card-mode temporary    # register with a temporary card (TMP001...), permanent card later (Re Assign Student)
```

There is no web page for it. Permanent mode hides Temporary Registration, Temporary Cards and Re Assign Student (web and app); temporary mode hides the direct permanent registration. Attendance and payment scanning accept both kinds of ID in either mode.

Who can do what with ID cards (`App\Support\CardAccess`): generate = Super Admin; download / print and replace / lost / damaged / deactivate = Super Admin, Admin, Branch Admin; assign (registration, Re Assign Student) and view = also Cashier; teachers have no access.

### Never on a live server

`php artisan migrate:fresh` — deletes every table and all data.
