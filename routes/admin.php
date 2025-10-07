<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ProjectsController;

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // Views
    Route::get('/', [AdminController::class, 'index'])->name('dashboard.admin');
    Route::get('/members', [AdminController::class, 'view_mems'])->name('members.view');
    Route::put('/members', [AdminController::class, 'update_member'])->name('members.update');

    // Form handlers
    Route::get('/members', [AdminController::class, 'view_mems'])->name('members.view');
    Route::get('/members/{id}', [AdminController::class, 'view_member']);
    Route::post('/members/add-member', [AdminController::class, 'add_mem'])->name('members.add');
    Route::delete('/members/delete/{id}', [AdminController::class, 'delete_mem'])->name('members.delete');
    Route::get('/search/{query}', [AdminController::class, 'search'])->name('members.search');

    // Project
    Route::get('/projects', [ProjectsController::class, 'index'])->name('admin.projects');
    Route::get('/projects/{id}', [ProjectsController::class, 'viewProject']);
    Route::post('/projects/create', [ProjectsController::class, 'create'])->name('admin.projects.create');
    Route::put('/projects/update/{id}', [ProjectsController::class, 'update'])->name('admin.projects.update');
    Route::delete('/projects/delete/{id}', [ProjectsController::class, 'delete'])->name('admin.projects.delete');
});