<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'client'])->prefix('client')->group(function (){
    Route::get('/', [ClientController::class, 'index']);
    Route::get('/tasks', [ClientController::class, 'viewTasks']);
    Route::get('/projects', [ClientController::class, 'viewProjects']);
});

