<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sprint extends Model
{
    use SoftDeletes;
    protected $attributes = [
    'status' => 'planning',
];

    protected $fillable = [
        'uuid',
        'project_id',
        'name',
        'goal',
        'status',
        'start_date',
        'end_date',
        'velocity',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
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

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function completedTasks()
    {
        return $this->hasMany(Task::class)
                    ->where('status', 'done');
    }

    public function getProgressAttribute(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;
        $done = $this->completedTasks()->count();
        return (int) round(($done / $total) * 100);
    }
}