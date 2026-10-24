<?php

namespace App\Console\Commands;

use App\Services\ParentHub\ParentHubService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegisterInstitute extends Command
{
    protected $signature = 'parenthub:register';

    protected $description =
    'Register / update this institute in the Parent Hub (nexora:deploy does this too)';

    public function __construct(
        private ParentHubService $parentHubService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Sending this institute to the Parent Hub...');

        try {
            $result = $this->parentHubService->syncInstitute();
        } catch (Throwable $e) {
            Log::error('Parent Hub Registration Command Failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (!$result['ok']) {
            $this->error('Institute registration failed: ' . $result['message']);

            return self::FAILURE;
        }

        $payload = $this->parentHubService->institutePayload();
        $this->info('Institute registered successfully: ' . $payload['institute_code'] . ' - ' . $payload['institute_name']);

        return self::SUCCESS;
    }
}
