<?php

namespace App\Http\Requests\Epic;

use Illuminate\Foundation\Http\FormRequest;

class StoreEpicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority'    => ['sometimes', 'in:critical,high,medium,low'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after:start_date'],
        ];
    }
}