<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\CourseSession;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** JSON shapes for the staff app's course screens. */
class CourseApiPresenter
{
    public function __construct(private CourseFeeService $fees)
    {
    }

    public function course(Course $course): array
    {
        $course->loadMissing(['days.hall:id,hall_name', 'branch:id,name']);

        return [
            'id' => $course->id,
            'code' => $course->code,
            'name' => $course->name,
            'branch' => $course->branch->name ?? null,
            'full_fee' => (float) $course->full_fee,
            'one_time_fee' => (float) $course->one_time_fee,
            'installment_count' => $course->installment_count,
            'installment_amount' => round((float) $course->full_fee / max(1, $course->installment_count), 2),
            'start_date' => $course->start_date->toDateString(),
            'end_date' => $course->end_date->toDateString(),
            'status' => $course->status(),
            'schedule' => $course->scheduleLabel(),
            'days' => $course->days->map(fn ($d) => [
                'day' => $d->day,
                'start_time' => substr($d->start_time, 0, 5),
                'end_time' => substr($d->end_time, 0, 5),
                'hall' => $d->hall->hall_name ?? null,
            ])->values(),
        ];
    }

    public function student(Student $student): array
    {
        return [
            'id' => $student->id,
            'custom_id' => $student->custom_id,
            'initial_name' => $student->initial_name,
            'mobile' => $student->mobile,
            'guardian_mobile' => $student->guardian_mobile,
            'img_url' => $student->img_url,
        ];
    }

    public function session(CourseSession $session): array
    {
        $session->loadMissing(['course:id,name,code', 'hall:id,hall_name']);

        return [
            'id' => $session->id,
            'course_id' => $session->course_id,
            'course_name' => $session->course->name ?? null,
            'course_code' => $session->course->code ?? null,
            'date' => $session->session_date->toDateString(),
            'start_time' => substr($session->start_time, 0, 5),
            'end_time' => substr($session->end_time, 0, 5),
            'hall' => $session->hall->hall_name ?? null,
            'status' => $session->status,
            'is_extra' => $session->is_custom && !$session->original_date,
            'can_mark' => $session->canMarkAttendance(),
            'present_count' => $session->attendances_count ?? $session->attendances()->count(),
        ];
    }

    public function enrollment(CourseEnrollment $enrollment): array
    {
        $enrollment->loadMissing(['course', 'installments']);
        $fees = $this->fees->summary($enrollment);

        return [
            'id' => $enrollment->id,
            'status' => $enrollment->status,
            'course' => [
                'id' => $enrollment->course->id,
                'code' => $enrollment->course->code,
                'name' => $enrollment->course->name,
            ],
            'payment_plan' => $enrollment->payment_plan,
            'enrolled_at' => $enrollment->enrolled_at->toDateString(),
            'fees' => [
                'total_fee' => $fees['total_fee'],
                'standard_fee' => $fees['standard_fee'],
                'special_fee' => $fees['special_fee'],
                'fee_reason' => $fees['fee_reason'],
                'paid' => $fees['paid'],
                'balance' => $fees['balance'],
                'overdue' => $fees['overdue'],
                'next_installment' => $fees['next_installment'],
                'installments' => $fees['installments'],
            ],
        ];
    }

    /** Everything the app needs to print the 58mm receipt. */
    public function receipt(CoursePayment $payment): array
    {
        $payment->loadMissing(['course', 'student', 'branch', 'takenBy:id,name', 'enrollment']);
        $fees = $this->fees->summary($payment->enrollment);

        return [
            'id' => $payment->id,
            'institute_name' => config('institution.name', config('app.name')),
            'branch' => $payment->branch->name ?? null,
            'title' => 'COURSE PAYMENT',
            'receipt_number' => $payment->receipt_number,
            'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
            'status' => $payment->status,
            'student_id' => $payment->student->custom_id ?? null,
            'student_name' => $payment->student->initial_name ?? null,
            'course_code' => $payment->course->code ?? null,
            'course_name' => $payment->course->name ?? null,
            'payment_method' => $payment->payment_method,
            'amount' => (float) $payment->amount,
            'special_fee_reason' => $fees['special_fee'] ? $fees['fee_reason'] : null,
            'course_fee' => $fees['total_fee'],
            'total_paid' => $fees['paid'],
            'balance' => $fees['balance'],
            'next_due_date' => $fees['next_installment']['due_date'] ?? null,
            'next_due_amount' => $fees['next_installment']['remaining'] ?? null,
            'taken_by' => $payment->takenBy->name ?? null,
        ];
    }

