<?php

namespace Tests\Feature\Web;

use App\Services\Web\MonthlyReport\MonthlyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Monthly report PDFs: the two views were empty files, so the downloads
 * were blank. Class 1 has one paid and one free card student,
 * class 2 one unpaid student (October 2026, by fee month).
 */
class MonthlyReportPdfTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        DB::table('student_class_enrollments')->where('id', $this->inst['e2'])->update(['is_free_card' => 1]);
        DB::table('teacher_branches')->insert([
            ['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']],
            ['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b2']],
        ]);
        $this->actingAs($this->inst['admin']);
    }

    protected function query(): string
    {
        return '?teacher_id=' . $this->inst['teacher'] . '&year=2026&month=10';
    }

    public function test_student_payment_pdf_downloads_for_both_bases(): void
    {
        foreach (['fee-month', 'paid-date'] as $basis) {
            $response = $this->get("/monthly-report/student-payments/{$basis}/pdf" . $this->query());

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('content-type'));
            $this->assertGreaterThan(3000, strlen($response->getContent()));
        }
    }

    public function test_student_payment_pdf_shows_class_wise_counts(): void
    {
        $report = app(MonthlyReportService::class)
            ->teacherStudentPaymentReport($this->inst['teacher'], 2026, 10, 'fee-month');

        $html = view('pdf.teacher.teacher_with_student_payment_report', [
            'report' => $report, 'year' => 2026, 'month' => 10,
        ])->render();

        $this->assertStringContainsString('Student Payment Report', $html);
        $this->assertStringContainsString('October 2026', $html);
        $this->assertStringContainsString('Paid 1', $html);
        $this->assertStringContainsString('Free Card 1', $html);
        $this->assertStringContainsString('Unpaid 0', $html);   // class 1
        $this->assertStringContainsString('Unpaid 1', $html);   // class 2
        $this->assertStringContainsString('2,000.00', $html);
    }

    public function test_teacher_salary_pdf_downloads(): void
    {
        $response = $this->get('/monthly-report/teacher-salary/pdf?branch_id=' . $this->inst['b1'] . '&year=2026&month=10');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertGreaterThan(3000, strlen($response->getContent()));
    }

    public function test_collection_report_shows_which_month_was_paid(): void
    {
        // S3 pays the September fee late, in October
        DB::table('payments')->insert([
            'branch_id' => $this->inst['b2'], 'student_id' => $this->inst['s3'],
            'student_class_enrollment_id' => $this->inst['e3'], 'user_id' => $this->inst['cashier']->id,
            'amount' => 2000, 'paid_at' => '2026-10-10 10:00:00', 'payment_month' => '2026-09-01',
            'payment_method' => 'cash', 'status' => 'completed', 'receipt_number' => 'R-SEP',
        ]);

        $service = app(MonthlyReportService::class);
        $teacher = $this->inst['teacher'];

        $byCollection = $service->teacherStudentPaymentReport($teacher, 2026, 10, 'paid-date');
        $s3 = $byCollection['classes'][1]['categories'][0]['students']['paid'][0];
        $this->assertSame('Sep 2026', $s3['paid_for']);
        $this->assertSame('10 Oct 2026', $s3['paid_on']);

        $html = view('pdf.teacher.teacher_with_student_payment_report', [
            'report' => $byCollection, 'year' => 2026, 'month' => 10,
        ])->render();
        $this->assertStringContainsString('Paid For', $html);
        $this->assertStringContainsString('Sep 2026', $html);

        // By fee month the September payment is not in October; no Paid For column
        $byFeeMonth = $service->teacherStudentPaymentReport($teacher, 2026, 10, 'fee-month');
        $this->assertSame('unpaid', $byFeeMonth['classes'][1]['categories'][0]['students']['unpaid'][0]['status']);

        $html = view('pdf.teacher.teacher_with_student_payment_report', [
            'report' => $byFeeMonth, 'year' => 2026, 'month' => 10,
        ])->render();
        $this->assertStringNotContainsString('Paid For', $html);
        $this->assertStringContainsString('05 Oct 2026', $html);   // S1 paid on
    }

    public function test_inactive_class_shows_only_in_months_with_payments(): void
    {
        // Class 1 is closed; S1 paid the October fee on 5 Oct, S2 (free card) is left
        DB::table('student_classes')->where('id', $this->inst['c1'])->update(['is_active' => 0]);
        DB::table('student_class_enrollments')->where('student_class_id', $this->inst['c1'])->update(['is_active' => 0]);

        $service = app(MonthlyReportService::class);
        $teacher = $this->inst['teacher'];
        $names = fn (array $report) => array_column($report['classes'], 'class_name');

        // October: the closed class still shows, with the student who paid
        foreach (['fee-month', 'paid-date'] as $basis) {
            $october = $service->teacherStudentPaymentReport($teacher, 2026, 10, $basis);
            $this->assertSame(['Maths 10 CMB', 'Maths 10 KDY'], $names($october));
            $this->assertFalse($october['classes'][0]['is_active']);
            $this->assertSame(1, $october['classes'][0]['categories'][0]['total_students']);
            $this->assertSame(1, $october['classes'][0]['categories'][0]['paid_count']);
            $this->assertSame(2, $october['summary']['total_classes']);
            $this->assertEquals(2000, $october['summary']['total_collected']);
        }

        // November: no payments, so the closed class is skipped
        $november = $service->teacherStudentPaymentReport($teacher, 2026, 11, 'fee-month');
        $this->assertSame(['Maths 10 KDY'], $names($november));
        $this->assertSame(1, $november['summary']['total_classes']);

        $html = view('pdf.teacher.teacher_with_student_payment_report', [
            'report' => $service->teacherStudentPaymentReport($teacher, 2026, 10, 'fee-month'), 'year' => 2026, 'month' => 10,
        ])->render();
        $this->assertStringContainsString('Inactive', $html);
    }
}
