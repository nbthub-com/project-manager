<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return Inertia::render('Dashboard', [
            'stats' => [
                'tasks' => [
                    'total'     => $user->assignedTasks()->count(),
                    'pending'   => $user->assignedTasks()->where('status', 'pending')->count(),
                    'running'   => $user->assignedTasks()->where('status', 'in_progress')->count(),
                    'completed' => $user->assignedTasks()->where('status', 'completed')->count(),
                    'cancelled' => $user->assignedTasks()->where('status', 'cancelled')->count(),
                ],
                'projects' => [
                    'total'     => $user->managedProjects()->count(),
                    'running'   => $user->managedProjects()->where('status', 'in_progress')->count(),
                    'completed' => $user->managedProjects()->where('status', 'completed')->count(),
                    'cancelled' => $user->managedProjects()->where('status', 'cancelled')->count(),
                ],
            ]
        ]);
    }
}
