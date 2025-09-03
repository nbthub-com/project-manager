<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectsModel extends Model
{
    protected $table = 'projects';
    protected $fillable = [
        'title', 'manager_id', 'description',
        'is_starred', 'status'
    ];
}
