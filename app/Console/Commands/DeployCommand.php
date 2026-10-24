<?php

namespace App\Console\Commands;

use App\Services\System\DeployCheckService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One command to run after every deploy:
 *
 *   php artisan nexora:deploy
 *
 *  1. Checks the server (.env, PHP, folders, database)
 *  2. Runs new migrations
 *  3. Seeds the basic data on the first deploy (or with --seed)
 *  4. Health checks (tables, roles, admin, routes, controllers, views, pages)
 *  5. Rebuilds the caches
 *  6. Sends this institute's details to the Parent Hub (parent app login)
 *  7. Prints READY FOR PRODUCTION / NOT READY  (exit code 0 / 1)
 */
class DeployCommand extends Command
{
    protected $signature = 'nexora:deploy
        {--seed : Run the seeders even when the database already has data (resets the super admin password to the .env value)}
        {--check : Only run the checks - no migrate, no seed, no cache rebuild}
        {--maintenance : Show the maintenance page while migrating}
        {--no-optimize : Do not rebuild config / route / view caches}';

    protected $description = 'Migrate, seed (first time), run health checks and confirm the system is ready for production';

    protected array $report = [];

    public function handle(DeployCheckService $checker): int
    {
        $checkOnly = (bool) $this->option('check');
        $started = microtime(true);

        $this->newLine();
        $this->line('<options=bold>  NexoraEDU deploy</>  ' . now()->format('Y-m-d H:i:s') . '  ' . base_path());

        // Use the live .env values, not an old config cache
        if (!$checkOnly) {
            $this->runQuietly('config:clear');
        }

        /*
         * 1. Environment
         */
        $this->section('1', 'Server and .env');
        $this->record($checker->environmentChecks());

        if ($this->hasFailure('Database connection')) {
            return $this->finish($started, 'The database is not reachable, so nothing else was run.');
        }

        $this->record(['Storage link' => $checker->storageLink()]);

        /*
         * 2. Migrations
         */
        $this->section('2', 'Database tables');

        if ($checkOnly) {
            $pending = $checker->pendingMigrations();
            $this->record(['Migrations' => $pending
                ? $checker->warn(count($pending) . ' waiting (not run with --check)')
                : $checker->ok('Up to date')]);
        } else {
            $this->migrate($checker);
        }

        /*
         * 3. Seed
         */
        $this->section('3', 'Basic data');

        if ($checkOnly) {
            $this->record(['Seed' => $checker->ok('Skipped (--check)')]);
        } else {
            $this->seed($checker);
        }

        /*
         * 4. Health
         */
        $this->section('4', 'Health checks');
        $this->record($checker->healthChecks());

        /*
         * 5. Optimise
         */
        $this->section('5', 'Caches');

        if ($checkOnly || $this->option('no-optimize')) {
            $this->runQuietly('view:clear');
            $this->record(['Caches' => $checker->ok('Skipped')]);
        } else {
            $this->optimise($checker);
        }

        /*
         * 6. Parent Hub (institute details saved there - parents log in through it)
         */
        $this->section('6', 'Parent Hub');
        $this->record(['Parent Hub settings' => $checker->parentHubSettings()]);

        if ($this->hasFailure('Parent Hub settings')) {
            // nothing to send
        } elseif ($checkOnly) {
            $this->record(['Parent Hub' => $checker->ok('Not sent (--check)')]);
        } else {
            $this->record(['Parent Hub' => $checker->parentHubSync()]);
        }

        return $this->finish($started);
    }

    /*
    |--------------------------------------------------------------------------
    | Steps
    |--------------------------------------------------------------------------
    */

    protected function migrate(DeployCheckService $checker): void
    {
        $pending = $checker->pendingMigrations();

        if (!$pending) {
            $this->record(['Migrations' => $checker->ok('Up to date - nothing to run')]);

            return;
        }

        $useMaintenance = (bool) $this->option('maintenance');

        if ($useMaintenance) {
            $this->runQuietly('down', ['--retry' => 60]);
        }

        try {
            $exit = Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            if ($exit !== 0) {
                throw new \RuntimeException($output ?: 'migrate returned ' . $exit);
            }

            $this->record(['Migrations' => $checker->ok('Ran ' . count($pending) . ' migration(s)')]);

            foreach ($pending as $name) {
                $this->line('      <fg=gray>+ ' . $name . '</>');
            }
        } catch (Throwable $e) {
            $this->record(['Migrations' => $checker->fail(\Illuminate\Support\Str::limit($e->getMessage(), 300))]);
        } finally {
            if ($useMaintenance) {
                $this->runQuietly('up');
            }
        }
    }

