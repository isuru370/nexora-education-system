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
                'card_number' => $i % 2 ? sprintf('C%05d', $i) : null,
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
