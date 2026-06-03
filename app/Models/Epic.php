<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Epic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'project_id',
        'title',
        'description',
        'priority',
        'status',
        'start_date',
        'end_date',
        'ai_generated',
    ];


    protected $attributes = [
        'status'       => 'open',
        'ai_generated' => false,
    ];
    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
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

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}