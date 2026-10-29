<?php

namespace App\Http\Requests\Admin;

class UpdateProveedorRequest extends StoreProveedorRequest
{
    public function rules(): array
    {
        return [...parent::rules(),
            'estado' => ['sometimes', 'required', 'in:activo,suspendido,retirado'],
            'motivo_estado' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
