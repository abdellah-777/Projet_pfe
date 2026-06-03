<?php

namespace App\Models\AI;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AIArtifact extends Model
{
    protected $table = 'ai_artifacts';

    protected $fillable = [
        'project_id',
        'created_by',
        'type',
        'title',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}