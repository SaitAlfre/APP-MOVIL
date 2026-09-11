<?php

namespace App\Http\Requests\Admin;

use App\Application\Proveedores\DatosProveedor;
use Illuminate\Foundation\Http\FormRequest;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50'],
            'nombres' => ['required', 'string', 'max:150'],
            'dni' => ['required', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
            'tachos' => ['required', 'integer', 'min:1'],
            'capacidad_tacho_l' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function aDatosProveedor(): DatosProveedor
    {
        return new DatosProveedor(
            codigo: $this->string('codigo')->toString(),
            nombres: $this->string('nombres')->toString(),
            dni: $this->string('dni')->toString(),
            telefono: $this->string('telefono')->toString() ?: null,
            direccion: $this->string('direccion')->toString() ?: null,
            zonaId: $this->integer('zona_id'),
            tachos: $this->integer('tachos'),
            capacidadTachoL: (float) $this->input('capacidad_tacho_l'),
        );
    }
}
