<?php

use App\Http\Controllers\Api\Admission\AdmissionPaymentController;
use App\Http\Controllers\Api\Attendance\AttendanceReportController;
use App\Http\Controllers\Api\Attendance\AttendanceScheduleController;
use App\Http\Controllers\Api\StudentCard\StudentCardAssignmentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\ClassSchedule\ClassScheduleController;
use App\Http\Controllers\Api\StudentClass\GradeController;
use App\Http\Controllers\Api\ClassHall\InstituteHallController;
use App\Http\Controllers\Api\Dashboard\MobileDashboardController;
use App\Http\Controllers\Api\Attendance\AttendanceController;
use App\Http\Controllers\Api\Payment\PaymentController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\StudentImage\QuickPhotoController;
use App\Http\Controllers\Api\Attendance\StudentAttendanceController;
use App\Http\Controllers\Api\Attendance\StudentAttendanceReadController;
use App\Http\Controllers\Api\Attendance\StudentAttendanceScanController;
use App\Http\Controllers\Api\StudentCard\StudentCardController;
use App\Http\Controllers\Api\StudentClass\StudentClassController;
use App\Http\Controllers\Api\StudentClassEnrollment\StudentClassEnrollmentController;
use App\Http\Controllers\Api\Student\StudentController;
use App\Http\Controllers\Api\Student\StudentPermanentRegistrationController;
use App\Http\Controllers\Api\Student\StudentTemporaryRegistrationController;
use App\Http\Controllers\Api\StudentImage\StudentImageController;
use App\Http\Controllers\Api\Payment\StudentPaymentController;
use App\Http\Controllers\Api\Payment\StudentPaymentReadController;
use App\Http\Controllers\Api\StudentTute\StudentTuteController;
use App\Http\Controllers\Api\Teacher\MyStudentsController;
use App\Http\Controllers\Api\Teacher\TeacherClassesController;
use App\Http\Controllers\Api\Teacher\TeacherDashboardController;
use App\Http\Controllers\Api\Teacher\TeacherProfileController;
use App\Http\Controllers\Api\Teacher\TeacherTimetableController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Mobile App + Web API
|
*/

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

// 10 attempts per minute per IP (stops password guessing)
Route::post(
    '/login',
    [LoginController::class, 'login']
)->middleware('throttle:10,1')->name('api.login');

/*
|--------------------------------------------------------------------------
| Any logged-in user (staff and teachers)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'user.active',
])->group(function () {

    // Who am I? Role, branches and which part of the app to open
    Route::get(
        '/me',
        [LoginController::class, 'me']
    )->name('api.me');

    Route::post(
        '/logout',
        [LoginController::class, 'logout']
    )->name('api.logout');

    // Optional features on for this institute (show / hide app menus)
    Route::get(
        '/features',
        [\App\Http\Controllers\Api\Feature\FeatureController::class, 'index']
    )->name('api.features');
});


/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Staff app (SUPER_ADMIN always passes the role check)
|--------------------------------------------------------------------------
| Data is limited to the user's own branches by BranchAccessService.
*/

