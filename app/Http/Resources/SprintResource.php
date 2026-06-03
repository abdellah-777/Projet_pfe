<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SprintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->uuid,
            'name'        => $this->name,
            'goal'        => $this->goal,
            'status'      => $this->status,
            'start_date'  => $this->start_date?->toDateString(),
            'end_date'    => $this->end_date?->toDateString(),
            'velocity'    => $this->velocity,
            'progress'    => $this->progress,
            'tasks_count' => $this->tasks_count ?? 0,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}