<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\Admin\ProjectsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'client'])->prefix('client')->group(function (){
    Route::get('/', [ClientController::class, 'index']);
    Route::get('/projects', [ClientController::class, 'viewProjects']);
    Route::get('/projects/{id}', [ProjectsController::class, 'viewProject']);
});

