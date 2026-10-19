<?php



use App\Http\Controllers\Web\ActivityLog\ActivityLogController;

use App\Http\Controllers\Web\OrganizerPayment\OrganizerPaymentController;

use App\Http\Controllers\Web\User\SystemUserController;

use App\Http\Controllers\Web\Auth\LoginController;


use App\Http\Controllers\Web\Setting\DatabaseBackupController;
use App\Http\Controllers\Web\Setting\NotificationSettingController;

use App\Http\Controllers\Web\Exam\ExamController;

use App\Http\Controllers\Web\ExtraIncome\ExtraIncomeController;

use App\Http\Controllers\Web\Notification\FcmTokenController;

use App\Http\Controllers\Web\Auth\ForgotPasswordController;

use App\Http\Controllers\Web\StudentImage\ImageUploadController;

use App\Http\Controllers\Web\InstituteExpense\InstituteExpenseController;




use App\Http\Controllers\Web\Log\LogController;

use App\Http\Controllers\Web\Attendance\MonthlyClassAttendanceReportController;

use App\Http\Controllers\Web\Notification\NotificationController;



use App\Http\Controllers\Web\StudentImage\StudentImageController;


use App\Http\Controllers\Web\Attendance\TodayAttendanceController;

use App\Http\Controllers\Web\User\UserController;


use App\Http\Controllers\Web\User\UserProfileController;
use App\Http\Controllers\Web\Admission\AdmissionController;
use App\Http\Controllers\Web\Admission\AdmissionPaymentController;
use App\Http\Controllers\Web\Attendance\AttendanceController;
use App\Http\Controllers\Web\Branch\BranchController;
use App\Http\Controllers\Web\ClassCategory\ClassCategoryController;
use App\Http\Controllers\Web\ClassCategoryFee\ClassCategoryFeeController;
use App\Http\Controllers\Web\ClassCategoryFeeOption\ClassCategoryFeeOptionController;
use App\Http\Controllers\Web\ClassHall\ClassHallController;
use App\Http\Controllers\Web\ClassSchedule\ClassScheduleController;
use App\Http\Controllers\Web\ClassSchedule\ClassTimeTableController;
use App\Http\Controllers\Web\Dashboard\DashboardController;
use App\Http\Controllers\Web\GenerateStudentId\GenerateStudentIdController;
use App\Http\Controllers\Web\MonthlyReport\MonthlyReportController;
use App\Http\Controllers\Web\Report\DailyCollectionReportController;
use App\Http\Controllers\Web\Report\InstituteIncomeReportController;
use App\Http\Controllers\Web\Report\MonthlyCollectionReportController;
use App\Http\Controllers\Web\Report\TeacherCollectionReportController;
use App\Http\Controllers\Web\PaymentReminder\PaymentReminderController;
use App\Http\Controllers\Web\Organizer\OrganizerController;
use App\Http\Controllers\Web\Payment\PaymentController;
use App\Http\Controllers\Web\Payment\PaymentHistoryController;
use App\Http\Controllers\Web\Receipt\ReceiptController;
use App\Http\Controllers\Web\Report\AnnualReportController;
use App\Http\Controllers\Web\Report\BranchCollectionReportController;
use App\Http\Controllers\Web\Student\StudentCardRegistrationController;
use App\Http\Controllers\Web\Student\StudentController;
use App\Http\Controllers\Web\Student\StudentTemporaryRegistrationController;
use App\Http\Controllers\Web\StudentCard\StudentCardController;
use App\Http\Controllers\Web\StudentClass\StudentClassController;
use App\Http\Controllers\Web\StudentClass\StudentClassManagementController;
use App\Http\Controllers\Web\StudentClassEnrollment\StudentClassEnrollmentController;
use App\Http\Controllers\Web\Teacher\TeacherController;
use App\Http\Controllers\Web\TeacherSalary\TeacherSalaryController;
use App\Http\Controllers\Web\TemporaryCard\TemporaryIDCardController;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Route;



/*

|--------------------------------------------------------------------------

| Public Routes

|--------------------------------------------------------------------------

*/



Route::get('/', function () {

    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return view('welcome');
})->name('welcome');





Route::get('/contact_administrator', function () {

    return view('contact_administrator');
})->name('contact_administrator');



Route::post('/contact_administrator', function (Request $request) {

    $validated = $request->validate([

        'full_name'  => ['required', 'string', 'max:100'],

        'email'      => ['required', 'email', 'max:150'],

        'phone'      => ['required', 'string', 'max:20'],

        'subject'    => ['required', 'string', 'max:150'],

        'message'    => ['required', 'string', 'max:5000'],

        'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png'],

    ]);



    $toEmail = 'info@nexorait.lk';



    Mail::send('emails.contact_administrator', [

        'data' => $validated,

    ], function ($mail) use ($validated, $toEmail, $request) {

        $mail->to($toEmail)

            ->subject('Contact Form: ' . $validated['subject'])

            ->replyTo($validated['email'], $validated['full_name']);



        if ($request->hasFile('attachment')) {

            $file = $request->file('attachment');



            $mail->attach($file->getRealPath(), [

                'as' => $file->getClientOriginalName(),

                'mime' => $file->getMimeType(),

            ]);
        }
    });



    return back()->with('success', 'Your message has been sent successfully.');
})->name('contact_administrator.send');





/*

|--------------------------------------------------------------------------

| Forgot Password

|--------------------------------------------------------------------------

*/



Route::prefix('forgot-password')

    ->controller(ForgotPasswordController::class)

    ->group(function () {



        Route::get('/', 'index')->name('forgot_password.form');

        Route::post('/send-otp', 'sendOtp')->name('forgot_password.send_otp');

        Route::post('/verify-otp', 'verifyOtp')->name('forgot_password.verify_otp');

        Route::post('/resend-otp', 'resendOtp')->name('forgot_password.resend_otp');

        Route::post('/reset-password', 'updatePassword')->name('forgot_password.reset');
    });





/*

|--------------------------------------------------------------------------

| Authentication

|--------------------------------------------------------------------------

*/



// Login

Route::get('/login', function () {
    return view('auth.login');
})->name('login');



Route::post(

    '/login',

    [LoginController::class, 'login']

)->name('login.post');



// Logout

Route::post(

    '/logout',

    [LoginController::class, 'logout']

)->name('logout')->middleware('role:ADMIN,BRANCH_ADMIN,USER');





/*

|--------------------------------------------------------------------------

| Protected Admin Routes

|--------------------------------------------------------------------------

|

| Only authenticated active users

|

*/



