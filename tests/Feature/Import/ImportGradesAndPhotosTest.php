<?php

namespace Tests\Feature\Import;

use App\Models\Grade;
use App\Models\QuickPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Moving an institute from the old system: grades and quick photos. */
class ImportGradesAndPhotosTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->dir = sys_get_temp_dir() . '/nexora-import-' . uniqid();
        mkdir($this->dir . '/images', 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/images/*') ?: []);
        array_map('unlink', glob($this->dir . '/*.*') ?: []);
        @rmdir($this->dir . '/images');
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function test_grades_are_added_once(): void
    {
        Grade::create(['grade_name' => '11', 'is_active' => true]);
        $deleted = Grade::create(['grade_name' => 'ALL', 'is_active' => true]);
        $deleted->delete();

        $this->artisan('nexora:grades', ['action' => 'add', 'names' => ['2025 A/L', ' 2027  A/L ', 'all', '11', '2025 a/l']])
            ->expectsOutput('Added: 2025 A/L')
            ->expectsOutput('Added: 2027 A/L')
            ->expectsOutput('Restored: ALL')
            ->expectsOutput('Already there: 11')
            ->assertExitCode(0);

        $this->assertSame(['11', 'ALL', '2025 A/L', '2027 A/L'], Grade::orderBy('id')->pluck('grade_name')->all());
        $this->artisan('nexora:grades', ['action' => 'remove', 'names' => ['x']])->assertExitCode(1);
    }

    private function dump(): string
    {
        $sql = <<<'SQL'
-- Dumping data for table savidya_old.quick_photo_details
INSERT INTO `quick_photo_details` (`id`, `custom_id`, `quick_img`, `grade_id`, `is_active`, `created_at`, `updated_at`, `grade_name`) VALUES
	(8, 'CQS00001', 'https://admin.old.test/uploads/1727429708.jpg', 28, 0, '2024-09-27 19:05:12', '2024-09-27 20:26:21', '11'),
	(9, 'CQS00002', 'https://admin.old.test/uploads/1727429799.JPG', 28, 1, '2024-09-28 08:00:00', NULL, 'Pre school (5)'),
	(10, 'CQS00003', 'https://admin.old.test/uploads/1727430000.jpg', 29, 1, NULL, NULL, '2025 A/L'),
	(11, 'CQS00004', '1766943080', 31, 1, NULL, NULL, 'ALL');
SQL;
        file_put_contents($this->dir . '/quick_image.sql', $sql);

        return $this->dir . '/quick_image.sql';
    }

    public function test_dry_run_changes_nothing(): void
    {
        file_put_contents($this->dir . '/images/1727429708.jpg', 'jpg');

        $this->artisan('nexora:import-photos', ['file' => $this->dump(), '--images' => $this->dir . '/images', '--dry-run' => true])
            ->expectsOutput('DRY RUN - nothing was changed')
            ->assertExitCode(0);

        $this->assertSame(0, QuickPhoto::count());
        Storage::disk('public')->assertMissing('uploads/1727429708.jpg');
    }

    public function test_photos_are_imported_with_their_pictures(): void
    {
        file_put_contents($this->dir . '/images/1727429708.jpg', 'picture-1');
        file_put_contents($this->dir . '/images/1727429799.JPG', 'picture-2');
        // already uploaded to the server by hand
        Storage::disk('public')->put('uploads/1727430000.jpg', 'picture-3');

        $this->artisan('nexora:import-photos', ['file' => $this->dump(), '--images' => $this->dir . '/images'])
            ->assertExitCode(0);

        $this->assertSame(3, QuickPhoto::count());

        $used = QuickPhoto::where('custom_id', 'CQS00001')->first();
        $this->assertSame('uploads/1727429708.jpg', $used->image_path);
        $this->assertFalse($used->is_active, 'used in the old system');
        $this->assertSame('2024-09-27 19:05:12', $used->created_at->format('Y-m-d H:i:s'));
        Storage::disk('public')->assertExists('uploads/1727429708.jpg');
        $this->assertSame('picture-1', Storage::disk('public')->get('uploads/1727429708.jpg'));

        $this->assertTrue(QuickPhoto::where('custom_id', 'CQS00002')->value('is_active'), 'still free for a new registration');
        $this->assertSame('uploads/1727430000.jpg', QuickPhoto::where('custom_id', 'CQS00003')->value('image_path'));
        $this->assertFalse(QuickPhoto::where('custom_id', 'CQS00004')->exists(), 'not an image');
        $this->assertStringContainsString('CQS00004', file_get_contents(storage_path('logs/import-photos-problems.txt')));

        // run again: nothing new, a photo used in the new system keeps its state
        QuickPhoto::where('custom_id', 'CQS00002')->update(['is_active' => false]);
        $this->artisan('nexora:import-photos', ['file' => $this->dump(), '--images' => $this->dir . '/images'])->assertExitCode(0);
        $this->assertSame(3, QuickPhoto::count());
        $this->assertFalse(QuickPhoto::where('custom_id', 'CQS00002')->value('is_active'));
    }

    public function test_missing_pictures_are_skipped(): void
    {
        $this->artisan('nexora:import-photos', ['file' => $this->dump()])->assertExitCode(0);

        $this->assertSame(0, QuickPhoto::count());
        $this->assertStringContainsString('CQS00001: picture 1727429708.jpg not found', file_get_contents(storage_path('logs/import-photos-problems.txt')));
    }

    public function test_csv_works_too(): void
    {
        file_put_contents($this->dir . '/photos.csv', "\xEF\xBB\xBFcustom_id,quick_img,is_active\nCQS00010,https://x.test/uploads/a1.jpg,1\n");
        file_put_contents($this->dir . '/images/a1.jpg', 'a');

        $this->artisan('nexora:import-photos', ['file' => $this->dir . '/photos.csv', '--images' => $this->dir . '/images'])->assertExitCode(0);

        $this->assertSame('uploads/a1.jpg', QuickPhoto::where('custom_id', 'CQS00010')->value('image_path'));
    }

    public function test_bad_input_fails(): void
    {
        $this->artisan('nexora:import-photos', ['file' => $this->dir . '/nope.sql'])->assertExitCode(1);
        $this->artisan('nexora:import-photos', ['file' => $this->dump(), '--images' => $this->dir . '/nope'])->assertExitCode(1);
    }
}
