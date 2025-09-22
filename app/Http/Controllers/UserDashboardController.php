<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ProjectsModel;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Stats for tasks assigned to the user
        $taskStats = $user->assignedTasks()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $taskStats['total'] = $user->assignedTasks()->count();

        // Stats for projects managed by the user
        $projectStats = $user->managedProjects()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $projectStats['total'] = $user->managedProjects()->count();

        return Inertia::render('Dashboard', [
            'taskStats'    => $taskStats,
            'projectStats' => $projectStats,
        ]);
    }

    public function projects_render(Request $request)
    {
        $user = auth()->user();

        $projects = $user->managedProjects()
            ->with(['manager', 'client'])
            ->when($request->filter_id, fn($q) => $q->where('id', $request->filter_id))
            ->when($request->filter_manager, fn($q) => 
                $q->whereHas('manager', fn($m) => $m->where('name', 'like', "%{$request->filter_manager}%"))
            )
            ->when($request->filter_status, fn($q) => $q->where('status', $request->filter_status))
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Project', [
            'projects' => $projects,
            'filters'  => $request->only(['filter_id', 'filter_manager', 'filter_status', 'per_page', 'page']),
            'clients'  => \App\Models\User::where('role', 'client')->get(['id', 'name']),
            'managers' => [$user->name],
        ]);
    }

    public function editProject(Request $request, $projectId)
    {
        $user = auth()->user();
        $project = ProjectsModel::find($projectId);

        if (!$project) {
            return redirect()->back()->with('error', 'Project not found.');
        }

        if ($project->manager_id != $user->id) {
            return redirect()->back()->with('error', 'You are not authorized to edit this project.');
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'client_id'   => 'required|exists:users,id',
            'description' => 'nullable|string',
            'status'      => 'required|string|in:pending,in_progress,review,testing,completed,cancelled',
        ]);

        $project->update($validated);

        return redirect()->back()->with('success', 'Project updated successfully.');
    }
}
