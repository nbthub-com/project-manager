<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TasksModel;
use App\Models\User;

class ProjectsModel extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'title',
        'manager_id',
        'client_id',
        'description',
        'is_starred',
        'status',
    ];

    protected $casts = [
        'is_starred' => 'boolean',
    ];

    // Relationship: project has many tasks
    public function tasks()
    {
        return $this->hasMany(TasksModel::class, 'pr_id');
    }

    // Relationship: project belongs to a manager (User)
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Relationship: project belongs to a client (User)
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // Add this to prevent mass assignment issues
    protected $guarded = ['id'];
}
