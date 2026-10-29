<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

/**
 * Public privacy policy page (Play Store privacy policy URL).
 */
class PrivacyPolicyTest extends TestCase
{
    public function test_anyone_can_open_the_privacy_policy(): void
    {
        config([
            'services.nexora.support_email' => 'support@nexorait.lk',
            'services.institute.name' => 'Savidya Education',
            'services.institute.contact_mobile' => '0771234567',
        ]);

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Camera')
            ->assertSee('Bluetooth')
            ->assertSee('support@nexorait.lk')
            ->assertSee('Savidya Education')
            ->assertSee('0771234567');
    }

    public function test_contact_falls_back_to_the_institute_when_not_set(): void
    {
        config([
            'services.nexora.support_email' => null,
            'services.institute.contact_email' => null,
            'services.institute.contact_mobile' => null,
        ]);

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('contact your institute')
            ->assertDontSee('Nexora IT (developer):');
    }
}
