<?php

namespace App\Services\System;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Checks used by `php artisan nexora:deploy`.
 *
 * Every check returns ['status' => ok|warn|fail, 'message' => string].
 *  - fail = the system must not go live like this
 *  - warn = works, but something should be fixed
 */
class DeployCheckService
{
    public const OK = 'ok';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    /** Tables the system cannot run without. */
    public const CORE_TABLES = [
        'users', 'user_types', 'system_users', 'branches', 'branch_users',
        'students', 'teachers', 'grades', 'subjects', 'student_classes',
        'class_categories', 'class_category_fees', 'class_category_fee_options',
        'student_class_enrollments', 'class_halls', 'class_schedule_patterns',
        'class_schedules', 'student_attendances', 'payments', 'admissions',
        'admission_payments', 'receipt_counters', 'student_cards', 'exams',
        'student_results', 'personal_access_tokens', 'activity_logs',
    ];

    public const USER_TYPES = ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER', 'TEACHER'];

    public const PHP_EXTENSIONS = [
        'pdo_mysql' => true, 'mbstring' => true, 'openssl' => true, 'tokenizer' => true,
        'xml' => true, 'ctype' => true, 'json' => true, 'fileinfo' => true, 'curl' => true,
        // needed for images / PDF / Excel, but the rest of the system works without them
        'gd' => false, 'zip' => false,
    ];

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    */

    public function environmentChecks(): array
    {
        $isProduction = app()->environment('production');

        $checks = [];

        $checks['PHP version'] = version_compare(PHP_VERSION, '8.0.0', '>=')
            ? $this->ok('PHP ' . PHP_VERSION)
            : $this->warn('PHP ' . PHP_VERSION . ' - use PHP 8.0 or newer');

        $missing = collect(self::PHP_EXTENSIONS)->keys()->reject(fn ($ext) => extension_loaded($ext));
        $missingRequired = $missing->filter(fn ($ext) => self::PHP_EXTENSIONS[$ext]);

        $checks['PHP extensions'] = match (true) {
            $missingRequired->isNotEmpty() => $this->fail('Missing: ' . $missingRequired->implode(', ')),
            $missing->isNotEmpty() => $this->warn('Missing: ' . $missing->implode(', ') . ' (images / PDF / Excel)'),
            default => $this->ok('All required extensions loaded'),
        };

        $checks['APP_KEY'] = config('app.key')
            ? $this->ok('Set')
            : $this->fail('Empty - run: php artisan key:generate');

        $checks['APP_ENV'] = $isProduction
            ? $this->ok('production')
            : $this->warn(app()->environment() . ' - set APP_ENV=production on the live server');

        $checks['APP_DEBUG'] = match (true) {
            !config('app.debug') => $this->ok('false'),
            $isProduction => $this->fail('true - errors would show passwords and keys. Set APP_DEBUG=false'),
            default => $this->warn('true - set APP_DEBUG=false on the live server'),
        };

        $url = (string) config('app.url');
        $checks['APP_URL'] = match (true) {
            $url === '' || Str::contains($url, ['localhost', '127.0.0.1']) => $this->warn(($url ?: 'empty') . ' - set your real domain'),
            !Str::startsWith($url, 'https://') => $this->warn($url . ' - use https://'),
            default => $this->ok($url),
        };

        $checks['Timezone'] = $this->ok((string) config('app.timezone'));

        $notWritable = collect([
            'storage/app' => storage_path('app'),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ])->reject(fn ($path) => is_dir($path) && is_writable($path))->keys();

        $checks['Writable folders'] = $notWritable->isEmpty()
            ? $this->ok('storage, bootstrap/cache')
            : $this->fail('Not writable: ' . $notWritable->implode(', ') . ' - run: chmod -R 775 storage bootstrap/cache');

        $checks['Database connection'] = $this->databaseConnection();

        $mailer = (string) config('mail.default');
        $checks['Mail'] = in_array($mailer, ['log', 'array'], true)
            ? $this->warn("MAIL_MAILER={$mailer} - password reset emails will not be sent")
            : $this->ok($mailer);

        return $checks;
    }

