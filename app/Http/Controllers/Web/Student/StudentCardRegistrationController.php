<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Student\StoreStudentCardRegistrationRequest;
use App\Services\Web\Student\Registtration\StudentCardRegistrationService;
use Exception;
use Illuminate\Support\Facades\Log;

class StudentCardRegistrationController extends Controller
{
    public function __construct(
        private StudentCardRegistrationService $registrationService
    ) {
    }

    /**
     * Show permanent student card registration form.
     */
    public function create()
    {
        return $this->registrationService->createForm();
    }

    /**
     * Register student using permanent physical ID card.
     */
    public function store(
        StoreStudentCardRegistrationRequest $request
    ) {
        try {

            $result = $this->registrationService->register(
                $request->validated()
            );

            return redirect()
                ->route('student-card-registration.create')
                ->with($result);

        } catch (Exception $e) {

            Log::error(
                'Permanent student card registration failed.',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'user_id' => auth()->id(),
                ]
            );

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}