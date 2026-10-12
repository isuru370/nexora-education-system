<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstitutePayment;
use App\Models\PaymentReason;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstituteExpenseController extends Controller
{
    /**
     * Display all institute expenses.
     */
    public function index(Request $request)
    {
        $query = InstitutePayment::with([
            'user',
            'paymentReason',
        ])->where('payment_type', 'expense');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', '%' . $search . '%')
                    ->orWhere('note', 'like', '%' . $search . '%')
                    ->orWhere('reason_code', 'like', '%' . $search . '%');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Date Range
        |--------------------------------------------------------------------------
        */
        if ($request->filled('from_date')) {
            $query->whereDate(
                'payment_date',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {
            $query->whereDate(
                'payment_date',
                '<=',
                $request->to_date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Month / Year
        |--------------------------------------------------------------------------
        */
        if (
            $request->filled('month') &&
            $request->filled('year')
        ) {
            $query->whereMonth(
                'payment_date',
                $request->month
            )->whereYear(
                'payment_date',
                $request->year
            );
        } elseif ($request->filled('month')) {
            $query->whereMonth(
                'payment_date',
                $request->month
            );
        } elseif ($request->filled('year')) {
            $query->whereYear(
                'payment_date',
                $request->year
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Amount Range
        |--------------------------------------------------------------------------
        */
        if ($request->filled('min_amount')) {
            $query->where(
                'amount',
                '>=',
                $request->min_amount
            );
        }

        if ($request->filled('max_amount')) {
            $query->where(
                'amount',
                '<=',
                $request->max_amount
            );
        }

        /*
        |--------------------------------------------------------------------------
        | User Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('user_id')) {
            $query->where(
                'user_id',
                $request->user_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Current Month
        |--------------------------------------------------------------------------
        */
        if ($request->boolean('current_month')) {
            $query->whereMonth(
                'payment_date',
                Carbon::now()->month
            )->whereYear(
                'payment_date',
                Carbon::now()->year
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */
        $sortField = $request->get(
            'sort',
            'payment_date'
        );

        $sortOrder = strtolower(
            $request->get('order', 'desc')
        );

        $allowedSorts = [
            'payment_date',
            'amount',
            'created_at',
            'id',
            'status',
        ];

        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'payment_date';
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query->orderBy(
            $sortField,
            $sortOrder
        );

        /*
        |--------------------------------------------------------------------------
        | Secondary Sorting
        |--------------------------------------------------------------------------
        */
        if ($sortField !== 'id') {
            $query->orderBy('id', 'desc');
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        $expenses = $query
            ->paginate(15)
            ->appends($request->query());

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        $users = InstitutePayment::where(
            'payment_type',
            'expense'
        )
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id')
            ->filter();

        /*
        |--------------------------------------------------------------------------
        | Payment Reasons
        |--------------------------------------------------------------------------
        */
        $reasons = PaymentReason::orderBy(
            'name'
        )->get();

        return view(
            'admin.institute-expenses.index',
            compact(
                'expenses',
                'users',
                'reasons'
            )
        );
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $reasons = PaymentReason::orderBy(
            'name'
        )->get();

        return view(
            'admin.institute-expenses.create',
            compact('reasons')
        );
    }

    /**
     * Store new expense.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:150',
            ],

            'reason_code' => [
                'nullable',
                'string',
                'max:50',
                'exists:payment_reasons,reason_code',
            ],
        ]);

        $note = $this->generateNote(
            $validated['amount'],
            $validated['payment_date'],
            isset($validated['reason'])
                ? $validated['reason']
                : null
        );

        InstitutePayment::create([
            'amount' => $validated['amount'],

            'payment_date' => $validated['payment_date'],

            'reason' => isset($validated['reason'])
                ? $validated['reason']
                : null,

            'reason_code' => isset($validated['reason_code'])
                ? $validated['reason_code']
                : null,

            'payment_type' => 'expense',

            'status' => 'paid',

            'user_id' => auth()->id(),

            'note' => $note,
        ]);

        return redirect()
            ->route('admin.institute-expenses.index')
            ->with(
                'success',
                'Expense created successfully.'
            );
    }

    /**
     * Display a single expense.
     */
    public function show(
        InstitutePayment $instituteExpense
    ) {
        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        */
        if (
            $instituteExpense->payment_type !== 'expense'
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Load Relationships
        |--------------------------------------------------------------------------
        */
        $instituteExpense->load([
            'user',
            'paymentReason',
        ]);

        return view(
            'admin.institute-expenses.show',
            compact('instituteExpense')
        );
    }

    /**
     * Show edit form.
     */
    public function edit(
        InstitutePayment $instituteExpense
    ) {
        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        */
        if (
            $instituteExpense->payment_type !== 'expense'
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Current Month Restriction
        |--------------------------------------------------------------------------
        */
        if (
            ! $this->canModify(
                $instituteExpense
            )
        ) {
            return back()->with(
                'error',
                'You can edit only current month records.'
            );
        }

        $reasons = PaymentReason::orderBy(
            'name'
        )->get();

        return view(
            'admin.institute-expenses.edit',
            compact(
                'instituteExpense',
                'reasons'
            )
        );
    }

    /**
     * Update expense.
     */
    public function update(
        Request $request,
        InstitutePayment $instituteExpense
    ) {
        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        */
        if (
            $instituteExpense->payment_type !== 'expense'
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Current Month Restriction
        |--------------------------------------------------------------------------
        */
        if (
            ! $this->canModify(
                $instituteExpense
            )
        ) {
            return back()->with(
                'error',
                'You can update only current month records.'
            );
        }

        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:150',
            ],

            'reason_code' => [
                'nullable',
                'string',
                'max:50',
                'exists:payment_reasons,reason_code',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Prevent Updating To Another Month
        |--------------------------------------------------------------------------
        */
        $newPaymentDate = Carbon::parse(
            $validated['payment_date']
        );

        if (
            $newPaymentDate->month !== Carbon::now()->month ||
            $newPaymentDate->year !== Carbon::now()->year
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Expense payment date must be within the current month.'
                );
        }

        $note = $this->generateNote(
            $validated['amount'],
            $validated['payment_date'],
            isset($validated['reason'])
                ? $validated['reason']
                : null
        );

        $instituteExpense->update([
            'amount' => $validated['amount'],

            'payment_date' => $validated['payment_date'],

            'reason' => isset($validated['reason'])
                ? $validated['reason']
                : null,

            'reason_code' => isset($validated['reason_code'])
                ? $validated['reason_code']
                : null,

            'payment_type' => 'expense',

            'status' => 'paid',

            'user_id' => auth()->id(),

            'note' => $note,
        ]);

        return redirect()
            ->route('admin.institute-expenses.index')
            ->with(
                'success',
                'Expense updated successfully.'
            );
    }

    /**
     * Delete expense.
     */
    public function destroy(
        InstitutePayment $instituteExpense
    ) {
        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        */
        if (
            $instituteExpense->payment_type !== 'expense'
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Current Month Restriction
        |--------------------------------------------------------------------------
        */
        if (
            ! $this->canModify(
                $instituteExpense
            )
        ) {
            return back()->with(
                'error',
                'You can delete only current month records.'
            );
        }

        $instituteExpense->delete();

        return redirect()
            ->route('admin.institute-expenses.index')
            ->with(
                'success',
                'Expense deleted successfully.'
            );
    }

    /**
     * Toggle expense status.
     */
    public function toggleStatus(
        InstitutePayment $instituteExpense
    ) {
        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        */
        if (
            $instituteExpense->payment_type !== 'expense'
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Current Month Restriction
        |--------------------------------------------------------------------------
        */
        if (
            ! $this->canModify(
                $instituteExpense
            )
        ) {
            return back()->with(
                'error',
                'You can change status only for current month records.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Toggle
        |--------------------------------------------------------------------------
        */
        if ($instituteExpense->status === 'paid') {
            $instituteExpense->status = 'pending';
        } else {
            $instituteExpense->status = 'paid';
        }

        $instituteExpense->save();

        return back()->with(
            'success',
            'Expense status updated successfully.'
        );
    }

    /**
     * Check whether expense belongs to current month.
     */
    private function canModify(
        InstitutePayment $expense
    ): bool {
        $paymentDate = Carbon::parse(
            $expense->payment_date
        )->startOfDay();

        $now = Carbon::now();

        return $paymentDate->month === $now->month
            && $paymentDate->year === $now->year;
    }

    /**
     * Generate automatic expense note.
     */
    private function generateNote(
        $amount,
        $paymentDate,
        ?string $reason = null
    ): string {
        $date = Carbon::parse(
            $paymentDate
        )->format('Y-m-d');

        $parts = [
            'Institute expense recorded',
            'Date: ' . $date,
            'Amount: Rs. ' . number_format(
                (float) $amount,
                2
            ),
        ];

        if (!empty($reason)) {
            $parts[] = 'Reason: ' . $reason;
        }

        return implode(
            ' | ',
            $parts
        );
    }
}