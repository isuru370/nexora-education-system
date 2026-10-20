<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * A small institute for tests:
 * - Colombo branch: class "Maths 10 CMB" (fee 2000), students A. Silva (S1) and B. Perera (S2)
 * - Kandy branch:   class "Maths 10 KDY", student E. Kandy (S3)
 * - S1 and S2 have parent portal logins (password "pass1234")
 * - S1 paid October, has a cancelled November payment, one exam result,
 *   one attendance and one notification; S2 has its own notification.
 *
 * Use with RefreshDatabase. Returns ids in $this->inst.
 */
trait BuildsInstitute
{
    protected array $inst = [];

    protected function buildInstitute(): array
    {
        $userType = fn (string $code) => DB::table('user_types')->insertGetId(['name' => $code, 'code' => $code, 'is_active' => 1]);
        $makeUser = fn (string $name, int $type) => User::forceCreate([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . '@test.lk',
            'password' => 'password123',
            'user_type_id' => $type,
            'is_active' => 1,
        ]);

        $admin = $makeUser('Admin', $userType('ADMIN'));
        $cashier = $makeUser('Cashier', $userType('USER'));

        $b1 = DB::table('branches')->insertGetId(['name' => 'Colombo', 'code' => 'CMB', 'is_active' => 1]);
        $b2 = DB::table('branches')->insertGetId(['name' => 'Kandy', 'code' => 'KDY', 'is_active' => 1]);
        DB::table('branch_users')->insert(['user_id' => $cashier->id, 'branch_id' => $b1]);

        $teacher = DB::table('teachers')->insertGetId([
            'custom_id' => 'T1', 'full_name' => 'Nimal Perera', 'initials' => 'N.', 'email' => 't@test.lk',
            'mobile' => '0770000000', 'nic' => '900000000V', 'bday' => '1990-01-01', 'gender' => 'male',
            'address1' => 'Colombo', 'is_active' => 1,
        ]);

        $subject = DB::table('subjects')->insertGetId(['subject_name' => 'Maths']);
        $grade = DB::table('grades')->insertGetId(['grade_name' => '10']);
        $category = DB::table('class_categories')->insertGetId(['category_name' => 'Theory', 'code' => 'TH']);

        $makeClass = function (int $branch, string $name) use ($teacher, $subject, $grade, $category) {
            $class = DB::table('student_classes')->insertGetId([
                'branch_id' => $branch, 'class_name' => $name, 'teacher_id' => $teacher,
                'subject_id' => $subject, 'grade_id' => $grade, 'is_active' => 1,
            ]);
            $fee = DB::table('class_category_fees')->insertGetId(['student_class_id' => $class, 'class_category_id' => $category]);
            $option = DB::table('class_category_fee_options')->insertGetId([
                'class_category_fee_id' => $fee, 'label' => 'Normal', 'fee' => 2000, 'is_default' => 1,
            ]);

            return [$class, $fee, $option];
        };

        [$c1, $f1, $o1] = $makeClass($b1, 'Maths 10 CMB');
        [$c2, $f2, $o2] = $makeClass($b2, 'Maths 10 KDY');

        // Payment split: teacher 60%, institute 40%
        foreach ([[$b1, $c1], [$b2, $c2]] as [$branch, $class]) {
            DB::table('class_payment_configs')->insert([
                'branch_id' => $branch, 'student_class_id' => $class, 'teacher_id' => $teacher,
                'teacher_percentage' => 60, 'organizer_percentage' => 0, 'institution_percentage' => 40,
                'effective_from' => '2026-01-01', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $n = 0;
        $makeStudent = function (int $branch, string $name) use (&$n, $grade) {
            $n++;

            return DB::table('students')->insertGetId([
                'custom_id' => "S$n", 'branch_id' => $branch, 'initial_name' => $name, 'full_name' => $name,
                'guardian_mobile' => '0771234567', 'grade_id' => $grade, 'img_url' => '',
                'permanent_qr_active' => 1, 'is_active' => 1,
            ]);
        };

        $enroll = fn (int $branch, int $student, int $class, int $fee, int $option) => DB::table('student_class_enrollments')->insertGetId([
            'branch_id' => $branch, 'student_id' => $student, 'student_class_id' => $class,
            'class_category_fee_id' => $fee, 'class_category_fee_option_id' => $option,
            'is_active' => 1, 'is_free_card' => 0, 'enrolled_at' => '2026-01-01',
        ]);

        $s1 = $makeStudent($b1, 'A. Silva');
        $e1 = $enroll($b1, $s1, $c1, $f1, $o1);
        $s2 = $makeStudent($b1, 'B. Perera');
        $e2 = $enroll($b1, $s2, $c1, $f1, $o1);
        $s3 = $makeStudent($b2, 'E. Kandy');
        $e3 = $enroll($b2, $s3, $c2, $f2, $o2);

        foreach ([[$s1, 'S1'], [$s2, 'S2']] as [$student, $username]) {
            DB::table('student_portal_logins')->insert([
                'student_id' => $student, 'username' => $username, 'password' => Hash::make('pass1234'),
                'is_verified' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $receipt = 0;
        $pay = function (int $student, int $enrollment, float $amount, string $paidAt, string $month, string $status = 'completed') use ($b1, $cashier, &$receipt) {
            return DB::table('payments')->insertGetId([
                'branch_id' => $b1, 'student_id' => $student, 'student_class_enrollment_id' => $enrollment,
                'user_id' => $cashier->id, 'amount' => $amount, 'paid_at' => $paidAt, 'payment_month' => $month,
                'payment_method' => 'cash', 'status' => $status, 'receipt_number' => 'R' . (++$receipt),
            ]);
        };

        $pay($s1, $e1, 2000, '2026-10-05 09:00:00', '2026-10-01');
        $pay($s1, $e1, 999, '2026-10-06 09:00:00', '2026-11-01', 'cancelled');

        $exam = DB::table('exams')->insertGetId([
            'branch_id' => $b1, 'title' => 'Term test', 'student_class_id' => $c1, 'class_category_id' => $category,
            'exam_date' => '2026-10-10', 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'completed',
        ]);
        DB::table('student_results')->insert([
            ['branch_id' => $b1, 'student_id' => $s1, 'exam_id' => $exam, 'marks' => 78, 'max_marks' => 100, 'percentage' => 78, 'grade' => 'A', 'rank' => 1, 'status' => 'passed'],
            ['branch_id' => $b1, 'student_id' => $s2, 'exam_id' => $exam, 'marks' => 41, 'max_marks' => 100, 'percentage' => 41, 'grade' => 'S', 'rank' => 2, 'status' => 'passed'],
        ]);

        // Tuesday 3 PM - 5 PM, every week
        $pattern = DB::table('class_schedule_patterns')->insertGetId([
            'branch_id' => $b1, 'student_class_id' => $c1, 'class_category_fee_id' => $f1,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'start_time' => '15:00:00',
            'end_time' => '17:00:00', 'class_day' => 'tuesday', 'is_active' => 1,
        ]);
        $schedule = DB::table('class_schedules')->insertGetId([
            'branch_id' => $b1, 'class_schedule_pattern_id' => $pattern, 'student_class_id' => $c1,
            'class_category_fee_id' => $f1, 'class_date' => '2026-10-13', 'day_of_week' => 'tuesday',
            'start_time' => '15:00', 'end_time' => '17:00', 'status' => 'scheduled', 'is_active' => 1,
        ]);
        $pastSchedule = DB::table('class_schedules')->insertGetId([
            'branch_id' => $b1, 'class_schedule_pattern_id' => $pattern, 'student_class_id' => $c1,
            'class_category_fee_id' => $f1, 'class_date' => '2026-10-06', 'day_of_week' => 'tuesday',
            'start_time' => '15:00', 'end_time' => '17:00', 'status' => 'completed', 'is_active' => 1,
        ]);
        DB::table('student_attendances')->insert([
            'branch_id' => $b1, 'student_id' => $s1, 'student_class_enrollment_id' => $e1,
            'class_schedule_id' => $pastSchedule, 'attended_at' => '2026-10-06 15:05:00', 'mark_method' => 'qr_mobile',
        ]);

        $notification = fn (int $student, string $title, string $body) => DB::table('notifications')->insertGetId([
            'branch_id' => $b1, 'student_id' => $student, 'title' => $title, 'body' => $body,
            'type' => 'payment', 'status' => 'sent', 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ] + (Schema::hasColumn('notifications', 'data') ? ['data' => null] : []));

        $n1 = $notification($s1, 'Payment received', 'October fee Rs. 2,000 paid.');
        $n2 = $notification($s2, 'Private to S2', 'Only B. Perera may read this.');

        return $this->inst = compact(
            'admin', 'cashier', 'b1', 'b2', 'teacher', 'c1', 'c2', 'f1', 'o1',
            's1', 's2', 's3', 'e1', 'e2', 'e3', 'exam', 'schedule', 'n1', 'n2'
        );
    }
}
