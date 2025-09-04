<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MailboxController;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'user'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mailbox', [MailboxController::class, 'index']);
    Route::post('/mailbox/send', [MailboxController::class, 'send']);
    Route::put('/mailbox/update/{id}', [MailboxController::class, 'update']);
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
