<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AIArtifactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'title'      => $this->title,
            'content'    => $this->content,
            'metadata'   => $this->metadata,
            'created_by' => [
                'id'   => $this->creator?->uuid,
                'name' => $this->creator?->name,
            ],
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}