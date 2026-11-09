<?php

namespace Tests\Feature\Classes;

use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use App\Models\StudentClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Class create / edit: class details, shares and "Categories & Fees" saved
 * together; a class can't be saved without a fee.
 */
class ClassFormFeesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private int $theory;
    private int $revision;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        $this->theory = (int) DB::table('class_categories')->where('category_name', 'Theory')->value('id');
        $this->revision = (int) DB::table('class_categories')->insertGetId(['category_name' => 'Revision', 'code' => 'RV', 'is_active' => 1]);
    }

    private function classData(array $override = []): array
    {
        return array_merge([
            'branch_id' => $this->inst['b1'],
            'class_name' => 'Science 11',
            'class_type' => 'offline',
            'medium' => 'Sinhala',
            'teacher_id' => $this->inst['teacher'],
            'grade_id' => DB::table('grades')->value('id'),
            'subject_id' => DB::table('subjects')->value('id'),
            'teacher_percentage' => 70,
            'organizer_percentage' => 0,
            'institution_percentage' => 30,
            'is_active' => 1,
        ], $override);
    }

    private function optionsOf(StudentClass $class)
    {
        return ClassCategoryFeeOption::query()
            ->whereHas('classCategoryFee', fn ($q) => $q->where('student_class_id', $class->id))
            ->orderBy('id')
            ->get();
    }

    public function test_create_page_shows_the_categories_section(): void
    {
        $this->actingAs($this->inst['admin'])->get(route('student-classes.create'))
            ->assertOk()
            ->assertSee('Categories &amp; Fees', false)
            ->assertSee('Revision')
            ->assertSee('Add fee option');
    }

    public function test_class_is_created_with_categories_and_options_together(): void
    {
        $this->actingAs($this->inst['admin'])->post(route('student-classes.store'), $this->classData([
            'fees' => [
                ['category_id' => $this->theory, 'default' => 1, 'options' => [
                    ['label' => 'Half card', 'fee' => 1000],
                    ['label' => 'Normal', 'fee' => 2000],
                ]],
                ['category_id' => $this->revision, 'options' => [['label' => 'Normal', 'fee' => 1500]]],
            ],
        ]))->assertRedirect(route('student-classes.index'));

        $class = StudentClass::where('class_name', 'Science 11')->firstOrFail();
        $options = $this->optionsOf($class);

        $this->assertSame(2, ClassCategoryFee::where('student_class_id', $class->id)->count());
        $this->assertSame(['Half card', 'Normal', 'Normal'], $options->pluck('label')->all());
        $this->assertSame([false, true, true], $options->pluck('is_default')->map(fn ($v) => (bool) $v)->all());
        $this->assertEquals(1500, $options->last()->fee);
    }

    public function test_a_class_needs_a_fee(): void
    {
        $admin = $this->inst['admin'];

        $this->actingAs($admin)->from(route('student-classes.create'))
            ->post(route('student-classes.store'), $this->classData())
            ->assertSessionHasErrors('fees');

        $this->actingAs($admin)->post(route('student-classes.store'), $this->classData([
            'fees' => [
                ['category_id' => $this->theory, 'options' => [['label' => 'Normal', 'fee' => 2000], ['label' => 'normal', 'fee' => 100]]],
                ['category_id' => $this->theory, 'options' => [['label' => 'X', 'fee' => '']]],
            ],
        ]))->assertSessionHasErrors(['fees.0.options.1.label', 'fees.1.category_id', 'fees.1.options.0.fee']);

        $this->assertSame(0, StudentClass::where('class_name', 'Science 11')->count());
    }

    public function test_edit_changes_fee_adds_option_and_turns_one_off(): void
    {
        $class = StudentClass::where('class_name', 'Maths 10 CMB')->firstOrFail();
        $normal = $this->optionsOf($class)->first();
        $categoryFee = $normal->classCategoryFee;

        $this->actingAs($this->inst['admin'])->get(route('student-classes.edit', $class))
            ->assertOk()->assertSee('value="' . $normal->id . '"', false);

        $this->actingAs($this->inst['admin'])->put(route('student-classes.update', $class), $this->classData([
            'class_name' => 'Maths 10 CMB',
            'teacher_percentage' => 60, 'institution_percentage' => 40,
            'fees' => [
                ['id' => $categoryFee->id, 'category_id' => $this->theory, 'is_active' => 1, 'default' => 1, 'options' => [
                    ['id' => $normal->id, 'label' => 'Normal', 'fee' => 2500, 'is_active' => 0],
                    ['label' => 'Special', 'fee' => 1800],
                ]],
            ],
        ]))->assertRedirect(route('student-classes.index'));

        $options = $this->optionsOf($class)->keyBy('label');
        $this->assertEquals(2500, $options['Normal']->fee);
        $this->assertFalse((bool) $options['Normal']->is_active);
        $this->assertFalse((bool) $options['Normal']->is_default);
        $this->assertTrue((bool) $options['Special']->is_default);
        $this->assertSame(1, ClassCategoryFee::where('student_class_id', $class->id)->count(), 'no duplicate category');
    }

    public function test_an_option_of_another_class_cannot_be_changed(): void
    {
        $class = StudentClass::where('class_name', 'Maths 10 CMB')->firstOrFail();
        $other = $this->optionsOf(StudentClass::where('class_name', 'Maths 10 KDY')->firstOrFail())->first();

        $this->actingAs($this->inst['admin'])->put(route('student-classes.update', $class), $this->classData([
            'class_name' => 'Maths 10 CMB', 'teacher_percentage' => 60, 'institution_percentage' => 40,
            'fees' => [['category_id' => $this->theory, 'options' => [['id' => $other->id, 'label' => 'Hack', 'fee' => 1]]]],
        ]))->assertSessionHasErrors('fees');

        $this->assertEquals(2000, $other->fresh()->fee);
    }

    public function test_class_list_flags_classes_without_fees(): void
    {
        $class = StudentClass::where('class_name', 'Maths 10 KDY')->firstOrFail();
        ClassCategoryFeeOption::query()->whereHas('classCategoryFee', fn ($q) => $q->where('student_class_id', $class->id))->update(['is_active' => false]);

        $html = $this->actingAs($this->inst['admin'])->get(route('student-classes.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'Fees not set'));
    }
}
