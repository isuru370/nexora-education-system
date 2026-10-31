<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sends SMS through one provider account.
 *
 * Two channels (config/services.php):
 *
 *   institute  new SmsService / app(SmsService::class): the institute's own
 *              provider (SMS_PROVIDER = chatbiz | sendlk, SMS_*): payment,
 *              attendance, class schedule, reminders ...
 *   system     SmsService::system(): NexoraIT's ChatBiz account (SMS_SYSTEM_*),
 *              for login / password SMS: student registration (parent portal
 *              login), parent portal password, teacher and staff logins.
 *              Not set = the institute settings are used.
 *
 * Every method returns the same array for both providers:
 *   success, http_status, provider_status_code, message_id, body, json, error
 */
class SmsService
{
    public const CHATBIZ = 'chatbiz';
    public const SENDLK = 'sendlk';

    public const PROVIDERS = [self::CHATBIZ, self::SENDLK];

    public const DEFAULT_URLS = [
        self::CHATBIZ => 'https://smssender.chatbiz.net/v1',
        self::SENDLK => 'https://sms.send.lk/api/v3',
    ];

    protected string $provider;
    protected string $baseUrl;
    protected string $userId;
    protected string $apiKey;
    protected string $senderId;
    protected string $channel;

    /**
     * @param  array|null  $settings  provider, base_url, user_id, api_key, sender_id
     *                                (default: the institute settings)
     */
    public function __construct(?array $settings = null, string $channel = 'institute')
    {
        $settings = $settings ?? self::instituteSettings();

        $this->provider = in_array($settings['provider'] ?? null, self::PROVIDERS, true)
            ? $settings['provider']
            : self::CHATBIZ;

        $this->baseUrl = rtrim((string) (($settings['base_url'] ?? null) ?: self::DEFAULT_URLS[$this->provider]), '/');
        $this->userId = (string) ($settings['user_id'] ?? '');
        $this->apiKey = (string) ($settings['api_key'] ?? '');
        $this->senderId = (string) ($settings['sender_id'] ?? '');
        $this->channel = $channel;
    }

    /** NexoraIT account for login / password SMS (falls back to the institute account) */
    public static function system(): self
    {
        $system = (array) config('services.sms_system', []);

        if (trim((string) ($system['api_key'] ?? '')) === '') {
            return new self(self::instituteSettings(), 'system (institute account)');
        }

        return new self($system + ['provider' => self::CHATBIZ], 'system');
    }

    public static function instituteSettings(): array
    {
        $settings = (array) config('services.sms', []);
        $provider = strtolower(trim((string) ($settings['provider'] ?? self::CHATBIZ)));

        // A ChatBiz URL left in SMS_BASE_URL must not be used for send.lk
        if ($provider === self::SENDLK && str_contains((string) ($settings['base_url'] ?? ''), 'chatbiz')) {
            $settings['base_url'] = null;
        }

        return ['provider' => $provider] + $settings;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function channel(): string
    {
        return $this->channel;
    }

    public function senderId(): string
    {
        return $this->senderId;
    }

    /** API key / sender ID present (no network call) */
    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->senderId !== ''
            && ($this->provider !== self::CHATBIZ || $this->userId !== '');
    }

    /**
     * Send single SMS
     */
    public function sendSms(string $recipient, string $message): array
    {
        return $this->send([$this->formatNumber($recipient)], $message, 15);
    }

    /**
     * Send bulk SMS
     */
    public function sendBulkSms(array $numbers, string $message): array
    {
        $formatted = array_values(array_filter(array_map(
            fn ($number) => $this->formatNumber((string) $number),
            $numbers
        )));

        if (empty($formatted)) {
            return $this->result(false, null, 207, null, null, null, 'No contact numbers.');
        }

        return $this->send($formatted, $message, 30);
    }

    /**
     * Send OTP
     */
    public function sendOtp(string $number): array
    {
        $otp = (string) random_int(100000, 999999);

        $response = $this->sendSms($number, "Your verification code is: {$otp}");

        return [
            'success' => $response['success'] ?? false,
            'otp' => $otp,
            'sms_response' => $response,
        ];
    }

