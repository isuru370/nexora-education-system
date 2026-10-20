<?php

use App\Http\Controllers\Api\Parent\Attendance\StudentAttendanceController;
use App\Http\Controllers\Api\Parent\Auth\ParentAuthController;
use App\Http\Controllers\Api\Parent\ClassSchedule\ClassScheduleController;
use App\Http\Controllers\Api\Parent\Dashboard\DashboardController;
use App\Http\Controllers\Api\Parent\Exam\ExamController;
use App\Http\Controllers\Api\Parent\FCM\FcmTokenController;
use App\Http\Controllers\Api\Parent\Notification\ParentNotificationController;
use App\Http\Controllers\Api\Parent\Payment\StudentPaymentController;
use App\Http\Controllers\Api\Parent\Result\ResultController;
use App\Http\Controllers\Api\Parent\Teacher\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parent API  (/api/parent/v1/...)
|--------------------------------------------------------------------------
|
| /auth/login returns data.token. Every other route takes
| "Authorization: Bearer <token>" and the student comes from the token
| (see App\Http\Middleware\AuthenticateParentApi, config/parent_api.php).
|
*/

Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [ParentAuthController::class, 'login'])
        ->middleware('throttle:parent-login');

    Route::middleware('parent.api')->group(function () {

        /*
        | Authentication
        */
        Route::post('/auth/logout', [ParentAuthController::class, 'logout']);
        Route::post('/auth/change-password', [ParentAuthController::class, 'changePassword']);

        /*
        | Dashboard
        */
        Route::post('/dashboard', [DashboardController::class, 'fetchDashboardData']);

        /*
        | Firebase Cloud Messaging
        */
        Route::prefix('/fcm')->group(function () {
            Route::post('/token', [FcmTokenController::class, 'store']);
            Route::post('/logout', [FcmTokenController::class, 'logout']);
        });

        /*
        | Attendance, payments, exams, results, teachers, schedule
        */
        Route::post('/attendance', [StudentAttendanceController::class, 'index']);
        Route::post('/payments', [StudentPaymentController::class, 'index']);
        Route::post('/exams', [ExamController::class, 'index']);
        Route::post('/results', [ResultController::class, 'index']);
        Route::post('/teachers', [TeacherController::class, 'index']);
        Route::post('/schedule', [ClassScheduleController::class, 'index']);

        /*
        | Notifications
        | The fixed paths come before /notifications/{id}, and {id} is numbers
        | only, so "read-all", "unread", "stats" are never taken as an id.
        */
        Route::prefix('/notifications')->group(function () {
            Route::post('/', [ParentNotificationController::class, 'index']);
            Route::post('/unread', [ParentNotificationController::class, 'unread']);
            Route::post('/unread/count', [ParentNotificationController::class, 'unreadCount']);
            Route::post('/read-all', [ParentNotificationController::class, 'markAllAsRead']);
            Route::post('/read/delete', [ParentNotificationController::class, 'deleteRead']);
            Route::post('/stats', [ParentNotificationController::class, 'stats']);

            Route::post('/{id}', [ParentNotificationController::class, 'show'])->whereNumber('id');
            Route::post('/{id}/read', [ParentNotificationController::class, 'markAsRead'])->whereNumber('id');
            Route::post('/{id}/delete', [ParentNotificationController::class, 'destroy'])->whereNumber('id');
        });
    });
});
