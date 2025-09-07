<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TasksModel;
use App\Models\User;
use Inertia\Inertia;

class TasksController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Fetch tasks depending on role
        if ($user->role === 'admin') {
            $tasks = TasksModel::with(['manager', 'assignee', 'project'])
                ->latest()
                ->get();
        } else {
            $tasks = TasksModel::with(['manager', 'assignee', 'project'])
                ->where('to_id', $user->id)
                ->orWhere('by_id', $user->id)
                ->latest()
                ->get();
        }

        // Transform tasks
        $tasks = $tasks->map(function ($task) {
            return [
                'id'          => $task->id,
                'title'       => $task->title,
                'role_title'  => $task->role_title,
                'status'      => $task->status,
                'description' => $task->description,
                'manager'     => $task->manager ? $task->manager->name : null,
                'assignee'    => $task->assignee ? $task->assignee->name : null,
                'project'     => $task->project ? $task->project->title : null,
                'created_at'  => $task->created_at->toDateTimeString(),
                'updated_at'  => $task->updated_at->toDateTimeString(),
            ];
        });

        // Projects where the user is a manager
        $managerOf = $user->managedProjects()
            ->select('id', 'title')
            ->get()
            ->map(function ($project) {
                return [
                    'id'    => $project->id,
                    'title' => $project->title,
                ];
            });

        // All non-admin user names (for assigning tasks)
        $names = User::where('role', '!=', 'admin')
            ->select('id', 'name')
            ->get();

        return Inertia::render('Tasks', [
            'tasks'      => $tasks,
            'manager_of' => $managerOf,
            'names'      => $names,
        ]);
    }
    public function create(Request $request)
    {
        $user = auth()->user();
        // Validate request
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'to_id'       => 'required|exists:users,id',
            'project_id'  => 'required|exists:projects,id',
        ]);

        // Only if the creator is manager of that project
        $isManager = $user->managedProjects()
            ->where('id', $validated['project_id'])
            ->exists();

        if (! $isManager && $user->role !== 'admin') {
            return back()->withErrors([
                'project_id' => 'You are not authorized to assign tasks for this project.'
            ]);
        }

        // Create task
        $task = TasksModel::create([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'status'      => 'pending',
            'to_id'       => $validated['to_id'],
            'by_id'       => auth()->id(),
            'pr_id'       => $validated['project_id'], // ✅ map to actual column
        ]);

        return redirect('/tasks')
            ->with('success', 'Task created successfully.');
    }
}
