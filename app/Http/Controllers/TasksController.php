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
           $tasksQuery = TasksModel::with(['manager', 'assignee', 'project', 'notes.member'])
            ->orderBy('title', 'asc');

        } else {
            $tasksQuery = TasksModel::with(['manager', 'assignee', 'project', 'notes.member'])
                ->where(function ($q) use ($user) {
                    $q->where('to_id', $user->id)  // Assignee
                    ->orWhere('by_id', $user->id)  // Manager
                    ->orWhereHas('project', function ($q) use ($user) {
                        $q->where('client_id', $user->id);  // Client
                    });
                })
                ->orderBy('title', 'asc');
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

        if ($request->filled('filter_priority')) {
            $tasksQuery->where('priority', $request->input('filter_priority'));
        }

        $tasks = $tasksQuery->paginate($perPage);
        
        // Admin sees all projects
        if ($user->role === 'admin') {
            $managerOf = ProjectsModel::select('id', 'title')->get();
        } else {
            // Regular users see only the projects they manage or are clients of
            $managerOf = ProjectsModel::where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)  // Manager
                ->orWhere('client_id', $user->id);  // Client
            })->select('id', 'title')->get();
        }
        
        $mappedTasks = $tasks->getCollection()->map(function ($task) {
            return [
                'id'          => $task->id,
                'title'       => $task->title,
                'role_title'  => $task->role_title,
                'status'      => $task->status,
                'priority'    => $task->priority,
                'deadline'    => $task->deadline?->format('Y-m-d'),
                'description' => $task->description,
                'manager'     => $task->manager?->name,
                'assignee'    => $task->assignee?->name,
                'project'     => $task->project?->title,
                'created_at'  => $task->created_at->toDateTimeString(),
                'updated_at'  => $task->updated_at->toDateTimeString(),
                'notes'       => $task->notes->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'content' => $note->content,
                        'member' => [
                            'id' => $note->member->id,
                            'name' => $note->member->name
                        ],
                        'created_at' => $note->created_at->toDateTimeString(),
                        'updated_at' => $note->updated_at->toDateTimeString(),
                    ];
                })
            ];
        });
        
        $tasks->setCollection($mappedTasks);
        
        $names = User::where('role', 'user')
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
                'filter_project',
                'filter_priority'
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
            'priority'    => 'required|in:high,medium,low',
            'deadline'    => 'nullable|date',
        ]);
        // Slug bana letay hein
        $validated['role_title'] = Str::slug($validated['role_title']);
        $isManager = $user->managedProjects()
            ->where('id', $validated['project_id'])
            ->exists();
        $isClient = $user->clientProjects()
            ->where('id', $validated['project_id'])
            ->exists();
        if (!$isManager && !$isClient && $user->role !== 'admin') {
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
            'role_title'  => $validated['role_title'],
            'priority'    => $validated['priority'],
            'deadline'    => $validated['deadline'],
        ]);
        return redirect('/tasks')->with('success', 'Task created successfully.');
    }
    public function update(Request $request, $id)
    {
        $task = TasksModel::findOrFail($id);
        $user = auth()->user();

        $isManager = $user->managedProjects()
            ->where('id', $task->pr_id)
            ->exists();

        $isAssignee = $task->to_id === $user->id;
        $isClient = $user->clientProjects()
            ->where('id', $task->pr_id)
            ->exists();

        // if user is neither admin, manager, nor assignee → reject
        if (!$isManager && !$isAssignee && !$isClient && $user->role !== 'admin') {
            return redirect('/tasks')->with('error', 'You cannot update this task!');
        }

        if ($isAssignee && $user->role !== 'admin' && !$isManager) {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,in_progress,completed,testing,review,cancelled',
            ]);

            $task->update([
                'status' => $validated['status'],
            ]);

            return redirect('/tasks')->with('success', 'As assignee, only status was updated!');
        }

        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'role_title'  => 'nullable|string',
            'to_id'       => 'nullable|exists:users,id',
            'project_id'  => 'nullable|exists:projects,id',
            'status'      => 'nullable|string|in:pending,in_progress,completed,testing,review,cancelled',
            'priority'    => 'nullable|in:high,medium,low',
            'deadline'    => 'nullable|date',
        ]);

        if (!empty($validated['role_title'])) {
            $validated['role_title'] = Str::slug($validated['role_title']);
        }

    $task->update(array_filter($validated, fn($val) => $val !== null));

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