<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'custom_id',
        'temporary_qr_code',
        'temporary_qr_code_expire_date',
        'branch_id',
        'full_name',
        'initial_name',
        'mobile',
        'whatsapp_mobile',
        'email',
        'nic',
        'bday',
        'gender',
        'address1',
        'address2',
        'address3',
        'guardian_fname',
        'guardian_lname',
        'guardian_nic',
        'guardian_mobile',
        'grade_id',
        'class_type',
        'admission',
        'student_school',
        'img_url',
        'last_image_update_at',
        'is_active',
        'permanent_qr_active',
        'student_disable',
    ];

    protected $casts = [
        'temporary_qr_code_expire_date' => 'datetime',
        'bday' => 'date',
        'last_image_update_at' => 'date',
        'admission' => 'boolean',
        'is_active' => 'boolean',
        'permanent_qr_active' => 'boolean',
        'student_disable' => 'boolean',
    ];

    /**
     * Student's grade.
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Student's branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Temporary ID card.
     */
    public function temporaryIdCard()
    {
        return $this->hasOne(TemporaryIdCard::class);
    }

    /**
     * Student class enrollments.
     */
    public function enrollments()
    {
        return $this->hasMany(StudentClassEnrollment::class);
    }

    /**
     * Student attendances.
     */
    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * Student payments.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Admission payments.
     */
    public function admissionPayments()
    {
        return $this->hasMany(AdmissionPayment::class);
    }

    /**
     * Student portal login.
     */
    public function portalLogin()
    {
        return $this->hasOne(StudentPortalLogin::class);
    }

    /**
     * Student exam results.
     */
    public function results()
    {
        return $this->hasMany(StudentResult::class);
    }

    /**
     * Student FCM tokens.
     */
    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    /**
     * Active FCM tokens.
     */
    public function activeFcmTokens(): HasMany
    {
        return $this->fcmTokens()
            ->where('is_active', true);
    }

    /**
     * Student notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Unread notifications.
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()
            ->whereNull('read_at');
    }

    /**
     * All cards issued to the student.
     */
    public function studentCards(): HasMany
    {
        return $this->hasMany(StudentCard::class);
    }

    /**
     * Current active enrollment.
     */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentClassEnrollment::class)
            ->where('is_active', true)
            ->latestOfMany();
    }

    /**
     * Current active card.
     */
    public function currentCard(): HasOne
    {
        return $this->hasOne(StudentCard::class)
            ->where('is_current', true);
    }

    /**
     * Student card history.
     */
    public function cardHistories()
    {
        return $this->hasMany(StudentCardHistory::class);
    }
}