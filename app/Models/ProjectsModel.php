<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TasksModel;

class ProjectsModel extends Model
{
    protected $table = 'projects';
    protected $fillable = [
        'title', 'manager_id', 'description',
        'is_starred', 'status'
    ];
    
    protected $casts = [
        'is_starred' => 'boolean',
    ];
    
    public function tasks()
    {
        return $this->hasMany(TasksModel::class, 'pr_id');
    }
    
    // Add this to prevent mass assignment issues
    protected $guarded = ['id'];
}