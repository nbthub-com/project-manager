<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\TasksModel;
use App\Models\ProjectsModel;

class AdminController extends Controller
{
    public function index()
    {
        // Simply send all users and managers for dashboard display
        $users = User::where('role', 'user')->count();
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'user_count' => $users ?? 0,
            ],
        ]);
    }

    public function view_mems()
    {
        $users = User::where('role', '!=', 'admin')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'email']);

        $users = $users->map(function ($user) {
            // Projects managed
            $projectsAssigned = ProjectsModel::where('manager_id', $user->id)->count();
            $projectsDone = ProjectsModel::where('manager_id', $user->id)
                ->where('status', 'completed')
                ->count();

            // Tasks assigned to the user
            $tasksAssigned = TasksModel::where('to_id', $user->id)->count();
            $tasksDone = TasksModel::where('to_id', $user->id)
                ->where('status', 'completed')
                ->count();

            // Roles in projects (grab distinct role_title from tasks table)
            $roles = TasksModel::where('to_id', $user->id)
                ->distinct()
                ->pluck('role_title')
                ->toArray();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'projects_assigned' => $projectsAssigned,
                'projects_done' => $projectsDone,
                'tasks_assigned' => $tasksAssigned,
                'tasks_done' => $tasksDone,
                'roles' => $roles,
            ];
        });

        return Inertia::render('admin/Members', [
            'users' => $users,
        ]);
    }

    public function add_mem(Request $request)
    {
        // Normalize values before validation
        $request->merge([
            'name' => strtolower($request->name),
            'email' => strtolower($request->email),
            'role' => strtolower($request->role),
        ]);

        // Validate with case-insensitive uniqueness
        $validated = $request->validate([
            'name' => [
                'required',
                'min:3',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(name) = ?', [$value])->exists()) {
                        $fail('The '.$attribute.' has already been taken.');
                    }
                },
            ],
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(email) = ?', [$value])->exists()) {
                        $fail('The '.$attribute.' has already been taken.');
                    }
                },
            ],
            'role' => 'required|in:user',
            'password' => 'required|min:6',
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'user' => $user,
        ], 201);
    }
    public function search($query)
    {
        $query = strtolower($query);
        if(!$query){
            return redirect()->route('members.view');
        }
        $users = User::where('role', '!=', 'admin')
            ->where(function($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$query}%"]);
            })
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'email', 'role']);

        return response()->json($users);
    }
}
