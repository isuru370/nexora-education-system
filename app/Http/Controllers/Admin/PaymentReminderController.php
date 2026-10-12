<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\ClassCategoryFee;
use App\Models\StudentClass;
use App\Services\PaymentReminderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exports\PaymentReminderUnpaidExport;
use App\Exports\PaymentReminderAllExport;
use App\Services\Notification\NotificationService;
use Maatwebsite\Excel\Facades\Excel;

class PaymentReminderController extends Controller
{
    protected $paymentReminderService;

    protected $notificationService;

    public function __construct(
        PaymentReminderService $paymentReminderService,
        NotificationService $notificationService
    ) {
        $this->paymentReminderService = $paymentReminderService;
        $this->notificationService = $notificationService;
    }

    /**
     * Payment Reminder Dashboard
     *
     * Filters:
     * - Class
     * - Category
     * - Payment Month
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Active Classes
        |--------------------------------------------------------------------------
        */

        $classes = StudentClass::query()
            ->with([
                'teacher',
                'subject',
                'grade',
            ])
            ->where('is_active', true)
            ->orderBy('class_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Request values
        |--------------------------------------------------------------------------
        */

        $classId = $request->input('class_id');

        $categoryFeeId = $request->input(
            'class_category_fee_id'
        );

        $paymentMonth = $request->input(
            'payment_month',
            now()->startOfMonth()->format('Y-m-d')
        );

        /*
        |--------------------------------------------------------------------------
        | Available months
        |--------------------------------------------------------------------------
        */

        $availableMonths = $this->getAvailableMonths();

        /*
        |--------------------------------------------------------------------------
        | Default values
        |--------------------------------------------------------------------------
        */

        $enrollments = collect();

        $summary = null;

        $classDetails = null;

        $selectedCategory = null;

        $categories = collect();

        /*
        |--------------------------------------------------------------------------
        | Load categories when class is selected
        |--------------------------------------------------------------------------
        */

        if ($classId) {

            $categories = ClassCategoryFee::query()
                ->with([
                    'category',
                    'activeFeeOptions',
                ])
                ->where(
                    'student_class_id',
                    $classId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('id')
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | Validate and load results
        |--------------------------------------------------------------------------
        */

        if (
            $classId &&
            $categoryFeeId &&
            $paymentMonth
        ) {

            /*
            |--------------------------------------------------------------------------
            | Validate class
            |--------------------------------------------------------------------------
            */

            $class = StudentClass::query()
                ->with([
                    'teacher',
                    'subject',
                    'grade',
                ])
                ->where(
                    'is_active',
                    true
                )
                ->findOrFail($classId);

            /*
            |--------------------------------------------------------------------------
            | Validate category belongs to selected class
            |--------------------------------------------------------------------------
            */

            $selectedCategory = ClassCategoryFee::query()
                ->with([
                    'category',
                    'activeFeeOptions',
                ])
                ->where(
                    'id',
                    $categoryFeeId
                )
                ->where(
                    'student_class_id',
                    $classId
                )
                ->where(
                    'is_active',
                    true
                )
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Normalize month
            |--------------------------------------------------------------------------
            */

            $month = Carbon::createFromFormat(
                'Y-m-d',
                $paymentMonth
            )->startOfMonth();

            $paymentMonth = $month->format('Y-m-d');

            /*
            |--------------------------------------------------------------------------
            | Get enrollments
            |--------------------------------------------------------------------------
            |
            | Class + Category + Month
            |
            */

            $enrollments = $this->paymentReminderService
                ->getEnrollmentsWithStatus(
                    (int) $classId,
                    (int) $categoryFeeId,
                    $paymentMonth
                );

            /*
            |--------------------------------------------------------------------------
            | Calculate summary from same collection
            |--------------------------------------------------------------------------
            |
            | No additional service query.
            |
            */

            $summary = $this->calculateSummary(
                $enrollments
            );

            /*
            |--------------------------------------------------------------------------
            | Class details
            |--------------------------------------------------------------------------
            */

            $classDetails = [
                'class' => $class,

                'category' => $selectedCategory,

                'month' => $month->format('F Y'),

                'month_start' =>
                $month->copy()->startOfMonth(),

                'month_end' =>
                $month->copy()->endOfMonth(),

                'summary' => $summary,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'admin.payment-reminder.index',
            compact(
                'classes',
                'categories',
                'classId',
                'categoryFeeId',
                'paymentMonth',
                'availableMonths',
                'enrollments',
                'summary',
                'classDetails',
                'selectedCategory'
            )
        );
    }

    /**
     * Get active categories for selected class.
     *
     * AJAX endpoint.
     */
    public function getCategories(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => [
                'required',
                'integer',
                'exists:student_classes,id',
            ],
        ]);

        $classId = (int) $request->input('class_id');

        $categories = $this->paymentReminderService
            ->getCategories($classId);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Calculate summary from already loaded collection.
     *
     * Prevents duplicate database queries.
     */
    private function calculateSummary(
        $enrollments
    ): array {

        $freeCardCount = $enrollments
            ->where('is_free_card', true)
            ->count();

        $paidCount = $enrollments
            ->filter(function ($item) {
                return !$item['is_free_card']
                    && $item['is_paid'];
            })
            ->count();

        $unpaidCount = $enrollments
            ->filter(function ($item) {
                return !$item['is_free_card']
                    && !$item['is_paid'];
            })
            ->count();

        return [
            'total_students' =>
            $enrollments->count(),

            'paid_count' =>
            $paidCount,

            'unpaid_count' =>
            $unpaidCount,

            'free_card_count' =>
            $freeCardCount,

            'total_expected_amount' =>
            round(
                $enrollments
                    ->reject(function ($item) {
                        return $item['is_free_card'];
                    })
                    ->sum('expected_fee'),
                2
            ),

            'total_paid_amount' =>
            round(
                $enrollments->sum('paid_amount'),
                2
            ),

            'total_unpaid_amount' =>
            round(
                $enrollments
                    ->filter(function ($item) {
                        return !$item['is_free_card'];
                    })
                    ->sum('balance'),
                2
            ),

            'total_attendance' =>
            $enrollments->sum('attendance_count'),
        ];
    }

    /**
     * Get unpaid students.
     *
     * AJAX endpoint.
     */
    public function getUnpaid(
        Request $request
    ): JsonResponse {

        $validated = $this->validatePaymentFilters(
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | Get unpaid
        |--------------------------------------------------------------------------
        */

        $unpaid = $this->paymentReminderService
            ->getUnpaidEnrollments(
                $validated['class_id'],
                $validated['class_category_fee_id'],
                $validated['payment_month']
            );

        /*
        |--------------------------------------------------------------------------
        | Return clean JSON
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => $unpaid
                ->map(
                    fn($item) =>
                    $this->formatStudentResponse($item)
                )
                ->values(),

            'count' => $unpaid->count(),
        ]);
    }

    /**
     * Get paid students.
     *
     * AJAX endpoint.
     */
    public function getPaid(
        Request $request
    ): JsonResponse {

        $validated = $this->validatePaymentFilters(
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | Get paid
        |--------------------------------------------------------------------------
        */

        $paid = $this->paymentReminderService
            ->getPaidEnrollments(
                $validated['class_id'],
                $validated['class_category_fee_id'],
                $validated['payment_month']
            );

        /*
        |--------------------------------------------------------------------------
        | Return clean JSON
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => $paid
                ->map(
                    fn($item) =>
                    $this->formatStudentResponse($item)
                )
                ->values(),

            'count' => $paid->count(),
        ]);
    }

    /**
     * Get summary statistics.
     *
     * AJAX endpoint.
     */
    public function getSummary(
        Request $request
    ): JsonResponse {

        $validated = $this->validatePaymentFilters(
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | Get summary
        |--------------------------------------------------------------------------
        */

        $summary = $this->paymentReminderService
            ->getSummary(
                $validated['class_id'],
                $validated['class_category_fee_id'],
                $validated['payment_month']
            );

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * Send reminder to selected students.
     *
     * Flow:
     * 1. Validate request
     * 2. Normalize values
     * 3. Verify category belongs to class
     * 4. Call service to send reminders
     * 5. Return response
     */
    public function sendReminder(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'class_id' => [
                'required',
                'integer',
                'exists:student_classes,id',
            ],

            'class_category_fee_id' => [
                'required',
                'integer',
                'exists:class_category_fees,id',
            ],

            'payment_month' => [
                'required',
                'date_format:Y-m-d',
            ],

            'student_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'student_ids.*' => [
                'integer',
                'exists:students,id',
            ],

            'reminder_type' => [
                'required',
                'in:sms,notification',
            ],

            'message' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Normalize values
        |--------------------------------------------------------------------------
        */

        $validated['class_id'] = (int) $validated['class_id'];

        $validated['class_category_fee_id'] =
            (int) $validated['class_category_fee_id'];

        $validated['student_ids'] = collect(
            $validated['student_ids']
        )
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $validated['payment_month'] =
            Carbon::createFromFormat(
                'Y-m-d',
                $validated['payment_month']
            )
            ->startOfMonth()
            ->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | Validate category belongs to class
        |--------------------------------------------------------------------------
        */

        $this->validateCategoryBelongsToClass(
            $validated['class_id'],
            $validated['class_category_fee_id']
        );

        /*
        |--------------------------------------------------------------------------
        | Notification
        |--------------------------------------------------------------------------
        */

        if ($validated['reminder_type'] === 'notification') {

            $message = trim(
                $validated['message']
                    ?? ''
            );

            /*
    |--------------------------------------------------------------------------
    | Default payment reminder message
    |--------------------------------------------------------------------------
    */

            if ($message === '') {
                $message =
                    'Payment Reminder: Your monthly class payment is still pending. '
                    . 'Please make the payment at your earliest convenience. Thank you.';
            }

            /*
    |--------------------------------------------------------------------------
    | Send FCM notifications
    |--------------------------------------------------------------------------
    */

            $result = $this->notificationService->sendToMany(
                $validated['student_ids'],
                [
                    'title' => 'Payment Reminder',
                    'message' => $message,
                    'type' => NotificationType::REMINDER,
                    'data' => [
                        'class_id' =>
                        (string) $validated['class_id'],

                        'class_category_fee_id' =>
                        (string) $validated['class_category_fee_id'],

                        'payment_month' =>
                        $validated['payment_month'],

                        'notification_type' =>
                        'payment_reminder',
                    ],
                ]
            );

            /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

            if ($result['queued'] > 0) {

                $message =
                    $result['queued']
                    . ' notification(s) queued successfully.';

                if (!empty($result['failed'])) {
                    $message .= ' '
                        . count($result['failed'])
                        . ' notification(s) could not be queued.';
                }

                return back()->with(
                    'success',
                    $message
                );
            }

            return back()->with(
                'error',
                'No notifications could be queued.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SMS - Service handles individual/bulk logic
        |--------------------------------------------------------------------------
        */

        $result = $this->paymentReminderService->sendReminders(
            $validated
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        if ($result['success']) {

            return back()->with(
                'success',
                $result['message']
            );
        }

        return back()->with(
            'error',
            $result['message']
        );
    }

    /**
     * Export unpaid students as Excel.
     */
    public function exportUnpaid(Request $request)
    {
        $validated = $this->validatePaymentFilters($request);

        return Excel::download(
            new PaymentReminderUnpaidExport(
                (int) $validated['class_id'],
                (int) $validated['class_category_fee_id'],
                $validated['payment_month']
            ),
            'unpaid_students_' .
                $validated['payment_month'] .
                '.xlsx'
        );
    }

    /**
     * Export all students with payment status as Excel.
     */
    public function exportAll(Request $request)
    {
        $validated = $this->validatePaymentFilters($request);

        return Excel::download(
            new PaymentReminderAllExport(
                (int) $validated['class_id'],
                (int) $validated['class_category_fee_id'],
                $validated['payment_month']
            ),
            'payment_status_' .
                $validated['payment_month'] .
                '.xlsx'
        );
    }

    /**
     * Validate class/category/month filters.
     *
     * Also verifies that category belongs
     * to selected class.
     */
    private function validatePaymentFilters(
        Request $request
    ): array {

        $validated = $request->validate([

            'class_id' => [
                'required',
                'integer',
                'exists:student_classes,id',
            ],

            'class_category_fee_id' => [
                'required',
                'integer',
                'exists:class_category_fees,id',
            ],

            'payment_month' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Verify active class
        |--------------------------------------------------------------------------
        */

        $classExists = StudentClass::query()
            ->where(
                'id',
                $validated['class_id']
            )
            ->where(
                'is_active',
                true
            )
            ->exists();

        if (!$classExists) {

            abort(
                422,
                'Selected class is not active.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify category belongs to class
        |--------------------------------------------------------------------------
        */

        $this->validateCategoryBelongsToClass(
            (int) $validated['class_id'],
            (int) $validated['class_category_fee_id']
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize values
        |--------------------------------------------------------------------------
        */

        $validated['class_id'] =
            (int) $validated['class_id'];

        $validated['class_category_fee_id'] =
            (int) $validated['class_category_fee_id'];

        $validated['payment_month'] =
            Carbon::createFromFormat(
                'Y-m-d',
                $validated['payment_month']
            )
            ->startOfMonth()
            ->format('Y-m-d');

        return $validated;
    }

    /**
     * Verify category fee belongs to selected class.
     */
    private function validateCategoryBelongsToClass(
        int $classId,
        int $categoryFeeId
    ): ClassCategoryFee {

        return ClassCategoryFee::query()
            ->with([
                'category',
                'activeFeeOptions',
            ])
            ->where(
                'id',
                $categoryFeeId
            )
            ->where(
                'student_class_id',
                $classId
            )
            ->where(
                'is_active',
                true
            )
            ->firstOrFail();
    }

    /**
     * Format enrollment for AJAX response.
     */
    private function formatStudentResponse(
        array $item
    ): array {

        $student = $item['student'];

        return [
            'enrollment_id' =>
            $item['enrollment']->id,

            'student_id' =>
            $student->id,

            'custom_id' =>
            $student->custom_id,

            'student_name' =>
            $student->full_name
                ?: $student->initial_name,

            'mobile' =>
            $student->mobile,

            'whatsapp_mobile' =>
            $student->whatsapp_mobile,

            'guardian_mobile' =>
            $student->guardian_mobile,

            'fee_option' =>
            isset($item['fee_option'])
                ? $item['fee_option']
                : null,

            'fee_option_id' =>
            isset($item['fee_option_id'])
                ? $item['fee_option_id']
                : null,

            'fee_option_label' =>
            isset($item['fee_option_label'])
                ? $item['fee_option_label']
                : null,

            'fee_option_fee' =>
            isset($item['fee_option_fee'])
                ? $item['fee_option_fee']
                : 0,

            'expected_fee' =>
            $item['expected_fee'],

            'final_fee' =>
            isset($item['final_fee'])
                ? $item['final_fee']
                : $item['expected_fee'],

            'paid_amount' =>
            $item['paid_amount'],

            'balance' =>
            isset($item['balance'])
                ? $item['balance']
                : max(
                    (float) $item['expected_fee']
                        - (float) $item['paid_amount'],
                    0
                ),

            'is_free_card' =>
            isset($item['is_free_card'])
                ? (bool) $item['is_free_card']
                : false,

            'status' =>
            $item['status'],

            'is_paid' =>
            $item['is_paid'],

            'attendance_count' =>
            $item['attendance_count'],

            'payment_month' =>
            $item['payment_month'],
        ];
    }

    /**
     * Get last 12 months.
     */
    private function getAvailableMonths(): array
    {
        $months = [];

        for ($i = 0; $i < 12; $i++) {

            $date = now()
                ->subMonths($i)
                ->startOfMonth();

            $months[$date->format('Y-m-d')] = $date->format('F Y');
        }

        return $months;
    }
}
