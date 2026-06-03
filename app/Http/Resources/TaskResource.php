<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->uuid,
            'title'           => $this->title,
            'description'     => $this->description,
            'type'            => $this->type,
            'status'          => $this->status,
            'priority'        => $this->priority,
            'story_points'    => $this->story_points,
            'order'           => $this->order,
            'due_date'        => $this->due_date?->toDateString(),
            'estimated_hours' => $this->estimated_hours,
            'logged_hours'    => $this->logged_hours,
            'labels'          => $this->labels ?? [],
            'ai_generated'    => $this->ai_generated,
            'is_blocked'      => $this->isBlocked(),
            'assignee'        => $this->whenLoaded('assignee', fn() => [
                'id'         => $this->assignee->uuid,
                'name'       => $this->assignee->name,
                'avatar_url' => $this->assignee->avatar_url,
            ]),
            'epic'            => $this->whenLoaded('epic', fn() => [
                'id'    => $this->epic->uuid,
                'title' => $this->epic->title,
            ]),
            'sprint'          => $this->whenLoaded('sprint', fn() => [
                'id'   => $this->sprint->uuid,
                'name' => $this->sprint->name,
            ]),
            'subtasks_count'  => $this->subtasks_count ?? 0,
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}