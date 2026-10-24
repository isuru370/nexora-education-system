<?php

namespace Tests\Feature;

use App\Support\InstituteInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** GET /api/institute: name / tagline / logo for the staff app. */
class InstituteInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_reads_the_institute_without_login(): void
    {
        config([
            'app.url' => 'https://mihisara.nexorait.lk/',
            'services.institute.code' => 'MHEI',
            'services.institute.name' => 'Mihisara Education Institute',
            'services.institute.tagline' => 'Towards a bright future',
            'services.institute.logo' => 'logo/logo.png',
            'services.institute.short_name' => null,
        ]);

        $this->getJson('/api/institute')->assertOk()->assertExactJson([
            'status' => 'success',
            'message' => 'Institute loaded successfully.',
            'data' => [
                'code' => 'MHEI',
                'name' => 'Mihisara Education Institute',
                'short_name' => 'MihisaraEdu',
                'tagline' => 'Towards a bright future',
                'logo_url' => 'https://mihisara.nexorait.lk/logo/logo.png',
                'app_url' => 'https://mihisara.nexorait.lk',
            ],
        ]);
    }

    public function test_short_name_and_fallbacks(): void
    {
        config(['services.institute.name' => 'NEXORA Education Institute', 'services.institute.short_name' => null]);
        $this->assertSame('NexoraEdu', InstituteInfo::shortName());

        config(['services.institute.name' => 'Savidya Higher Education Institute']);
        $this->assertSame('SavidyaEdu', InstituteInfo::shortName());

        config(['services.institute.short_name' => 'SHEI App']);
        $this->assertSame('SHEI App', InstituteInfo::shortName());

        config(['services.institute.name' => null, 'app.name' => 'Kandy Academy', 'services.institute.short_name' => '']);
        $this->assertSame('Kandy Academy', InstituteInfo::name());
        $this->assertSame('KandyEdu', InstituteInfo::shortName());

        config(['services.institute.logo' => 'https://cdn.lk/a.png']);
        $this->assertSame('https://cdn.lk/a.png', InstituteInfo::logoUrl());

        config(['services.institute.logo' => '']);
        $this->assertNull(InstituteInfo::logoUrl());
    }
}
