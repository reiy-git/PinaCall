<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClearanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\EnrolleeController;
use App\Http\Controllers\EnrollmentApplicationController;
use App\Http\Controllers\GradesController;
use App\Http\Controllers\PortalAdminController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

// Stateless API routes: Vue calls these endpoints without Laravel web-session CSRF.
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/enrollees/options', [EnrolleeController::class, 'options']);
Route::post('/enrollees/apply', [EnrolleeController::class, 'apply'])->middleware('throttle:register');

// Public lobby data is anonymous and contains generated queue tickets only.
Route::get('/queue/lobby', [QueueController::class, 'lobbyDisplay']);

Route::middleware('portal.token')->group(function () {
    Route::post('/auth/first-login-reset', [AuthController::class, 'firstLoginReset'])->middleware('throttle:login');
    Route::get('/admin/enrollees', [EnrolleeController::class, 'index']);
    Route::get('/admin/enrollees/{id}/documents/{index}', [EnrolleeController::class, 'document']);
    Route::post('/admin/enrollees/{id}/approve', [EnrolleeController::class, 'approve']);
    Route::post('/admin/enrollees/{id}/reject', [EnrolleeController::class, 'reject']);
    Route::post('/admin/enrollees/{id}/resend', [EnrolleeController::class, 'resend'])->middleware('throttle:register');
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/personas', [AuthController::class, 'getPersonas']);

    Route::get('/applications', [EnrollmentApplicationController::class, 'index']);
    Route::get('/students', [EnrollmentApplicationController::class, 'students']);
    Route::post('/applications', [EnrollmentApplicationController::class, 'store']);
    Route::get('/applications/{id}', [EnrollmentApplicationController::class, 'show']);
    Route::patch('/applications/{id}/review', [EnrollmentApplicationController::class, 'review']);

    Route::get('/clearances', [ClearanceController::class, 'index']);
    Route::post('/clearances/request', [ClearanceController::class, 'requestClearance']);
    Route::get('/clearances/{id}', [ClearanceController::class, 'show']);
    Route::patch('/clearances/{id}/step', [ClearanceController::class, 'updateStep']);

    Route::get('/courses', [GradesController::class, 'getCourses']);
    Route::get('/grades', [GradesController::class, 'index']);
    Route::patch('/grades/{id}', [GradesController::class, 'updateGrade']);
    Route::post('/grades/{enrollment}/release', [GradesController::class, 'releaseGrade']);

    Route::get('/documents', [DocumentRequestController::class, 'index']);
    Route::post('/documents/request', [DocumentRequestController::class, 'store']);
    Route::patch('/documents/{id}/status', [DocumentRequestController::class, 'updateStatus']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/sections', [SectionController::class, 'index']);
    Route::get('/faculty', [SectionController::class, 'faculty']);
    Route::post('/sections', [SectionController::class, 'store']);
    Route::patch('/sections/{section}', [SectionController::class, 'update']);
    Route::post('/sections/{section}/enroll', [SectionController::class, 'enroll']);
    Route::get('/sections/{section}/roster', [SectionController::class, 'roster']);

    Route::post('/admin/users', [PortalAdminController::class, 'storeUser']);
    Route::patch('/admin/users/{user}', [PortalAdminController::class, 'updateUser']);
    Route::post('/admin/courses', [PortalAdminController::class, 'storeCourse']);
    Route::patch('/admin/courses/{course}', [PortalAdminController::class, 'updateCourse']);

    // Queue check-in and status are authenticated and bound to the signed-in student.
    Route::post('/queue/activate', [QueueController::class, 'activatePhysicalToken']);
    Route::get('/queue/status', [QueueController::class, 'authenticatedQueueStatus']);

    // Queue management (staff-authenticated)
    Route::get('/queue/staff', [QueueController::class, 'staffQueue']);
    Route::post('/queue/call-next', [QueueController::class, 'callNext']);
    Route::post('/queue/{id}/recall', [QueueController::class, 'recallDisplay']);
    Route::post('/queue/{id}/no-show', [QueueController::class, 'markNoShow']);
    Route::post('/queue/{id}/complete', [QueueController::class, 'completeTransaction']);
    Route::get('/queue/physical-tokens', [QueueController::class, 'physicalTokens']);
    Route::get('/queue/physical-tokens/{physicalToken}/history', [QueueController::class, 'physicalTokenHistory']);
    Route::get('/queue/physical-tokens/{physicalToken}/qr', [QueueController::class, 'physicalTokenQr']);
    Route::post('/queue/physical-tokens/{physicalToken}/reset', [QueueController::class, 'resetPhysicalToken']);
    Route::post('/queue/physical-tokens/{physicalToken}/missing', [QueueController::class, 'markPhysicalTokenMissing']);
    Route::post('/queue/{id}/ready-for-pickup', [QueueController::class, 'markReadyForPickup']);
});
