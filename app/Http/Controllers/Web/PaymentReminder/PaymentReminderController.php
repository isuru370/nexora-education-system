<?php

namespace App\Http\Controllers\Web\PaymentReminder;

use App\Exports\Web\Report\TableReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PaymentReminder\PaymentReminderFilterRequest;
use App\Http\Requests\Web\PaymentReminder\SendPaymentReminderRequest;
use App\Services\Web\PaymentReminder\PaymentReminderService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class PaymentReminderController extends Controller
{
    public function __construct(
        protected PaymentReminderService $reminderService
    ) {}

    /**
     * Class + category + fee month -> payment status of every student.
     */
    public function index(PaymentReminderFilterRequest $request)
    {
        $validated = $request->validated();
        $month = $request->month();
        $classId = isset($validated['class_id']) ? (int) $validated['class_id'] : null;
        $categoryFeeId = isset($validated['class_category_fee_id']) ? (int) $validated['class_category_fee_id'] : null;

        $categories = collect();
        $report = null;

        try {
            if ($classId) {
                $categories = $this->reminderService->categoryOptions($classId);

                // A class with only one category does not need a second click
                if (!$categoryFeeId && $categories->count() === 1) {
                    $categoryFeeId = (int) $categories->first()->id;
                }
            }

            if ($classId && $categoryFeeId) {
                $report = $this->reminderService->report($classId, $categoryFeeId, $month);
            }
        } catch (DomainException $e) {
            return redirect()->route('payment-reminder.index')->with('error', \App\Support\ErrorDetail::safe($e));
        }

        return view('payment-reminder.index', [
            'classes' => $this->reminderService->classOptions(),
            'categories' => $categories,
            'months' => $this->reminderService->monthOptions(),
            'classId' => $classId,
            'categoryFeeId' => $categoryFeeId,
            'month' => $month,
            'report' => $report,
            'defaultMessage' => $this->reminderService->defaultMessage(),
            'placeholders' => PaymentReminderService::PLACEHOLDERS,
        ]);
    }

    public function send(SendPaymentReminderRequest $request)
    {
        $validated = $request->validated();
        $month = $request->month();

        $back = redirect()->route('payment-reminder.index', [
            'class_id' => $validated['class_id'],
            'class_category_fee_id' => $validated['class_category_fee_id'],
            'month' => $month->format('Y-m'),
        ]);

        try {
            $result = $this->reminderService->send(
                (int) $validated['class_id'],
                (int) $validated['class_category_fee_id'],
                $month,
                $validated['student_ids'],
                $validated['channel'],
                $validated['message'] ?? null
            );
        } catch (DomainException $e) {
            return $back->with('error', \App\Support\ErrorDetail::safe($e));
        } catch (Throwable $e) {
            report($e);

            return $back->with('error', 'Failed to send the reminders. Please try again.');
        }

        return $back->with($result['sent'] > 0 ? 'success' : 'error', $result['message']);
    }

    /**
     * /export/{excel|pdf}?class_id=&class_category_fee_id=&month=&status=all|unpaid
     */
    public function export(PaymentReminderFilterRequest $request, string $format)
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        $validated = $request->validated();
        $onlyUnpaid = ($validated['status'] ?? 'all') === 'unpaid';

        try {
            $report = $this->reminderService->report(
                (int) $validated['class_id'],
                (int) $validated['class_category_fee_id'],
                $request->month()
            );

            $rows = $onlyUnpaid
                ? $report['students']->where('status', PaymentReminderService::STATUS_UNPAID)->values()
                : $report['students'];

            $class = $report['class'];
            $summary = $report['summary'];
            $table = $this->reminderService->table();
            $title = ($onlyUnpaid ? 'Unpaid Students' : 'Payment Status') . ' - ' . $class->class_name . ' - ' . $report['month_label'];
            $fileName = ($onlyUnpaid ? 'unpaid-students-' : 'payment-status-') . $class->id . '-' . $report['month']->format('Y-m');

            $info = [
                'Branch: ' . optional($class->branch)->name,
                'Category: ' . optional($report['category']->category)->category_name,
                'Students: ' . $summary['students'] . ' (paid ' . $summary['paid'] . ', unpaid ' . $summary['unpaid'] . ', free card ' . $summary['free_card'] . ')',
                'Unpaid balance: Rs. ' . number_format($summary['balance'], 2),
            ];

            if ($format === 'excel') {
                return Excel::download(
                    new TableReportExport($title, $info, $table, $rows, $onlyUnpaid ? 'Unpaid' : 'Payment Status'),
                    $fileName . '.xlsx'
                );
            }

            return Pdf::loadView('pdf.reports.table', [
                'title' => $title,
                'infoLines' => $info,
                'table' => $table,
                'rows' => $rows,
            ])
                ->setPaper('a4', 'landscape')
                ->download($fileName . '.pdf');
        } catch (DomainException $e) {
            return redirect()->route('payment-reminder.index')->with('error', \App\Support\ErrorDetail::safe($e));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to generate the file.');
        }
    }
}