Route::middleware([

    'auth',

    'user.active',

    'role:SUPER_ADMIN'

])->group(function () {

    /*

|--------------------------------------------------------------------------

|  Laravel Logs

|--------------------------------------------------------------------------

*/



    Route::get(

        '/logs/laravel',

        [LogController::class, 'index']

    )->name('logs.laravel.index')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



    Route::get('/logs/laravel/content', [LogController::class, 'content'])

        ->name('logs.laravel.content')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



    Route::post(

        '/logs/laravel/clear',

        [LogController::class, 'clear']

    )->name('logs.laravel.clear')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



    Route::get(

        '/logs/laravel/download',

        [LogController::class, 'download']

    )->name('logs.laravel.download')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



    Route::get(

        '/logs/laravel/stats',

        [LogController::class, 'stats']

    )

        ->name('logs.laravel.stats')->middleware('role:ADMIN,BRANCH_ADMIN,USER');
});



Route::middleware([

    'auth',

    'user.active',

])->prefix('admin')

    ->name('admin.')

    ->group(function () {

        Route::get('/profile', [UserProfileController::class, 'index'])

            ->name('profile.index')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        Route::post('/profile', [UserProfileController::class, 'update'])

            ->name('profile.update')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        Route::post('/profile/password', [UserProfileController::class, 'changePassword'])

            ->name('profile.password')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        Route::middleware('role:ADMIN')->group(function () {

            Route::resource(

                'system-users',

                SystemUserController::class

            );
        });



        Route::get('system-users/export/excel', [SystemUserController::class, 'exportExcel'])

            ->name('system-users.export.excel')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        Route::get('system-users/export/pdf', [SystemUserController::class, 'exportPdf'])

            ->name('system-users.export.pdf')->middleware('role:ADMIN,BRANCH_ADMIN,USER');

        /*

        |--------------------------------------------------------------------------

        | Student Image Upload

        |--------------------------------------------------------------------------

        */



        Route::post(

            '/upload/student-image',

            [ImageUploadController::class, 'uploadStudentImage']

        )->name('upload.student.image')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        Route::post(

            '/upload/quick-photo',

            [ImageUploadController::class, 'uploadQuickPhoto']

        )->name('upload.quick.photo')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        Route::delete(

            '/upload/delete',

            [ImageUploadController::class, 'delete']

        )->name('upload.delete')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        /*

        |--------------------------------------------------------------------------

        | Attendance

        |--------------------------------------------------------------------------

        */



        Route::get('/attendance', function () {

            return view('attendance.index');
        })->name('attendance.index')->middleware('role:ADMIN,BRANCH_ADMIN,USER');


        /*

|--------------------------------------------------------------------------

| STUDENT EXAM

|--------------------------------------------------------------------------

*/



        // Upcoming exams view

        Route::get(

            'exams/upcoming',

            [ExamController::class, 'upcoming']

        )->name('exams.upcoming')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Exam counts for dashboard

        Route::get(

            'exams/counts',

            [ExamController::class, 'counts']

        )->name('exams.counts')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Export Excel

        Route::get(

            'exams/export/excel',

            [ExamController::class, 'exportExcel']

        )->name('exams.export.excel')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Export PDF

        Route::get(

            'exams/export/pdf',

            [ExamController::class, 'exportPdf']

        )->name('exams.export.pdf')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Hall availability check (AJAX)

        Route::get(

            'exams/check-hall-availability',

            [

                ExamController::class,

                'checkHallAvailability'

            ]

        )->name('exams.check-hall-availability')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Class search (AJAX) - must be above the resource routes,
        // otherwise exams/{exam} catches "search-classes"
        Route::get(
            'exams/search-classes',
            [ExamController::class, 'searchClasses']
        )->name('exams.search-classes')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');

        // Get categories by class (AJAX)

        Route::get(

            'exams/get-categories',

            [ExamController::class, 'getCategoriesByClass']

        )->name('exams.get-categories')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Resource routes

        Route::middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER')->group(function () {

            Route::resource(

                'exams',

                ExamController::class

            )->except(['destroy']);
        });



        // Delete (soft delete)

        Route::delete(

            'exams/{exam}',

            [ExamController::class, 'destroy']

        )->name('exams.destroy')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Cancel exam

        Route::post(

            'exams/{exam}/cancel',

            [ExamController::class, 'cancel']

        )->name('exams.cancel')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Quick status update

        Route::put(

            'exams/{exam}/status',

            [ExamController::class, 'updateStatus']

        )->name('exams.update-status')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');






        /*

|--------------------------------------------------------------------------

| EXAM RESULTS / MARK ENTRY

|--------------------------------------------------------------------------

*/



        // Mark entry page

        Route::get(

            'exams/{exam}/mark-entry',

            [ExamController::class, 'markEntry']

        )->name('exams.mark-entry')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Save student marks

        Route::post(

            'exams/{exam}/save-marks',

            [ExamController::class, 'saveMarks']

        )->name('exams.save-marks')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // View exam results

        Route::get(

            'exams/{exam}/results',

            [ExamController::class, 'results']

        )->name('exams.results')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        // Recalculate ranks

        Route::post(

            'exams/{exam}/recalculate-ranks',

            [ExamController::class, 'recalculateRanks']

        )->name('exams.recalculate-ranks')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        Route::get(

            'exams/{exam}/results/excel',

            [ExamController::class, 'exportResultsExcel']

        )->name('exams.results.excel')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');



        Route::get(

            'exams/{exam}/results/pdf',

            [ExamController::class, 'exportResultsPdf']

        )->name('exams.results.pdf')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');





        /*

        |--------------------------------------------------------------------------

        | Organizer Payments

        |--------------------------------------------------------------------------

        */



        Route::get('/organizer-payments', [

            OrganizerPaymentController::class,

            'index'

        ])->name('organizer-payments.index')->middleware('role:ADMIN,BRANCH_ADMIN');



        Route::get('/organizer-payments/{organizer}/pay', [

            OrganizerPaymentController::class,

            'pay'

        ])->name('organizer-payments.pay')->middleware('role:ADMIN,BRANCH_ADMIN');



        Route::post('/organizer-payments/{organizer}/store', [

            OrganizerPaymentController::class,

            'store'

        ])->name('organizer-payments.store')->middleware('role:ADMIN,BRANCH_ADMIN');



        Route::delete('/organizer-payments/{organizerPayment}', [

            OrganizerPaymentController::class,

            'destroy'

        ])->name('organizer-payments.destroy')->middleware('role:ADMIN,BRANCH_ADMIN');



        Route::get('/organizer-payments/{organizer}/salary-slip', [

            OrganizerPaymentController::class,

            'salarySlip'

        ])->name('organizer-payments.salary-slip')->middleware('role:ADMIN,BRANCH_ADMIN');



        Route::post('/organizer-payments/{organizer}/adjustment', [

            OrganizerPaymentController::class,

            'storeAdjustment'

        ])->name('organizer-payments.adjustment-store')->middleware('role:ADMIN,BRANCH_ADMIN');

        /*

        |--------------------------------------------------------------------------

        | Extra Incomes

        |--------------------------------------------------------------------------

        */



        Route::middleware('role:ADMIN')->group(function () {

            Route::resource(

                'extra-incomes',

                ExtraIncomeController::class

            );
        });







        Route::get('/student-images', [StudentImageController::class, 'index'])

            ->name('student-images.index')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        Route::post('/student-images/{quickPhoto}/assign', [StudentImageController::class, 'assign'])

            ->name('student-images.assign')->middleware('role:ADMIN,BRANCH_ADMIN,USER');



        /*

|--------------------------------------------------------------------------

| Institute Expenses

|--------------------------------------------------------------------------

*/



        Route::middleware('role:ADMIN')->group(function () {



            Route::resource(

                'institute-expenses',

                InstituteExpenseController::class

            );



            Route::patch(

                'institute-expenses/{instituteExpense}/toggle-status',

                [InstituteExpenseController::class, 'toggleStatus']

            )->name(

                'institute-expenses.toggle-status'

            );
        });



        Route::get('/today-attendance', [TodayAttendanceController::class, 'index'])

            ->name('today-attendance.index')->middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER');




















        /*

|--------------------------------------------------------------------------

| System Setting

|--------------------------------------------------------------------------

*/



        Route::middleware('role:ADMIN')->group(function () {

            Route::get('setting', [DatabaseBackupController::class, 'index'])

                ->name('setting.index');



            Route::get('setting/backup/export', [DatabaseBackupController::class, 'export'])

                ->name('setting.backup.export');



            Route::post('setting/backup/import', [DatabaseBackupController::class, 'import'])

                ->name('setting.backup.import');

            // Parent notifications: on / off + push or SMS (whole system)
            Route::get('setting/notifications', [NotificationSettingController::class, 'index'])
                ->name('setting.notifications');

            Route::put('setting/notifications', [NotificationSettingController::class, 'update'])
                ->name('setting.notifications.update');
        });



        /*

|--------------------------------------------------------------------------

|  Activity Logs

|--------------------------------------------------------------------------

*/



        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index')->middleware('role:ADMIN');

        Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export')->middleware('role:ADMIN');

        Route::post('/activity-logs/clear', [ActivityLogController::class, 'clearOld'])->name('activity-logs.clear')->middleware('role:ADMIN');





        // Notification Routes

        Route::prefix('notifications')

            ->name('notifications.')

            ->middleware('role:ADMIN,BRANCH_ADMIN,USER')

            ->group(function () {



                // List all notifications

                Route::get('/', [NotificationController::class, 'index'])

                    ->name('index');



                // Create notification

                Route::get('/create', [NotificationController::class, 'create'])

                    ->name('create');



                // Store notification

                Route::post('/', [NotificationController::class, 'store'])

                    ->name('store');



                // Bulk send notification

                Route::post('/bulk', [NotificationController::class, 'sendBulk'])

                    ->name('bulk');



                // Show single notification

                Route::get('/{id}', [NotificationController::class, 'show'])

                    ->name('show');



                // Mark as read

                Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])

                    ->name('mark-read');



                // Mark all as read

                Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])

                    ->name('mark-all-read');



                // Retry failed notification

                Route::post('/{id}/retry', [NotificationController::class, 'retry'])

                    ->name('retry');



                // Cancel notification

                Route::post('/{id}/cancel', [NotificationController::class, 'cancel'])

                    ->name('cancel');



                // Delete notification

                Route::delete('/{id}', [NotificationController::class, 'destroy'])

                    ->name('destroy');



                // Delete old notifications

                Route::delete('/cleanup', [NotificationController::class, 'deleteOld'])

                    ->name('cleanup');



                // Export notifications

                Route::get('/export', [NotificationController::class, 'export'])

                    ->name('export');
            });







        // ============================================

        // FCM TOKEN ROUTES

        // ============================================

        Route::prefix('fcm-tokens')

            ->name('fcm-tokens.')

            ->middleware('role:ADMIN')

            ->group(function () {



                // List all tokens

                Route::get('/', [FcmTokenController::class, 'index'])

                    ->name('index');




                // Show single token

                Route::get('/{id}', [FcmTokenController::class, 'show'])

                    ->name('show');




                // Student tokens

                Route::get('/student/{studentId}', [FcmTokenController::class, 'studentTokens'])

                    ->name('student');




                // Activate token

                Route::post('/{id}/activate', [FcmTokenController::class, 'activate'])

                    ->name('activate');




                // Deactivate token

                Route::post('/{id}/deactivate', [FcmTokenController::class, 'deactivate'])

                    ->name('deactivate');




                // Delete token

                Route::delete('/{id}', [FcmTokenController::class, 'destroy'])

                    ->name('destroy');




                // Delete all inactive tokens

                Route::delete('/inactive/delete', [FcmTokenController::class, 'deleteInactive'])

                    ->name('delete-inactive');




                // Export tokens

                Route::get('/export', [FcmTokenController::class, 'export'])

                    ->name('export');




                // Stats (AJAX)

                Route::get('/stats', [FcmTokenController::class, 'stats'])

                    ->name('stats');
            });


        /*

|--------------------------------------------------------------------------

| User Management

|--------------------------------------------------------------------------

*/



        Route::middleware('role:ADMIN')->group(function () {

            Route::resource('users', UserController::class);
        });



        // Toggle Active Status

        Route::patch(

            'users/{user}/toggle-active',

            [UserController::class, 'toggleActive']

        )->name('users.toggleActive')->middleware('role:ADMIN');



        // Change Password

        Route::patch(

            'users/{user}/change-password',

            [UserController::class, 'changePassword']

        )->name('users.changePassword')->middleware('role:ADMIN');



        // Restore Soft Deleted User

        Route::post(

            'users/{id}/restore',

            [UserController::class, 'restore']

        )->name('users.restore')->middleware('role:ADMIN');



        // Force Delete User

        Route::delete(

            'users/{id}/force-delete',

            [UserController::class, 'forceDelete']

        )->name('users.forceDelete')->middleware('role:ADMIN');



        // Bulk Actions

        Route::post(

            'users/bulk-action',

            [UserController::class, 'bulkAction']

        )->name('users.bulkAction')->middleware('role:ADMIN');



        // Export Routes

        Route::get(

            'users-export/excel',

            [UserController::class, 'exportExcel']

        )->name('users.exportExcel')->middleware('role:ADMIN');



        Route::get(

            'users-export/pdf',

            [UserController::class, 'exportPdf']

        )->name('users.exportPdf')->middleware('role:ADMIN');



        // API Routes for AJAX

        Route::get(

            'api/user-types',

            [UserController::class, 'getUserTypes']

        )->name('api.user-types')->middleware('role:ADMIN');



        Route::get(

            'api/users/stats',

            [UserController::class, 'getStats']

        )->name('api.users.stats')->middleware('role:ADMIN');










        Route::middleware('role:ADMIN')->group(function () {



            Route::get(

                '/monthly-class-attendance-report',

                [MonthlyClassAttendanceReportController::class, 'index']

            )->name('monthly-class-attendance-report.index')->middleware('role:ADMIN');



            Route::get(

                '/monthly-class-attendance-report/export',

                [MonthlyClassAttendanceReportController::class, 'export']

            )->name('monthly-class-attendance-report.export')->middleware('role:ADMIN');
        });
    });

