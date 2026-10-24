<?php

namespace Tests\Feature\Course;

use App\Models\Student;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CoursePaymentService;
use App\Services\Web\Receipt\ReceiptListService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Course payments are listed on the Receipts page with the other receipts. */
class CourseReceiptsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00:00');
        $this->buildInstitute();
        Features::set('courses', true);
        $this->buildCourse();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_course_receipts_are_on_the_receipts_page(): void
    {
        $enrollment = app(CourseEnrollmentService::class)->enroll($this->inst['course'], Student::findOrFail($this->inst['s1']), 'full');
        $kept = app(CoursePaymentService::class)->pay($enrollment, 4000, 'cash', 'manual_web', $this->inst['admin']->id);
        $cancelled = app(CoursePaymentService::class)->pay($enrollment, 1000, 'cash', 'manual_web', $this->inst['admin']->id);
        app(CoursePaymentService::class)->cancel($cancelled, 'Wrong amount', $this->inst['admin']->id);

        $service = app(ReceiptListService::class);
        $this->actingAs($this->inst['admin']);

        // Shares the receipt number series with the other payments
        $this->assertStringStartsWith('REC-', $kept->receipt_number);

        $active = $service->list(['type' => ReceiptListService::TYPE_COURSE]);
        $this->assertSame([$kept->receipt_number], $active->pluck('receipt_number')->all());
        $this->assertSame('Diploma in IT', $active[0]['class_name']);
        $this->assertSame(4000.0, $service->summary($active)['totalAmount']);

        $deleted = $service->list(['type' => ReceiptListService::TYPE_COURSE, 'status' => 'deleted']);
        $this->assertSame('Cancelled', $deleted[0]['status']);
        $this->assertSame('Wrong amount', $deleted[0]['delete_reason']);

        $this->get('/receipts')->assertOk()
            ->assertSee('Course Payment')
            ->assertSee($kept->receipt_number)
            ->assertSee(route('course-payments.receipt', $kept), false);
        $this->get('/receipts?receipt_number=' . $kept->receipt_number)->assertOk()->assertSee('Diploma in IT');
        $this->get('/receipts/export/excel')->assertOk();

        // Institutes without courses do not see them
        Features::set('courses', false);
        $this->assertCount(0, $service->list([])->where('type', ReceiptListService::TYPE_COURSE));
        $this->get('/receipts')->assertOk()->assertDontSee('Course Payment');
    }
}