    public function databaseConnection(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->ok(config('database.default') . ' / ' . DB::connection()->getDatabaseName());
        } catch (Throwable $e) {
            return $this->fail('Cannot connect: ' . Str::limit($e->getMessage(), 160) . ' - check DB_* in .env');
        }
    }

    public function storageLink(): array
    {
        $link = public_path('storage');

        if (file_exists($link)) {
            return $this->ok('public/storage exists');
        }

        try {
            Artisan::call('storage:link');
        } catch (Throwable $e) {
            // reported below
        }

        return file_exists($link)
            ? $this->ok('Created public/storage link')
            : $this->warn('public/storage missing - run: php artisan storage:link (student photos will not show)');
    }

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    /** Migration file names that have not run yet. */
    public function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles([database_path('migrations')]);

        if (!$migrator->repositoryExists()) {
            return array_keys($files);
        }

        $ran = $migrator->getRepository()->getRan();

        return array_values(array_diff(array_keys($files), $ran));
    }

    /** True for a fresh install (no user types yet). */
    public function needsFirstSeed(): bool
    {
        return !Schema::hasTable('user_types') || DB::table('user_types')->count() === 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Health checks (after migrate)
    |--------------------------------------------------------------------------
    */

    public function healthChecks(): array
    {
        $checks = [];

        $missingTables = collect(self::CORE_TABLES)->reject(fn ($t) => Schema::hasTable($t));
        $checks['Core tables'] = $missingTables->isEmpty()
            ? $this->ok(count(self::CORE_TABLES) . ' tables present')
            : $this->fail('Missing: ' . $missingTables->implode(', '));

        $checks['Pending migrations'] = ($pending = $this->pendingMigrations())
            ? $this->fail(count($pending) . ' not run: ' . Str::limit(implode(', ', $pending), 160))
            : $this->ok('None');

        $checks['User roles'] = $this->safe(function () {
            $codes = DB::table('user_types')->pluck('code')->map(fn ($c) => strtoupper($c));
            $missing = collect(self::USER_TYPES)->diff($codes);

            return $missing->isEmpty()
                ? $this->ok(implode(', ', self::USER_TYPES))
                : $this->fail('Missing: ' . $missing->implode(', ') . ' - run: php artisan nexora:deploy --seed');
        });

        $checks['Admin account'] = $this->safe(function () {
            $count = DB::table('users')
                ->join('user_types', 'user_types.id', '=', 'users.user_type_id')
                ->whereIn(DB::raw('UPPER(user_types.code)'), ['SUPER_ADMIN', 'ADMIN'])
                ->where('users.is_active', true)
                ->whereNull('users.deleted_at')
                ->count();

            return $count > 0
                ? $this->ok($count . ' active admin account(s)')
                : $this->fail('No active admin - set SUPER_ADMIN_USERNAME / SUPER_ADMIN_PASSWORD and run with --seed');
        });

        $checks['Branches'] = $this->safe(function () {
            $count = DB::table('branches')->where('is_active', true)->whereNull('deleted_at')->count();

            return $count > 0
                ? $this->ok($count . ' active branch(es)')
                : $this->warn('No active branch - staff cannot work until a branch exists');
        });

        $checks['Routes'] = $this->routeCheck();
        $checks['Controllers'] = $this->controllerCheck();

        $checks['Views'] = $this->safe(function () {
            Artisan::call('view:cache');

            return $this->ok('All Blade views compile');
        });

        $checks['Storage'] = $this->safe(function () {
            $file = 'deploy-check-' . Str::random(8) . '.txt';
            Storage::disk('local')->put($file, 'ok');
            $read = Storage::disk('local')->get($file);
            Storage::disk('local')->delete($file);

            return $read === 'ok' ? $this->ok('Write / read / delete') : $this->fail('Read back failed');
        });

        $checks['Cache'] = $this->safe(function () {
            $key = 'deploy-check-' . Str::random(8);
            Cache::put($key, 'ok', 30);
            $value = Cache::get($key);
            Cache::forget($key);

            return $value === 'ok' ? $this->ok(config('cache.default')) : $this->fail('Cache did not return the value');
        });

        foreach ($this->httpSmokeTests() as $name => $result) {
            $checks[$name] = $result;
        }

        return $checks;
    }

    /** Every route points to a controller method that exists. */
    public function routeCheck(): array
    {
        $broken = [];
        $count = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $count++;
            $uses = $route->getAction('uses');

            if (!is_string($uses)) {
                continue; // closure
            }

            [$class, $method] = Str::contains($uses, '@') ? explode('@', $uses, 2) : [$uses, '__invoke'];

            if (!class_exists($class)) {
                $broken[] = $route->uri() . " ({$class} not found)";
            } elseif (!method_exists($class, $method)) {
                $broken[] = $route->uri() . " ({$method} missing)";
            }
        }

        return $broken
            ? $this->fail(count($broken) . ' broken: ' . Str::limit(implode('; ', $broken), 220))
            : $this->ok($count . ' routes OK');
    }

    /** Every controller can be created (catches missing service classes / imports). */
    public function controllerCheck(): array
    {
        $classes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => $r->getAction('uses'))
            ->filter(fn ($u) => is_string($u))
            ->map(fn ($u) => explode('@', $u)[0])
            ->unique()
            ->filter(fn ($c) => class_exists($c));

        $broken = [];

        foreach ($classes as $class) {
            try {
                app()->make($class);
            } catch (Throwable $e) {
                $broken[] = class_basename($class) . ': ' . Str::limit($e->getMessage(), 90);
            }
        }

        return $broken
            ? $this->fail(count($broken) . ' cannot start: ' . Str::limit(implode('; ', $broken), 260))
            : $this->ok($classes->count() . ' controllers OK');
    }

    /** Real requests through the app (no login). */
    public function httpSmokeTests(): array
    {
        $tests = [
            'Page: home (/)' => ['GET', '/', [], [200], 'text/html'],
            'Page: login' => ['GET', '/login', [], [200], 'text/html'],
            'API: protected route' => ['GET', '/api/me', [], [401], 'application/json'],
            'API: wrong login' => ['POST', '/api/login', ['email' => 'deploy-check@invalid.local', 'password' => 'wrong-password', 'device_name' => 'deploy-check'], [401, 403, 422], 'application/json'],
        ];

        $results = [];

        foreach ($tests as $name => [$method, $uri, $data, $expected, $accept]) {
            $results[$name] = $this->safe(function () use ($method, $uri, $data, $expected, $accept) {
                $request = Request::create($uri, $method, $data, [], [], ['HTTP_ACCEPT' => $accept]);
                $response = app(HttpKernel::class)->handle($request);
                $code = $response->getStatusCode();

                return in_array($code, $expected, true)
                    ? $this->ok("{$method} {$uri} -> {$code}")
                    : $this->fail("{$method} {$uri} -> {$code} (expected " . implode('/', $expected) . ')'
                        . (isset($response->exception) ? ': ' . Str::limit($response->exception->getMessage(), 120) : ''));
            });
        }

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function ok(string $message): array
    {
        return ['status' => self::OK, 'message' => $message];
    }

    public function warn(string $message): array
    {
        return ['status' => self::WARN, 'message' => $message];
    }

    public function fail(string $message): array
    {
        return ['status' => self::FAIL, 'message' => $message];
    }

    protected function safe(callable $check): array
    {
        try {
            return $check();
        } catch (Throwable $e) {
            return $this->fail(Str::limit($e->getMessage(), 200));
        }
    }
}