Route::prefix('student-cards')
    ->name('student-cards.')
    ->controller(StudentCardController::class)
    ->group(function () {

        // Card inventory
        Route::get('/', [StudentCardController::class, 'index'])
            ->name('index')->middleware([
                'auth',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Generate cards
        Route::get('/generate', [StudentCardController::class, 'generate'])
            ->name('generate')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        Route::post('/generate', [StudentCardController::class, 'generateCards'])
            ->name('generate.store')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Available cards
        Route::get('/available', [StudentCardController::class, 'available'])
            ->name('available')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Search cards
        Route::get('/search', [StudentCardController::class, 'search'])
            ->name('search')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Preview cards
        Route::get('/preview', [StudentCardController::class, 'preview'])
            ->name('preview')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Check current student card
        Route::post('/check-current-card', [StudentCardController::class, 'checkCurrentCard'])
            ->name('check-current-card')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Assign card form
        Route::get('/assign', [StudentCardController::class, 'assignForm'])
            ->name('assign-form')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Assign card
        Route::post('/assign', [StudentCardController::class, 'assign'])
            ->name('assign')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Student card history
        Route::get('/students/{student}/history', [StudentCardController::class, 'history'])
            ->name('history')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Branch sequences
        Route::get('/branch-sequences', [
            StudentCardController::class,
            'branchSequences'
        ])->name('branch-sequences')->middleware([
            'auth',
            'user.active',
            'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
        ]);


        // Replace card form
        Route::get('/{card}/replace', [StudentCardController::class, 'replaceForm'])
            ->name('replace-form')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Replace card
        Route::put('/{card}/replace', [StudentCardController::class, 'replace'])
            ->name('replace')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Mark card as lost
        Route::patch('/{card}/lost', [StudentCardController::class, 'markLost'])
            ->name('lost')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Mark card as damaged
        Route::patch('/{card}/damaged', [StudentCardController::class, 'markDamaged'])
            ->name('damaged')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Deactivate card
        Route::patch('/{card}/deactivate', [StudentCardController::class, 'deactivate'])
            ->name('deactivate')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Card details — keep this after static routes
        Route::get('/{card}', [StudentCardController::class, 'show'])
            ->name('show')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);
    });


/*
|--------------------------------------------------------------------------
| Generate Student ID
|--------------------------------------------------------------------------
*/

Route::prefix('generate-student-id')
    ->name('generate-student-id.')
    ->controller(GenerateStudentIdController::class)
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Index - Display student ID card generation page
        |--------------------------------------------------------------------------
        */
        Route::get('/', 'index')
            ->name('index')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

    });

