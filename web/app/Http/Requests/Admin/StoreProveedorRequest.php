<?php

namespace App\Http\Requests\Admin;

use App\Application\Proveedores\CredencialesCuentaMovil;
use App\Application\Proveedores\DatosProveedor;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Acciones del registro unificado que reutilizan una cuenta ya existente y no piden credenciales. */
    private const ACCIONES_SIN_CREDENCIALES = ['asociar_cuenta', 'vincular_existentes'];

    protected function prepareForValidation(): void
    {
        foreach (['dni', 'nombres'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => trim($this->input($campo))]);
            }
        }
        if (is_string($this->input('username'))) {
            $this->merge(['username' => strtolower(trim($this->input('username')))]);
        }
    }

    public function rules(): array
    {
        $dniAnterior = $this->route('proveedor') ? Proveedor::query()->find($this->route('proveedor'))?->dni : null;
        $documentoSinCambios = $dniAnterior !== null && $this->input('dni') === $dniAnterior;

        return [
            'codigo' => ['nullable', 'string', 'max:50'],
            'nombres' => ['required', 'string', 'max:150'],
            'dueno' => ['nullable', 'string', 'max:150'],
            'dni' => $documentoSinCambios ? ['required', 'string', 'max:20'] : ['required', 'string', 'regex:/^[0-9]{8}$/'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
            'tachos' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'capacidad_tacho_l' => ['sometimes', 'numeric', 'min:0.01'],
            ...($this->route('proveedor') ? [] : $this->reglasRegistro()),
        ];
    }

    /** @return array<string, list<mixed>> */
    private function reglasRegistro(): array
    {
        $reglas = [
            'crear_cuenta' => ['nullable', 'boolean'],
            'accion_registro' => ['nullable', 'string', Rule::in(['vincular_ficha', 'asociar_cuenta', 'vincular_existentes'])],
            'origen' => ['nullable', 'string', Rule::in(['proveedores', 'usuarios'])],
        ];
        if ($this->exigeCredenciales()) {
            $reglas += [
                'username' => ['required', 'string', 'regex:/^[a-z0-9._]{3,30}$/', Rule::unique('usuarios', 'username')],
                'pin' => ['required', 'string', 'regex:/\A[0-9]{4}\z/'],
                'pin_confirmation' => ['required', 'same:pin'],
            ];
        }

        return $reglas;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.unique' => 'Este nombre de usuario ya está ocupado. Elige otro.',
            'username.regex' => 'Usa entre 3 y 30 caracteres: minúsculas, números, punto o guion bajo.',
            'pin.regex' => 'El PIN debe tener exactamente 4 dígitos numéricos.',
            'pin_confirmation.same' => 'Los PIN no coinciden. Escríbelos de nuevo.',
            'pin_confirmation.required' => 'Repite el PIN para confirmar.',
        ];
    }

    /**
     * Desde Usuarios y accesos el rol Proveedor ya implica crear su acceso; desde Proveedores el acceso
     * es opcional («Habilitar acceso móvil»). Reutilizar una cuenta existente nunca pide credenciales.
     */
    private function exigeCredenciales(): bool
    {
        $solicitaAcceso = $this->origen() === 'usuarios' || $this->boolean('crear_cuenta');

        return $solicitaAcceso && ! in_array($this->input('accion_registro'), self::ACCIONES_SIN_CREDENCIALES, true);
    }

    public function aCredenciales(): ?CredencialesCuentaMovil
    {
        if (! $this->exigeCredenciales()) {
            return null;
        }

        return new CredencialesCuentaMovil($this->string('username')->toString(), $this->string('pin')->toString());
    }

    public function accionRegistro(): ?string
    {
        return $this->filled('accion_registro') ? $this->string('accion_registro')->toString() : null;
    }

    public function origen(): string
    {
        return $this->input('origen') === 'usuarios' ? 'usuarios' : 'proveedores';
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'PRV-'.Str::upper(Str::random(8));
        } while (Proveedor::query()->where('codigo', $codigo)->exists());

        return $codigo;
    }

    public function aDatosProveedor(): DatosProveedor
    {
        $anterior = Proveedor::query()->find($this->route('proveedor'));

        $codigo = $this->string('codigo')->toString();
        if ($codigo === '') {
            $codigo = $anterior?->codigo ?? $this->generarCodigo();
        }

        return new DatosProveedor(
            codigo: $codigo,
            nombres: $this->string('nombres')->toString(),
            dni: $this->string('dni')->toString(),
            telefono: $this->string('telefono')->toString() ?: null,
            direccion: $this->exists('direccion') ? ($this->string('direccion')->toString() ?: null) : $anterior?->direccion,
            zonaId: $this->integer('zona_id'),
            tachos: $this->integer('tachos', $anterior?->tachos ?? 1),
            capacidadTachoL: (float) $this->input('capacidad_tacho_l', $anterior?->capacidad_tacho_l ?? 40),
            dueno: $this->string('dueno')->toString() ?: null,
            responsableIncluido: $this->exists('dueno'),
        );
    }
}
