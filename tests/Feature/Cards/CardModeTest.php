<?php

namespace Tests\Feature\Cards;

use App\Models\User;
use App\Support\CardAccess;
use App\Support\CardMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Student ID cards:
 * - card mode (permanent / temporary) is chosen by the developer with
 *   `php artisan nexora:card-mode` (default permanent)
 * - generate: SUPER_ADMIN; download / manage: + ADMIN, BRANCH_ADMIN;
 *   assign / view: + USER; teachers: nothing
 */
class CardModeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();

        $this->users = [
            'SUPER_ADMIN' => $this->makeUser('SUPER_ADMIN'),
            'ADMIN' => $this->inst['admin'],
            'BRANCH_ADMIN' => $this->makeUser('BRANCH_ADMIN'),
            'USER' => $this->inst['cashier'],
            'TEACHER' => $this->makeUser('TEACHER'),
        ];
    }

    private function makeUser(string $code): User
    {
        $type = DB::table('user_types')->where('code', $code)->value('id')
            ?? DB::table('user_types')->insertGetId(['name' => $code, 'code' => $code, 'is_active' => 1]);

        $user = User::forceCreate([
            'name' => $code,
            'email' => strtolower($code) . '@cards.test',
            'password' => 'password123',
            'user_type_id' => $type,
            'is_active' => 1,
        ]);

        DB::table('branch_users')->insert(['user_id' => $user->id, 'branch_id' => $this->inst['b1']]);

        return $user;
    }

    /* ------------------------------------------------------------------ */
    /* Mode + command                                                     */
    /* ------------------------------------------------------------------ */

    public function test_default_mode_is_permanent_and_the_command_changes_it(): void
    {
        $this->assertSame('permanent', CardMode::get());
        $this->assertSame('permanent', card_mode());
        $this->assertFalse(CardMode::isSet());

        $this->artisan('nexora:card-mode')
            ->expectsOutput('Card mode: PERMANENT - Permanent card at registration (default, not set yet)')
            ->assertExitCode(0);

        $this->artisan('nexora:card-mode temporary')
            ->expectsOutput('Card mode is now TEMPORARY: Temporary card first (permanent card later).')
            ->assertExitCode(0);

        $this->assertTrue(CardMode::temporary());
        $this->assertDatabaseHas('system_settings', ['key' => 'card_mode', 'value' => 'temporary']);

        $this->artisan('nexora:card-mode both')->assertExitCode(1);
        $this->assertTrue(CardMode::temporary(), 'unknown mode must not change it');

        $this->artisan('nexora:card-mode PERMANENT')->assertExitCode(0);
        $this->assertTrue(CardMode::permanent());
    }

    /* ------------------------------------------------------------------ */
    /* Web: one way to register                                           */
    /* ------------------------------------------------------------------ */

    public function test_permanent_mode_has_no_temporary_registration_or_cards(): void
    {
        $this->actingAs($this->users['USER']);

        $this->get('/student-temporary-registration')
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('error');
        $this->post('/student-temporary-registration', [])->assertRedirect(route('students.index'));

        $this->actingAs($this->users['ADMIN']);
        $this->get('/temporary_id_cards')->assertRedirect(route('students.index'));

        $this->assertNotSame(403, $this->get('/student-card-registration')->status());
        $this->assertNotSame(302, $this->get('/student-card-registration')->status());

        $page = $this->get('/students')->assertOk();
        $page->assertSee('New Registration');
        $page->assertDontSee('Temporary Card)');
        $page->assertDontSee('Temporary Cards');
    }

    public function test_temporary_mode_has_no_direct_permanent_registration(): void
    {
        CardMode::set('temporary');
        $this->actingAs($this->users['USER']);

        $this->get('/student-card-registration')->assertRedirect(route('students.index'));
        $this->assertSame(200, $this->get('/student-temporary-registration')->status());

        $page = $this->get('/students')->assertOk();
        $page->assertSee('New Registration (Temporary Card)');
        $page->assertSee('Temporary Cards');
    }

    /* ------------------------------------------------------------------ */
    /* Web: who can do what                                               */
    /* ------------------------------------------------------------------ */

    public function test_card_routes_use_the_card_roles(): void
    {
        $expect = [
            'student-cards.index' => CardAccess::VIEW_ROLES,
            'student-cards.show' => CardAccess::VIEW_ROLES,
            'student-cards.generate' => CardAccess::GENERATE_ROLES,
            'student-cards.generate.store' => CardAccess::GENERATE_ROLES,
            'student-cards.preview' => CardAccess::DOWNLOAD_ROLES,
            'student-cards.assign-form' => CardAccess::ASSIGN_ROLES,
            'student-cards.assign' => CardAccess::ASSIGN_ROLES,
            'student-cards.replace' => CardAccess::MANAGE_ROLES,
            'student-cards.lost' => CardAccess::MANAGE_ROLES,
            'student-cards.damaged' => CardAccess::MANAGE_ROLES,
            'student-cards.deactivate' => CardAccess::MANAGE_ROLES,
            'temporary_id_cards.index' => CardAccess::VIEW_ROLES,
            'temporary_id_cards.create' => CardAccess::GENERATE_ROLES,
            'temporary_id_cards.store' => CardAccess::GENERATE_ROLES,
            'temporary_id_cards.download-pdf' => CardAccess::DOWNLOAD_ROLES,
            'temporary_id_cards.pdf' => CardAccess::DOWNLOAD_ROLES,
            'temporary_id_cards.change-status' => CardAccess::MANAGE_ROLES,
            'generate-student-id.index' => CardAccess::DOWNLOAD_ROLES,
            'student-card-registration.store' => CardAccess::ASSIGN_ROLES,
            'student-temporary-registration.store' => CardAccess::ASSIGN_ROLES,
            'api.card.reassign' => CardAccess::MANAGE_ROLES,
        ];

        foreach ($expect as $name => $roles) {
            $middleware = app('router')->getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('role:' . $roles, $middleware, $name);
            $this->assertNotContains('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER', $middleware, $name);
        }

        $this->assertSame('SUPER_ADMIN', CardAccess::GENERATE_ROLES);
        $this->assertStringNotContainsString('TEACHER', CardAccess::VIEW_ROLES);
        $this->assertStringNotContainsString('USER', CardAccess::MANAGE_ROLES);
    }

    public function test_only_super_admin_can_open_card_generation(): void
    {
        foreach (['ADMIN', 'BRANCH_ADMIN', 'USER', 'TEACHER'] as $role) {
            $this->actingAs($this->users[$role]);
            $this->get('/student-cards/generate')->assertForbidden();
        }

        $this->actingAs($this->users['SUPER_ADMIN']);
        $this->assertNotSame(403, $this->get('/student-cards/generate')->status());
    }

    public function test_download_pages_are_for_admins_and_branch_admins(): void
    {
        foreach (['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'] as $role) {
            $this->actingAs($this->users[$role]);
            $this->assertNotSame(403, $this->get('/generate-student-id')->status(), $role);
        }

        foreach (['USER', 'TEACHER'] as $role) {
            $this->actingAs($this->users[$role]);
            $this->get('/generate-student-id')->assertForbidden();
        }
    }

    public function test_teachers_cannot_open_card_pages(): void
    {
        $this->actingAs($this->users['TEACHER']);

        $this->get('/student-cards')->assertForbidden();
        $this->get('/student-cards/available')->assertForbidden();

        $this->actingAs($this->users['USER']);
        $this->assertSame(200, $this->get('/student-cards')->status());
    }

    public function test_cashier_sees_cards_but_no_generate_button(): void
    {
        $this->actingAs($this->users['USER']);
        $this->get('/student-cards')->assertOk()->assertDontSee('Generate Cards');

        $this->actingAs($this->users['SUPER_ADMIN']);
        $this->get('/student-cards')->assertOk()->assertSee('Generate Cards');
    }

    /* ------------------------------------------------------------------ */
    /* Staff app API                                                      */
    /* ------------------------------------------------------------------ */

    public function test_app_registration_follows_the_card_mode(): void
    {
        Sanctum::actingAs($this->users['USER']);

        // permanent mode: temporary registration is refused
        $this->postJson('/api/student', [])
            ->assertForbidden()
            ->assertJson(['success' => false, 'card_mode' => 'permanent']);
        $this->assertNotSame(403, $this->postJson('/api/student-new', [])->status());

        CardMode::set('temporary');

        $this->postJson('/api/student-new', [])
            ->assertForbidden()
            ->assertJson(['card_mode' => 'temporary']);
        $this->assertNotSame(403, $this->postJson('/api/student', [])->status());
    }

    public function test_cashier_cannot_replace_a_card_from_the_app(): void
    {
        Sanctum::actingAs($this->users['USER']);
        $this->postJson('/api/re-assign', [])->assertForbidden();

        Sanctum::actingAs($this->users['BRANCH_ADMIN']);
        $this->assertNotSame(403, $this->postJson('/api/re-assign', [])->status());
    }

    public function test_app_menus_follow_the_card_mode_and_role(): void
    {
        Sanctum::actingAs($this->users['USER']);

        $features = $this->getJson('/api/mobile-dashboard')->assertOk()->json('data.features');
        $this->assertContains('register_permanent', $features);
        $this->assertNotContains('register_temporary', $features);
        $this->assertNotContains('reassign_card', $features, 'no Re Assign in permanent mode');
        $this->assertNotContains('replace_card', $features, 'cashier cannot replace cards');
        $this->getJson('/api/mobile-dashboard')->assertJsonPath('data.card_mode', 'permanent');

        CardMode::set('temporary');

        $features = $this->getJson('/api/mobile-dashboard')->json('data.features');
        $this->assertContains('register_temporary', $features);
        $this->assertContains('reassign_card', $features);

        Sanctum::actingAs($this->users['BRANCH_ADMIN']);
        $this->assertContains('replace_card', $this->getJson('/api/mobile-dashboard')->json('data.features'));

        $this->getJson('/api/features')->assertOk()->assertJsonPath('card_mode', 'temporary');
        $this->getJson('/api/me')->assertOk()->assertJsonFragment(['card_mode' => 'temporary']);
    }
}
