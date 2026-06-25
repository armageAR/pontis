<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WorkshopIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'zone_number' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:active,disabled'],
            'work_day' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:number,name,zone_number,work_day,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'my_workshops_only' => ['nullable', 'in:0,1,true,false'],
        ];
    }
}
