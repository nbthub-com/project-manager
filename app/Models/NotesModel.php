<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectsModel;
use App\Models\TasksModel;
use App\Models\User;

class NotesModel extends Model
{
    protected $table = 'notes';

    protected $fillable = [
        'content',
        'context',
        'context_id',
        'member_id',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function project()
    {
        return $this->belongsTo(ProjectsModel::class, 'context_id')
                    ->where('context', 'proj');
    }

    public function task()
    {
        return $this->belongsTo(TasksModel::class, 'context_id')
                    ->where('context', 'task');
    }

    public static function forProjects()
    {
        return static::query()
            ->where('context', 'proj');
    }

    public static function forTasks()
    {
        return static::query()
            ->where('context', 'task');
    }
}
