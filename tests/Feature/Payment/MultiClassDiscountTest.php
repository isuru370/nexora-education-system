<?php

namespace Tests\Feature\Payment;

use App\Services\Web\Payment\PaymentSplitSnapshotService;
use App\Support\MultiClassDiscount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Multi-class discount (Payment Rules): several classes paid at once get the
 * rule's amount off each class fee; the teacher bears it.
 *
 * Fixture: S1 in Maths 10 CMB (fee 2,000, teacher 60%). Here S1 also joins 3
 * more classes of the same branch (2,000 each).
 */
class MultiClassDiscountTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    /** @var array<int, int> enrollment ids of S1, the fixture's first */
    private array $enrollments = [];

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => true])]);
        $this->buildInstitute();

        $this->enrollments = [$this->inst['e1']];
        $teacher = DB::table('teachers')->value('id');
        $grade = DB::table('grades')->value('id');
        $subject = DB::table('subjects')->value('id');
        $category = DB::table('class_categories')->value('id');

        foreach (['Science 10', 'English 10', 'ICT 10'] as $name) {
            $class = DB::table('student_classes')->insertGetId([
                'branch_id' => $this->inst['b1'], 'class_name' => $name, 'teacher_id' => $teacher,
                'subject_id' => $subject, 'grade_id' => $grade, 'is_active' => 1,
            ]);
            $fee = DB::table('class_category_fees')->insertGetId(['student_class_id' => $class, 'class_category_id' => $category]);
            $option = DB::table('class_category_fee_options')->insertGetId([
                'class_category_fee_id' => $fee, 'label' => 'Normal', 'fee' => 2000, 'is_default' => 1,
            ]);
            DB::table('class_payment_configs')->insert([
                'branch_id' => $this->inst['b1'], 'student_class_id' => $class, 'teacher_id' => $teacher,
                'teacher_percentage' => 60, 'organizer_percentage' => 0, 'institution_percentage' => 40,
                'effective_from' => '2026-01-01', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->enrollments[] = DB::table('student_class_enrollments')->insertGetId([
                'branch_id' => $this->inst['b1'], 'student_id' => $this->inst['s1'], 'student_class_id' => $class,
                'class_category_fee_id' => $fee, 'class_category_fee_option_id' => $option,
                'is_active' => 1, 'is_free_card' => 0, 'enrolled_at' => '2026-01-01',
            ]);
        }

        Sanctum::actingAs($this->inst['cashier']);
    }

    private function bulkPay(array $enrollmentIds, float $amount = 1900)
    {
        return $this->postJson('/api/new-payment/bulk-pay', ['payments' => array_map(fn ($id) => [
            'code' => 'S1', 'enrollment_id' => $id, 'amount' => $amount, 'discount_amount' => 0,
            'payment_month' => '2026-12', 'payment_method' => 'cash', 'mark_method' => 'qr_mobile',
        ], $enrollmentIds)]);
    }

    public function test_four_classes_paid_at_once_get_the_discount_and_the_teacher_bears_it(): void
    {
        MultiClassDiscount::save(['enabled' => true, 'min_classes' => 4, 'amount' => 100]);

        $response = $this->bulkPay($this->enrollments)->assertSuccessful();
        $this->assertEquals(400, $response->json('data.receipt.multi_class_discount'));
        $this->assertEquals(400, $response->json('data.receipt.summary.total_multi_class_discount'));

        $payments = DB::table('payments')->whereIn('student_class_enrollment_id', $this->enrollments)
            ->where('payment_month', 'like', '2026-12%')->get();
        $this->assertCount(4, $payments);
        foreach ($payments as $payment) {
            $this->assertEquals(1900, $payment->amount);
            $this->assertEquals(100, $payment->discount_amount);
            $this->assertEquals(100, $payment->multi_class_discount);
            $this->assertStringContainsString('Multi-class discount', $payment->note);

            // 2,000 x 60% = 1,200 less the 100 discount; the institute keeps its 800
            $split = DB::table('payment_split_snapshots')->where('payment_id', $payment->id)->first();
            $this->assertEquals(1100, $split->teacher_amount);
            $this->assertEquals(800, $split->institution_amount);
            $this->assertEquals(100, $split->multi_class_discount);
        }
    }

    public function test_fewer_classes_a_free_card_or_the_rule_off_give_no_discount(): void
    {
        MultiClassDiscount::save(['enabled' => true, 'min_classes' => 4, 'amount' => 100]);

        // only 3 classes paid at once
        $this->bulkPay(array_slice($this->enrollments, 0, 3), 2000)->assertSuccessful();
        $this->assertSame(0, DB::table('payments')->where('multi_class_discount', '>', 0)->count());

        // 4 picked, but one is a free card: 3 count
        DB::table('payment_split_snapshots')->delete();
        DB::table('payments')->where('payment_month', 'like', '2026-12%')->delete();
        DB::table('student_class_enrollments')->where('id', $this->enrollments[3])->update(['is_free_card' => 1]);
        $this->bulkPay($this->enrollments, 2000)->assertSuccessful();
        $this->assertSame(0, DB::table('payments')->where('multi_class_discount', '>', 0)->count());

        // the rule off
        DB::table('payment_split_snapshots')->delete();
        DB::table('payments')->where('payment_month', 'like', '2026-12%')->delete();
        DB::table('student_class_enrollments')->where('id', $this->enrollments[3])->update(['is_free_card' => 0]);
        MultiClassDiscount::save(['enabled' => false, 'min_classes' => 4, 'amount' => 100]);
        $this->bulkPay($this->enrollments, 2000)->assertSuccessful();
        $this->assertSame(0, DB::table('payments')->where('multi_class_discount', '>', 0)->count());
    }

    public function test_the_discount_cannot_come_from_the_request(): void
    {
        MultiClassDiscount::save(['enabled' => false, 'min_classes' => 4, 'amount' => 100]);

        $this->postJson('/api/new-payment/bulk-pay', ['payments' => [[
            'code' => 'S1', 'enrollment_id' => $this->enrollments[0], 'amount' => 2000, 'discount_amount' => 0,
            'multi_class_discount' => 500,
            'payment_month' => '2026-12', 'payment_method' => 'cash', 'mark_method' => 'qr_mobile',
        ]]])->assertSuccessful();

        $this->assertEquals(0, DB::table('payments')->value('multi_class_discount'));
    }

    public function test_month_info_tells_the_screen_the_rule_and_the_split_formula_is_unchanged_without_it(): void
    {
        MultiClassDiscount::save(['enabled' => true, 'min_classes' => 5, 'amount' => 200]);

        $info = $this->postJson('/api/new-payment/month-info', [
            'code' => 'S1', 'enrollment_id' => $this->enrollments[0], 'payment_month' => '2026-12',
        ])->assertOk()->json('data');
        $this->assertSame(['enabled' => true, 'min_classes' => 5, 'amount' => 200], array_map(
            fn ($v) => is_float($v) && floor($v) == $v ? (int) $v : $v,
            $info['multi_class_discount']
        ));
        $this->assertTrue($info['counts_for_multi_class']);
        $this->assertEquals(2000, $info['class_fee']);

        // no discount: the same split as before
        $this->assertSame([2925.0, 0.0, 1075.0], PaymentSplitSnapshotService::split(4000, 100, 75, 0));
        // discount 100: teacher 2,925 - 100, institute unchanged
        $this->assertSame([2825.0, 0.0, 1075.0], PaymentSplitSnapshotService::split(3900, 100, 75, 0, 100));
    }

    public function test_super_admin_sets_the_rule_on_payment_rules(): void
    {
        $this->actingAs($this->makeRoleUser('SUPER_ADMIN'));

        $this->put(route('payment-rules.update'), [
            'multi_class_enabled' => 1, 'multi_class_min' => 5, 'multi_class_amount' => 300,
        ])->assertRedirect(route('payment-rules.index'));

        $this->assertSame(['enabled' => true, 'min_classes' => 5, 'amount' => 300.0], MultiClassDiscount::all());
        $this->get(route('payment-rules.index'))->assertOk()->assertSee('Multi-class discount');

        // a cashier / admin cannot
        $this->actingAs($this->inst['admin'])->put(route('payment-rules.update'), ['multi_class_enabled' => 0])->assertForbidden();
    }
}
