<?php

namespace App\Services\Web\StudentClass;

use App\Models\ClassPaymentConfig;
use App\Models\StudentClass;
use Illuminate\Support\Facades\Auth;

class ClassPaymentConfigService
{
    /**
     * Create payment configuration for a student class.
     */
    public function createForClass(
        StudentClass $studentClass,
        array $data
    ): ClassPaymentConfig {
        return ClassPaymentConfig::create([
            'branch_id' => $studentClass->branch_id,
            'student_class_id' => $studentClass->id,
            'teacher_id' => $studentClass->teacher_id,
            'organizer_id' => $data['organizer_id'] ?? null,
            'teacher_percentage' => $data['teacher_percentage'],
            'organizer_percentage' => $data['organizer_percentage'] ?? 0,
            'institution_percentage' => $data['institution_percentage'],
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Update payment configuration for a student class.
     */
    public function updateForClass(
        StudentClass $studentClass,
        array $data
    ): ClassPaymentConfig {
        $paymentConfig = ClassPaymentConfig::firstOrNew([
            'student_class_id' => $studentClass->id,
        ]);

        $paymentConfig->branch_id = $studentClass->branch_id;
        $paymentConfig->teacher_id = $studentClass->teacher_id;
        $paymentConfig->organizer_id = $data['organizer_id'] ?? null;
        $paymentConfig->teacher_percentage = $data['teacher_percentage'];
        $paymentConfig->organizer_percentage = $data['organizer_percentage'] ?? 0;
        $paymentConfig->institution_percentage = $data['institution_percentage'];
        $paymentConfig->effective_from = $data['effective_from'] ?? null;
        $paymentConfig->effective_to = $data['effective_to'] ?? null;

        if (!$paymentConfig->exists) {
            $paymentConfig->is_active = true;
            $paymentConfig->created_by = Auth::id();
        }

        $paymentConfig->save();

        return $paymentConfig;
    }
}