    /**
     * Present / absent list for a class day, plus a ready message to share
     * with the teachers on WhatsApp.
     */
    public function sessionReport(CourseSession $session): array
    {
        $session->loadMissing(['course.courseTeachers.teacher:id,custom_id,full_name,mobile', 'hall:id,hall_name']);

        $marked = $session->attendances()->with('student:id,custom_id,initial_name,mobile')->get()->keyBy('student_id');

        $students = CourseEnrollment::query()
            ->with('student:id,custom_id,initial_name,mobile,guardian_mobile')
            ->where('course_id', $session->course_id)
            ->where('status', 'active')
            ->get()
            ->map(fn ($e) => $e->student)
            ->filter()
            ->sortBy('initial_name')
            ->values();

        $present = $students->filter(fn ($s) => $marked->has($s->id))->values();
        $absent = $students->reject(fn ($s) => $marked->has($s->id))->values();

        $row = fn (Student $s) => [
            'student_id' => $s->id,
            'custom_id' => $s->custom_id,
            'initial_name' => $s->initial_name,
            'mobile' => $s->mobile,
            'guardian_mobile' => $s->guardian_mobile,
            'attended_at' => optional(optional($marked->get($s->id))->attended_at)->format('h:i A'),
        ];

        $text = $this->shareText($session, $present, $absent);

        return [
            'session' => $this->session($session),
            'summary' => [
                'students' => $students->count(),
                'present' => $present->count(),
                'absent' => $absent->count(),
                'percentage' => $students->count() ? (int) round($present->count() / $students->count() * 100) : 0,
            ],
            'present' => $present->map($row)->values(),
            'absent' => $absent->map($row)->values(),
            'share_text' => $text,
            'teachers' => $session->course->courseTeachers->map(fn ($ct) => [
                'id' => $ct->teacher->id,
                'name' => $ct->teacher->full_name,
                'mobile' => $ct->teacher->mobile,
                // Opens WhatsApp to the teacher with the report filled in
                'whatsapp_url' => $this->whatsAppUrl($ct->teacher->mobile, $text),
            ])->values(),
        ];
    }

    protected function shareText(CourseSession $session, Collection $present, Collection $absent): string
    {
        $time = Carbon::parse($session->start_time)->format('h:i A') . ' - ' . Carbon::parse($session->end_time)->format('h:i A');
        $total = $present->count() + $absent->count();
        $line = fn (Student $s, int $i) => ($i + 1) . '. ' . trim(($s->custom_id ? $s->custom_id . ' ' : '') . $s->initial_name);

        $text = "*{$session->course->name}*" . ($session->course->code ? " ({$session->course->code})" : '') . "\n"
            . $session->session_date->format('D, d M Y') . " · {$time}" . ($session->hall ? " · {$session->hall->hall_name}" : '') . "\n\n"
            . "Present: {$present->count()} / {$total}\n"
            . "Absent: {$absent->count()}\n";

        if ($absent->isNotEmpty()) {
            $text .= "\n*Absent*\n" . $absent->values()->map($line)->implode("\n") . "\n";
        }

        if ($present->isNotEmpty()) {
            $text .= "\n*Present*\n" . $present->values()->map($line)->implode("\n") . "\n";
        }

        return rtrim($text) . "\n\n- " . config('institution.name', config('app.name'));
    }

    /** wa.me link (Sri Lanka 07X -> 947X); null without a mobile. */
    protected function whatsAppUrl(?string $mobile, string $text): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $mobile);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '94' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
    }
}
