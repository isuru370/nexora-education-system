<?php

namespace App\Console\Commands;

use App\Services\Web\ClassPromotion\PromoteClassService;
use Illuminate\Console\Command;

/**
 * "Promote Class": on the new class's start date, the old class and its
 * enrollments become inactive and the students' grade changes.
 * Runs every hour (app/Console/Kernel.php); the page also does it when opened.
 */
class ApplyClassPromotionsCommand extends Command
{
    protected $signature = 'nexora:apply-class-promotions';

    protected $description = 'Finish class promotions whose start date has come';

    public function handle(PromoteClassService $service): int
    {
        $done = $service->applyDue();
        $this->info($done ? "Finished $done class promotion(s)." : 'No class promotion due.');

        return self::SUCCESS;
    }
}
