<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->uuid,
            'name'         => $this->name,
            'email'        => $this->email,
            'job_title'    => $this->job_title,
            'avatar_url'   => $this->avatar_url,
            'organization' => [
                'id'   => $this->organization?->uuid,
                'name' => $this->organization?->name,
                'slug' => $this->organization?->slug,
                'plan' => $this->organization?->plan,
            ],
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}