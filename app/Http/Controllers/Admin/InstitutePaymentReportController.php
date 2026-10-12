<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentSplitSnapshot;

class InstitutePaymentReportController extends Controller
{
    /**
     * Yearly Institute Payment Report
     *
     * Returns monthly:
     * - Total payments collected
     * - Institution income
     */
    public function yearlyPaymentReport(Request $request)
    {
        try {
            $year = (int) $request->get('year', now()->year);

            $monthlyTotals = PaymentSplitSnapshot::query()
                ->selectRaw('
                    MONTH(payment_date) as month,
                    SUM(payment_amount) as total_payment,
                    SUM(institution_amount) as institute_total
                ')
                ->whereYear('payment_date', $year)
                ->groupByRaw('MONTH(payment_date)')
                ->orderByRaw('MONTH(payment_date)')
                ->get()
                ->keyBy('month');

            $labels = [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ];

            $totalPayments = [];
            $institutionPayments = [];

            for ($i = 1; $i <= 12; $i++) {
                if (isset($monthlyTotals[$i])) {
                    $totalPayments[] = (float) $monthlyTotals[$i]->total_payment;
                    $institutionPayments[] = (float) $monthlyTotals[$i]->institute_total;
                } else {
                    $totalPayments[] = 0;
                    $institutionPayments[] = 0;
                }
            }

            return response()->json([
                'success' => true,
                'year' => $year,
                'labels' => $labels,

                'total_payments' => $totalPayments,

                'institution_payments' => $institutionPayments,
            ]);
        } catch (\Exception $e) {
            Log::error('Yearly Institute Payment Report Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'year' => $request->get('year'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }
}