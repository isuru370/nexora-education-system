<?php

namespace Tests\Feature\Import;

use App\Console\Commands\ImportOldTeachersCommand;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\Branch;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Import\OldValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Moving an institute from the old system: students and teachers. */
class ImportStudentsTeachersTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->dir = sys_get_temp_dir() . '/nexora-import-' . uniqid();
        mkdir($this->dir . '/images', 0777, true);

        $this->branch = Branch::create(['name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]);
        foreach (['10', '11', '2027 A/L'] as $g) {
            Grade::create(['grade_name' => $g, 'is_active' => true]);
        }
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/images/*') ?: []);
        array_map('unlink', glob($this->dir . '/*.*') ?: []);
        @rmdir($this->dir . '/images');
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function csv(string $name, array $header, array $rows): string
    {
        $file = $this->dir . '/' . $name;
        $h = fopen($file, 'w');
        fwrite($h, "\xEF\xBB\xBF"); // Excel BOM
        fputcsv($h, $header);
        foreach ($rows as $row) {
            fputcsv($h, array_map(fn ($c) => $row[$c] ?? '', $header));
        }
        fclose($h);

        return $file;
    }

    private const STUDENT_COLS = ['custom_id', 'fname', 'lname', 'mobile', 'email', 'whatsapp_mobile', 'nic', 'bday', 'gender',
        'address1', 'address2', 'address3', 'guardian_fname', 'guardian_lname', 'guardian_nic', 'guardian_mobile',
        'is_active', 'img_url', 'grade', 'admission', 'student_school', 'created_at', 'updated_at'];

    private function students(): string
    {
        return $this->csv('students.csv', self::STUDENT_COLS, [
            [
                'custom_id' => 'CS0001', 'fname' => 'Kasun  Perera', 'lname' => 'K. Perera', 'mobile' => '0771234567 / 0711111111',
                'whatsapp_mobile' => '94771234567', 'email' => 'NULL', 'nic' => '200812345678', 'bday' => '05/17/2008', 'gender' => 'Male',
                'address1' => 'No 1', 'guardian_fname' => 'Sunil', 'guardian_lname' => 'Perera', 'guardian_mobile' => '771234567',
                'is_active' => '1', 'img_url' => 'https://admin.old.test/uploads/1727429708.jpg', 'grade' => '2027 A/L Grade',
                'admission' => '1', 'student_school' => 'Royal College', 'created_at' => '2024-09-27 19:05:12', 'updated_at' => '2024-10-01 10:00:00',
            ],
            [
                'custom_id' => 'SA25001', 'fname' => 'Nimali', 'lname' => 'N. Silva', 'mobile' => '`0712223334', 'nic' => '200812345678',
                'bday' => '2009-02-30', 'gender' => 'female', 'guardian_mobile' => 'NULL', 'is_active' => '1',
                'img_url' => 'https://admin.old.test/uploads/1727430000.jpg', 'grade' => '10', 'admission' => '0',
                'created_at' => '2024-09-28 08:00:00',
            ],
            [
                // newer student: only in the CSV export (no gender / address)
                'custom_id' => 'SA25002', 'fname' => 'A.B.', 'lname' => 'Fernando', 'mobile' => '0755555555',
                'bday' => '2010-01-15', 'guardian_mobile' => '0755555555', 'is_active' => '1', 'grade' => '11',
                'img_url' => 'https://other.test/images/logo.png',
            ],
            [
                'custom_id' => 'SA25003', 'fname' => 'Dilini', 'lname' => 'D. Jayasinghe', 'gender' => 'Female',
                'guardian_mobile' => '0766666666', 'grade' => '11', 'img_url' => 'https://admin.old.test/uploads/missing.jpg',
            ],
            ['custom_id' => '', 'fname' => 'No id', 'lname' => 'No id', 'grade' => '10'],
        ]);
    }

    public function test_old_value_cleaning(): void
    {
        $this->assertSame('0771234567', OldValue::phone('0771234567 / 0711111111'));
        $this->assertSame('0771234567', OldValue::phone('94771234567'));
        $this->assertSame('0771234567', OldValue::phone('771234567'));
        $this->assertSame('0712223334', OldValue::phone('071222`3334'));
        $this->assertNull(OldValue::phone('NULL'));
        $this->assertNull(OldValue::phone('father works abroad'));
        $this->assertTrue(OldValue::phoneLooksWrong('07712345678'));

        $this->assertSame('2008-05-17', OldValue::date('05/17/2008'));
        $this->assertSame('2008-05-17', OldValue::date('17/05/2008'));
        $this->assertSame('2008-05-17', OldValue::date('2008-05-17'));
        $this->assertNull(OldValue::date('2009-02-30'));
        $this->assertNull(OldValue::date('NULL'));

        $this->assertSame('2027 a/l', OldValue::gradeKey(' 2027  A/L Grade'));
        $this->assertSame('10', OldValue::gradeKey('Grade 10'));
        $this->assertSame('female', OldValue::gender('Female'));
        $this->assertNull(OldValue::gender(''));
    }

    public function test_missing_grades_stop_the_import(): void
    {
        $file = $this->csv('s.csv', self::STUDENT_COLS, [
            ['custom_id' => 'CS0001', 'fname' => 'A', 'lname' => 'A', 'grade' => '2025 A/L'],
        ]);

        $this->artisan('nexora:import-students', ['file' => $file])
            ->expectsOutput('These grades are not in the database: "2025 A/L"')
            ->assertExitCode(1);

        $this->assertSame(0, Student::count());
    }

    public function test_students_dry_run_changes_nothing(): void
    {
        file_put_contents($this->dir . '/images/1727429708.jpg', 'jpg');

        $this->artisan('nexora:import-students', ['file' => $this->students(), '--images' => $this->dir . '/images', '--dry-run' => true])
            ->expectsOutput('DRY RUN - nothing was changed')
            ->assertExitCode(0);

        $this->assertSame(0, Student::count());
        Storage::disk('public')->assertMissing('uploads/1727429708.jpg');
    }

    public function test_students_are_imported(): void
    {
        file_put_contents($this->dir . '/images/1727429708.jpg', 'jpg');
        Storage::disk('public')->put('uploads/1727430000.jpg', 'already here');

        $this->artisan('nexora:import-students', ['file' => $this->students(), '--images' => $this->dir . '/images'])
            ->assertExitCode(0);

        $this->assertSame(4, Student::count());
        Storage::disk('public')->assertExists('uploads/1727429708.jpg');

        $kasun = Student::where('custom_id', 'CS0001')->first();
        $this->assertSame('Kasun Perera', $kasun->full_name);
        $this->assertSame('K. Perera', $kasun->initial_name);
        $this->assertSame('0771234567', $kasun->mobile);
        $this->assertSame('0771234567', $kasun->whatsapp_mobile);
        $this->assertSame('0771234567', $kasun->guardian_mobile);
        $this->assertNull($kasun->email);
        $this->assertSame('2008-05-17', $kasun->bday->format('Y-m-d'));
        $this->assertSame('male', $kasun->gender);
        $this->assertSame('2027 A/L', $kasun->grade->grade_name);
        $this->assertSame($this->branch->id, $kasun->branch_id);
        $this->assertSame('uploads/1727429708.jpg', $kasun->img_url);
        $this->assertTrue($kasun->permanent_qr_active);
        $this->assertTrue($kasun->admission);
        $this->assertSame('2024-09-27 19:05:12', $kasun->created_at->format('Y-m-d H:i:s'));

        $nimali = Student::where('custom_id', 'SA25001')->first();
        $this->assertNull($nimali->nic);                       // same NIC as CS0001
        $this->assertNull($nimali->bday);                      // 30 February
        $this->assertSame('0712223334', $nimali->guardian_mobile); // from mobile
        $this->assertSame('uploads/1727430000.jpg', $nimali->img_url);

        $new = Student::where('custom_id', 'SA25002')->first();
        $this->assertSame('male', $new->gender);
        $this->assertSame('uploads/male.png', $new->img_url);  // logo placeholder is not a photo

        $girl = Student::where('custom_id', 'SA25003')->first();
        $this->assertSame('uploads/female.png', $girl->img_url); // picture not found

        $problems = file_get_contents(storage_path('logs/import-students-problems.txt'));
        $this->assertStringContainsString('SA25001: NIC 200812345678 is also on CS0001', $problems);
        $this->assertStringContainsString('SA25002: no gender', $problems);
        $this->assertStringContainsString('(no custom_id): skipped', $problems);

        // Again: nothing new
        $this->artisan('nexora:import-students', ['file' => $this->students()])->assertExitCode(0);
        $this->assertSame(4, Student::count());
    }

    private const TEACHER_COLS = ['custom_id', 'fname', 'lname', 'email', 'mobile', 'nic', 'bday', 'gender', 'address1', 'address2',
        'address3', 'is_active', 'graduation_details', 'experience', 'account_number', 'bank_code', 'branch_code', 'branch_name',
        'created_at', 'updated_at'];

    private function teachers(): string
    {
        $base = ['email' => 'kamal@test.lk', 'mobile' => '0771234567', 'nic' => '801234567V', 'bday' => '1980-05-01',
            'gender' => 'male', 'address1' => 'Galle', 'graduation_details' => 'BSc', 'experience' => '10 years',
            'account_number' => '123456789', 'bank_code' => '7010', 'branch_code' => '15', 'branch_name' => 'Anuradhapura'];

        return $this->csv('teachers.csv', self::TEACHER_COLS, [
            // the same teacher entered twice: the inactive row first
            ['custom_id' => 'CT0005', 'fname' => 'Mr. Kamal', 'lname' => 'Perera', 'is_active' => '0', 'created_at' => '2024-09-14 22:58:28'] + $base,
            ['custom_id' => 'CT0014', 'fname' => 'Mr. Kamal', 'lname' => 'Perera', 'is_active' => '1', 'created_at' => '2024-09-14 23:53:07'] + $base,
            ['custom_id' => 'SAT0002', 'fname' => 'Mrs. Chathurika Madhavi', 'lname' => 'Wickramasinghe', 'email' => 'chathu@test.lk',
                'nic' => '199012345678', 'bday' => '03/21/1990', 'gender' => 'Female', 'mobile' => '0712223334', 'address1' => 'Matara',
                'is_active' => '0', 'bank_code' => '9999', 'branch_code' => '1'],
        ]);
    }

    public function test_teacher_names(): void
    {
        $cmd = new ImportOldTeachersCommand();
        $this->assertSame(['Mr. Kamal Perera', 'Mr. K. Perera'], $cmd->names('Mr. Kamal', 'Perera'));
        $this->assertSame(['A.B.Silva Fernando', 'A. B. S. Fernando'], $cmd->names('A.B.Silva', 'Fernando'));
        [, $initials] = $cmd->names('Mrs. Chathurika Madhavi', 'Wickramasinghe');
        $this->assertSame('C. M. Wickramasinghe', $initials); // title dropped to fit 20
    }

    public function test_teachers_are_imported(): void
    {
        $bank = Bank::create(['bank_name' => 'Bank of Ceylon', 'bank_code' => '7010', 'is_active' => true]);
        BankBranch::create(['bank_id' => $bank->id, 'branch_name' => 'Ampara', 'branch_code' => '15']);
        $right = BankBranch::create(['bank_id' => $bank->id, 'branch_name' => 'Anuradhapura', 'branch_code' => '15']);

        $this->artisan('nexora:import-teachers', ['file' => $this->teachers(), '--dry-run' => true])->assertExitCode(0);
        $this->assertSame(0, Teacher::count());

        $this->artisan('nexora:import-teachers', ['file' => $this->teachers()])->assertExitCode(0);

        $this->assertSame(['CT0014', 'SAT0002'], Teacher::orderBy('custom_id')->pluck('custom_id')->all());

        $kamal = Teacher::where('custom_id', 'CT0014')->first();
        $this->assertTrue($kamal->is_active);
        $this->assertSame('Mr. Kamal Perera', $kamal->full_name);
        $this->assertSame('Mr. K. Perera', $kamal->initials);
        $this->assertSame('801234567V', $kamal->nic);
        $this->assertSame($right->id, $kamal->bank_branch_id);
        $this->assertSame('123456789', $kamal->account_number);
        $this->assertSame([$this->branch->id], $kamal->branches()->pluck('branches.id')->all());

        $chathu = Teacher::where('custom_id', 'SAT0002')->first();
        $this->assertFalse($chathu->is_active);
        $this->assertSame('1990-03-21', $chathu->bday->format('Y-m-d'));
        $this->assertSame('female', $chathu->gender);
        $this->assertNull($chathu->bank_branch_id);

        $problems = file_get_contents(storage_path('logs/import-teachers-problems.txt'));
        $this->assertStringContainsString('CT0005: NOT imported - same email / NIC as CT0014', $problems);
        $this->assertStringContainsString('SAT0002: bank branch 9999 / 1 not found', $problems);

        $this->artisan('nexora:import-teachers', ['file' => $this->teachers()])->assertExitCode(0);
        $this->assertSame(2, Teacher::count());
    }

    public function test_new_teacher_id_follows_imported_ids(): void
    {
        $base = ['full_name' => 'T', 'initials' => 'T', 'mobile' => '0771234567', 'bday' => '1980-01-01',
            'gender' => 'male', 'address1' => 'x'];
        Teacher::create(['custom_id' => 'SAT0007', 'email' => 'a@t.lk', 'nic' => '1'] + $base);
        Teacher::create(['custom_id' => 'CT0019', 'email' => 'b@t.lk', 'nic' => '2'] + $base);

        $service = app(\App\Services\Web\Teacher\TeacherService::class);
        $next = (fn () => $this->generateCustomId())->call($service);

        $this->assertSame('SAT008', $next);
    }
}
