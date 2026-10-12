<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class SmsService
{
    protected string $baseUrl;
    protected string $userId;
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config(
                'services.sms.base_url',
                'https://smssender.chatbiz.net/v1'
            ),
            '/'
        );

        $this->userId = (string) config('services.sms.user_id');
        $this->apiKey = (string) config('services.sms.api_key');
        $this->senderId = (string) config('services.sms.sender_id');
    }

    /**
     * Send single SMS
     */
    public function sendSms(string $recipient, string $message): array
    {
        try {
            $recipient = $this->formatNumber($recipient);

            $response = Http::asForm()
                ->timeout(15)
                ->post("{$this->baseUrl}/send", [
                    'user_id' => $this->userId,
                    'api_key' => $this->apiKey,
                    'sender_id' => $this->senderId,
                    'recipient_contact_no' => $recipient,
                    'message' => $message,
                ]);

            return $this->buildResponse($response);

        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Send bulk SMS
     */
    public function sendBulkSms(
        array $numbers,
        string $message
    ): array {
        try {
            $formattedNumbers = array_map(
                fn ($number) => $this->formatNumber((string) $number),
                $numbers
            );

            $formattedNumbers = array_values(
                array_filter($formattedNumbers)
            );

            if (empty($formattedNumbers)) {
                return [
                    'success' => false,
                    'provider_status_code' => 207,
                    'error' => 'No contact numbers.',
                ];
            }

            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/bulk", [
                    'user_id' => $this->userId,
                    'api_key' => $this->apiKey,
                    'sender_id' => $this->senderId,
                    'recipient_contact_no' => implode(',', $formattedNumbers),
                    'message' => $message,
                ]);

            return $this->buildResponse($response);

        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Send OTP
     */
    public function sendOtp(string $number): array
    {
        $otp = (string) random_int(100000, 999999);

        $message = "Your verification code is: {$otp}";

        $response = $this->sendSms(
            $number,
            $message
        );

        return [
            'success' => $response['success'] ?? false,
            'otp' => $otp,
            'sms_response' => $response,
        ];
    }

    /**
     * Get SMS account balance
     */
    public function getBalance(): array
    {
        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post("{$this->baseUrl}/balance", [
                    'user_id' => $this->userId,
                    'api_key' => $this->apiKey,
                ]);

            return $this->buildResponse($response);

        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Build provider response
     */
    private function buildResponse(Response $response): array
    {
        $body = $response->json();

        if (!is_array($body)) {
            return [
                'success' => false,
                'http_status' => $response->status(),
                'provider_status_code' => null,
                'message_id' => null,
                'body' => $response->body(),
                'json' => null,
                'error' => 'Invalid response from SMS provider.',
            ];
        }

        $providerStatusCode = isset($body['status_code'])
            ? (int) $body['status_code']
            : null;

        $success = $providerStatusCode === 204;

        return [
            'success' => $success,
            'http_status' => $response->status(),
            'provider_status_code' => $providerStatusCode,
            'message_id' => $body['msg_id'] ?? null,
            'body' => $response->body(),
            'json' => $body,
            'error' => $success
                ? null
                : $this->getStatusMessage($providerStatusCode, $body),
        ];
    }

    /**
     * Get readable ChatBiz status message
     */
    private function getStatusMessage(
        ?int $statusCode,
        array $body = []
    ): string {
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
            default => $body['error']
                ?? 'Unknown SMS provider error.',
        };
    }

    /**
     * Exception response
     */
    private function exceptionResponse(Throwable $e): array
    {
        return [
            'success' => false,
            'http_status' => null,
            'provider_status_code' => null,
            'message_id' => null,
            'body' => null,
            'json' => null,
            'error' => $e->getMessage(),
        ];
    }

    /**
     * Format Sri Lankan mobile number
     */
    private function formatNumber(string $number): string
    {
        $number = preg_replace('/\D+/', '', trim($number));

        if (str_starts_with($number, '0')) {
            return '94' . substr($number, 1);
        }

        if (str_starts_with($number, '94')) {
            return $number;
        }

        return $number;
    }
}