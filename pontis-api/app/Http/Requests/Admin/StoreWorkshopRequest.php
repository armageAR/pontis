<?php

namespace App\Http\Requests\Admin;

use App\Models\Workshop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreWorkshopRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorizar antes de validar: un usuario sin permiso recibe 403 y no
        // se le filtran errores de validación (por ejemplo, número ya usado).
        return Gate::allows('create', Workshop::class);
    }

    public function rules(): array
    {
        return [
            'zone_number' => ['nullable', 'integer', 'min:1'],
            'zone_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'integer', 'min:1', 'unique:workshops,number'],
            'work_day' => ['nullable', 'string', 'max:50'],
            'work_frequency' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,disabled'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim($this->name)]);
        }
    }
}
