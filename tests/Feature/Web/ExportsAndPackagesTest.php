<?php

namespace Tests\Feature\Web;

use App\Exports\Web\Report\TableReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Downloads (Excel / PDF / print) and the packages behind them still work
 * after a framework or package upgrade.
 */
class ExportsAndPackagesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 15:20:00');
        $this->buildInstitute();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_every_download_without_a_parameter_works_for_an_admin(): void
    {
        $uris = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('GET', $route->methods())
                && !str_contains($route->uri(), '{')
                && in_array('web', $route->gatherMiddleware())
                && preg_match('/export|download|pdf|excel|print/i', $route->uri())
                && !preg_match('/backup/i', $route->uri()))
            ->map(fn (Route $route) => '/' . ltrim($route->uri(), '/'))
            ->unique()
            ->values();

        $this->assertNotEmpty($uris);

        $errors = [];
        foreach ($uris as $uri) {
            $response = $this->actingAs($this->inst['admin'])->get($uri);

            if ($response->getStatusCode() >= 500) {
                X
                    . ($response->exception ? ': ' . mb_substr($response->exception->getMessage(), 0, 200) : '');
            }
        }

        $this->assertSame([], $errors, "Downloads with server errors:\n" . implode("\n", $errors));
    }

    public function test_excel_file_is_made_and_read_back(): void
    {
        $export = new TableReportExport(
            'Test report',
            ['Date: 13 Oct 2026'],
            ['headings' => ['Name', 'Amount (Rs.)'], 'columns' => ['name', 'amount'], 'money' => ['amount'], 'total' => null],
            collect([(object) ['name' => 'K. Perera', 'amount' => 2000]]),
            'Payments'
        );

        $response = Excel::download($export, 'test.xlsx');
        $file = $response->getFile()->getPathname();

        $this->assertStringStartsWith('PK', file_get_contents($file, false, null, 0, 2));

        $sheets = Excel::toArray(new class {}, $file, null, \Maatwebsite\Excel\Excel::XLSX);
        $this->assertStringContainsString('K. Perera', json_encode($sheets));
    }

    public function test_pdf_and_qr_code_are_made(): void
    {
        $pdf = Pdf::loadHTML('<h1>Receipt</h1><p>Rs. 2,000.00</p>')->output();
        $this->assertStringStartsWith('%PDF', $pdf);

        $svg = (string) QrCode::format('svg')->size(120)->generate('SA08001');
        $this->assertStringContainsString('<svg', $svg);
    }
}
