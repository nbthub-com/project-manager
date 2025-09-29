<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProjectsModel;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Validation\Rule;

class ProjectsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $filterId = $request->input('filter_id');
        $filterManager = $request->input('filter_manager');
        $filterStatus = $request->input('filter_status');
        $filterStarred = $request->input('filter_starred');
        $filterClient = $request->input('filter_client');
        
        // Start with base query using relationships
        $query = ProjectsModel::with(['manager', 'client', 'notes.member'])
            ->orderBy('projects.title', 'asc');
        
        if ($filterId) {
            $query->where('projects.id', $filterId);
        }
        if ($filterManager) {
            $query->whereHas('manager', function($q) use ($filterManager) {
                $q->where('name', 'like', "%{$filterManager}%");
            });
        }
        if ($filterClient) {
            $query->whereHas('client', function($q) use ($filterClient) {
                $q->where('name', 'like', "%{$filterClient}%");
            });
        }
        if ($filterStatus) {
            $query->where('projects.status', $filterStatus);
        }
        if ($filterStarred !== null && $filterStarred !== '') {
            $query->where('projects.is_starred', $filterStarred === 'true' || $filterStarred === '1');
        }
        
        $projects = $query->paginate($perPage);
        
        $managers = User::where('role', '!=', 'client')
            ->distinct()
            ->orderBy('name')
            ->pluck('name');
            
        $clients = User::where('role', 'client')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();
            
        return Inertia::render('admin/Projects', [
            'projects' => $projects,
            'managers' => $managers,
            'clients' => $clients,
            'filters' => $request->only([
                'filter_id',
                'filter_manager', 
                'filter_client',
                'filter_status', 
                'filter_starred',
                'per_page'
            ]),
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|min:3|string',
            'manager' => 'required|string|exists:users,name',
            'client' => [
                'required',
                'string',
                Rule::exists('users', 'name')->where('role', 'client')
            ],
            'description' => 'nullable|string',
            'is_starred' => 'nullable|boolean',
        ]);

        // Check uniqueness
        $exists = ProjectsModel::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])->first();
        if ($exists) {
            return back()->withErrors(['title' => 'Project title already exists.'])->withInput();
        }

        $manager = User::where('name', $validated['manager'])->first();
        if (!$manager) {
            return redirect()->route('admin.projects')
                ->with('error', 'Selected manager not found!');
        }
        if($manager->role === 'client')
        {
            return redirect()->route('admin.projects')
                ->with('error', 'Client cannot be manager!');
        }
        $client = User::where('name', $validated['client'])->where('role', 'client')->first();
        if (!$client) {
            return redirect()->route('admin.projects')
                ->with('error', 'Selected client not found!');
        }
        ProjectsModel::create([
            'title' => $validated['title'],
            'manager_id' => $manager->id,
            'client_id' => $client->id,
            'description' => $validated['description'] ?? '',
            'is_starred' => $validated['is_starred'] ?? false,
            'status' => 'in_progress'
        ]);

        return redirect()->route('admin.projects')
            ->with('success', 'Project added successfully!');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|min:3|string',
            'manager' => 'required|string|exists:users,name',
            'client' => [
                'required',
                'string',
                Rule::exists('users', 'name')->where('role', 'client')
            ],
            'description' => 'nullable|string',
            'is_starred' => 'nullable|boolean',
            'status' => 'required|in:pending,in_progress,completed,testing,review,cancelled'
        ]);

        $project = ProjectsModel::findOrFail($id);
        
        // Check uniqueness (exclude self)
        $exists = ProjectsModel::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])
            ->where('id', '!=', $id)
            ->first();
        if ($exists) {
            return back()->withErrors(['title' => 'Project title already exists.'])->withInput();
        }

        $manager = User::where('name', $validated['manager'])->first();
        if (!$manager) {
            return redirect()->route('admin.projects')
                ->with('error', 'Selected manager not found!');
        }
        if($manager->role === 'client')
        {
            return redirect()->route('admin.projects')
                ->with('error', 'Client cannot be manager!');
        }
        $client = User::where('name', $validated['client'])->where('role', 'client')->first();
        if (!$client) {
            return redirect()->route('admin.projects')
                ->with('error', 'Selected client not found!');
        }
        $project->update([
            'title' => $validated['title'],
            'manager_id' => $manager->id,
            'client_id' => $client->id,
            'description' => $validated['description'] ?? '',
            'is_starred' => $validated['is_starred'] ?? false,
            'status' => $validated['status']
        ]);

        // Update tasks status if project is cancelled
        if ($validated['status'] === 'cancelled') {
            $project->tasks()->update(['status' => 'cancelled']);
        }

        return redirect()->route('admin.projects')
            ->with('success', 'Project updated successfully!');
    }

    public function delete($id)
    {
        $project = ProjectsModel::findOrFail($id);
        
        // Check if project has tasks before deleting
        if ($project->tasks()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete project with associated tasks.']);
        }
        
        $project->delete();
        
        return redirect()->route('admin.projects')
            ->with('success', 'Project deleted successfully!');
    }
}