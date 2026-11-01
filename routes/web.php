
<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AssistantController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // AI assistant routes.
    Route::get('assistant', [AssistantController::class, 'index'])
        ->name('assistant.index');

    Route::get('assistant/conversation/{conversation}', [AssistantController::class, 'show'])
        ->name('assistant.conversation');

    Route::post('assistant/message', [AssistantController::class, 'message'])
        ->name('assistant.message');

    // Appointment routes.
    Route::get('appointments', [AppointmentController::class, 'index'])
        ->name('appointments.index');
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
        ->name('appointments.cancel');
    Route::put('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])
        ->name('appointments.reschedule');

    // Admin-only doctor management routes.
    Route::middleware('admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::resource('doctors', DoctorController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });
});

require __DIR__.'/settings.php';
