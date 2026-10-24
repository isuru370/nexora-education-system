<?php

namespace App\Services\ParentHub;

use App\Support\InstituteInfo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParentHubService
{
    public function registerStudent(
        string $username,
        string $studentCustomId
    ): bool {

        try {

            $response = Http::timeout(10)
                ->post(
                    rtrim(
                        config('services.parent_hub.url'),
                        '/'
                    ) . '/register-student',
                    [

                        'secret_key' =>
                        config('services.parent_hub.secret'),

                        'username' => $username,

                        'student_custom_id' =>
                        $studentCustomId,

                        'institute_code' =>
                        config('services.institute.code'),

                        'status' => true,
                    ]
                );

            if (!$response->successful()) {

                Log::error(
                    'Parent Hub Register Failed',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return false;
            }

            return true;
        } catch (\Throwable $e) {

            Log::error(
                'Parent Hub Register Error',
                [
                    'message' => $e->getMessage(),
                    'username' => $username,
                ]
            );

            return false;
        }
    }
    /** Register / update this institute in the Parent Hub. */
    public function registerInstitute(): bool
    {
        return $this->syncInstitute()['ok'];
    }

    /**
     * Settings the Parent Hub needs, with what is missing.
     *
     * @return string[]  missing .env keys
     */
    public function missingSettings(): array
    {
        $missing = [];

        foreach ([
            'PARENT_HUB_URL' => config('services.parent_hub.url'),
            'PARENT_HUB_SECRET' => config('services.parent_hub.secret'),
            'INSTITUTE_CODE' => config('services.institute.code'),
        ] as $key => $value) {
            if (blank($value)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * What is sent to the hub. The institute .env is the master copy: it
     * overwrites name / contacts edited in the hub admin.
     */
    public function institutePayload(): array
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        return [
            'secret_key' => config('services.parent_hub.secret'),
            'institute_code' => config('services.institute.code'),
            'institute_name' => InstituteInfo::name(),
            'api_url' => $appUrl,
            'contact_email' => config('services.institute.contact_email') ?: null,
            'contact_mobile' => config('services.institute.contact_mobile') ?: null,
            'logo' => InstituteInfo::logoUrl(),
            'app_version' => config('app.version') ?: null,
        ];
    }

    /**
     * Send this institute's details to the hub.
     *
     * @return array{ok: bool, reachable: bool, status: ?int, message: string, data: ?array}
     */
    public function syncInstitute(): array
    {
        if ($missing = $this->missingSettings()) {
            return $this->result(false, true, null, 'Missing in .env: ' . implode(', ', $missing));
        }

        $url = rtrim((string) config('services.parent_hub.url'), '/') . '/register-institute';

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->post($url, $this->institutePayload());
        } catch (\Throwable $e) {
            Log::error('Institute Registration Failed', ['message' => $e->getMessage()]);

            return $this->result(false, false, null, 'Cannot reach the Parent Hub (' . $url . '): ' . Str::limit($e->getMessage(), 150));
        }

        if ($response->successful()) {
            return $this->result(true, true, $response->status(), 'Saved', (array) $response->json('data'));
        }

        Log::error('Parent Hub Registration Failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $message = match (true) {
            $response->status() === 401 => 'The Parent Hub refused PARENT_HUB_SECRET (401) - use the same secret as the hub',
            $response->status() === 422 => 'The Parent Hub refused the details: ' . $this->firstError($response->json()),
            $response->status() === 404 => 'Wrong PARENT_HUB_URL (404): ' . $url,
            default => 'The Parent Hub answered ' . $response->status(),
        };

        return $this->result(false, true, $response->status(), $message);
    }

    protected function firstError(mixed $body): string
    {
        $errors = is_array($body) ? ($body['errors'] ?? []) : [];

        foreach ($errors as $messages) {
            return is_array($messages) ? (string) ($messages[0] ?? '') : (string) $messages;
        }

        return is_array($body) ? (string) ($body['message'] ?? 'invalid data') : 'invalid data';
    }

    protected function result(bool $ok, bool $reachable, ?int $status, string $message, ?array $data = null): array
    {
        return compact('ok', 'reachable', 'status', 'message', 'data');
    }
}
