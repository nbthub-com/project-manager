<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TasksModel;
use App\Models\User;
use App\Models\Project;
use Inertia\Inertia;
/*
        'title', 'role_title', 'status',
        'description', 'assigned_id', 'project_id'
*/
class TasksController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return TasksModel::all()
    }
}
