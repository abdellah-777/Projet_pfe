<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->uuid,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'status'        => $this->status,
            'start_date'    => $this->start_date?->toDateString(),
            'end_date'      => $this->end_date?->toDateString(),
            'owner'         => [
                'id'   => $this->owner?->uuid,
                'name' => $this->owner?->name,
            ],
            'members_count' => $this->members_count ?? 0,
            'tasks_count'   => $this->tasks_count ?? 0,
            'ai_metadata'   => $this->ai_metadata,
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}