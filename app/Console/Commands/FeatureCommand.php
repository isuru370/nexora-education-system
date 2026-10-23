<?php

namespace App\Console\Commands;

use App\Support\Features;
use Illuminate\Console\Command;

/**
 * Switch features on / off for this institute (server only - there is no
 * web page for it, so an institute cannot switch on a feature itself).
 */
class FeatureCommand extends Command
{
    protected $signature = 'nexora:feature
        {feature? : Feature key, e.g. courses (leave out to list them)}
        {state? : on or off}';

    protected $description = 'List features, or switch one on / off for this institute';

    public function handle(): int
    {
        $key = $this->argument('feature');
        $state = strtolower((string) $this->argument('state'));

        if ($key === null || $key === 'list') {
            $this->list();

            return self::SUCCESS;
        }

        if (!Features::isKnown($key)) {
            $this->error("Unknown feature \"{$key}\". Known: " . implode(', ', array_keys(Features::known())));

            return self::FAILURE;
        }

        if (!in_array($state, ['on', 'off'], true)) {
            $this->line("{$key}: " . (Features::enabled($key) ? 'ON' : 'off'));
            $this->line("Use: php artisan nexora:feature {$key} on|off");

            return $state === '' ? self::SUCCESS : self::FAILURE;
        }

        Features::set($key, $state === 'on');

        $this->info(Features::known()[$key]['name'] . ' is now ' . ($state === 'on' ? 'ON' : 'OFF') . ' for this institute.');

        return self::SUCCESS;
    }

    protected function list(): void
    {
        $rows = [];
        foreach (Features::all() as $key => $on) {
            $rows[] = [$key, Features::known()[$key]['name'] ?? $key, $on ? 'ON' : 'off'];
        }

        $this->table(['Key', 'Feature', 'This institute'], $rows);
    }
}
