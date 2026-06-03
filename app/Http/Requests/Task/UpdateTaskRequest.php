<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['sometimes', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'type'            => ['sometimes', 'in:story,task,bug,subtask'],
            'status'          => ['sometimes', 'in:todo,in_progress,review,done'],
            'priority'        => ['sometimes', 'in:critical,high,medium,low'],
            'epic_id'         => ['nullable', 'exists:epics,id'],
            'sprint_id'       => ['nullable', 'exists:sprints,id'],
            'assignee_id'     => ['nullable', 'exists:users,id'],
            'story_points'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'due_date'        => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'logged_hours'    => ['nullable', 'numeric', 'min:0'],
            'labels'          => ['nullable', 'array'],
        ];
    }
}