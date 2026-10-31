<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

/**
 * Check the SMS settings on a server:
 *   php artisan nexora:sms-test                 balance of both accounts
 *   php artisan nexora:sms-test 0771234567      + a test SMS (institute account)
 *   php artisan nexora:sms-test 0771234567 --system   test SMS from the NexoraIT account
 */
class SmsTestCommand extends Command
{
    protected $signature = 'nexora:sms-test
        {mobile? : Send a test SMS to this number}
        {--system : Use the NexoraIT account (login / registration SMS)}';

    protected $description = 'Show the SMS accounts and balance, or send a test SMS';

    public function handle(): int
    {
        $institute = new SmsService();
        $system = SmsService::system();

        foreach (['Institute (payment, attendance ...)' => $institute, 'NexoraIT (logins, registration)' => $system] as $label => $sms) {
            $balance = $sms->isConfigured() ? $sms->getBalance() : null;
            $this->line(sprintf(
                '%-38s %s, sender %s, %s',
                $label,
                $sms->provider() . ($sms->channel() === 'system (institute account)' ? ' (= institute account)' : ''),
                $sms->senderId() ?: '-',
                !$sms->isConfigured()
                    ? 'NOT SET'
                    : (($balance['success'] ?? false) ? 'balance ' . $this->text($balance['current_balance'] ?? '?') : 'balance error: ' . ($balance['error'] ?? '?'))
            ));
        }

        $mobile = $this->argument('mobile');
        if (!$mobile) {
            return self::SUCCESS;
        }

        $sms = $this->option('system') ? $system : $institute;
        $result = $sms->sendSms($mobile, config('services.institute.name', config('app.name')) . ': test SMS from NexoraEDU (' . now()->format('d M Y H:i') . ').');

        if ($result['success'] ?? false) {
            $this->info('Sent through ' . $sms->provider() . ($result['message_id'] ? ' (id ' . $result['message_id'] . ')' : '') . '.');

            return self::SUCCESS;
        }

        $this->error('Not sent: ' . ($result['error'] ?? 'unknown error'));

        return self::FAILURE;
    }

    private function text($value): string
    {
        return is_scalar($value) ? (string) $value : json_encode($value);
    }
}
