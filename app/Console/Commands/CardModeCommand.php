<?php

namespace App\Console\Commands;

use App\Support\CardMode;
use Illuminate\Console\Command;

/**
 * Choose how this institute gives student ID cards (server only - there is
 * no web page for it, so an institute cannot change it itself).
 *
 *   php artisan nexora:card-mode              show the current mode
 *   php artisan nexora:card-mode permanent    permanent card at registration
 *   php artisan nexora:card-mode temporary    temporary card first
 */
class CardModeCommand extends Command
{
    protected $signature = 'nexora:card-mode
        {mode? : permanent or temporary (leave out to see the current mode)}';

    protected $description = 'Show or set how this institute gives student ID cards (permanent / temporary)';

    public function handle(): int
    {
        $mode = $this->argument('mode');

        if ($mode === null) {
            $this->line('Card mode: ' . strtoupper(CardMode::get()) . ' - ' . CardMode::label()
                . (CardMode::isSet() ? '' : ' (default, not set yet)'));
            $this->line('Use: php artisan nexora:card-mode permanent|temporary');

            return self::SUCCESS;
        }

        $mode = strtolower(trim($mode));

        if (!in_array($mode, CardMode::MODES, true)) {
            $this->error("Unknown card mode \"{$mode}\". Use: " . implode(' or ', CardMode::MODES));

            return self::FAILURE;
        }

        CardMode::set($mode);

        $this->info('Card mode is now ' . strtoupper($mode) . ': ' . CardMode::label($mode) . '.');

        return self::SUCCESS;
    }
}