    /**
     * SMS account balance. Also gives 'current_balance' (number or text) for both providers.
     */
    public function getBalance(): array
    {
        try {
            if ($this->provider === self::SENDLK) {
                $response = $this->sendLk()->timeout(15)->get("{$this->baseUrl}/balance");
                $result = $this->sendLkResult($response);
                $result['current_balance'] = $this->sendLkBalance($result['json']['data'] ?? null);

                return $result;
            }

            $response = Http::asForm()->timeout(15)->post("{$this->baseUrl}/balance", [
                'user_id' => $this->userId,
                'api_key' => $this->apiKey,
            ]);
            $result = $this->chatBizResult($response);
            $json = $result['json'] ?? [];
            $result['current_balance'] = $json['main_balance'] ?? $json['current_balance'] ?? $json['balance'] ?? null;

            return $result;
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Providers                                                          */
    /* ------------------------------------------------------------------ */

    /** @param  array<int, string>  $numbers */
    protected function send(array $numbers, string $message, int $timeout): array
    {
        try {
            if ($this->provider === self::SENDLK) {
                $response = $this->sendLk()->asForm()->timeout($timeout)->post("{$this->baseUrl}/sms/send", [
                    'recipient' => implode(',', $numbers),
                    'sender_id' => $this->senderId,
                    'message' => $message,
                ]);

                return $this->sendLkResult($response);
            }

            $response = Http::asForm()
                ->timeout($timeout)
                ->post($this->baseUrl . (count($numbers) > 1 ? '/bulk' : '/send'), [
                    'user_id' => $this->userId,
                    'api_key' => $this->apiKey,
                    'sender_id' => $this->senderId,
                    'recipient_contact_no' => implode(',', $numbers),
                    'message' => $message,
                ]);

            return $this->chatBizResult($response);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    protected function sendLk(): PendingRequest
    {
        return Http::withToken($this->apiKey)->acceptJson();
    }

    /** send.lk: {"status":"success","data":...} / {"status":"error","message":"..."} */
    protected function sendLkResult(Response $response): array
    {
        $body = $response->json();

        if (!is_array($body)) {
            return $this->result(false, $response->status(), null, null, $response->body(), null,
                $response->status() === 401 ? 'Invalid send.lk API token.' : 'Invalid response from SMS provider.');
        }

        $success = $response->successful() && strtolower((string) ($body['status'] ?? '')) === 'success';
        $data = $body['data'] ?? null;
        $messageId = is_array($data) ? ($data['uid'] ?? $data['id'] ?? $data[0]['uid'] ?? null) : null;

        return $this->result(
            $success,
            $response->status(),
            null,
            $messageId,
            $response->body(),
            $body,
            $success ? null : (string) ($body['message'] ?? ($response->status() === 401 ? 'Invalid send.lk API token.' : 'SMS sending failed.'))
        );
    }

    /** send.lk balance "data" can be a number, a string or an object */
    protected function sendLkBalance($data)
    {
        if (is_numeric($data) || (is_string($data) && $data !== '')) {
            return $data;
        }
        if (is_array($data)) {
            foreach (['remaining_unit', 'remaining_balance', 'sms_unit', 'balance', 'unit'] as $key) {
                if (isset($data[$key]) && !is_array($data[$key])) {
                    return $data[$key];
                }
            }
        }

        return null;
    }

    /** ChatBiz: {"status_code":204,"msg_id":...}; 204 = sent */
    protected function chatBizResult(Response $response): array
    {
        $body = $response->json();

        if (!is_array($body)) {
            return $this->result(false, $response->status(), null, null, $response->body(), null, 'Invalid response from SMS provider.');
        }

        $code = isset($body['status_code']) ? (int) $body['status_code'] : null;
        $success = $code === 204;

        return $this->result(
            $success,
            $response->status(),
            $code,
            $body['msg_id'] ?? null,
            $response->body(),
            $body,
            $success ? null : $this->chatBizMessage($code, $body)
        );
    }

    private function chatBizMessage(?int $statusCode, array $body = []): string
    {
        return match ($statusCode) {
            201 => 'Sender ID / API status is inactive.',
            202 => 'Invalid API key.',
            203 => 'Contact number is invalid or operator is not supported.',
            204 => 'Successfully sent the message.',
            205 => 'Length of contact number is invalid.',
            206 => 'Country is not available for SMS sending.',
            207 => 'No contact numbers.',
            208 => 'Account balance is insufficient.',
            209 => 'Sending the message was unsuccessful.',
            210 => 'Client account is suspended.',
            211 => 'Sender ID is missing or not approved.',
            212 => 'Rate card is not set for the country.',
            213 => 'SMS route is not set for the country.',
            214 => 'API is under maintenance.',
            215 => $body['error'] ?? 'Scheduled maintenance.',
            216 => 'Message contains a blocked word.',
            default => $body['error'] ?? 'Unknown SMS provider error.',
        };
    }

    /* ------------------------------------------------------------------ */

    private function result(bool $success, ?int $http, ?int $code, $messageId, ?string $body, ?array $json, ?string $error): array
    {
        return [
            'success' => $success,
            'provider' => $this->provider,
            'http_status' => $http,
            'provider_status_code' => $code,
            'message_id' => $messageId,
            'body' => $body,
            'json' => $json,
            'error' => $error,
            // Some jobs read this key in their exception message
            'provider_message' => $error,
        ];
    }

    private function exceptionResponse(Throwable $e): array
    {
        return $this->result(false, null, null, null, null, null, $e->getMessage());
    }

    /**
     * Sri Lankan mobile number as 94XXXXXXXXX (both providers)
     */
    private function formatNumber(string $number): string
    {
        $number = preg_replace('/\D+/', '', trim($number));

        if (str_starts_with($number, '0')) {
            return '94' . substr($number, 1);
        }

        if (strlen($number) === 9 && str_starts_with($number, '7')) {
            return '94' . $number;
        }

        return $number;
    }
}
