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
        // === Projects ===
        $projectStats = ProjectsModel::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $projectStats['total'] = ProjectsModel::count();

        // === Tasks ===
        $taskStats = TasksModel::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $taskStats['total'] = TasksModel::count();

        return Inertia::render('admin/Dashboard', [
            'taskStats'    => $taskStats,
            'projectStats' => $projectStats,
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
            ->orderByRaw("CASE WHEN role = 'client' THEN 0 ELSE 1 END") // clients first
            ->orderBy('name', 'asc'); // alphabetical within each group

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
            
            $tasks = TasksModel::where('to_id', $user->id)
                    ->orWhere('by_id', $user->id)
                    ->get();
                
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
                'tasks'  => $tasks,
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

    public function view_member($id)
    {
        $member = User::with([
            'managedProjects' => [
                'tasks.notes.member',
                'notes.member',
                'client',
            ],
            'clientProjects' => [
                'tasks.notes.member',
                'notes.member',
                'manager',
                'client',
            ],
            'assignedTasks' => [
                'project',
                'notes.member',
                'manager',
                'assignee'
            ],
            'givenTasks' => [
                'project',
                'notes.member',
                'assignee'
            ],
        ])->findOrFail($id);

        // Get all users for assignee dropdown
        $names = User::where('id', '!=', $id) // Exclude current member
                    ->whereIn('role', ['user', 'admin']) // Only members/admins, not clients
                    ->select('id', 'name')
                    ->get();

        // Get all roles for dropdown
        $roles = \App\Models\TasksModel::distinct()
                    ->whereNotNull('role_title')
                    ->pluck('role_title')
                    ->toArray();

        // Get clients and managers for project creation
        $clients = [];
        $managers = [];
        
        $clients = User::where('role', 'client')->pluck('name')->toArray();        
        $managers = User::whereIn('role', ['user', 'admin'])->pluck('name')->toArray();

        return Inertia::render('admin/MemberShow', [
            'member' => $member->toArray(),
            'names' => $names,
            'roles' => $roles,
            'manager_of' => $member->managedProjects,
            'clients' => $clients,
            'managers' => $managers,
        ]);
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
            ->orderByRaw("CASE WHEN role = 'client' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc')
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
    public function update_member(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:users,id',
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,'.$request->id,
            'role' => 'required|in:user,client',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $user = User::findOrFail($validated['id']);
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }
        $user->update($updateData);
        return redirect()->back()->with('success', 'Member updated successfully!');
    }
}