Route::middleware([
    'auth:sanctum',
    'user.active',
    'role:ADMIN,BRANCH_ADMIN,USER',
    'staff.branch',
])->group(function () {

    // Scan a permanent student card (available / already assigned + history)
    Route::post(
        '/qr-code',
        [StudentCardController::class, 'searchQrcode']
    )->name('api.card.searchQrcode');

    // Replace a student's card (lost / damaged / worn out)
    Route::post(
        '/re-assign',
        [StudentCardController::class, 'reAssign']
    )->name('api.card.reassign');


    /*
    |--------------------------------------------------------------------------
    | Student
    |--------------------------------------------------------------------------
    */


    // Register a student with a temporary ID card (TMP001...)
    Route::post(
        '/student',
        [StudentTemporaryRegistrationController::class, 'store']
    )->name('api.QuickStudentStore');

    // Register a student with a permanent student card
    Route::post(
        '/student-new',
        [StudentPermanentRegistrationController::class, 'store']
    )->name('api.student.store');

    Route::get(
        '/students',
        [StudentController::class, 'fetchAllStudent']
    )->name('api.students.all');

    Route::get(
        '/students/filter',
        [StudentController::class, 'studentDetailsSearch']
    )->name('api.students.filter');

    // /students/search?search=S001  or  /students/search/S001 (mobile app)
    Route::get(
        '/students/search/{search?}',
        [StudentController::class, 'searchStudent']
    )->name('api.students.search');

    Route::post(
        '/students/update-image',
        [StudentController::class, 'updateStudentImage']
    )->name('api.students.update-image');


    Route::post(
        '/quick-photo/upload',
        [QuickPhotoController::class, 'uploadQuickPhoto']
    )->name('api.quick-photo.upload');

    Route::post(
        '/quick-photo/update',
        [QuickPhotoController::class, 'imageUpdate']
    )->name('api.quick-photo.imageUpdate');

    Route::get(
        '/students-image/fetch-image',
        [StudentImageController::class, 'fetchStudentImage']
    )->name('api.students.fetch-image');

    Route::post(
        '/students-image/update-image',
        [StudentImageController::class, 'updateStudentImage']
    )->name('api.students-image.update-image');


    Route::get(
        '/grades',
        [GradeController::class, 'fetchGrade']
    )->name('api.grades.fetch');


    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    // Read payment info from QR
    Route::post(
        '/payments/read',
        [StudentPaymentReadController::class, 'read']
    )->name('api.payments.read');



    /*
    |--------------------------------------------------------------------------
    | Student Payment Store
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/payments/today-payments',
        [StudentPaymentController::class, 'todayReceipt']
    )->name('api.student-payments.today-receipt');

    Route::post(
        '/payments/today',
        [StudentPaymentController::class, 'todayPayments']
    );

    Route::get(
        '/payments/students/{studentId}/enrollments/{enrolledId}',
        [StudentPaymentController::class, 'fetchStudentAllPayment']
    );



    /*
|--------------------------------------------------------------------------
| New Payment
|--------------------------------------------------------------------------
|
| New payment architecture
| Used by Web + Mobile
|
*/

    Route::prefix('new-payment')
        ->name('api.new-payment.')
        ->group(function () {

            /*
        |--------------------------------------------------------------------------
        | Read Student Payment Information
        |--------------------------------------------------------------------------
        |
        | QR / Student ID
        |
        */

            Route::post(
                '/read',
                [PaymentController::class, 'read']
            )->name('read');


            /*
        |--------------------------------------------------------------------------
        | Single Payment
        |--------------------------------------------------------------------------
        */

            Route::post(
                '/pay',
                [PaymentController::class, 'pay']
            )->name('pay');


            /*
        |--------------------------------------------------------------------------
        | Bulk Payment
        |--------------------------------------------------------------------------
        |
        | One or more payments can be sent.
        | If only one payment is sent, it is also valid.
        |
        */

            Route::post(
                '/bulk-pay',
                [PaymentController::class, 'bulkPay']
            )->name('bulk-pay');
        });


    /*
    |--------------------------------------------------------------------------
    | Student Admission Payment Delete
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/admission',
        [AdmissionPaymentController::class, 'fetchAdmission']
    )->name('api.admission-payment.fetch-admission');

    Route::get(
        '/admission-payments',
        [AdmissionPaymentController::class, 'fetchAdmissionPayment']
    )->name('api.admission-payment.fetch-admission-payments');

    Route::post(
        '/admission-payments/store',
        [AdmissionPaymentController::class, 'storeAdmissionPayment']
    )->name('api.admission-payment.store-admission-payment');


    /*
    |--------------------------------------------------------------------------
    | Student Payment Delete
    |--------------------------------------------------------------------------
    */

    Route::delete(
        '/payments/{paymentId}',
        [StudentPaymentController::class, 'destroy']
    )->name('api.student-payments.destroy');



    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    */

    // Read attendance info from QR
    Route::post(
        '/attendance/read',
        [StudentAttendanceReadController::class, 'read']
    )->name('api.attendance.read');

    // old version of attendance scan
    Route::post(
        '/attendance/scan',
        [StudentAttendanceScanController::class, 'scan']
    )->name('api.attendance.scan');


    // New version of attendance scan

    Route::post(
        '/new-attendance/scan',
        [AttendanceController::class, 'scan']
    )->name('api.new-attendance.scan');


    Route::post(
        '/attendance/store',
        [StudentAttendanceController::class, 'store']
    )->name('api.attendance.store');

    Route::get(
        '/attendance/students/{studentId}/enrollments/{enrolledId}',
        [StudentAttendanceController::class, 'studentAttendanceHistory']
    );

    Route::delete(
        '/student-attendances/{attendanceId}',
        [StudentAttendanceController::class, 'deleteAttendance']
    )->name('api.student-attendances.delete');

    Route::post(
        'attendance/schedules',
        [AttendanceScheduleController::class, 'index']
    );

    Route::post(
        'attendance/report',
        [AttendanceReportController::class, 'index']
    );


    Route::post(
        '/student-tutes/read',
        [StudentTuteController::class, 'readStudentTute']
    )->name('api.student-tutes.read');

    Route::post(
        '/student-tutes/store',
        [StudentTuteController::class, 'store']
    )->name('api.student-tutes.store');

    Route::get(
        '/student-tutes/students/{studentId}/enrollments/{enrolledId}',
        [StudentTuteController::class, 'studentTuteHistory']
    );


    Route::get(
        '/student-classes/{gradeId}',
        [StudentClassController::class, 'fetchStudentClass']
    )->name('api.student-classes.fetch_student_classes');





    /*
    |--------------------------------------------------------------------------
    | Class Enrollments
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/student-class-enrollments/{studentId}',
        [StudentClassEnrollmentController::class, 'fetchStudentClasses']
    )->name('api.student-class-enrollments.fetch_student_classes');


    Route::post(
        '/student-class-enrollments',
        [StudentClassEnrollmentController::class, 'store']
    )->name('api.student-class-enrollments.store');

    Route::patch(
        '/student-class-enrollments/{enrollment}',
        [StudentClassEnrollmentController::class, 'update']
    )->name('api.student-class-enrollments.patch');

    Route::post(
        '/student-class-enrollments/read-student-class',
        [StudentClassEnrollmentController::class, 'readStudentClass']
    )->name('api.student-class-enrollments.read_student_class');


    Route::patch(
        '/student-class-enrollments/toggle-status/{enrollmentId}',
        [StudentClassEnrollmentController::class, 'toggleClassStatusChange']
    );

    Route::patch(
        '/student-class-enrollments/{enrollmentId}/deactivate',
        [StudentClassEnrollmentController::class, 'deactivateEnrollment']
    )->name('api.student-class-enrollments.deactivate');



    Route::get(
        '/class-schedule/today-class',
        [ClassScheduleController::class, 'todayClasses']
    );

    Route::get(
        '/class-schedule/ongoing-class',
        [ClassScheduleController::class, 'fetchOngoingClass']
    );

    Route::get(
        '/class-schedule/category',
        [ClassScheduleController::class, 'fetchClassCategory']
    );

    Route::get(
        '/class-schedule/fetch',
        [ClassScheduleController::class, 'fetchClassSchedule']
    );

    Route::post(
        '/class-schedule/store',
        [ClassScheduleController::class, 'storeAddNewDay']
    );

    Route::put(
        '/class-schedule/update',
        [ClassScheduleController::class, 'updateClassSchedule']
    );

    Route::patch(
        '/class-schedule/cancel',
        [ClassScheduleController::class, 'classCancel']
    );

    Route::get(
        '/institute-halls',
        [InstituteHallController::class, 'fetchInstituteHall']
    );


    Route::get(
        '/mobile-dashboard',
        [MobileDashboardController::class, 'mobileDashboardDetails']
    );

    /*
    |--------------------------------------------------------------------------
    | Admin Notifications (Send, Manage)
    |--------------------------------------------------------------------------
    */

    Route::prefix('/notifications')->group(function () {
        // Send notifications
        Route::post('/send', [NotificationController::class, 'send']);
        Route::post('/send-now', [NotificationController::class, 'sendNow']);
        Route::post('/bulk', [NotificationController::class, 'sendBulk']);
        Route::post('/send-to-all', [NotificationController::class, 'sendToAll']);
        Route::post('/send-to-grade/{grade}', [NotificationController::class, 'sendToGrade']);

        // Get notification status
        Route::get('/{id}/status', [NotificationController::class, 'status']);

        // Manage notifications
        Route::post('/{id}/retry', [NotificationController::class, 'retry']);
        Route::post('/{id}/cancel', [NotificationController::class, 'cancel']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);

        // List and filter
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/student/{studentId}/history', [NotificationController::class, 'history']);

        // Stats and maintenance
        Route::get('/stats', [NotificationController::class, 'stats']);
        Route::delete('/cleanup', [NotificationController::class, 'deleteOld']);
    });

    /*
|--------------------------------------------------------------------------
| Student Card Assignment
|--------------------------------------------------------------------------
*/

    Route::prefix('student-card-assignment')
        ->name('api.student-card-assignment.')
        ->group(function () {

            Route::post(
                '/search-student',
                [StudentCardAssignmentController::class, 'searchStudent']
            )->name('search-student');

            Route::post(
                '/search-card',
                [StudentCardAssignmentController::class, 'searchAvailableCard']
            )->name('search-card');

            Route::post(
                '/assign',
                [StudentCardAssignmentController::class, 'assignCard']
            )->name('assign');
        });
});



