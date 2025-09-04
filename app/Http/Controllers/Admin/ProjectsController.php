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
    public function addProject(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|min:3|string',
            'manager' => 'required|string',
            'description' => 'nullable',
            'is_starred' => 'nullable|boolean',
        ]);
        // Check uniqueness
        $exists = ProjectsModel::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])->first();
        if ($exists) {
            return redirect()
                ->route('admin.projects');
        }
        $manager_id = User::where('name', $validated['manager'])->value('id');

        // STATUS: CAN ONLY IN: INPROGRESS, COMPLETED or ABORTED
        ProjectsModel::create([
            'title' => $validated['title'],
            'manager_id' => $manager_id,
            'description' => $validated['description'] ?? '',
            'is_starred' => $validated['is_starred'] ?? false,
            'status' => 'inprogress'
        ]);
        return redirect()
            ->route('admin.projects');
    }
}
