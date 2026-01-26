<?php

use App\Http\Controllers\Api\V1\AutomationApiController;
use App\Http\Controllers\Api\V1\SendController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Webhooks (no authentication required, but validated internally)
Route::post('/webhooks/sns', [WebhookController::class, 'handleSns'])->name('webhooks.sns');

// API v1 routes
Route::prefix('v1')->group(function () {
    // Email sending
    Route::post('/send', [SendController::class, 'send'])->name('api.v1.send');

    // Email status
    Route::get('/emails/{id}', [SendController::class, 'status'])->name('api.v1.emails.status');
    Route::get('/emails/{id}/events', [SendController::class, 'events'])->name('api.v1.emails.events');

    // Automations API
    Route::get('/automations', [AutomationApiController::class, 'index'])->name('api.v1.automations.index');
    Route::get('/automations/{automation}', [AutomationApiController::class, 'show'])->name('api.v1.automations.show');
    Route::post('/automations/{automation}/trigger', [AutomationApiController::class, 'trigger'])->name('api.v1.automations.trigger');
    Route::post('/automations/{automation}/enroll', [AutomationApiController::class, 'enroll'])->name('api.v1.automations.enroll');
    Route::get('/automations/{automation}/enrollments/{email}', [AutomationApiController::class, 'enrollmentStatus'])->name('api.v1.automations.enrollment-status');
});