/*
|--------------------------------------------------------------------------
| Teacher API v1
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'user.active',
    'role:TEACHER',
])
    ->prefix('v1/teacher')
    ->name('api.v1.teacher.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard',
            [TeacherDashboardController::class, 'index']
        )->name('dashboard');

        // My Classes
        Route::get(
            '/classes',
            [TeacherClassesController::class, 'index']
        )->name('classes');

        // My Timetable
        Route::get(
            '/timetable',
            [TeacherTimetableController::class, 'index']
        )->name('timetable');

        // My Students
        Route::get(
            '/students',
            [MyStudentsController::class, 'index']
        )->name('students');

        // Teacher Profile
        Route::get(
            '/profile',
            [TeacherProfileController::class, 'show']
        )->name('profile');

        Route::put(
            '/profile',
            [TeacherProfileController::class, 'update']
        )->name('profile.update');

        Route::post(
            '/profile/change-password',
            [TeacherProfileController::class, 'changePassword']
        )->name('profile.change-password');
    });

/*
|--------------------------------------------------------------------------
| Staff app: Courses (feature "courses" - 404 when it is off)
|--------------------------------------------------------------------------
| See App\Http\Controllers\Api\Course\CourseController for the screens.
*/

Route::middleware([
    'auth:sanctum',
    'user.active',
    'role:ADMIN,BRANCH_ADMIN,USER',
    'staff.branch',
    'feature:courses',
])->prefix('courses')->name('api.courses.')->controller(\App\Http\Controllers\Api\Course\CourseController::class)->group(function () {
    Route::get('/', 'index')->name('index');

    // Course Attendance
    Route::get('sessions', 'sessions')->name('sessions');
    Route::post('sessions/{courseSession}/attendance', 'markAttendance')->whereNumber('courseSession')->name('sessions.attendance');
    Route::get('sessions/{courseSession}/report', 'sessionReport')->whereNumber('courseSession')->name('sessions.report');

    // Course Payment / enrolling
    Route::post('student', 'student')->name('student');
    Route::post('{course}/enroll', 'enroll')->whereNumber('course')->name('enroll');
    Route::post('enrollments/{courseEnrollment}/pay', 'pay')->whereNumber('courseEnrollment')->name('pay');
    Route::get('payments/{coursePayment}/receipt', 'receipt')->whereNumber('coursePayment')->name('receipt');
});
