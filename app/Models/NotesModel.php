<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectsModel;
use App\Models\TasksModel;
use App\Models\User;

class Note extends Model
{
    protected $table = 'notes';

    protected $fillable = [
        'content',
        'context',
        'context_id',
        'member_id',
        'type',
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

    public static function forProjects($type = null)
    {
        return static::query()
            ->where('context', 'proj')
            ->when($type, fn($q) => $q->where('type', $type));
    }

    public static function forTasks($type = null)
    {
        return static::query()
            ->where('context', 'task')
            ->when($type, fn($q) => $q->where('type', $type));
    }
}
