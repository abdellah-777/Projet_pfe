<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'organization_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'original_idea',
        'status',
        'start_date',
        'end_date',
        'ai_metadata',
    ];

    protected $casts = [
        'ai_metadata' => 'array',
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];

    // هاد السطر هو المهم
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            if (!$model->slug) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function epics()
    {
        return $this->hasMany(Epic::class);
    }

    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function aiContext()
    {
        return $this->hasOne(AI\AIContext::class);
    }

    public function aiArtifacts()
    {
        return $this->hasMany(AI\AIArtifact::class);
    }

    public function activeSprint()
    {
        return $this->hasOne(Sprint::class)
                    ->where('status', 'active');
    }
}