<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;

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
    public function viewTasks()
    {
        $user = auth()->user();

        $tasks = $user->clientTasks()
            ->with(['project', 'manager'])
            ->paginate(10)
            ->through(function ($task) {
                return [
                    'id'          => $task->id,
                    'title'       => $task->title,
                    'status'      => $task->status,
                    'priority'    => $task->priority,
                    'deadline'    => $task->deadline,
                    'project'     => $task->project?->title ?? '—',
                    'manager'     => $task->manager?->name ?? '—',
                    'description' => $task->description
                ];
            });

        return Inertia::render('client/Tasks', [
            'tasks' => $tasks,
        ]);
    }
    public function viewProjects()
    {
        $user = auth()->user();

        $projects = $user->clientProjects()
            ->with(['manager'])
            ->paginate(10)
            ->through(function ($project) {
                return [
                    'id'          => $project->id,
                    'title'       => $project->title,
                    'status'      => $project->status,
                    'manager'     => $project->manager?->name ?? '—',
                    'description' => $project->description,
                    'task_count'  => $project->tasks->count()
                ];
            });

        return Inertia::render('client/Project', [
            'projects' => $projects,
        ]);
    }
}
