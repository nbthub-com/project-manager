<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AdminController;

Route::middleware(['auth', 'admin'])->group(function () {
    // Views
    Route::get('/', [AdminController::class, 'index'])->name('dashboard.admin');
    Route::get('/members', [AdminController::class, 'view_mems'])->name('members.view');

    // Form handlers
    Route::post('/members/add-member', [AdminController::class, 'add_mem']);
});