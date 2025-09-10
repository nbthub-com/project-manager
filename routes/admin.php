<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ProjectsController;

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // Views
    Route::get('/', [AdminController::class, 'index'])->name('dashboard.admin');
    Route::get('/members', [AdminController::class, 'view_mems'])->name('members.view');

    // Form handlers
    Route::post('/members/add-member', [AdminController::class, 'add_mem']);
    Route::get('/search/{query}', [AdminController::class, 'search'])->name('admin.search');

    // Project
    Route::get('/projects', [ProjectsController::class, 'index'])->name('admin.projects');
    Route::post('/projects/create', [ProjectsController::class, 'create'])->name('admin.projects.create');
    Route::put('/projects/update/{id}', [ProjectsController::class, 'update'])->name('admin.projects.update');
    Route::delete('/projects/delete/{id}', [ProjectsController::class, 'delete'])->name('admin.projects.delete');
});