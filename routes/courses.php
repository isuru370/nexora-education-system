<?php

use App\Http\Controllers\Web\Course\CourseController;
use App\Http\Controllers\Web\Course\CourseDeskController;
use App\Http\Controllers\Web\Course\CourseEnrollmentController;
use App\Http\Controllers\Web\Course\CourseReportController;
use App\Http\Controllers\Web\Course\CoursePaymentController;
use App\Http\Controllers\Web\Course\CourseSessionController;
use App\Http\Controllers\Web\Course\CourseTeacherPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Courses (feature "courses" - 404 when it is off for this institute)
|--------------------------------------------------------------------------
| Loaded from routes/web.php.
*/

Route::middleware(['auth', 'user.active', 'feature:courses'])->group(function () {

    // Staff: view courses, enroll, take payments, mark attendance
    Route::middleware('role:ADMIN,BRANCH_ADMIN,USER')->group(function () {
        Route::get('courses', [CourseController::class, 'index'])->name('courses.index');

        // Front desk: scan a student / today's class days
        Route::get('course-desk/enroll', [CourseDeskController::class, 'enroll'])->name('course-desk.enroll');
        Route::get('course-desk/payment', [CourseDeskController::class, 'payment'])->name('course-desk.payment');
        Route::get('course-desk/attendance', [CourseDeskController::class, 'attendance'])->name('course-desk.attendance');

        Route::get('course-payments', [CoursePaymentController::class, 'index'])->name('course-payments.index');
        Route::get('course-payments/{coursePayment}/receipt', [CoursePaymentController::class, 'receipt'])
            ->whereNumber('coursePayment')->name('course-payments.receipt');

        Route::prefix('courses/{course}')->where(['course' => '[0-9]+'])->name('courses.')->group(function () {
            Route::get('/', [CourseController::class, 'show'])->name('show');

            Route::post('enrollments', [CourseEnrollmentController::class, 'store'])->name('enrollments.store');
            Route::get('enrollments/{courseEnrollment}', [CourseEnrollmentController::class, 'show'])
                ->whereNumber('courseEnrollment')->name('enrollments.show');
            Route::post('enrollments/{courseEnrollment}/payments', [CoursePaymentController::class, 'store'])
                ->whereNumber('courseEnrollment')->name('payments.store');

            Route::get('sessions/{courseSession}', [CourseSessionController::class, 'show'])
                ->whereNumber('courseSession')->name('sessions.show');
            Route::post('sessions/{courseSession}/attendance', [CourseSessionController::class, 'mark'])
                ->whereNumber('courseSession')->name('sessions.mark');
            Route::delete('sessions/{courseSession}/attendance/{courseAttendance}', [CourseSessionController::class, 'unmark'])
                ->whereNumber(['courseSession', 'courseAttendance'])->name('sessions.unmark');
        });
    });

    // Admins: create / change courses, cancel payments, pay teachers
    Route::middleware('role:ADMIN,BRANCH_ADMIN')->group(function () {
        Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [CourseController::class, 'store'])->name('courses.store');

        Route::get('course-reports/{type}', [CourseReportController::class, 'show'])
            ->where('type', 'daily|monthly|yearly|courses')->name('course-reports.show');

        Route::get('course-teacher-payments', [CourseTeacherPaymentController::class, 'index'])->name('course-teacher-payments.index');
        Route::get('course-teacher-payments/{teacher}', [CourseTeacherPaymentController::class, 'show'])
            ->whereNumber('teacher')->name('course-teacher-payments.show');
        Route::get('course-teacher-payouts/{courseTeacherPayout}/slip', [CourseTeacherPaymentController::class, 'slip'])
            ->whereNumber('courseTeacherPayout')->name('course-teacher-payouts.slip');
        Route::patch('course-payments/{coursePayment}/cancel', [CoursePaymentController::class, 'cancel'])
            ->whereNumber('coursePayment')->name('course-payments.cancel');

        Route::prefix('courses/{course}')->where(['course' => '[0-9]+'])->name('courses.')->group(function () {
            Route::get('edit', [CourseController::class, 'edit'])->name('edit');
            Route::put('/', [CourseController::class, 'update'])->name('update');
            Route::patch('toggle-active', [CourseController::class, 'toggleActive'])->name('toggle-active');

            Route::patch('enrollments/{courseEnrollment}/cancel', [CourseEnrollmentController::class, 'cancel'])
                ->whereNumber('courseEnrollment')->name('enrollments.cancel');

            Route::patch('sessions/{courseSession}/status', [CourseSessionController::class, 'status'])
                ->whereNumber('courseSession')->name('sessions.status');

            Route::post('payouts', [CourseTeacherPaymentController::class, 'store'])->name('payouts.store');
            Route::patch('payouts/{courseTeacherPayout}/cancel', [CourseTeacherPaymentController::class, 'cancel'])
                ->whereNumber('courseTeacherPayout')->name('payouts.cancel');
        });
    });
});
