<?php

namespace App\Models\AI;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class AIContext extends Model
{
    protected $table = 'ai_contexts';

    protected $fillable = [
        'project_id',
        'context_data',
        'conversation_history',
        'last_updated_at',
    ];

    protected $casts = [
        'context_data'         => 'array',
        'conversation_history' => 'array',
        'last_updated_at'      => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}