<?php

namespace App\Http\Requests\Acopiador;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer'],
            'litros' => ['required', 'numeric', 'gt:0'],
            'tachos' => ['required', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string', 'max:255'],
            'forzar' => ['sometimes', 'boolean'],
        ];
    }
}
