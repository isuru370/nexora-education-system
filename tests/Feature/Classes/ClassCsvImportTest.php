<?php

namespace Tests\Feature\Classes;

use App\Models\ClassCategoryFeeOption;
use App\Models\ClassPaymentConfig;
use App\Models\StudentClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Classes -> categories -> fee options from a CSV: template, export,
 * preview (nothing saved), confirm, branch admin limits.
 */
class ClassCsvImportTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private const HEADER = 'branch,class_name,teacher_id,teacher_name,subject,grade,medium,class_type,class_active,category,option_label,fee,is_default,option_active,teacher_percentage,organizer_code,organizer_percentage,institution_percentage';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->buildInstitute();
    }

    private function csv(array $lines): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('classes.csv', "\xEF\xBB\xBF" . self::HEADER . "\n" . implode("\n", $lines) . "\n");
    }

    private function preview($user, array $lines)
    {
        return $this->actingAs($user)->post(route('class-import.preview'), ['file' => $this->csv($lines)]);
    }

    private function confirm($user, $preview)
    {
        $token = $preview->viewData('token');
        $this->assertNotNull($token, 'preview had errors: ' . json_encode(collect($preview->viewData('result')['rows'])->where('status', 'error')->pluck('changes')));

        return $this->actingAs($user)->post(route('class-import.apply'), ['token' => $token]);
    }

    public function test_page_template_and_export(): void
    {
        $admin = $this->inst['admin'];

        $this->actingAs($admin)->get(route('class-import.index'))->assertOk()->assertSee('Nimal Perera')->assertSee('Upload and preview');
        $this->actingAs($admin)->get(route('student-classes.index'))->assertSee('Import / Update (CSV)');

        $template = $this->actingAs($admin)->get(route('class-import.template'))->assertOk()->getContent();
        $this->assertStringContainsString(self::HEADER, $template);

        $export = $this->actingAs($admin)->get(route('class-import.export'))->assertOk()->getContent();
        $this->assertStringContainsString('Colombo,"Maths 10 CMB",T1,"Nimal Perera",Maths,10,Sinhala,offline,yes,Theory,Normal,2000,yes,yes,60,,0,40', $export);
        $this->assertStringContainsString('Maths 10 KDY', $export);
    }

    public function test_new_class_with_two_options_is_created_only_after_confirm(): void
    {
        $admin = $this->inst['admin'];
        $lines = [
            'Colombo,Science 11,T1,Nimal Perera,Science,11,English,offline,yes,Theory,Normal,2500,yes,yes,70,,0,30',
            'Colombo,Science 11,T1,Nimal Perera,Science,11,English,offline,yes,Theory,Half card,1250,no,yes,70,,0,30',
            'CMB,Science 11,T1,N. Perera,Science,11,English,offline,yes,Paper class,Normal,1500,,,70,,0,30',
        ];

        $preview = $this->preview($admin, $lines)->assertOk();
        $preview->assertSee('New class')->assertSee('New category');
        $this->assertSame(1, $preview->viewData('result')['summary']['new_classes']);
        $this->assertSame(0, StudentClass::where('class_name', 'Science 11')->count(), 'preview must not save');

        $this->confirm($admin, $preview)->assertRedirect(route('student-classes.index'))->assertSessionHas('success');

        $class = StudentClass::where('class_name', 'Science 11')->firstOrFail();
        $this->assertSame('English', $class->medium);
        $this->assertSame(3, ClassCategoryFeeOption::whereHas('classCategoryFee', fn ($q) => $q->where('student_class_id', $class->id))->count());
        $config = ClassPaymentConfig::where('student_class_id', $class->id)->firstOrFail();
        $this->assertEquals(70, $config->teacher_percentage);
        $this->assertEquals(30, $config->institution_percentage);
        $this->assertDatabaseHas('subjects', ['subject_name' => 'Science']);
        $this->assertDatabaseHas('class_categories', ['category_name' => 'Paper class']);

        // the first option of each category is the default
        $this->assertSame(2, ClassCategoryFeeOption::whereHas('classCategoryFee', fn ($q) => $q->where('student_class_id', $class->id))->where('is_default', true)->count());
    }

    public function test_existing_class_fee_and_shares_are_updated_and_the_same_file_again_changes_nothing(): void
    {
        $admin = $this->inst['admin'];
        $lines = [
            'Colombo,Maths 10 CMB,T1,Nimal Perera,Maths,10,Sinhala,offline,yes,Theory,Normal,2500,yes,yes,65,,0,35',
            'Colombo,Maths 10 CMB,T1,Nimal Perera,Maths,10,Sinhala,offline,yes,Theory,Half card,1000,no,yes,65,,0,35',
        ];

        $preview = $this->preview($admin, $lines)
            ->assertSee('Normal: fee Rs. 2,000.00 → Rs. 2,500.00', false)
            ->assertSee('Shares 60/0/40 → 65/0/35', false);
        $this->confirm($admin, $preview);

        $class = StudentClass::where('class_name', 'Maths 10 CMB')->firstOrFail();
        $this->assertSame(1, StudentClass::where('class_name', 'Maths 10 CMB')->count(), 'no duplicate class');
        $this->assertEquals(2500, ClassCategoryFeeOption::where('label', 'Normal')->whereHas('classCategoryFee', fn ($q) => $q->where('student_class_id', $class->id))->value('fee'));
        $this->assertEquals(65, ClassPaymentConfig::where('student_class_id', $class->id)->value('teacher_percentage'));

        // the other branch's class is untouched
        $kdy = StudentClass::where('class_name', 'Maths 10 KDY')->firstOrFail();
        $this->assertEquals(2000, ClassCategoryFeeOption::whereHas('classCategoryFee', fn ($q) => $q->where('student_class_id', $kdy->id))->value('fee'));

        // upload the same file again: nothing to change
        $again = $this->preview($admin, $lines);
        $this->assertSame(['new_classes' => 0, 'updated_classes' => 0, 'new_categories' => 0, 'new_options' => 0, 'updated_options' => 0, 'unchanged' => 2, 'errors' => 0], $again->viewData('result')['summary']);
    }

    public function test_errors_block_the_whole_file(): void
    {
        $admin = $this->inst['admin'];

        $preview = $this->preview($admin, [
            'Colombo,New A,T1,Nimal Perera,Maths,10,Sinhala,offline,yes,Theory,Normal,2000,yes,yes,70,,0,30',
            'Colombo,New B,T9,Someone,Maths,10,Sinhala,offline,yes,Theory,Normal,2000,yes,yes,70,,0,30',
            'Colombo,New C,T1,Sunil Silva,Maths,10,Sinhala,offline,yes,Theory,Normal,2000,yes,yes,70,,0,30',
            'Colombo,New D,T1,Nimal Perera,Maths,10,Sinhala,weekly,yes,Theory,Normal,abc,yes,yes,70,,0,20',
            'Nowhere,New E,T1,Nimal Perera,Maths,10,Sinhala,offline,yes,Theory,Normal,2000,yes,yes,70,,10,20',
            'Colombo,New A,T1,Nimal Perera,Maths,10,Sinhala,online,yes,Theory,Normal,2000,yes,yes,70,,0,30',
        ])->assertOk();

        $text = $preview->getContent();
        foreach ([
            'Teacher ID &quot;T9&quot; not found.',
            'T1 is Nimal Perera, not Sunil Silva.',
            'class_type must be offline, online or hybrid.',
            'fee must be a number (0 or more).',
            'must add up to 100 (now 90)',
            'Branch &quot;Nowhere&quot; not found.',
            'organizer_code is needed',
            '&quot;class_type&quot; is different from row 2 of the same class.',
            'The same class, category and option is also on row 2.',
        ] as $message) {
            $this->assertStringContainsString($message, $text);
        }

        $this->assertNull($preview->viewData('token'));
        $this->assertSame(0, StudentClass::where('class_name', 'New A')->count());
    }

    public function test_branch_admin_only_changes_their_branch(): void
    {
        $branchAdmin = $this->makeRoleUser('BRANCH_ADMIN', $this->inst['b1']);

        $export = $this->actingAs($branchAdmin)->get(route('class-import.export'))->assertOk()->getContent();
        $this->assertStringContainsString('Maths 10 CMB', $export);
        $this->assertStringNotContainsString('Maths 10 KDY', $export);

        $this->preview($branchAdmin, [
            'Kandy,Maths 10 KDY,T1,Nimal Perera,Maths,10,Sinhala,offline,yes,Theory,Normal,9999,yes,yes,60,,0,40',
        ])->assertSee("You can't change classes of the Kandy branch.");

        $this->assertDatabaseMissing('class_category_fee_options', ['fee' => 9999]);

        // cashiers cannot open it
        $this->actingAs($this->inst['cashier'])->get(route('class-import.index'))->assertForbidden();
    }

    public function test_option_can_be_deactivated_and_default_moved(): void
    {
        $admin = $this->inst['admin'];
        $preview = $this->preview($admin, [
            'Colombo,Maths 10 CMB,T1,Nimal Perera,Maths,10,Sinhala,offline,yes,Theory,Normal,2000,no,no,60,,0,40',
            'Colombo,Maths 10 CMB,T1,Nimal Perera,Maths,10,Sinhala,offline,yes,Theory,Special,1800,yes,yes,60,,0,40',
        ]);
        $this->confirm($admin, $preview);

        $class = StudentClass::where('class_name', 'Maths 10 CMB')->firstOrFail();
        $options = ClassCategoryFeeOption::whereHas('classCategoryFee', fn ($q) => $q->where('student_class_id', $class->id))->get()->keyBy('label');
        $this->assertFalse((bool) $options['Normal']->is_active);
        $this->assertFalse((bool) $options['Normal']->is_default);
        $this->assertTrue((bool) $options['Special']->is_default);
    }

    public function test_confirm_token_belongs_to_the_uploader(): void
    {
        $preview = $this->preview($this->inst['admin'], [
            'Colombo,Other 1,T1,Nimal Perera,Maths,10,Sinhala,offline,yes,Theory,Normal,2000,yes,yes,70,,0,30',
        ]);
        $token = $preview->viewData('token');

        $other = $this->makeRoleUser('SUPER_ADMIN');
        $this->actingAs($other)->post(route('class-import.apply'), ['token' => $token])
            ->assertRedirect(route('class-import.index'))->assertSessionHasErrors('file');
        $this->assertSame(0, StudentClass::where('class_name', 'Other 1')->count());
    }
}
