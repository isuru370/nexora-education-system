<?php

namespace App\Services\Parent\FCM;

use App\Models\FcmToken;

class FcmTokenService
{
    public function save(array $data): void
    {
        // A token belongs to one browser / phone. If it was saved for another
        // student or device (e.g. a parent switched child on the same phone),
        // move it here instead of failing on the unique token column.
        FcmToken::where('token', $data['token'])
            ->where(function ($query) use ($data) {
                $query->where('student_id', '!=', $data['student_id'])
                    ->orWhere('device_id', '!=', $data['device_id'])
                    ->orWhereNull('device_id');
            })
            ->delete();

        FcmToken::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'device_id'  => $data['device_id'],
            ],
            [
                'token'         => $data['token'],
                'device_name'   => $data['device_name'] ?? null,
                'device_type'   => $data['device_type'],
                'app_version'   => $data['app_version'] ?? null,
                'is_active'     => true,
                'last_login_at' => now(),
            ]
        );
    }

    public function logout(array $data): void
    {
        FcmToken::where('student_id', $data['student_id'])
            ->where('device_id', $data['device_id'])
            ->update([
                'is_active' => false,
            ]);
    }

    public function activeTokens(int $studentId)
    {
        return FcmToken::query()
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->pluck('token');
    }
}