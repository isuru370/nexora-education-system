<?php

namespace Tests\Feature\Cards;

use App\Support\CardMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Temporary card mode: the next ISSUED card (lowest number) is given to a
 * new student automatically; staff may still type another card.
 */
class AutoTemporaryCardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => true, 'success' => true])]);
        Storage::fake('public');
        $this->buildInstitute();
        CardMode::set('temporary');

        $this->grade = (int) DB::table('grades')->value('id');

        foreach ([
            ['TMP1000', 'issued'], ['TMP046', 'issued'], ['TMP045', 'issued'],
            ['TMP044', 'active'], ['TMP047', 'downloaded'],
        ] as [$number, $status]) {
            DB::table('temporary_id_cards')->insert([
                'temporary_id_number' => $number, 'card_number' => 'C-' . $number, 'status' => $status,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    protected function register(array $extra = [])
    {
        return $this->actingAs($this->inst['admin'], 'sanctum')->postJson('/api/student', $extra + [
            'branch_id' => $this->inst['b1'], 'initial_name' => 'K. Perera', 'guardian_mobile' => '0771234567',
            'grade_id' => $this->grade, 'gender' => 'male',
        ]);
    }

    public function test_cards_are_given_in_order_and_custom_id_is_returned(): void
    {
        $this->actingAs($this->inst['admin'], 'sanctum')->getJson('/api/temporary-cards/next')
            ->assertOk()->assertJsonPath('data.next', 'TMP045')->assertJsonPath('data.issued_left', 3);

        // App with the old empty box sends just "TMP"
        $first = $this->register(['temporary_qr_code' => 'TMP'])->assertCreated();
        $first->assertJsonPath('card.temporary_qr_code', 'TMP045');
        $this->assertNotEmpty($first->json('card.custom_id'));
        $this->assertSame($first->json('student.custom_id'), $first->json('card.custom_id'));

        $this->register()->assertCreated()->assertJsonPath('card.temporary_qr_code', 'TMP046');
        $this->register()->assertCreated()->assertJsonPath('card.temporary_qr_code', 'TMP1000');

        $this->assertSame('active', DB::table('temporary_id_cards')->where('temporary_id_number', 'TMP045')->value('status'));
        $this->assertNotNull(DB::table('students')->where('temporary_qr_code', 'TMP045')->value('temporary_qr_code_expire_date'));

        // none left -> clear message, nothing saved
        $before = DB::table('students')->count();
        $this->register()->assertStatus(422)->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('No issued temporary ID cards', $this->register()->json('message'));
        $this->assertSame($before, DB::table('students')->count());
    }

    public function test_staff_can_give_a_different_card(): void
    {
        // "46" typed = TMP046; TMP045 stays for the next student
        $this->register(['temporary_qr_code' => '46'])->assertCreated()->assertJsonPath('card.temporary_qr_code', 'TMP046');
        $this->register()->assertCreated()->assertJsonPath('card.temporary_qr_code', 'TMP045');

        // a card that is not issued is refused
        $this->register(['temporary_qr_code' => 'TMP047'])->assertStatus(422);
    }

    public function test_web_form_shows_the_next_card_and_the_result(): void
    {
        $this->actingAs($this->inst['admin'])->get('/student-temporary-registration')
            ->assertOk()->assertSee('Auto: TMP045')->assertSee('3 issued card(s) left');

        $admission = DB::table('admissions')->insertGetId(['name' => 'New', 'amount' => 0, 'is_active' => 1]);

        $this->actingAs($this->inst['admin'])
            ->from('/student-temporary-registration')
            ->post('/student-temporary-registration', [
                'branch_id' => $this->inst['b1'], 'initial_name' => 'K. Perera', 'guardian_mobile' => '0771234567',
                'grade_id' => $this->grade, 'gender' => 'male', 'admission_id' => $admission,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('registered_card', fn ($card) => $card['temporary_qr_code'] === 'TMP045' && !empty($card['custom_id']));

        $customId = DB::table('students')->where('temporary_qr_code', 'TMP045')->value('custom_id');

        $this->actingAs($this->inst['admin'])->get('/student-temporary-registration')
            ->assertSee('Auto: TMP046');

        $this->assertNotEmpty($customId);
    }
}
