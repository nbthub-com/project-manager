<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\NotesController;

Route::get('/', [UserDashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'user'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Mailbox For All
    Route::get('/mailbox', [MailboxController::class, 'index']);
    Route::post('/mailbox/send', [MailboxController::class, 'send']);
    Route::put('/mailbox/update/{id}', [MailboxController::class, 'update']);

    // Using patch as we are just toggling...
    Route::patch('/mailbox/update/read/{id}', [MailboxController::class, 'setToRead']);

    // Mailbox:: Outbox message delete route...
    Route::delete('/mailbox/delete/{id}', [MailboxController::class, 'destroy']);

    // Tasks For both User and Admin
    Route::get('/tasks', [TasksController::class, 'index']);
    Route::post('/tasks/create', [TasksController::class, 'create']);
    Route::put('/tasks/update/{id}', [TasksController::class, 'update']);
    Route::delete('/tasks/delete/{id}', [TasksController::class, 'delete']);

    // Project for User
    Route::get('/projects', [UserDashboardController::class, 'projects_render']);
    Route::put('/projects/edit/{projectId}', [UserDashboardController::class, 'editProject']);

    // Notes
    Route::post('/notes', [NotesController::class, 'create']);
});



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/client.php';
