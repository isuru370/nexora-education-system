<?php

namespace App\Http\Controllers\Web\GenerateStudentId;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GenerateStudentId\IndexStudentIdCardRequest;
use App\Models\Student;
use App\Services\Web\Admission\CardReplacementFeeService;
use App\Services\Web\GenerateStudentId\GenerateStudentIdService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateStudentIdController extends Controller
{
    public function __construct(
        private GenerateStudentIdService $service,
        private CardReplacementFeeService $cardFees
    ) {}

    /**
     * Display the student ID card generation page.
     */
    public function index(IndexStudentIdCardRequest $request)
    {
        try {
            $validated = $request->validated();

            $students = $this->service->getIndexData(
                $validated['search'] ?? null,
                $validated['show'] ?? 'all'
            );
            $show = $validated['show'] ?? 'all';

            // how many times each card was downloaded (2nd time = card fee)
            $downloads = $this->cardFees->downloadInfo($students->pluck('id')->all());
            $cardFees = $this->cardFees->items();

            return view('generate-student-id.index', compact('students', 'show', 'downloads', 'cardFees'));

        } catch (Throwable $e) {
            Log::error('Student ID Card Fetch Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return back()->with('error', 'Something went wrong.');
        }
    }

    /**
     * POST /{student}/download - record a download before the browser makes
     * the PNG. 1st download free; later ones need admission_id (+ method):
     * 402 + "payment_required" tells the page to ask for the fee.
     */
    public function recordDownload(Request $request, Student $student): JsonResponse
    {
        $data = $request->validate([
            'admission_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'in:cash,card,bank_transfer,online'],
        ]);

        try {
            [, $payment] = $this->cardFees->download(
                $student,
                $data['admission_id'] ?? null,
                $data['payment_method'] ?? 'cash',
                auth()->id()
            );
        } catch (DomainException $e) {
            if ($e->getMessage() === 'payment_required') {
                $last = $this->cardFees->downloadInfo([$student->id])->get($student->id);

                return response()->json([
                    'success' => false,
                    'payment_required' => true,
                    'message' => 'This ID card was already downloaded. Choose the card fee to download it again.',
                    'times' => (int) ($last->times ?? 0),
                    'last_at' => $last ? \Carbon\Carbon::parse($last->last_at)->format('d M Y, h:i A') : null,
                ], 402);
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment ? [
                'receipt_number' => $payment->receipt_number,
                'item' => optional($payment->admission)->name,
                'amount' => (float) $payment->amount,
            ] : null,
        ]);
    }

    /**
     * POST /bulk-download {ids: []} - students never downloaded before are
     * recorded and may be zipped; the rest are skipped (one by one + fee).
     */
    public function recordBulkDownload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);

        return response()->json(['success' => true] + $this->cardFees->bulk($data['ids'], auth()->id()));
    }

    /**
     * Print a student's ID card.
     */
    public function print(Student $student)
    {
        try {
            return view('generate-student-id.print', compact('student'));

        } catch (Throwable $e) {
            Log::error('Failed to load print view.', [
                'student_id' => $student->id,
                'message'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to print ID card.');
        }
    }
}