<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Task extends Model
{
    use SoftDeletes;
    protected $attributes = [
    'status'       => 'todo',
    'order'        => 0,
    'logged_hours' => 0,
    'ai_generated' => false,
];

    protected $fillable = [
        'uuid',
        'project_id',
        'epic_id',
        'sprint_id',
        'assignee_id',
        'reporter_id',
        'parent_task_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'story_points',
        'order',
        'due_date',
        'estimated_hours',
        'logged_hours',
        'labels',
        'ai_generated',
        'ai_metadata',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'labels'       => 'array',
        'ai_metadata'  => 'array',
        'ai_generated' => 'boolean',
    ];
    public function getRouteKeyName(): string
{
    return 'uuid';
}

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid());
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function epic()
    {
        return $this->belongsTo(Epic::class);
    }

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function dependencies()
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        );
    }

    public function isBlocked(): bool
    {
        return $this->dependencies()
                    ->where('status', '!=', 'done')
                    ->exists();
    }
}