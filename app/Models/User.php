<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\ProjectsModel;
use App\Models\TasksModel;
use App\Models\ClientModel;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Returns all the projects that this user manages.
    public function managedProjects()
    {
        return $this->hasMany(ProjectsModel::class, 'manager_id');
    }

    // Returns all the tasks assigned to this user
    public function assignedTasks()
    {
        return $this->hasMany(TasksModel::class, 'to_id');
    }

    // Returns all the tasks assigned by this user
    public function givenTasks()
    {
        return $this->hasMany(TasksModel::class, 'by_id');
    }

    public function clientTasks()
    {
        return $this->hasManyThrough(
            TasksModel::class,
            ProjectsModel::class,
            'client_id',
            'pr_id',
            'id',
            'id'
        );
    }

    public function clientProjects()
    {
        return $this->hasMany(ProjectsModel::class, 'client_id');
    }
}
