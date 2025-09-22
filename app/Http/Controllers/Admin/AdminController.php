<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\TasksModel;
use App\Models\ProjectsModel;
use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        // === Users ===
        $totalMembers   = User::where('role', 'user'   )->count();
        $totalManagers  = User::where('role', 'manager')->count();

        // === Top 5 Members with most tasks ===
        $topMembers = User::where('role', 'user')
            ->withCount('assignedTasks') // correct relation
            ->orderByDesc('assigned_tasks_count')
            ->take(5)
            ->get(['id', 'name', 'email']);

        // === Top 5 Managers with most projects ===
        $topManagers = User::where('role', 'manager')
            ->withCount('managedProjects') // correct relation
            ->orderByDesc('managed_projects_count')
            ->take(5)
            ->get(['id', 'name', 'email']);

        // === Projects ===
        $totalProjects     = ProjectsModel::count();
        $runningProjects   = ProjectsModel::where('status', 'in_progress')->count();
        $completedProjects = ProjectsModel::where('status', 'completed')->count();
        $cancelledProjects = ProjectsModel::where('status', 'cancelled')->count();

        // === Tasks ===
        $totalTasks     = TasksModel::count();
        $pendingTasks   = TasksModel::where('status', 'pending')->count();
        $runningTasks   = TasksModel::where('status', 'in_progress')->count();
        $completedTasks = TasksModel::where('status', 'completed')->count();
        $cancelledTasks = TasksModel::where('status', 'cancelled')->count();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'members' => [
                    'total'    => $totalMembers,
                    'managers' => $totalManagers,
                ],
                'top' => [
                    'members'  => $topMembers,
                    'managers' => $topManagers,
                ],
                'projects' => [
                    'total'     => $totalProjects,
                    'running'   => $runningProjects,
                    'completed' => $completedProjects,
                    'cancelled' => $cancelledProjects,
                ],
                'tasks' => [
                    'total'     => $totalTasks,
                    'pending'   => $pendingTasks,
                    'running'   => $runningTasks,
                    'completed' => $completedTasks,
                    'cancelled' => $cancelledTasks,
                ],
            ],
        ]);
    }
    // Helper method to get user data with stats
    private function getUserData($request)
    {
        $perPage = $request->input('per_page', 10);
        $filterRole = $request->input('filter_role');
        $filterId = $request->input('filter_id');
        $filterName = $request->input('filter_name');

        // Start with base query
        $query = User::where('role', '!=', 'admin')
            ->orderBy('id', 'desc');

        // Apply ID filter if provided
        if ($filterId) {
            $query->where('id', $filterId);
        }
        if ($filterName) {
            $query->where('name', $filterName);
        }

        // Get paginated users
        $users = $query->paginate($perPage);

        // Get distinct roles for filter dropdown
        $roles = TasksModel::distinct()
            ->pluck('role_title')
            ->filter()
            ->values()
            ->toArray();

        // Map users with additional data
        $mappedUsers = $users->getCollection()->map(function ($user) {
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
            $userRoles = TasksModel::where('to_id', $user->id)
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
                'roles' => $userRoles,
                'is_client' => $user->role === 'client',
                'client_tasks' => $user->clientTasks()->count(),
                'client_projects' => $user->clientProjects()->count()
            ];
        });
        
        // Set the mapped collection back to the paginator
        $users->setCollection($mappedUsers);
        
        return [
            'users' => $users,
            'roles' => $roles,
        ];
    }

    public function view_mems(Request $request)
    {
        $data = $this->getUserData($request);

        return Inertia::render('admin/Members', [
            'users' => $data['users'],
            'roles' => $data['roles'],
            'filters' => $request->only(['filter_role', 'filter_id', 'filter_name','per_page']),
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
            'role' => 'required|in:user,client',
            'password' => 'required|min:6',
        ]);
        
        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        // Return Inertia response with updated users and flash message
        return redirect()->route('members.view')->with('success', 'Member added successfully!');
    }

    public function delete_mem($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        // Return Inertia response with updated users and flash message
        return redirect()->route('members.view')->with('success', 'Member deleted successfully!');
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

        // Map users to include stats
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
        
        return response()->json($users);
    }
}