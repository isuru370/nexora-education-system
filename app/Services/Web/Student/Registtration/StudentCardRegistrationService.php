<?php

namespace App\Services\Web\Student\Registtration;

use App\Models\Admission;
use App\Models\Grade;
use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Student\StudentAdmissionService;
use App\Services\Web\Student\StudentPhotoService;
use App\Services\Web\Student\StudentPortalService;
use App\Services\Web\StudentCard\StudentCardService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StudentCardRegistrationService
{
    public function __construct(
        private BranchAccessService $branchAccessService,
        private StudentCardService $studentCardService,
        private StudentAdmissionService $studentAdmissionService,
        private StudentPhotoService $studentPhotoService,
        private StudentPortalService $studentPortalService
    ) {}

    /**
     * Show permanent student card registration form.
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
            'student-card-registration.create',
            compact(
                'branches',
                'grades',
                'admissions'
            )
        );
    }

    /**
     * Register a student using a permanent physical ID card.
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
            | Physical Card QR
            |--------------------------------------------------------------------------
            */

            $qrCode = trim(
                (string) ($data['card_qr_code'] ?? '')
            );

            if ($qrCode === '') {
                throw new RuntimeException(
                    'Student Card QR Code is required.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Find and Lock Available Permanent Card
            |--------------------------------------------------------------------------
            */

            $card = $this->studentCardService
                ->findAvailableCardByQr($qrCode);

            /*
            |--------------------------------------------------------------------------
            | Verify Card Belongs to Selected Branch
            |--------------------------------------------------------------------------
            |
            | Scanned card must belong to the branch selected in the form.
            |
            */

            if ((int) $card->branch_id !== $branchId) {
                throw new RuntimeException(
                    'This student card does not belong to the selected branch.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Registration Values
            |--------------------------------------------------------------------------
            */

            $image = $data['image'] ?? null;

            $admissionId = $data['admission_id'] ?? null;

            $hasAdmission = !empty($data['admission']);

            /*
            |--------------------------------------------------------------------------
            | Remove Registration-only Fields
            |--------------------------------------------------------------------------
            */

            unset(
                $data['card_qr_code'],
                $data['image'],
                $data['admission_id']
            );

            /*
            |--------------------------------------------------------------------------
            | Permanent Registration
            |--------------------------------------------------------------------------
            |
            | Permanent registration does NOT use temporary QR.
            |
            */

            $data['temporary_qr_code'] = null;
            $data['temporary_qr_code_expire_date'] = null;

            /*
            |--------------------------------------------------------------------------
            | Student Image
            |--------------------------------------------------------------------------
            */

            $data['img_url'] = $this->studentPhotoService
                ->handleStudentImage(
                    $image,
                    $data['gender'] ?? null
                );

            $data['last_image_update_at'] = now();

            /*
            |--------------------------------------------------------------------------
            | Create Student
            |--------------------------------------------------------------------------
            |
            | The scanned permanent card QR becomes custom_id.
            |
            */

            $data['custom_id'] = $card->qr_code;

            $student = Student::query()->create($data);

            if (!$student) {
                throw new RuntimeException(
                    'Failed to create student.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Assign Permanent Physical Student Card
            |--------------------------------------------------------------------------
            */

            $this->studentCardService->assignCard(
                $student->id,
                $card->id
            );

            /*
            |--------------------------------------------------------------------------
            | Refresh Student
            |--------------------------------------------------------------------------
            |
            | assignCard() updates:
            |
            | student.custom_id
            | permanent_qr_active
            | student_disable
            |
            */

            $student->refresh();

            /*
            |--------------------------------------------------------------------------
            | Admission Payment
            |--------------------------------------------------------------------------
            */

            $admissionPayment = null;

            if ($hasAdmission && $admissionId) {

                $admissionPayment =
                    $this->studentAdmissionService
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
                'success' => true,

                'student' => $student,

                'portal_username' => $student->custom_id,

                'portal_password' => $plainPassword,

                'admission_payment' => $admissionPayment,
            ];
        });
    }
}
