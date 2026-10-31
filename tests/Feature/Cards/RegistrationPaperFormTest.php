<?php

namespace Tests\Feature\Cards;

use App\Support\CardMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Blank A4 registration form to print (permanent card institutes) */
class RegistrationPaperFormTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
    }

    public function test_cashier_prints_the_form_with_the_institute_name(): void
    {
        config(['services.institute.name' => 'Success Academy']);

        $this->actingAs($this->inst['cashier'])->get('/student-card-registration/form')
            ->assertOk()
            ->assertSee('STUDENT REGISTRATION FORM')
            ->assertSee('Success Academy')
            ->assertSee('Parent / Guardian Mobile')
            ->assertSee('Image ID')
            ->assertSee('ID Card No. (QR)')
            ->assertSee('School')
            ->assertDontSee('NIC')
            ->assertDontSee('DECLARATION');

        $this->actingAs($this->inst['cashier'])->get('/student-card-registration')
            ->assertSee(route('student-card-registration.paper-form'), false);
    }

    public function test_only_permanent_card_institutes_and_staff(): void
    {
        CardMode::set(CardMode::TEMPORARY);

        $this->actingAs($this->inst['cashier'])->get('/student-card-registration/form')
            ->assertRedirect(route('students.index'));
    }
}
