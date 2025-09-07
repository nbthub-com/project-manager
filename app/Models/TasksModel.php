<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectsModel;

class TasksModel extends Model
{
    protected $table = 'tasks';

    protected $fillable = [
        'title',
        'role_title',
        'status',
        'description',
        'by_id',
        'to_id',
        'pr_id'
    ];

    // Manager (assigned by)
    public function manager()
    {
        return $this->belongsTo(User::class, 'by_id');
    }

    // Assignee (assigned to)
    public function assignee()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    // ProjectsModel
    public function project()
    {
        return $this->belongsTo(ProjectsModel::class, 'pr_id');
    }
}