    protected function seed(DeployCheckService $checker): void
    {
        $firstTime = $checker->needsFirstSeed();

        if (!$firstTime && !$this->option('seed')) {
            $this->record(['Seed' => $checker->ok('Already seeded (use --seed to run again)')]);

            return;
        }

        if (!config('setup.super_admin.username') || !config('setup.super_admin.password')) {
            $this->record(['Seed' => $checker->fail('Set SUPER_ADMIN_USERNAME and SUPER_ADMIN_PASSWORD in .env, then run again')]);

            return;
        }

        try {
            $exit = Artisan::call('db:seed', ['--force' => true]);

            if ($exit !== 0) {
                throw new \RuntimeException(trim(Artisan::output()) ?: 'db:seed returned ' . $exit);
            }

            $this->record(['Seed' => $checker->ok(
                ($firstTime ? 'First install: ' : '') . 'roles, branch, super admin, grades, banks, categories'
            )]);
        } catch (Throwable $e) {
            $this->record(['Seed' => $checker->fail(\Illuminate\Support\Str::limit($e->getMessage(), 300))]);
        }
    }

    protected function optimise(DeployCheckService $checker): void
    {
        $steps = [
            'Config cache' => 'config:cache',
            'Route cache' => 'route:cache',
            'View cache' => 'view:cache',
        ];

        $this->runQuietly('optimize:clear');

        foreach ($steps as $name => $command) {
            try {
                $exit = Artisan::call($command);

                $this->record([$name => $exit === 0
                    ? $checker->ok('Done')
                    : $checker->fail(trim(Artisan::output()) ?: "{$command} failed")]);
            } catch (Throwable $e) {
                // A failed cache would break the site - remove it again
                $this->runQuietly(str_replace(':cache', ':clear', $command));
                $this->record([$name => $checker->fail(\Illuminate\Support\Str::limit($e->getMessage(), 200))]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    */

    protected function section(string $number, string $title): void
    {
        $this->newLine();
        $this->line("<fg=cyan;options=bold>[{$number}/6] {$title}</>");
    }

    protected function record(array $checks): void
    {
        foreach ($checks as $name => $result) {
            $this->report[$name] = $result;

            [$icon, $color] = match ($result['status']) {
                DeployCheckService::OK => ['✔', 'green'],
                DeployCheckService::WARN => ['!', 'yellow'],
                default => ['✖', 'red'],
            };

            $this->line(sprintf(
                '  <fg=%s>%s</> %-22s <fg=%s>%s</>',
                $color,
                $icon,
                $name,
                $result['status'] === DeployCheckService::OK ? 'default' : $color,
                $result['message']
            ));
        }
    }

    protected function hasFailure(string $name): bool
    {
        return ($this->report[$name]['status'] ?? null) === DeployCheckService::FAIL;
    }

    protected function finish(float $started, ?string $stopReason = null): int
    {
        $fails = collect($this->report)->where('status', DeployCheckService::FAIL);
        $warns = collect($this->report)->where('status', DeployCheckService::WARN);
        $passed = collect($this->report)->where('status', DeployCheckService::OK)->count();
        $ready = $fails->isEmpty() && $stopReason === null;
        $seconds = round(microtime(true) - $started, 1);

        $this->newLine();

        if ($ready) {
            $this->line('<fg=black;bg=green;options=bold>                                                    </>');
            $this->line('<fg=black;bg=green;options=bold>   ✔  READY FOR PRODUCTION                          </>');
            $this->line('<fg=black;bg=green;options=bold>                                                    </>');
        } else {
            $this->line('<fg=white;bg=red;options=bold>                                                    </>');
            $this->line('<fg=white;bg=red;options=bold>   ✖  NOT READY FOR PRODUCTION                      </>');
            $this->line('<fg=white;bg=red;options=bold>                                                    </>');
        }

        $this->newLine();
        $this->line("  {$passed} passed, {$warns->count()} warning(s), {$fails->count()} failed  ({$seconds}s)");

        if ($stopReason) {
            $this->line("  <fg=red>{$stopReason}</>");
        }

        if ($fails->isNotEmpty()) {
            $this->newLine();
            $this->line('  <fg=red;options=bold>Fix these first:</>');
            foreach ($fails as $name => $result) {
                $this->line("   <fg=red>✖</> {$name}: {$result['message']}");
            }
        }

        if ($warns->isNotEmpty()) {
            $this->newLine();
            $this->line('  <fg=yellow;options=bold>Should fix:</>');
            foreach ($warns as $name => $result) {
                $this->line("   <fg=yellow>!</> {$name}: {$result['message']}");
            }
        }

        if ($ready) {
            $this->newLine();
            $this->line('  Remember the cron job for scheduled tasks (backups, class status, expired students):');
            $this->line('  <fg=gray>* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1</>');
        }

        $this->newLine();

        Log::info('nexora:deploy finished', [
            'ready' => $ready,
            'passed' => $passed,
            'warnings' => $warns->keys()->all(),
            'failed' => $fails->map(fn ($r) => $r['message'])->all(),
        ]);

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    protected function runQuietly($command, array $arguments = []): void
    {
        try {
            Artisan::call($command, $arguments);
        } catch (Throwable $e) {
            // not important enough to stop the deploy
        }
    }
}
