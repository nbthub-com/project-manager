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
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->input('per_page', 10);
        
        // Fetch tasks depending on role
        if ($user->role === 'admin') {
            $tasksQuery = TasksModel::with(['manager', 'assignee', 'project'])
                ->latest();
        } else {
            $tasksQuery = TasksModel::with(['manager', 'assignee', 'project'])
                ->where(function ($q) use ($user) {
                    $q->where('to_id', $user->id)
                    ->orWhere('by_id', $user->id);
                })
                ->latest();
        }
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->input('search');
            $tasksQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('manager', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('assignee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('project', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply status filter if provided
        if ($request->filled('filter_status')) {
            $tasksQuery->where('status', $request->input('filter_status'));
        }
        
        // Apply manager filter if provided
        if ($request->filled('filter_manager')) {
            $tasksQuery->whereHas('manager', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->input('filter_manager')}%");
            });
        }
        
        // Apply assignee filter if provided
        if ($request->filled('filter_assignee')) {
            $tasksQuery->whereHas('assignee', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->input('filter_assignee')}%");
            });
        }
        
        // Apply project filter if provided
        if ($request->filled('filter_project')) {
            $tasksQuery->whereHas('project', function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->input('filter_project')}%");
            });
        }
        
        $tasks = $tasksQuery->paginate($perPage);
        
        // Admin sees all projects
        if ($user->role === 'admin') {
            $managerOf = ProjectsModel::select('id', 'title')->get();
        } else {
            // Regular users see only the projects they manage
            $managerOf = $user->managedProjects()
                ->select('id', 'title')
                ->get();
        }
        
        $mappedTasks = $tasks->getCollection()->map(function ($task) {
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
        
        $tasks->setCollection($mappedTasks);
        
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
            'filters' => $request->only([
                'search', 
                'per_page',
                'filter_status',
                'filter_manager',
                'filter_assignee',
                'filter_project'
            ]),
        ]);
    }
    public function create(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'title'       => 'required|string|max:255|unique:tasks,title',
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