/*
|--------------------------------------------------------------------------
| Temporary ID Cards
|--------------------------------------------------------------------------
*/

Route::prefix('temporary_id_cards')
    ->name('temporary_id_cards.')
    ->controller(TemporaryIDCardController::class)
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Index - List all temporary ID cards
        |--------------------------------------------------------------------------
        */
        Route::get('/', 'index')
            ->name('index')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Create - Show create form
        |--------------------------------------------------------------------------
        */
        Route::get('/create', 'create')
            ->name('create')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Store - Save new temporary ID cards
        |--------------------------------------------------------------------------
        */
        Route::post('/', 'store')
            ->name('store')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Preview - Show preview page (empty state)
        |--------------------------------------------------------------------------
        */
        Route::get('/preview', 'preview')
            ->name('preview')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Generate Preview - Generate preview for a TMP range
        |--------------------------------------------------------------------------
        */
        Route::post('/preview-generate', 'generatePreview')
            ->name('preview-generate')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Update Status Page - Show status update form
        |--------------------------------------------------------------------------
        */
        Route::get('/status', 'updateStatusPage')
            ->name('update-status')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Change Status - Update card statuses
        |--------------------------------------------------------------------------
        */
        Route::post('/change-status', 'changeStatus')
            ->name('change-status')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Download PDF - Range-based PDF
        |--------------------------------------------------------------------------
        */
        Route::post('/download-pdf', 'downloadPdf')
            ->name('download-pdf')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Download Issued Cards PDF - Statistics report
        |--------------------------------------------------------------------------
        */
        Route::get('/pdf', 'downloadIdCardPdf')
            ->name('pdf')->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);
    });


/*
|--------------------------------------------------------------------------
| Student Card Registration
|--------------------------------------------------------------------------
*/

Route::prefix('student-card-registration')
    ->name('student-card-registration.')
    ->controller(StudentCardRegistrationController::class)
    ->group(function () {

        Route::get('/', 'create')
            ->name('create')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        Route::post('/', 'store')
            ->name('store')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);
    });


/*
|--------------------------------------------------------------------------
| Student Temporary Registration
|--------------------------------------------------------------------------
*/

Route::prefix('student-temporary-registration')
    ->name('student-temporary-registration.')
    ->controller(StudentTemporaryRegistrationController::class)
    ->group(function () {

        Route::get('/', 'create')
            ->name('create')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        Route::post('/', 'store')
            ->name('store')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);
    });


/*
|--------------------------------------------------------------------------
| Student Management
|--------------------------------------------------------------------------
*/

Route::prefix('students')
    ->name('students.')
    ->controller(StudentController::class)
    ->group(function () {

        // List
        Route::get('/', 'index')
            ->name('index')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Search
        Route::get('/search', 'search')
            ->name('search')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Export Excel
        Route::get('/export/excel', 'exportExcel')
            ->name('export.excel')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Export PDF
        Route::get('/export/pdf', 'exportPdf')
            ->name('export.pdf')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Export QR Details PDF
        Route::get('/export/qr-details-pdf', 'downloadQrDetailsPdf')
            ->name('export.qr-details-pdf')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // View
        Route::get('/{student}', 'show')
            ->whereNumber('student')
            ->name('show')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Edit
        Route::get('/{student}/edit', 'edit')
            ->whereNumber('student')
            ->name('edit')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Update
        Route::put('/{student}', 'update')
            ->whereNumber('student')
            ->name('update')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Toggle Active
        Route::patch('/{student}/toggle-active', 'toggleActive')
            ->whereNumber('student')
            ->name('toggle-active')
            ->middleware([
                'auth',
                'user.active',
                'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
            ]);

        // Delete
        Route::delete('/{student}', 'destroy')
            ->whereNumber('student')
            ->name('destroy')
            ->middleware([
                'auth',
                'user.active',
                'role:ADMIN,BRANCH_ADMIN',
            ]);
    });

