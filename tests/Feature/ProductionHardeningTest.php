<?php

namespace Tests\Feature;

use App\Services\FileUpload\ImageUploadService;
use App\Services\Web\Student\StudentPhotoService;
use App\Support\ErrorDetail;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Production hardening: web login limit, safe photo delete, hidden technical
 * errors, staff app login idle expiry, smaller photos.
 */
class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        Storage::fake('public');
    }

    /* ---------- 1. Web login rate limit ---------- */

    public function test_web_login_is_blocked_after_five_wrong_passwords(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        // Even the right password waits now
        $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Too many login attempts.', session('errors')->first('email'));
        $this->assertGuest();

        // Another staff member on the same WiFi is not locked out
        $this->post('/login', ['email' => 'cashier@test.lk', 'password' => 'password123'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($this->inst['cashier']);
    }

    public function test_successful_login_clears_the_count(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'password123']);
        $this->assertAuthenticatedAs($this->inst['admin']);
        $this->post('/logout');

        $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'wrong']);
        $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'password123']);
        $this->assertAuthenticatedAs($this->inst['admin']);
    }

    /* ---------- 2. Photo delete ---------- */

    public function test_upload_delete_only_removes_unused_photos(): void
    {
        $disk = Storage::disk('public');
        $disk->put('uploads/student.jpg', 'x');
        $disk->put('uploads/quick.jpg', 'x');
        $disk->put('uploads/leftover.jpg', 'x');
        $disk->put('logo.png', 'x');

        DB::table('students')->where('id', $this->inst['s1'])->update(['img_url' => 'uploads/student.jpg']);
        DB::table('quick_photos')->insert(['custom_id' => 'QP-001', 'image_path' => 'uploads/quick.jpg', 'is_active' => 1]);

        $this->actingAs($this->inst['cashier']);

        foreach (['uploads/student.jpg', 'uploads/quick.jpg', 'logo.png', 'uploads/../logo.png', 'uploads/male.png'] as $path) {
            $this->deleteJson('/admin/upload/delete', ['path' => $path])->assertStatus(422);
        }

        $this->deleteJson('/admin/upload/delete', ['path' => 'uploads/leftover.jpg'])->assertOk();

        $disk->assertExists(['uploads/student.jpg', 'uploads/quick.jpg', 'logo.png']);
        $disk->assertMissing('uploads/leftover.jpg');
    }

    public function test_replaced_student_photo_is_removed_unless_shared(): void
    {
        $disk = Storage::disk('public');
        $disk->put('uploads/old.jpg', 'x');
        $disk->put('uploads/from-quick.jpg', 'x');
        DB::table('quick_photos')->insert(['custom_id' => 'QP-002', 'image_path' => 'uploads/from-quick.jpg', 'is_active' => 0]);

        $photos = app(StudentPhotoService::class);
        $photos->deleteReplacedImage('uploads/old.jpg', 'uploads/new.jpg');
        $photos->deleteReplacedImage('uploads/from-quick.jpg', 'uploads/new.jpg');
        $photos->deleteReplacedImage('uploads/female.png', 'uploads/new.jpg');

        $disk->assertMissing('uploads/old.jpg');
        $disk->assertExists('uploads/from-quick.jpg');
    }

    /* ---------- 4. Technical errors hidden ---------- */

    public function test_technical_errors_are_hidden_but_own_messages_are_shown(): void
    {
        config(['app.debug' => false]);

        $sql = new QueryException('select * from secret_table', [], new \Exception('SQLSTATE[42S02] secret_table'));

        $this->assertSame(ErrorDetail::FALLBACK, ErrorDetail::safe($sql));
        $this->assertSame(ErrorDetail::FALLBACK, ErrorDetail::safe(new \TypeError('Argument 1 in /var/www/app/X.php')));
        $this->assertSame('Student not found.', ErrorDetail::safe(new DomainException('Student not found.')));
        $this->assertSame('Card already assigned.', ErrorDetail::safe(new \Exception('Card already assigned.')));

        config(['app.debug' => true]);
        $this->assertStringContainsString('secret_table', ErrorDetail::safe($sql));
    }

    /* ---------- 5. Staff app login idle expiry ---------- */

    public function test_staff_app_login_ends_after_seven_idle_days(): void
    {
        $user = $this->inst['cashier'];

        $active = $user->createToken('phone-1');
        $active->accessToken->forceFill(['last_used_at' => now()->subDays(6)])->save();

        $idle = $user->createToken('phone-2');
        $idle->accessToken->forceFill(['last_used_at' => now()->subDays(8)])->save();

        $this->withToken($active->plainTextToken)->getJson('/api/me')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($idle->plainTextToken)->getJson('/api/me')->assertUnauthorized();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $idle->accessToken->id]);

        // A new login is never idle
        $this->app['auth']->forgetGuards();
        $fresh = $user->createToken('phone-3');
        $this->withToken($fresh->plainTextToken)->getJson('/api/me')->assertOk();
    }

    /* ---------- 6. Smaller photos ---------- */

    public function test_uploaded_photos_are_resized(): void
    {
        $file = UploadedFile::fake()->image('big.png', 3000, 2000);

        $path = app(ImageUploadService::class)->upload($file, 'uploads');

        $this->assertStringStartsWith('uploads/', $path);
        $this->assertStringEndsWith('.jpg', $path);

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(800, $width);
        $this->assertSame(533, $height);
    }

    public function test_small_photos_are_not_enlarged(): void
    {
        $path = app(ImageUploadService::class)->upload(UploadedFile::fake()->image('small.jpg', 300, 400), 'uploads');

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([300, 400], [$width, $height]);
    }
}
