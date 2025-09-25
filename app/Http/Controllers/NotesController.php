<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotesModel;
use App\Models\TasksModel;
use App\Models\ProjectsModel;

class NotesController extends Controller
{
    public function index()
    {
        // List notes if needed
    }
    public function create(Request $request)
    {
        $request->validate([
            'content'    => 'required|string',
            'context'    => 'required|in:proj,task',
            'context_id' => 'required|integer',
            'member_id'  => 'required|exists:users,id',
            'type'       => 'nullable|in:note,question',
        ]);

        $user = auth()->user();

        if ($request->context === 'task') {
            $task = TasksModel::with('project.client', 'project.manager', 'assignee', 'manager')
                ->findOrFail($request->context_id);

            $allowed = (
                $task->to_id === $user->id ||                // assignee
                $task->by_id === $user->id ||                // task manager
                $task->project->manager_id === $user->id ||  // project manager
                $task->project->client_id === $user->id ||   // project client
                $user->role === 'admin'               // system admin
            );

            if (!$allowed) {
                return redirect()->back()->withErrors("You cannot create note for this task.");
            }
        }

        if ($request->context === 'proj') {
            $project = ProjectsModel::findOrFail($request->context_id);

            $allowed = (
                $project->manager_id === $user->id ||  // project manager
                $project->client_id === $user->id ||   // project client
                $user->role === 'admin'         // system admin
            );

            if (!$allowed) {
                return redirect()->back()->withErrors("You cannot create note for this project.");
            }
        }

        $created = NotesModel::create($request->only([
            'content', 'context', 'context_id', 'member_id', 'type'
        ]));
        
        // Load relationships for the response
        $created->load('member');
        
        // Return JSON response with the new note
        return response()->json([
            'note' => $created,
            'message' => 'Note created successfully!'
        ]);
    }    
}
