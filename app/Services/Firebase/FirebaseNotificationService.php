<?php

namespace App\Services\Firebase;

use App\Models\FcmToken;
use App\Models\Notification;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FirebaseNotificationService
{
    protected Client $client;

    protected string $projectId;

    /**
     * Firebase OAuth scope.
     */
    private const FIREBASE_SCOPE =
        'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * Cached Google OAuth access token.
     *
     * 55 minutes is intentionally lower than the usual
     * 1 hour access-token lifetime.
     */
    private const CACHE_KEY = 'firebase_access_token';

    private const CACHE_TTL = 3300;

    /**
     * FCM errors that indicate the registration token
     * is permanently invalid and should be deactivated.
     */
    private const PERMANENT_TOKEN_ERRORS = [
        'UNREGISTERED',
        'NOT_FOUND',
        'SENDER_ID_MISMATCH',
    ];

    /**
     * HTTP status codes that should normally be retried.
     */
    private const RETRYABLE_HTTP_STATUS_CODES = [
        408, // Request Timeout
        429, // Too Many Requests
        500, // Internal Server Error
        502, // Bad Gateway
        503, // Service Unavailable
        504, // Gateway Timeout
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
        ]);

        $this->projectId = (string) config('services.firebase.project_id');

        if ($this->projectId === '') {
            throw new RuntimeException(
                'Firebase project ID is not configured.'
            );
        }

        $serviceAccountPath = storage_path(
            'app/firebase/service-account.json'
        );

        if (!is_file($serviceAccountPath)) {
            throw new RuntimeException(
                'Firebase service account file not found.'
            );
        }
    }

    /**
     * Send notification to all active FCM tokens
     * belonging to the notification's student.
     */
    public function send(Notification $notification): void
    {
        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate processing
        |--------------------------------------------------------------------------
        */

        $notification->refresh();

        if ($notification->wasSent()) {
            Log::info('Notification already sent.', [
                'notification_id' => $notification->id,
            ]);

            return;
        }

        if ($notification->isCancelled()) {
            Log::info('Notification is cancelled.', [
                'notification_id' => $notification->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get active tokens
        |--------------------------------------------------------------------------
        */

        $tokens = FcmToken::query()
            ->where('student_id', $notification->student_id)
            ->where('is_active', true)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->pluck('token')
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            $notification->markAsFailed(
                'No active FCM tokens found.'
            );

            Log::warning('No active FCM tokens found.', [
                'notification_id' => $notification->id,
                'student_id' => $notification->student_id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Mark processing
        |--------------------------------------------------------------------------
        */

        $notification->markAsProcessing();

        /*
        |--------------------------------------------------------------------------
        | Get Firebase OAuth token
        |--------------------------------------------------------------------------
        */

        try {
            $accessToken = $this->getAccessToken();
        } catch (Throwable $e) {
            /*
             * IMPORTANT:
             *
             * Do not permanently mark the notification as FAILED here.
             *
             * This is usually a temporary infrastructure/authentication
             * problem and the queue job should be allowed to retry.
             */
            Log::error('Unable to obtain Firebase access token.', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $successCount = 0;
        $failedCount = 0;

        /*
        |--------------------------------------------------------------------------
        | Send to every active device
        |--------------------------------------------------------------------------
        */

        foreach ($tokens as $token) {
            try {
                $this->sendToToken(
                    $accessToken,
                    $token,
                    $notification
                );

                $successCount++;
            } catch (Throwable $e) {
                $failedCount++;

                /*
                |--------------------------------------------------------------------------
                | Invalid token?
                |--------------------------------------------------------------------------
                */

                if ($this->isPermanentTokenError($e)) {
                    $this->deactivateToken($token, $e);

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Access token problem?
                |--------------------------------------------------------------------------
                |
                | A cached OAuth token can occasionally become invalid.
                | Clear the cache so the next attempt can generate a fresh
                | token.
                |--------------------------------------------------------------------------
                */

                if ($this->isAuthenticationError($e)) {
                    $this->forgetAccessToken();

                    Log::warning(
                        'Firebase authentication error. Access token cache cleared.',
                        [
                            'notification_id' => $notification->id,
                            'token' => $this->maskToken($token),
                            'error' => $e->getMessage(),
                        ]
                    );

                    /*
                     * This is not a token problem.
                     *
                     * Throwing allows the queue job to retry with a
                     * newly generated access token.
                     */
                    throw $e;
                }

                /*
                |--------------------------------------------------------------------------
                | Retryable Firebase error
                |--------------------------------------------------------------------------
                */

                if ($this->isRetryableError($e)) {
                    Log::warning(
                        'Retryable Firebase error encountered.',
                        [
                            'notification_id' => $notification->id,
                            'token' => $this->maskToken($token),
                            'error' => $e->getMessage(),
                            'code' => $e->getCode(),
                        ]
                    );

                    /*
                     * Allow SendNotificationJob to retry.
                     */
                    throw $e;
                }

                /*
                |--------------------------------------------------------------------------
                | Non-retryable token/send error
                |--------------------------------------------------------------------------
                */

                Log::error(
                    'FCM token send failed.',
                    [
                        'notification_id' => $notification->id,
                        'student_id' => $notification->student_id,
                        'token' => $this->maskToken($token),
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final notification status
        |--------------------------------------------------------------------------
        */

        Log::info('Firebase notification completed.', [
            'notification_id' => $notification->id,
            'student_id' => $notification->student_id,
            'total_tokens' => count($tokens),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);

        /*
        |--------------------------------------------------------------------------
        | All failed
        |--------------------------------------------------------------------------
        */

        if ($successCount === 0) {
            $notification->markAsFailed(
                'All active FCM tokens failed.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | At least one succeeded
        |--------------------------------------------------------------------------
        */

        $notification->markAsSent();

        /*
        |--------------------------------------------------------------------------
        | Partial success
        |--------------------------------------------------------------------------
        */

        if ($failedCount > 0) {
            Log::warning(
                'Firebase notification partially succeeded.',
                [
                    'notification_id' => $notification->id,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'total_tokens' => count($tokens),
                ]
            );
        }
    }

    /**
     * Generate or retrieve cached Firebase OAuth access token.
     */
    private function getAccessToken(): string
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            function (): string {
                try {
                    $serviceAccountPath = storage_path(
                        'app/firebase/service-account.json'
                    );

                    $credentials = new ServiceAccountCredentials(
                        self::FIREBASE_SCOPE,
                        $serviceAccountPath
                    );

                    $httpHandler = HttpHandlerFactory::build();

                    $token = $credentials->fetchAuthToken(
                        $httpHandler
                    );

                    if (
                        !is_array($token) ||
                        empty($token['access_token'])
                    ) {
                        throw new RuntimeException(
                            'Google did not return a Firebase access token.'
                        );
                    }

                    Log::info(
                        'Firebase access token generated and cached.',
                        [
                            'expires_in' =>
                                $token['expires_in'] ?? null,
                        ]
                    );

                    return $token['access_token'];
                } catch (Throwable $e) {
                    Log::error(
                        'Failed to generate Firebase access token.',
                        [
                            'error' => $e->getMessage(),
                        ]
                    );

                    throw new RuntimeException(
                        'Unable to generate Firebase access token: ' .
                        $e->getMessage(),
                        (int) $e->getCode(),
                        $e
                    );
                }
            }
        );
    }

    /**
     * Remove cached OAuth access token.
     */
    private function forgetAccessToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Send a notification to one FCM registration token.
     */
    private function sendToToken(
        string $accessToken,
        string $deviceToken,
        Notification $notification
    ): void {
        $endpoint = sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            $this->projectId
        );

        $payload = [
            'message' => [
                'token' => $deviceToken,

                /*
                |--------------------------------------------------------------------------
                | Notification payload
                |--------------------------------------------------------------------------
                */

                'notification' => [
                    'title' => (string) $notification->title,
                    'body' => (string) $notification->body,
                ],

                /*
                |--------------------------------------------------------------------------
                | Data payload
                |--------------------------------------------------------------------------
                |
                | FCM data values should be strings.
                |--------------------------------------------------------------------------
                */

                'data' => [
                    'notification_id' =>
                        (string) $notification->id,

                    'type' =>
                        (string) $notification->type,

                    'timestamp' =>
                        (string) now()->timestamp,
                ],

                /*
                |--------------------------------------------------------------------------
                | Android
                |--------------------------------------------------------------------------
                */

                'android' => [
                    'priority' => 'high',

                    'notification' => [
                        'sound' => 'default',
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | iOS
                |--------------------------------------------------------------------------
                */

                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],

                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = $this->client->post(
                $endpoint,
                [
                    'headers' => [
                        'Authorization' =>
                            'Bearer ' . $accessToken,

                        'Content-Type' =>
                            'application/json',
                    ],

                    'json' => $payload,
                ]
            );
        } catch (Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Network / Guzzle error
            |--------------------------------------------------------------------------
            */

            Log::warning(
                'Firebase HTTP request failed.',
                [
                    'notification_id' => $notification->id,
                    'token' => $this->maskToken($deviceToken),
                    'error' => $e->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Firebase HTTP request failed: ' .
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        $statusCode = $response->getStatusCode();

        $body = (string) $response
            ->getBody()
            ->getContents();

        $responseBody = json_decode(
            $body,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        if ($statusCode === 200) {
            Log::info(
                'FCM message sent successfully.',
                [
                    'notification_id' => $notification->id,
                    'token' => $this->maskToken($deviceToken),
                    'message_name' =>
                        $responseBody['name'] ?? null,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Extract Firebase error
        |--------------------------------------------------------------------------
        */

        $error = is_array($responseBody['error'] ?? null)
            ? $responseBody['error']
            : [];

        $errorStatus = (string) (
            $error['status'] ?? 'UNKNOWN'
        );

        $errorMessage = (string) (
            $error['message'] ?? 'Unknown Firebase error.'
        );

        /*
        |--------------------------------------------------------------------------
        | Log API error
        |--------------------------------------------------------------------------
        */

        Log::error(
            'Firebase API error.',
            [
                'notification_id' => $notification->id,
                'status_code' => $statusCode,
                'error_status' => $errorStatus,
                'error_message' => $errorMessage,
                'token' => $this->maskToken($deviceToken),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Throw structured exception
        |--------------------------------------------------------------------------
        */

        throw new RuntimeException(
            sprintf(
                'Firebase API error: [%s] %s',
                $errorStatus,
                $errorMessage
            ),
            $statusCode
        );
    }

    /**
     * Determine whether the Firebase error means
     * the registration token is permanently invalid.
     */
    private function isPermanentTokenError(
        Throwable $exception
    ): bool {
        $message = strtoupper(
            $exception->getMessage()
        );

        /*
        |--------------------------------------------------------------------------
        | Explicit permanent FCM errors
        |--------------------------------------------------------------------------
        */

        foreach (self::PERMANENT_TOKEN_ERRORS as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 404 from FCM
        |--------------------------------------------------------------------------
        |
        | FCM token-related 404 responses are generally safe to treat
        | as invalid registration tokens.
        |--------------------------------------------------------------------------
        */

        return (int) $exception->getCode() === 404;
    }

    /**
     * Determine whether the Firebase error is an authentication error.
     */
    private function isAuthenticationError(
        Throwable $exception
    ): bool {
        $statusCode = (int) $exception->getCode();

        if (in_array($statusCode, [401, 403], true)) {
            return true;
        }

        $message = strtoupper(
            $exception->getMessage()
        );

        return str_contains($message, 'UNAUTHENTICATED')
            || str_contains($message, 'PERMISSION_DENIED')
            || str_contains($message, 'ACCESS_TOKEN')
            || str_contains($message, 'INVALID CREDENTIAL');
    }

    /**
     * Determine whether the Firebase error should be retried.
     */
    private function isRetryableError(
        Throwable $exception
    ): bool {
        $statusCode = (int) $exception->getCode();

        /*
        |--------------------------------------------------------------------------
        | HTTP retryable status
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $statusCode,
                self::RETRYABLE_HTTP_STATUS_CODES,
                true
            )
        ) {
            return true;
        }

        $message = strtoupper(
            $exception->getMessage()
        );

        /*
        |--------------------------------------------------------------------------
        | Common network / temporary errors
        |--------------------------------------------------------------------------
        */

        $retryableMessages = [
            'TIMEOUT',
            'TIMED OUT',
            'CONNECTION',
            'NETWORK',
            'TEMPORARY',
            'UNAVAILABLE',
            'RESOURCE_EXHAUSTED',
            'INTERNAL',
            'DEADLINE_EXCEEDED',
            'SERVICE_UNAVAILABLE',
            'TOO MANY REQUESTS',
        ];

        foreach ($retryableMessages as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deactivate an invalid FCM token.
     */
    private function deactivateToken(
        string $token,
        Throwable $exception
    ): void {
        try {
            $updated = FcmToken::query()
                ->where('token', $token)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            Log::info(
                'Invalid FCM token deactivated.',
                [
                    'token' => $this->maskToken($token),
                    'updated_rows' => $updated,
                    'reason' => $exception->getMessage(),
                ]
            );
        } catch (Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Token cleanup failure should NOT break notification sending.
            |--------------------------------------------------------------------------
            */

            Log::error(
                'Failed to deactivate invalid FCM token.',
                [
                    'token' => $this->maskToken($token),
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Mask FCM token before writing it to logs.
     */
    private function maskToken(string $token): string
    {
        $length = strlen($token);

        if ($length <= 16) {
            return '***';
        }

        return substr($token, 0, 8)
            . '...'
            . substr($token, -6);
    }
}