<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\TasksModel;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $user = User::with(['clientTasks', 'clientProjects'])->find($user->id);
        $taskStats = $user->clientTasks()
            ->selectRaw('tasks.status, COUNT(*) as count')
            ->groupBy('tasks.status')
            ->pluck('count', 'tasks.status');

        $taskStats['total'] = $user->clientTasks()->count();

        $projectStats = $user->clientProjects()
            ->selectRaw('projects.status, COUNT(*) as count')
            ->groupBy('projects.status')
            ->pluck('count', 'projects.status');

        $projectStats['total'] = $user->clientProjects()->count();
        return Inertia::render('client/Dashboard', [
            'taskStats'    => $taskStats,
            'projectStats' => $projectStats,
        ]);
    }
    
    public function viewProjects(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->input('per_page', 10);
        
        // Start with base query
        $projectsQuery = $user->clientProjects()
            ->with(['manager', 'notes']);
            
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->input('search');
            $projectsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('manager', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply status filter if provided
        if ($request->filled('filter_status')) {
            $projectsQuery->where('status', $request->input('filter_status'));
        }
        
        // Apply manager filter if provided
        if ($request->filled('filter_manager')) {
            $projectsQuery->whereHas('manager', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->input('filter_manager')}%");
            });
        }

        $projects = $projectsQuery->paginate($perPage);
        
        // Ensure pagination values are not null
        $projectsData = $projects->toArray();
        if (!isset($projectsData['from'])) {
            $projectsData['from'] = 0;
        }
        if (!isset($projectsData['to'])) {
            $projectsData['to'] = 0;
        }

        return Inertia::render('client/Project', [
            'projects' => $projectsData,
            'filters' => $request->only([
                'search', 
                'per_page',
                'filter_status',
                'filter_manager'
            ]),
        ]);
    }
}