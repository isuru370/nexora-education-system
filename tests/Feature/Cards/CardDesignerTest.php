<?php

namespace Tests\Feature\Cards;

use App\Models\User;
use App\Support\CardDesign;
use App\Support\CardMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * ID Card Designer (Super Admin): per-institute layout of the permanent and
 * temporary cards, used by the generate page, the temporary preview and PDF.
 */
class CardDesignerTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        CardDesign::forget();
        Storage::fake('public');

        $type = DB::table('user_types')->where('code', 'SUPER_ADMIN')->value('id')
            ?? DB::table('user_types')->insertGetId(['name' => 'SUPER_ADMIN', 'code' => 'SUPER_ADMIN', 'is_active' => 1]);
        $this->super = User::forceCreate([
            'name' => 'Super', 'email' => 'super@cards.test', 'password' => 'password123',
            'user_type_id' => $type, 'is_active' => 1,
        ]);
        DB::table('branch_users')->insert(['user_id' => $this->super->id, 'branch_id' => $this->inst['b1']]);
    }

    protected function tearDown(): void
    {
        CardDesign::forget();
        parent::tearDown();
    }

    private function fields(string $type, array $changes): string
    {
        $fields = CardDesign::get($type)['fields'];
        foreach ($changes as $key => $values) {
            $fields[$key] = array_merge($fields[$key], $values);
        }

        return json_encode($fields);
    }

    public function test_only_super_admin_opens_the_designer(): void
    {
        $this->actingAs($this->inst['admin'])->get('/card-designer')->assertForbidden();

        $this->actingAs($this->super)->get('/card-designer')
            ->assertOk()->assertSee('ID Card Designer')->assertSee('Default design')
            ->assertSee('data-field="qr"', false);

        $this->actingAs($this->super)->get('/card-designer?type=temporary')
            ->assertOk()->assertSee('data-field="code"', false);
    }

    public function test_default_design_has_no_hardcoded_institute_and_no_outside_qr_site(): void
    {
        $card = CardDesign::card(CardDesign::TEMPORARY, ['code' => 'TMP001']);
        $html = view('id-cards.card', ['card' => $card])->render();

        $this->assertStringNotContainsString('MIHISARA', $html);
        $this->assertStringContainsString(e(mb_strtoupper(CardDesign::splitName(\App\Support\InstituteInfo::name())[0])), $html);
        $this->assertStringContainsString('TMP001', $html);
        $this->assertStringContainsString('data:image/svg+xml;base64', $html);

        $permanent = view('id-cards.card', ['card' => CardDesign::card(CardDesign::PERMANENT, ['student_id' => 'S1', 'name' => 'A.B.SILVA'])])->render();
        $this->assertStringNotContainsString('qrserver', $permanent);
        $this->assertStringNotContainsString('MINIPALASA', $permanent);
        $this->assertStringContainsString('A.B.<br>SILVA', $permanent);
    }

    public function test_saving_moves_fields_and_keeps_them_on_the_card(): void
    {
        $this->actingAs($this->super)->put('/card-designer/permanent', [
            'fields' => $this->fields('permanent', [
                'qr' => ['x' => 5, 'y' => 6, 'w' => 20, 'h' => 20],
                'address' => ['on' => false],
                'grade' => ['on' => true],
                'student_id' => ['x' => 500, 'color' => 'red', 'size' => 99],
            ]),
        ])->assertRedirect('/card-designer?type=permanent');

        $design = CardDesign::get('permanent');
        $this->assertTrue($design['saved']);
        $this->assertSame([5.0, 6.0, 20.0, 20.0], [$design['fields']['qr']['x'], $design['fields']['qr']['y'], $design['fields']['qr']['w'], $design['fields']['qr']['h']]);
        $this->assertFalse($design['fields']['address']['on']);
        $this->assertTrue($design['fields']['grade']['on']);
        // out of range: kept on the card, bad colour ignored
        $this->assertSame(85.6, $design['fields']['student_id']['x'] + $design['fields']['student_id']['w']);
        $this->assertSame('#143d8d', $design['fields']['student_id']['color']);
        $this->assertSame(20.0, $design['fields']['student_id']['size']);

        $html = view('id-cards.card', ['card' => CardDesign::card('permanent', ['student_id' => 'S1', 'address' => 'Secret Road', 'grade' => 'Grade 9'])])->render();
        $this->assertStringContainsString('left:5mm;top:6mm', $html);
        $this->assertStringNotContainsString('Secret Road', $html);
        $this->assertStringContainsString('Grade 9', $html);

        // Generate Student ID page uses the design (temporary-card institutes)
        CardMode::set(CardMode::TEMPORARY);
        DB::table('students')->where('id', $this->inst['s1'])->update(['permanent_qr_active' => false, 'is_active' => true]);
        $this->actingAs($this->super)->get('/generate-student-id')
            ->assertOk()->assertSee('nx-id-field-qr', false)->assertDontSee('qrserver');
    }

    public function test_background_upload_and_reset(): void
    {
        $this->actingAs($this->super)->put('/card-designer/temporary', [
            'fields' => $this->fields('temporary', ['title' => ['text' => 'My Institute']]),
            'background' => UploadedFile::fake()->image('template.png', 1050, 590),
        ])->assertRedirect();

        $design = CardDesign::get('temporary');
        $this->assertStringStartsWith('card-designs/', $design['background']);
        Storage::disk('public')->assertExists($design['background']);
        $this->assertSame('My Institute', $design['fields']['title']['text']);

        $this->actingAs($this->super)->delete('/card-designer/temporary')->assertRedirect();

        Storage::disk('public')->assertMissing($design['background']);
        $this->assertFalse(CardDesign::get('temporary')['saved']);
    }

    public function test_not_an_image_is_refused(): void
    {
        $this->actingAs($this->super)->put('/card-designer/permanent', [
            'fields' => $this->fields('permanent', []),
            'background' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('background');

        $this->assertFalse(CardDesign::get('permanent')['saved']);
    }

    public function test_temporary_preview_and_pdf_use_the_design(): void
    {
        CardMode::set(CardMode::TEMPORARY);
        foreach ([1, 2] as $n) {
            DB::table('temporary_id_cards')->insert([
                'temporary_id_number' => sprintf('TMP%03d', $n), 'card_number' => 'C' . $n,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        CardDesign::save('temporary', ['code' => ['x' => 10, 'y' => 40]], null);

        $this->actingAs($this->super)->post('/temporary_id_cards/preview-generate', ['start' => 'TMP001', 'end' => 'TMP002'])
            ->assertOk()
            ->assertSee('TMP002')
            ->assertSee('left:10mm;top:40mm', false)
            ->assertDontSee('>MIHISARA<', false);

        $this->actingAs($this->super)->post('/temporary_id_cards/download-pdf', ['start' => 'TMP001', 'end' => 'TMP002'])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