/*
|--------------------------------------------------------------------------
| Teacher Management
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'user.active',
    'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
])
    ->prefix('teachers')
    ->name('teachers.')
    ->controller(TeacherController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->name('index')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('/create', 'create')
            ->name('create')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::post('/', 'store')
            ->name('store')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('/export/excel', 'exportExcel')
            ->name('exportExcel')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('/export/pdf', 'exportPdf')
            ->name('exportPdf')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('{teacher}/login-details', 'loginDetails')
            ->name('loginDetails')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::post('{teacher}/create-login', 'createLogin')
            ->name('create-login')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::post('{teacher}/reset-password', 'resetPassword')
            ->name('reset-password')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::patch('{teacher}/toggle-active', 'toggleActive')
            ->name('toggleActive')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('{teacher}/edit', 'edit')
            ->name('edit')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('{teacher}', 'show')
            ->name('show')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // PUT and PATCH share one route (two routes with one name break route:cache)
        Route::match(['put', 'patch'], '{teacher}', 'update')
            ->name('update')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::delete('{teacher}', 'destroy')
            ->name('destroy')
            ->middleware('role:ADMIN,BRANCH_ADMIN');
    });

/*
        |--------------------------------------------------------------------------

        | Student Classes

        |--------------------------------------------------------------------------
        */


Route::prefix('student-classes')
    ->name('student-classes.')
    ->controller(StudentClassController::class)
    ->group(function () {

        // List classes
        Route::get('/', 'index')
            ->name('index')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Create class form
        Route::get('/create', 'create')
            ->name('create')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Store new class
        Route::post('/', 'store')
            ->name('store')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Export classes
        Route::get('/export/excel', 'exportExcel')
            ->name('exportExcel')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('/export/pdf', 'exportPdf')
            ->name('exportPdf')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Search classes (AJAX)
        Route::get('/search', 'search')
            ->name('search')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // View single class
        Route::get('/{studentClass}', 'show')
            ->name('show')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Edit class form
        Route::get('/{studentClass}/edit', 'edit')
            ->name('edit')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Update class
        Route::put('/{studentClass}', 'update')
            ->name('update')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Delete class
        Route::delete('/{studentClass}', 'destroy')
            ->name('destroy')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Toggle active status
        Route::patch('/{studentClass}/toggle-active', 'toggleActive')
            ->name('toggleActive')
            ->middleware('role:ADMIN,BRANCH_ADMIN');

        // Toggle ongoing status
        Route::patch('/{studentClass}/toggle-ongoing', 'toggleOngoing')
            ->name('toggleOngoing')
            ->middleware('role:ADMIN,BRANCH_ADMIN');
    });

/*
|--------------------------------------------------------------------------
| Student Class Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('student-class-management')
    ->name('student-class-management.')
    ->controller(StudentClassManagementController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->name('index');


        // ✅ QR scan — GET
        Route::get('scan/{qrCode}', 'scanQr')
            ->name('scan-qr');


        // ✅ Search — POST (CSRF protection)
        Route::post('search', 'searchStudentClasses')
            ->name('search');


        // ✅ Assign Class — GET (form) + POST (submit)
        Route::get('assign/{studentId?}', 'showAssignClassForm')
            ->name('assign-form');

        Route::post('assign', 'assignClassToStudent')
            ->name('assign');


        Route::get('student/{studentId}', 'showStudentClasses')
            ->name('show')
            ->whereNumber('studentId');


        Route::get('payment/{studentId}/{studentClassId}/{enrollmentId}', 'payment')
            ->name('payment');

        Route::get('attendance/{studentId}/{studentClassId}/{enrollmentId}', 'attendance')
            ->name('attendance');

        Route::get('tute/{studentId}/{studentClassId}/{enrollmentId}', 'tute')
            ->name('tute');


        Route::patch('toggle-class/{enrollmentId}', 'toggleClassStatus')
            ->name('toggle-class');

        Route::patch('deactivate-class/{enrollmentId}', 'deactivateClass')
            ->name('deactivate-class');

        Route::patch('activate-class/{enrollmentId}', 'activateClass')
            ->name('activate-class');

        Route::patch('toggle-all-classes/{studentId}', 'toggleAllClassesStatus')
            ->name('toggle-all-classes');


        Route::get('category-fees/{classId}', 'getCategoryFees')
            ->name('category-fees');
    });

/*
|--------------------------------------------------------------------------
| Class Time Table Routes
|--------------------------------------------------------------------------
*/

Route::prefix('class-time-table')
    ->name('class-time-table.')
    ->controller(ClassTimeTableController::class)
    ->middleware(['auth'])
    ->group(function () {

        /*
        |------------------------------------------------------------------
        | Weekly Time Table — Page
        |------------------------------------------------------------------
        */
        Route::get('weekly', 'weeklyTimeTable')
            ->name('weekly');


        /*
        |------------------------------------------------------------------
        | Weekly Time Table — PDF Download
        |------------------------------------------------------------------
        */
        Route::get('weekly/pdf', 'downloadPdf')
            ->name('weekly.pdf');


        /*
        |------------------------------------------------------------------
        | Weekly Time Table — Excel Download
        |------------------------------------------------------------------
        */
        Route::get('weekly/excel', 'downloadExcel')
            ->name('weekly.excel');
    });

/*
        |--------------------------------------------------------------------------

        | Class Categories

        |--------------------------------------------------------------------------
        */

Route::prefix('class-categories')
    ->name('class-categories.')
    ->controller(ClassCategoryController::class)
    ->middleware('role:ADMIN,BRANCH_ADMIN')
    ->group(function () {

        // List categories
        Route::get('/', 'index')->name('index');

        // Create category
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');

        // Edit category
        Route::get('/{classCategory}/edit', 'edit')->name('edit');

        // Show category
        Route::get('/{classCategory}', 'show')->name('show');

        // Update category
        Route::put('/{classCategory}', 'update')->name('update');

        // Delete category
        Route::delete('/{classCategory}', 'destroy')->name('destroy');

        // Activate / deactivate category
        Route::patch('/{classCategory}/toggle-active', 'toggleActive')
            ->name('toggleActive');
    });

/*

        |--------------------------------------------------------------------------

        | Class Category Fees

        |--------------------------------------------------------------------------

        */

