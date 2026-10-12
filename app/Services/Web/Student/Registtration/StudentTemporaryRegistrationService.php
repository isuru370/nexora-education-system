<?php

namespace App\Services\Web\Student\Registtration;

use App\Models\Admission;
use App\Models\Grade;
use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Student\StudentAdmissionService;
use App\Services\Web\Student\StudentCustomIdService;
use App\Services\Web\Student\StudentPhotoService;
use App\Services\Web\Student\StudentPortalService;
use App\Services\Web\Student\StudentTemporaryCardService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StudentTemporaryRegistrationService
{
    public function __construct(
        private BranchAccessService $branchAccessService,
        private StudentCustomIdService $studentCustomIdService,
        private StudentPortalService $studentPortalService,
        private StudentAdmissionService $studentAdmissionService,
        private StudentPhotoService $studentPhotoService,
        private StudentTemporaryCardService $studentTemporaryCardService
    ) {}

    /**
     * Show temporary student registration form.
     */
    public function createForm()
    {
        $branches = $this->branchAccessService
            ->getAccessibleBranches();

        $grades = Grade::query()
            ->orderBy('grade_name')
            ->get();

        $admissions = Admission::query()
            ->active()
            ->orderBy('name')
            ->get();

        return view(
            'student-temporary-registration.create',
            compact('branches', 'grades', 'admissions')
        );
    }

    /**
     * Register student using temporary ID card.
     *
     * Used by both Web and Mobile API.
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Main Branch
            |--------------------------------------------------------------------------
            */

            $branchId = (int) ($data['branch_id'] ?? 0);

            if (!$this->branchAccessService->canAccessBranch($branchId)) {
                abort(
                    403,
                    'You do not have access to this branch.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Generate Temporary Registration Custom ID
            |--------------------------------------------------------------------------
            */

            $data['custom_id'] = $this->studentCustomIdService
                ->generate(
                    (int) $data['grade_id']
                );

            /*
            |--------------------------------------------------------------------------
            | Registration Values
            |--------------------------------------------------------------------------
            */

            $image = $data['image'] ?? null;

            $quickImageId = $data['quick_image_id'] ?? null;

            $hasAdmission = !empty($data['admission']);

            $admissionId = $data['admission_id'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | Temporary QR Validation
            |--------------------------------------------------------------------------
            */

            $error = $this->studentTemporaryCardService
                ->validateTemporaryQrCode(
                    $data['temporary_qr_code']
                );

            if ($error) {
                throw new RuntimeException($error);
            }

            /*
            |--------------------------------------------------------------------------
            | Student Image
            |--------------------------------------------------------------------------
            |
            | Mobile:
            | quick_image_id
            |
            | Web:
            | uploaded image
            |
            */

            if ($quickImageId) {

                $imgUrl = $this->studentPhotoService
                    ->assignQuickPhoto(
                        $quickImageId
                    );

                if (!$imgUrl) {
                    throw new RuntimeException(
                        'Quick image is invalid or already used.'
                    );
                }
            } else {

                $imgUrl = $this->studentPhotoService
                    ->handleStudentImage(
                        $image,
                        $data['gender'] ?? null
                    );
            }

            $data['img_url'] = $imgUrl;

            $data['last_image_update_at'] = now();

            /*
            |--------------------------------------------------------------------------
            | Remove Non-Student Fields
            |--------------------------------------------------------------------------
            */

            unset(
                $data['image'],
                $data['quick_image_id'],
                $data['admission_id']
            );

            /*
            |--------------------------------------------------------------------------
            | Create Student
            |--------------------------------------------------------------------------
            */

            $student = Student::query()->create($data);

            if (!$student) {
                throw new RuntimeException(
                    'Failed to create student.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Assign Temporary ID Card
            |--------------------------------------------------------------------------
            */

            $this->studentTemporaryCardService
                ->assignTemporaryCard(
                    $student,
                    $data['temporary_qr_code']
                );

            /*
            |--------------------------------------------------------------------------
            | Admission Payment
            |--------------------------------------------------------------------------
            */

            $admissionPayment = null;

            if ($hasAdmission && $admissionId) {

                $admissionPayment = $this->studentAdmissionService
                    ->createAdmissionPayment(
                        $student,
                        (int) $admissionId
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Student Portal
            |--------------------------------------------------------------------------
            */

            $plainPassword = $this->studentPortalService
                ->createStudentPortalLogin(
                    $student
                );

            /*
            |--------------------------------------------------------------------------
            | Refresh Student
            |--------------------------------------------------------------------------
            */

            $student->refresh();

            /*
            |--------------------------------------------------------------------------
            | Load Required Relationships
            |--------------------------------------------------------------------------
            */

            $student->load([
                'grade',
                'branch',
            ]);

            if ($admissionPayment) {
                $admissionPayment->load([
                    'admission',
                    'branch',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return [
                'student' => $student,

                'portal_username' => $student->custom_id,

                'portal_password' => $plainPassword,

                'admission_payment' => $admissionPayment,
            ];
        });
    }

    /**
     * Generate student custom ID.
     */
    public function generateCustomId(int $gradeId): string
    {
        return $this->studentCustomIdService
            ->generate($gradeId);
    }

    /**
     * Create student portal login.
     */
    public function createStudentPortalLogin(
        Student $student
    ): string {
        return $this->studentPortalService
            ->createStudentPortalLogin($student);
    }

    /**
     * Reset student portal password.
     */
    public function resetPortalPassword($login): void
    {
        $this->studentPortalService
            ->resetPortalPassword($login);
    }

    /**
     * Get default image URL.
     */
    public function getDefaultImageUrl(
        string $gender
    ): string {
        return $this->studentPhotoService
            ->getDefaultImageUrl($gender);
    }

    /**
     * Assign temporary card.
     */
    public function assignTemporaryCard(
        Student $student,
        string $temporaryQrCode
    ): void {
        $this->studentTemporaryCardService
            ->assignTemporaryCard(
                $student,
                $temporaryQrCode
            );
    }

    /**
     * Validate temporary QR code.
     */
    public function validateTemporaryQrCode(
        string $temporaryQrCode
    ): ?string {
        return $this->studentTemporaryCardService
            ->validateTemporaryQrCode(
                $temporaryQrCode
            );
    }

    /**
     * Create admission payment.
     */
    public function createAdmissionPayment(
        Student $student,
        int $admissionId
    ) {
        return $this->studentAdmissionService
            ->createAdmissionPayment(
                $student,
                $admissionId
            );
    }

    /**
     * Assign quick photo.
     */
    public function assignQuickPhoto(
        string $quickImageId
    ): ?string {
        return $this->studentPhotoService
            ->assignQuickPhoto(
                $quickImageId
            );
    }

    /**
     * Validate quick photo.
     */
    public function validateQuickPhoto(
        string $quickImageId
    ): bool {
        return $this->studentPhotoService
            ->validateQuickPhoto(
                $quickImageId
            );
    }

    /**
     * Sync admission payment.
     */
    public function syncAdmissionPayment(
        Student $student,
        bool $hasAdmission,
        ?int $admissionId
    ): void {
        $this->studentAdmissionService
            ->syncAdmissionPayment(
                $student,
                $hasAdmission,
                $admissionId
            );
    }

    /**
     * Handle student image.
     */
    public function handleStudentImage(
        $file,
        ?string $gender = null
    ): string {
        return $this->studentPhotoService
            ->handleStudentImage(
                $file,
                $gender
            );
    }
}
