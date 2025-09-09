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
                ->where(function ($q) use ($user) {
                    $q->where('to_id', $user->id)
                      ->orWhere('by_id', $user->id);
                })
                ->latest()
                ->get();
        }

        $tasks = $tasks->map(function ($task) {
            return [
                'id'          => $task->id,
                'title'       => $task->title,
                'role_title'  => $task->role_title,
                'status'      => $task->status,
                'description' => $task->description,
                'manager'     => $task->manager?->name,
                'assignee'    => $task->assignee?->name,
                'project'     => $task->project?->title,
                'created_at'  => $task->created_at->toDateTimeString(),
                'updated_at'  => $task->updated_at->toDateTimeString(),
            ];
        });

        $managerOf = $user->managedProjects()
            ->select('id', 'title')
            ->get();

        $names = User::where('role', '!=', 'admin')
            ->select('id', 'name')
            ->get();
        
        $roles = TasksModel::select('role_title')
            ->distinct()
            ->pluck('role_title');

        return Inertia::render('Tasks', [
            'tasks' => $tasks,
            'roles' => $roles,
            'names' => $names,
            'manager_of' => $managerOf,
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'role_title'  => 'required|string',
            'to_id'       => 'required|exists:users,id',
            'project_id'  => 'required|exists:projects,id',
        ]);

        $isManager = $user->managedProjects()
            ->where('id', $validated['project_id'])
            ->exists();

        if (! $isManager && $user->role !== 'admin') {
            return back()->withErrors([
                'project_id' => 'You are not authorized to assign tasks for this project.'
            ]);
        }

        TasksModel::create([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'status'      => 'pending',
            'to_id'       => $validated['to_id'],
            'by_id'       => $user->id,
            'pr_id'       => $validated['project_id'],
        ]);

        return redirect('/tasks')->with('success', 'Task created successfully.');
    }

    public function update(Request $request, $id)
    {
        $task = TasksModel::findOrFail($id);
        $user = auth()->user();

        // Authorization: allow task creator, project manager, or admin
        $isManager = $user->managedProjects()
            ->where('id', $task->pr_id)
            ->exists();

        if (!$isManager && $user->role !== 'admin') {
            return redirect('/tasks')->with('error', 'You cannot update this task!');
        }

        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'role_title'  => 'required|string',
            'to_id'       => 'nullable|exists:users,id',
            'project_id'  => 'nullable|exists:projects,id',
            'status'      => 'nullable|string|in:pending,in_progress,completed,cancelled',
        ]);

        $task->update([
            'title'       => $validated['title'] ?? $task->title,
            'description' => $validated['description'] ?? $task->description,
            'to_id'       => $validated['to_id'] ?? $task->to_id,
            'pr_id'       => $validated['project_id'] ?? $task->pr_id,
            'status'      => $validated['status'] ?? $task->status,
            'role_title'  => $validated['role_title'] ?? $task->role_title
        ]);

        return redirect('/tasks')->with('success', 'Task updated successfully!');
    }
}