Route::prefix('class-category-fees')
    ->name('class-category-fees.')
    ->controller(ClassCategoryFeeController::class)
    ->group(function () {

        // List all class category fees
        Route::get('/', 'index')->name('index')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Create
        Route::get('/create', 'create')->name('create')->middleware('role:ADMIN,BRANCH_ADMIN');
        Route::post('/', 'store')->name('store')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Get fees by student class (AJAX)
        Route::get('/by-class/{studentClass}', 'byClass')
            ->name('by-class')->middleware('role:ADMIN,BRANCH_ADMIN');

        // View details
        Route::get('/{id}', 'show')->name('show')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Edit
        Route::get('/{id}/edit', 'edit')->name('edit')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Update
        Route::put('/{id}', 'update')->name('update')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Delete
        Route::delete('/{id}', 'destroy')->name('destroy')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Activate / Deactivate
        Route::patch('/{id}/toggle-active', 'toggleActive')
            ->name('toggle-active')->middleware('role:ADMIN,BRANCH_ADMIN');
    });

/*

        |--------------------------------------------------------------------------

        | Class Category Fee Option

        |--------------------------------------------------------------------------

        */

Route::prefix('class-category-fees/{classCategoryFeeId}/fee-options')
    ->name('class-category-fee-options.')
    ->controller(ClassCategoryFeeOptionController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');

        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');

        Route::patch('/{id}/restore', 'restore')->name('restore');
        Route::patch('/{id}/set-default', 'setDefault')->name('set-default');
        Route::patch('/{id}/activate', 'activate')->name('activate');
        Route::patch('/{id}/deactivate', 'deactivate')->name('deactivate');
    });

/*

        |--------------------------------------------------------------------------

        | Class Halls

        |--------------------------------------------------------------------------

        */

Route::prefix('class-halls')
    ->name('class-halls.')
    ->controller(ClassHallController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('/create', 'create')->name('create')->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::post('/', 'store')->name('store')->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('/{classHall}/edit', 'edit')->name('edit')->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::get('/{classHall}', 'show')->name('show')->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::put('/{classHall}', 'update')->name('update')->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::delete('/{classHall}', 'destroy')->name('destroy')->middleware('role:ADMIN,BRANCH_ADMIN');

        Route::patch('/{classHall}/toggle-active', 'toggleActive')
            ->name('toggleActive')->middleware('role:ADMIN,BRANCH_ADMIN');
    });

/*

        |--------------------------------------------------------------------------

        | Class Schedules

        |--------------------------------------------------------------------------

        */

Route::prefix('class-schedules')
    ->name('class-schedules.')
    ->controller(ClassScheduleController::class)
    ->group(function () {

        // List and create
        Route::get('/', 'index')->name('index');
        Route::get('/today', 'todayClasses')->name('todayClasses');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');

        // Category schedules
        Route::get('/category-schedules', 'categorySchedules')
            ->name('categorySchedules');

        // Bulk schedule management
        Route::get('/bulk-edit', 'bulkEdit')->name('bulkEdit');

        Route::get('/patterns/{pattern}/edit', 'editBulkSchedule')
            ->name('editBulkSchedule');

        Route::put('/patterns/{pattern}', 'updateBulkSchedule')
            ->name('updateBulkSchedule');

        // Schedule status actions
        Route::patch('/{classSchedule}/toggle-active', 'toggleActive')
            ->name('toggleActive');

        Route::patch('/{classSchedule}/cancel', 'cancel')
            ->name('cancel');

        Route::patch('/{classSchedule}/status', 'statusUpdate')
            ->name('statusUpdate');

        // Individual schedule CRUD
        Route::get('/{classSchedule}/edit', 'edit')->name('edit');
        Route::get('/{classSchedule}', 'show')->name('show');
        Route::put('/{classSchedule}', 'update')->name('update');
        Route::delete('/{classSchedule}', 'destroy')->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| Student Class Enrollment Routes
|--------------------------------------------------------------------------
*/

Route::prefix('student-class-enrollments')
    ->name('student-class-enrollments.')
    ->controller(StudentClassEnrollmentController::class)
    ->group(function () {

        /*
        |------------------------------------------------------------------
        | Index — view only (all authenticated users)
        |------------------------------------------------------------------
        */
        Route::get('/', 'index')
            ->name('index')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER');

        /*
        |------------------------------------------------------------------
        | Create + Store — Admin only
        |------------------------------------------------------------------
        */
        Route::get('/create', 'create')
            ->name('create')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN');

        Route::post('/', 'store')
            ->name('store')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN');

        /*
        |------------------------------------------------------------------
        | Category-wise Students List — all users
        |------------------------------------------------------------------
        */
        Route::get(
            '/classes/{studentClass}/categories/{classCategory}/students',
            'categoryStudents'
        )->name('category-students')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER');

        /*
        |------------------------------------------------------------------
        | PDF Export — all users
        |------------------------------------------------------------------
        */
        Route::get(
            '/classes/{studentClass}/categories/{classCategory}/students/pdf',
            'categoryStudentsPdf'
        )->name('category-students.pdf')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER');

        /*
        |------------------------------------------------------------------
        | Excel Export — all users
        |------------------------------------------------------------------
        */
        Route::get(
            '/classes/{studentClass}/categories/{classCategory}/students/excel',
            'categoryStudentsExcel'
        )->name('category-students.excel')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER');

        /*
        |------------------------------------------------------------------
        | Category-wise Payment Students — all users
        |------------------------------------------------------------------
        */
        Route::get(
            '/classes/{class}/categories/{classCategoryFee}/payments/{year}/{month}',
            'classCategoryWisePaymentStudent'
        )->name('category-wise-payment')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER');

        /*
        |------------------------------------------------------------------
        | Restore — Admin only (must be BEFORE /{id} route)
        |------------------------------------------------------------------
        */
        Route::post('/{id}/restore', 'restore')
            ->name('restore')
            ->whereNumber('id')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN');

        /*
        |------------------------------------------------------------------
        | Show — all users
        |------------------------------------------------------------------
        */
        Route::get('/{studentClassEnrollment}', 'show')
            ->name('show')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER');

        /*
        |------------------------------------------------------------------
        | Edit — Admin only
        |------------------------------------------------------------------
        */
        Route::get('/{studentClassEnrollment}/edit', 'edit')
            ->name('edit')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN');

        /*
        |------------------------------------------------------------------
        | Update — Admin only
        |------------------------------------------------------------------
        */
        Route::put('/{studentClassEnrollment}', 'update')
            ->name('update')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN');

        /*
        |------------------------------------------------------------------
        | Delete — Admin only
        |------------------------------------------------------------------
        */
        Route::delete('/{studentClassEnrollment}', 'destroy')
            ->name('destroy')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN');

        /*
        |------------------------------------------------------------------
        | Toggle Active — Admin only
        |------------------------------------------------------------------
        */
        Route::patch('/{studentClassEnrollment}/toggle-active', 'toggleActive')
            ->name('toggle-active')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN');

        /*
        |------------------------------------------------------------------
        | Leave — all users
        |------------------------------------------------------------------
        */
        Route::patch('/{studentClassEnrollment}/leave', 'leave')
            ->name('leave')
            ->middleware('role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER');
    });

/*

        |--------------------------------------------------------------------------

        | Organizer Management

        |--------------------------------------------------------------------------

        */
Route::prefix('organizers')
    ->name('organizers.')
    ->controller(OrganizerController::class)
    ->group(function () {

        // Organizer list
        Route::get('/', 'index')->name('index')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Create organizer
        Route::get('/create', 'create')->name('create')->middleware('role:ADMIN,BRANCH_ADMIN');
        Route::post('/', 'store')->name('store')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Export routes (dynamic routes වලට කලින්)
        Route::get('/export/excel', 'exportExcel')->name('exportExcel')->middleware('role:ADMIN,BRANCH_ADMIN');
        Route::get('/export/pdf', 'exportPdf')->name('exportPdf')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Organizer details
        Route::get('/{organizer}', 'show')->name('show')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Edit organizer
        Route::get('/{organizer}/edit', 'edit')->name('edit')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Update organizer
        Route::put('/{organizer}', 'update')->name('update')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Delete organizer
        Route::delete('/{organizer}', 'destroy')->name('destroy')->middleware('role:ADMIN,BRANCH_ADMIN');

        // Activate / Deactivate organizer
        Route::patch('/{organizer}/toggle-active', 'toggleActive')
            ->name('toggleActive')->middleware('role:ADMIN,BRANCH_ADMIN');
    });

/*
        |--------------------------------------------------------------------------

        | Dashboard

        |--------------------------------------------------------------------------
        */
Route::middleware([
    'auth',
    'user.active',
    'role:SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER,TEACHER',
])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Branch Management (Admin Folder එකෙන් පිටත)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'user.active',
    'role:SUPER_ADMIN,ADMIN',
])->prefix('branches')
    ->name('branches.')
    ->controller(BranchController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{branch}', 'show')->name('show');
        Route::get('/{branch}/edit', 'edit')->name('edit');
        Route::put('/{branch}', 'update')->name('update');
        Route::patch('/{branch}/activate', 'activate')->name('activate');
        Route::patch('/{branch}/deactivate', 'deactivate')->name('deactivate');
        Route::patch('/{branch}/restore', 'restore')->name('restore');
        Route::delete('/{branch}', 'destroy')->name('destroy');
    });


