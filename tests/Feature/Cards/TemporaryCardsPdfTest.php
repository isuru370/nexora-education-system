<?php

namespace Tests\Feature\Cards;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Issued temporary ID cards report (/temporary_id_cards/pdf). It ran out
 * of memory with a few thousand cards; it must download.
 */
class TemporaryCardsPdfTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_issued_cards_report_downloads_with_many_cards(): void
    {
        $this->buildInstitute();
        $this->artisan('nexora:card-mode temporary')->assertExitCode(0);

        $rows = [];
        for ($i = 1; $i <= 1500; $i++) {
            $rows[] = [
                'temporary_id_number' => sprintf('TMP%05d', $i),
                'card_number' => sprintf('C%05d', $i),
                'student_id' => $i === 1 ? $this->inst['s1'] : null,
                'activated_at' => $i === 1 ? now() : null,
                'status' => 'issued',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('temporary_id_cards')->insert($chunk);
        }

        $response = $this->actingAs($this->inst['admin'])->get(route('temporary_id_cards.pdf'));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_a_range_over_120_cards_downloads_as_a_zip_of_pdfs(): void
    {
        $this->buildInstitute();
        $this->artisan('nexora:card-mode temporary')->assertExitCode(0);

        $rows = [];
        for ($i = 100; $i <= 229; $i++) {
            $rows[] = ['temporary_id_number' => 'TMP' . $i, 'card_number' => 'C' . $i, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('temporary_id_cards')->insert($rows);

        // Preview shows the first 120 and offers a ZIP of 2 PDFs
        $this->actingAs($this->inst['admin'])
            ->post(route('temporary_id_cards.preview-generate'), ['start' => 'TMP100', 'end' => 'TMP229'])
            ->assertOk()
            ->assertSee('Download ZIP (2 PDFs)')
            ->assertSee('Showing the first 120 of 130 cards');

        $response = $this->actingAs($this->inst['admin'])
            ->post(route('temporary_id_cards.download-pdf'), ['start' => 'TMP100', 'end' => 'TMP229']);

        $response->assertOk();
        $this->assertStringContainsString('temporary-id-cards-TMP100-TMP229.zip', $response->headers->get('content-disposition'));

        $zipPath = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertSame(['temporary-id-cards-TMP100-TMP219.pdf', 'temporary-id-cards-TMP220-TMP229.pdf'], $names);
        $this->assertSame(130, DB::table('temporary_id_cards')->where('status', 'downloaded')->count());
    }

    public function test_more_than_1000_cards_is_refused(): void
    {
        $this->buildInstitute();
        $this->artisan('nexora:card-mode temporary')->assertExitCode(0);

        $this->actingAs($this->inst['admin'])
            ->from(route('temporary_id_cards.preview'))
            ->post(route('temporary_id_cards.download-pdf'), ['start' => 'TMP1', 'end' => 'TMP1001'])
            ->assertRedirect()
            ->assertSessionHas('error', 'You can download maximum 1000 cards at once.');
    }

    public function test_typed_name_in_qr_search_is_not_logged_as_an_error(): void
    {
        $this->buildInstitute();
        \Laravel\Sanctum\Sanctum::actingAs($this->inst['admin']);

        $handler = \Mockery::spy(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $this->app->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, $handler);

        $this->getJson(route('api.students.filter', ['qr_code' => 'RITHUSHI AGINI']))
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $handler->shouldNotHaveReceived('report');
    }
}
