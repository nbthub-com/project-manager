<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasksModel extends Model
{
    protected $table = 'tasks';
    protected $fillable = [
        'title', 'role_title', 'status',
        'description', 'assigned_id', 'project_id'
    ];
}