/*
|--------------------------------------------------------------------------
| Web Payment Routes
|--------------------------------------------------------------------------
*/

Route::prefix('new-payment')
    ->name('new-payment.')
    ->controller(PaymentController::class)
    ->middleware(['auth', 'user.active', 'role:ADMIN,BRANCH_ADMIN,USER'])
    ->group(function () {

        // Show New Payment page
        Route::get('/', 'index')
            ->name('index');

        // Read student payment information (POST + CSRF)
        Route::post('read', 'read')
            ->name('read');

        // Store one payment
        Route::post('pay', 'pay')
            ->name('pay');

        // Store multiple payments
        Route::post('bulk-pay', 'bulkPay')
            ->name('bulk-pay');
    });

/*
|--------------------------------------------------------------------------
| Payments History (class fee payments)
|--------------------------------------------------------------------------
| List, details, receipt reprint and delete (soft delete with a reason).
| (old: admin/payments/today-receipt + mobile API delete)
*/
Route::prefix('payments')
    ->name('payments.')
    ->controller(PaymentHistoryController::class)
    ->middleware(['auth', 'user.active', 'role:ADMIN,BRANCH_ADMIN,USER'])
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export/{format}', 'export')
            ->where('format', 'excel|pdf')
            ->name('export');
        Route::get('/{payment}', 'show')->where('payment', '[0-9]+')->name('show');
        Route::get('/{payment}/receipt', 'receipt')->where('payment', '[0-9]+')->name('receipt');
        Route::delete('/{payment}', 'destroy')
            ->where('payment', '[0-9]+')
            ->middleware('throttle:30,1')
            ->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| Receipts
|--------------------------------------------------------------------------
|
| URL  : /receipts
| Name : receipts.*
|
*/

Route::prefix('receipts')
    ->name('receipts.')
    ->controller(ReceiptController::class)
    ->middleware([
        'auth',
        'user.active',
        'role:ADMIN,BRANCH_ADMIN,USER',
    ])
    ->group(function () {

        Route::get('/', 'index')->name('index');

        Route::get('/export/excel', 'exportExcel')->name('export.excel');

        Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
    });

/*
|--------------------------------------------------------------------------
| Admissions (admission types)
|--------------------------------------------------------------------------
|
| URL  : admissions
| Name : admissions.*  (used by controllers, views and sidebar)
|
*/

Route::prefix('admissions')
    ->name('admissions.')
    ->controller(AdmissionController::class)
    ->middleware([
        'auth',
        'user.active',
        'role:ADMIN,BRANCH_ADMIN,USER',
    ])
    ->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');

        Route::get('/{admission}', 'show')->name('show');
        Route::get('/{admission}/edit', 'edit')->name('edit');
        Route::match(['put', 'patch'], '/{admission}', 'update')->name('update');
        Route::delete('/{admission}', 'destroy')->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| Admission Payments
|--------------------------------------------------------------------------
|
| URL  : admission-payments
| Name : admission-payments.*
|
*/

Route::prefix('admission-payments')
    ->name('admission-payments.')
    ->controller(AdmissionPaymentController::class)
    ->middleware([
        'auth',
        'user.active',
        'role:ADMIN,BRANCH_ADMIN,USER',
    ])
    ->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');

        Route::get('/{admissionPayment}', 'show')->name('show');
        Route::get('/{admissionPayment}/edit', 'edit')->name('edit');
        Route::match(['put', 'patch'], '/{admissionPayment}', 'update')->name('update');
        Route::delete('/{admissionPayment}', 'destroy')->name('destroy');
    });

Route::prefix('new-attendance')
    ->name('new-attendance.')
    ->controller(AttendanceController::class)
    ->middleware([
        'auth',
        'user.active',
        'role:ADMIN,BRANCH_ADMIN,USER',
    ])
    ->group(function () {

        Route::get('/', 'index')
            ->name('index');

        Route::post('/scan', 'scan')
            ->name('scan');
    });

Route::prefix('new-attendance')
    ->name('new-attendance.')
    ->controller(AttendanceController::class)
    ->middleware([
        'auth',
        'user.active',
        'role:ADMIN,BRANCH_ADMIN,USER',
    ])
    ->group(function () {

        Route::get('/', 'index')
            ->name('index');

        Route::post('/scan', 'scan')
            ->name('scan');
    });

