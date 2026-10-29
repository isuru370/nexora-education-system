<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Student\StoreStudentRequest;
use App\Services\Web\Student\Registtration\StudentTemporaryRegistrationService;
use Exception;
use Illuminate\Support\Facades\Log;

class StudentTemporaryRegistrationController extends Controller
{
    public function __construct(
        private StudentTemporaryRegistrationService $studentTemporaryRegistrationService
    ) {
    }

    /**
     * Show temporary student registration form.
     */
    public function create()
    {
        return $this->studentTemporaryRegistrationService
            ->createForm();
    }

    /**
     * Register student with temporary ID card.
     */
    public function store(StoreStudentRequest $request)
    {
        try {

            $result = $this->studentTemporaryRegistrationService
                ->register(
                    $request->validated()
                );

            $redirect = back()
                ->with(
                    'success',
                    'Student created successfully.'
                )
                ->with(
                    'portal_username',
                    $result['portal_username']
                )
                ->with(
                    'portal_password',
                    $result['portal_password']
                )
                ->with(
                    'student',
                    $result['student']   // ← Receipt එකට අවශ්‍යයි
                );

            /*
            |--------------------------------------------------------------------------
            | Admission Payment - Receipt Auto Print
            |--------------------------------------------------------------------------
            |
            | Admission payment එකක් තිබේ නම්, receipt එක auto print කිරීමට
            | admission_payment session key එක යවන්න.
            |
            */

            if (!empty($result['admission_payment'])) {
                $redirect->with(
                    'admission_payment',
                    $result['admission_payment']
                );
            }

            return $redirect;

        } catch (Exception $e) {

            Log::error(
                'Temporary student registration failed.',
                [
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    \App\Support\ErrorDetail::safe($e)
                );
        }
    }
}