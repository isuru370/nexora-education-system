<?php

namespace Tests\Feature;

use App\Services\System\DeployCheckService;
use App\Services\System\FolderPermissionService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Folders made by root in the terminal (photo import, deploy caches) must
 * go back to the web user, or photo uploads fail with "Permission denied".
 */
class FolderPermissionTest extends TestCase
{
    private ?string $dir = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            $this->markTestSkipped('Needs root to change owners.');
        }

        $this->dir = storage_path('framework/testing/perm-' . uniqid());
        File::makeDirectory($this->dir . '/uploads', 0755, true);
        file_put_contents($this->dir . '/uploads/photo.jpg', 'x');
        chmod($this->dir . '/uploads/photo.jpg', 0644);
    }

    protected function tearDown(): void
    {
        if ($this->dir !== null) {
            File::deleteDirectory($this->dir);
        }
        parent::tearDown();
    }

    public function test_root_owned_upload_folder_goes_back_to_the_web_user(): void
    {
        config(['setup.web_user' => 'nobody']);
        $nobody = posix_getpwnam('nobody');

        $this->assertSame(0, fileowner($this->dir . '/uploads'));

        $result = app(FolderPermissionService::class)->fix([$this->dir]);
        clearstatcache();

        $this->assertSame(DeployCheckService::OK, $result['status']);
        $this->assertStringContainsString('-> nobody (3 fixed)', $result['message']);

        foreach ([$this->dir, $this->dir . '/uploads', $this->dir . '/uploads/photo.jpg'] as $path) {
            $this->assertSame($nobody['uid'], fileowner($path), $path);
            $this->assertSame($nobody['gid'], filegroup($path), $path);
        }

        $this->assertSame(0775, fileperms($this->dir . '/uploads') & 0777);
        $this->assertSame(0664, fileperms($this->dir . '/uploads/photo.jpg') & 0777);

        // Second run: nothing left to fix
        $again = app(FolderPermissionService::class)->fix([$this->dir]);
        $this->assertStringNotContainsString('fixed', $again['message']);
    }

    public function test_unknown_web_user_is_a_warning(): void
    {
        config(['setup.web_user' => 'no-such-user-xyz']);

        $result = app(FolderPermissionService::class)->fix([$this->dir]);

        $this->assertSame(DeployCheckService::WARN, $result['status']);
        $this->assertStringContainsString('set WEB_USER', $result['message']);
        $this->assertSame(0, fileowner($this->dir . '/uploads'));
    }
}
