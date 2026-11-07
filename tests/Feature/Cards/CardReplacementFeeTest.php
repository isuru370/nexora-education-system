<?php

namespace Tests\Feature\Cards;

use App\Support\CardMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Lost card fee: Admissions items ticked "Use for card replacement".
 * Permanent cards: charged on re-assign (app). Temporary cards: from the
 * 2nd ID card download on Generate Student ID.
 */
class CardReplacementFeeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected int $registration;
    protected int $fee;
    protected int $free;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();

        $this->registration = DB::table('admissions')->insertGetId(['name' => 'New registration', 'amount' => 1500, 'is_active' => 1]);
        $this->fee = DB::table('admissions')->insertGetId(['name' => 'ID card replacement', 'amount' => 200, 'is_active' => 1, 'for_card_replacement' => 1]);
        $this->free = DB::table('admissions')->insertGetId(['name' => 'Free card', 'amount' => 0, 'is_active' => 1, 'for_card_replacement' => 1]);
    }

    public function test_card_fees_are_kept_apart_from_registration_items(): void
    {
        Sanctum::actingAs($this->inst['admin']);

        $this->getJson('/api/admission')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'New registration');

        $this->getJson('/api/admission?purpose=card_replacement')->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/card-replacement-fees')->assertOk()
            ->assertJsonPath('data.0.name', 'Free card')
            ->assertJsonPath('data.1.amount', 200);
    }

    public function test_permanent_card_reassign_saves_the_fee_with_a_receipt(): void
    {
        CardMode::set(CardMode::PERMANENT);
        foreach ([1, 2, 3] as $n) {
            DB::table('student_cards')->insert([
                'branch_id' => $this->inst['b1'], 'card_sequence' => $n, 'global_qr_sequence' => $n,
                'card_number' => "CMB-000$n", 'qr_code' => "NXCMB000$n", 'status' => 'available', 'is_current' => 0,
            ]);
        }
        DB::table('student_cards')->where('qr_code', 'NXCMB0001')->update(['student_id' => $this->inst['s1'], 'status' => 'assigned', 'is_current' => 1]);

        Sanctum::actingAs($this->inst['admin']);

        $this->postJson('/api/re-assign', [
            'old_qr_code' => 'NXCMB0001', 'new_qr_code' => 'NXCMB0002', 'reason' => 'lost',
            'admission_id' => $this->fee, 'payment_method' => 'cash',
        ])->assertOk()
            ->assertJsonPath('data.qr_code', 'NXCMB0002')
            ->assertJsonPath('data.payment.item', 'ID card replacement')
            ->assertJsonPath('data.payment.amount', 200)
            ->assertJsonPath('data.payment.payment_method', 'cash');

        $payment = DB::table('admission_payments')->where('student_id', $this->inst['s1'])->first();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->receipt_number);
        $this->assertStringContainsString('NXCMB0001 -> NXCMB0002', $payment->note);

        // a registration item cannot be used as the card fee (and nothing changes)
        $this->postJson('/api/re-assign', [
            'old_qr_code' => 'NXCMB0002', 'new_qr_code' => 'NXCMB0003', 'reason' => 'lost', 'admission_id' => $this->registration,
        ])->assertStatus(422);
        $this->assertSame('NXCMB0002', DB::table('student_cards')->where('student_id', $this->inst['s1'])->where('is_current', 1)->value('qr_code'));
    }

    public function test_temporary_mode_second_download_needs_a_fee(): void
    {
        CardMode::set(CardMode::TEMPORARY);
        $this->actingAs($this->inst['admin']);
        $url = '/generate-student-id/' . $this->inst['s1'] . '/download';

        // 1st download: free
        $this->postJson($url)->assertOk()->assertJsonPath('payment', null);

        // 2nd: asks for the fee
        $this->postJson($url)->assertStatus(402)->assertJsonPath('payment_required', true)->assertJsonPath('times', 1);

        // with a free item: saved as Rs. 0
        $this->postJson($url, ['admission_id' => $this->free, 'payment_method' => 'cash'])
            ->assertOk()->assertJsonPath('payment.amount', 0);
        $this->assertSame(2, DB::table('student_id_card_downloads')->where('student_id', $this->inst['s1'])->count());
        $this->assertSame(1, DB::table('admission_payments')->where('student_id', $this->inst['s1'])->count());

        // a card fee does not mark the student as "admission paid"
        $this->assertSame(0, (int) DB::table('students')->where('id', $this->inst['s1'])->value('admission'));

        // page shows the note and the fee choices
        $this->get('/generate-student-id')->assertOk()
            ->assertSee('Downloaded 2')->assertSee('ID card replacement');

        // bulk: s1 skipped, s2 allowed
        $this->postJson('/generate-student-id/bulk-download', ['ids' => [$this->inst['s1'], $this->inst['s2']]])
            ->assertOk()
            ->assertJsonPath('allowed', [$this->inst['s2']])
            ->assertJsonPath('skipped', [$this->inst['s1']]);
    }
}