/*
|--------------------------------------------------------------------------
| Teacher Salaries
|--------------------------------------------------------------------------
| Branch is passed as ?branch_id= on GET pages and as a hidden field on forms.
| When it is missing, the first branch of the teacher the user can access is used.
*/

Route::prefix('teacher-salaries')
    ->name('teacher-salaries.')
    ->controller(TeacherSalaryController::class)
    ->middleware(['auth', 'user.active', 'role:ADMIN,BRANCH_ADMIN'])
    ->group(function () {

        Route::get('/', 'index')->name('index');

        Route::post('/{teacher}/pay', 'teacherSalaryPaid')
            ->where('teacher', '[0-9]+')
            ->name('pay');

        Route::post('/{teacher}/payment', 'teacherPaymentStore')
            ->where('teacher', '[0-9]+')
            ->name('payment.store');

        Route::delete('/payments/{paymentId}', 'paymentDelete')
            ->where('paymentId', '[0-9]+')
            ->name('payment-delete');

        Route::get('/{teacher}/{year}/{month}', 'show')
            ->where(['teacher' => '[0-9]+', 'year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
            ->name('show');

        Route::get('/{teacher}/{year}/{month}/slip', 'printSalarySlip')
            ->where(['teacher' => '[0-9]+', 'year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
            ->name('slip');

        Route::get('/{teacher}/{year}/{month}/details', 'paymentDetailsView')
            ->where(['teacher' => '[0-9]+', 'year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
            ->name('payment-details');

        Route::get('/{teacher}/{year}/{month}/summary', 'teacherPaymentAndClassSummery')
            ->where(['teacher' => '[0-9]+', 'year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
            ->name('payment-summary');
    });

/*
|--------------------------------------------------------------------------
| Monthly Reports
|--------------------------------------------------------------------------
| {basis}: fee-month = by payments.payment_month
|          paid-date = by payments.paid_at (collection date)
*/

Route::prefix('monthly-report')
    ->name('monthly-report.')
    ->controller(MonthlyReportController::class)
    ->middleware(['auth', 'user.active'])
    ->group(function () {

        Route::middleware('role:ADMIN,BRANCH_ADMIN,USER')->group(function () {

            Route::get('/', 'index')->name('index');

            Route::get('/teacher-salary/excel', 'teacherSalaryExcel')
                ->name('teacher-salary.excel');

            Route::get('/teacher-salary/pdf', 'teacherSalaryPdf')
                ->name('teacher-salary.pdf');

            Route::get('/teacher-expenses/{format}', 'teacherExpenseExport')
                ->where('format', 'excel|pdf')
                ->name('teacher-expense.export');
        });

        Route::middleware('role:ADMIN,BRANCH_ADMIN,USER,TEACHER')->group(function () {

            Route::get('/student-payments/{basis}/excel', 'studentPaymentExcel')
                ->where('basis', 'fee-month|paid-date')
                ->name('student-payments.excel');

            Route::get('/student-payments/{basis}/pdf', 'studentPaymentPdf')
                ->where('basis', 'fee-month|paid-date')
                ->name('student-payments.pdf');
        });
    });

/*
|--------------------------------------------------------------------------
| Payment Reports
|--------------------------------------------------------------------------
| All payment reports use App\Services\Web\Report\PaymentReportQuery,
| so totals are the same on every report.
*/

Route::prefix('reports')
    ->name('reports.')
    ->middleware(['auth', 'user.active'])
    ->group(function () {

        // Daily / date-range collection (old: admin/daily-report)
        Route::controller(DailyCollectionReportController::class)
            ->prefix('daily-collection')
            ->name('daily-collection.')
            ->middleware('role:ADMIN,BRANCH_ADMIN,USER')
            ->group(function () {
                Route::get('/{tab?}', 'index')
                    ->where('tab', 'summary|branches|students|admissions|fee-split|expenses')
                    ->name('index');

                Route::get('/{tab}/export/{format}', 'export')
                    ->where(['tab' => 'summary|branches|students|admissions|fee-split|expenses', 'format' => 'excel|pdf'])
                    ->name('export');
            });

                // Branch-wise income, expenses and net for a date range
        Route::controller(BranchCollectionReportController::class)
            ->prefix('branch-collection')
            ->name('branch-collection.')
            ->middleware('role:ADMIN,BRANCH_ADMIN')
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::get('/export/{format}', 'export')
                    ->where('format', 'excel|pdf')
                    ->name('export');
            });

                // Annual report: 12 months of income, expenses and net for a year
        Route::controller(AnnualReportController::class)
            ->prefix('annual')
            ->name('annual.')
            ->middleware('role:ADMIN,BRANCH_ADMIN')
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::get('/export/{format}', 'export')
                    ->where('format', 'excel|pdf')
                    ->name('export');
            });

        // One teacher's class fee collection (old: admin/teacher-report)
        Route::controller(TeacherCollectionReportController::class)
            ->prefix('teacher-collection')
            ->name('teacher-collection.')
            ->middleware('role:ADMIN,BRANCH_ADMIN,USER')
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::get('/export/{format}', 'export')
                    ->where('format', 'excel|pdf')
                    ->name('export');
            });

        // Institute income / expenses / net (old: admin/institute-reports + admin/institute-income)
        Route::controller(InstituteIncomeReportController::class)
            ->prefix('institute-income')
            ->name('institute-income.')
            ->middleware('role:ADMIN')
            ->group(function () {
                // Dashboard chart (JSON)
                Route::get('/yearly', 'yearly')->name('yearly');
                Route::get('/{tab?}', 'index')
                    ->where('tab', 'summary|classes|teachers|organizers|extra-income|expenses')
                    ->name('index');
                Route::get('/{tab}/export/{format}', 'export')
                    ->where(['tab' => 'summary|classes|teachers|organizers|extra-income|expenses', 'format' => 'excel|pdf'])
                    ->name('export');
            });

        // Monthly expected vs collected (old: admin/payment-collection-report)
        Route::controller(MonthlyCollectionReportController::class)
            ->prefix('monthly-collection')
            ->name('monthly-collection.')
            ->middleware('role:ADMIN')
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::get('/export/{format}', 'export')
                    ->where('format', 'excel|pdf')
                    ->name('export');
            });
    });

/*
|--------------------------------------------------------------------------
| Payment Reminder
|--------------------------------------------------------------------------
| Unpaid students of a class + category for a fee month, SMS / app reminders.
| (old: admin/payment-reminder)
*/
Route::prefix('payment-reminder')
    ->name('payment-reminder.')
    ->controller(PaymentReminderController::class)
    ->middleware(['auth', 'user.active', 'role:ADMIN,BRANCH_ADMIN,USER'])
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/send', 'send')
            ->middleware('throttle:20,1')
            ->name('send');
        Route::get('/export/{format}', 'export')
            ->where('format', 'excel|pdf')
            ->name('export');
    });
