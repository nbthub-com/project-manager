<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProjectsModel;
use Inertia\Inertia;
use App\Models\User;

class ProjectsController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Projects', [
            'projects' => ProjectsModel::select(
                'projects.id',
                'projects.title',
                'users.name as manager_name',
                'projects.description',
                'projects.is_starred',
                'projects.status'
            )
            ->leftJoin('users', 'projects.manager_id', '=', 'users.id')
            ->orderBy('projects.id', 'desc')
            ->get(),
            'names' => User::pluck('name'),
        ]);
    }
    public function create(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|min:3|string',
            'manager' => 'required|string',
            'description' => 'nullable|string',
            'is_starred' => 'nullable|boolean',
        ]);

        // Check uniqueness
        $exists = ProjectsModel::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])->first();
        if ($exists) {
            return redirect()->route('admin.projects')
                ->withErrors(['title' => 'Project title already exists.']);
        }

        $manager_id = User::where('name', $validated['manager'])->value('id');

        ProjectsModel::create([
            'title' => $validated['title'],
            'manager_id' => $manager_id,
            'description' => $validated['description'] ?? '',
            'is_starred' => $validated['is_starred'] ?? false,
            'status' => 'inprogress'
        ]);

        return redirect()->route('admin.projects')
            ->with('success', 'Project added successfully!');
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|min:3|string',
            'manager' => 'required|string',
            'description' => 'nullable|string',
            'is_starred' => 'nullable|boolean',
            'status' => 'required|in:inprogress,completed,aborted'
        ]);

        $project = ProjectsModel::findOrFail($id);

        // Check uniqueness (exclude self)
        $exists = ProjectsModel::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])
            ->where('id', '!=', $id)
            ->first();
        if ($exists) {
            return redirect()->route('admin.projects')
                ->withErrors(['title' => 'Project title already exists.']);
        }

        $manager_id = User::where('name', $validated['manager'])->value('id');

        $project->update([
            'title' => $validated['title'],
            'manager_id' => $manager_id,
            'description' => $validated['description'] ?? '',
            'is_starred' => $validated['is_starred'] ?? false,
            'status' => $validated['status']
        ]);

        return redirect()->route('admin.projects')
            ->with('success', 'Project updated successfully!');
    }
    public function delete($id)
    {
        $project = ProjectsModel::findOrFail($id);
        $project->delete();

        return redirect()->route('admin.projects')
            ->with('success', 'Project deleted successfully!');
    }
}
