<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TasksModel;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Models\ProjectsModel;

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

            // Admin sees all projects
            $managerOf = ProjectsModel::select('id', 'title')->get();
        } else {
            $tasks = TasksModel::with(['manager', 'assignee', 'project'])
                ->where(function ($q) use ($user) {
                    $q->where('to_id', $user->id)
                    ->orWhere('by_id', $user->id);
                })
                ->latest()
                ->get();

            // Regular users see only the projects they manage
            $managerOf = $user->managedProjects()
                ->select('id', 'title')
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

        // Slug bana letay hein
        $validated['role_title'] = Str::slug($validated['role_title']);

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
            'role_title'  => $validated['role_title']
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
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'role_title'  => 'required|string',
            'to_id'       => 'required|exists:users,id',
            'project_id'  => 'required|exists:projects,id',
            'status'      => 'required|string|in:pending,in_progress,completed,cancelled',
        ]);

        // Slug bana letay hein yahan bhi
        $validated['role_title'] = Str::slug($validated['role_title']);

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
    public function delete($id)
    {
        $task = TasksModel::findOrFail($id);
        $user = auth()->user();
        $isManager = $user->managedProjects()
            ->where('id', $task->pr_id)
            ->exists();
        if (! $isManager && $user->role !== 'admin') {
            return redirect('/tasks')->with('error', 'You cannot delete this task!');
        }
        $task->delete();
        return redirect('/tasks')->with('success', 'Task deleted successfully!');
    }
}
