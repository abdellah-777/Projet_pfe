<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'in:planning,active,on_hold,completed,archived'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after:start_date'],
        ];
    }
}