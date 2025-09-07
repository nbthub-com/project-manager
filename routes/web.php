<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\TasksController;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'user'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mailbox', [MailboxController::class, 'index']);
    Route::post('/mailbox/send', [MailboxController::class, 'send']);
    Route::put('/mailbox/update/{id}', [MailboxController::class, 'update']);

    // Using patch as we are just toggling...
    Route::patch('/mailbox/update/read/{id}', [MailboxController::class, 'setToRead']);

    // Mailbox:: Outbox message delete route...
    Route::delete('/mailbox/delete/{id}', [MailboxController::class, 'destroy']);

    // Tasks
    Route::get('/tasks', [TasksController::class, 'index']);
    Route::post('/tasks/create', [TasksController::class, 'create']);
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
