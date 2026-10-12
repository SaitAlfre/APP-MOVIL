<?php

namespace App\Http\Requests\Admin;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarMapaRutaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('operador')->user()?->puede('mapa_rutas', 'gestionar') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ruta = $this->route('ruta');
        $zonaId = $this->integer('zona_id');

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'zona_id' => ['required', 'integer', Rule::exists('zonas', 'id')->where(fn (Builder $query) => $query->where('activo', true)->when($ruta, fn (Builder $zona) => $zona->orWhere('id', $ruta->zona_id)))],
            'activo' => ['required', 'boolean'],
            'mapa_version' => [$ruta ? 'required' : 'nullable', 'integer', 'min:0'],
            'acopiador_id' => ['nullable', 'integer', Rule::exists('usuarios', 'id')->where('activo', true)],
            'indicaciones' => ['nullable', 'string', 'max:2000'],
            'paradas' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'paradas.*' => ['required', 'array:nombre,proveedor_id,latitud,longitud,referencia'],
            'paradas.*.nombre' => ['required', 'string', 'max:120'],
            'paradas.*.proveedor_id' => ['nullable', 'integer', 'distinct', Rule::exists('proveedores', 'id')->where(fn (Builder $query) => $query->where('zona_id', $zonaId)->where('estado', 'activo'))],
            'paradas.*.latitud' => ['required', 'numeric', 'between:-85,85'],
            'paradas.*.longitud' => ['required', 'numeric', 'between:-180,180'],
            'paradas.*.referencia' => ['nullable', 'string', 'max:500'],
            'trazado' => ['present', 'array', 'list', 'max:2000'],
            'trazado.*' => ['required', 'array:latitud,longitud'],
            'trazado.*.latitud' => ['required', 'numeric', 'between:-85,85'],
            'trazado.*.longitud' => ['required', 'numeric', 'between:-180,180'],
            'resumen_recorrido' => ['nullable', 'array:fuente,distancia_m,duracion_s,firma_paradas'],
            'resumen_recorrido.fuente' => ['required_with:resumen_recorrido', Rule::in(['carretera', 'manual'])],
            'resumen_recorrido.distancia_m' => ['nullable', 'numeric', 'between:0,40000000'],
            'resumen_recorrido.duracion_s' => ['nullable', 'numeric', 'between:0,2678400'],
            'resumen_recorrido.firma_paradas' => ['required_with:resumen_recorrido', 'string', 'max:4000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['paradas', 'trazado', 'resumen_recorrido'] as $campo) {
            $json = $this->input($campo.'_json');
            $this->merge([$campo => is_string($json) ? json_decode($json, true) : null]);
        }
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $id = $this->input('acopiador_id');
            if ($id !== null && ! $validator->errors()->has('acopiador_id')) {
                $usuario = Usuario::find($id);
                if (! $usuario?->tieneRol('acopiador') || $usuario->estaBloqueada()) {
                    $validator->errors()->add('acopiador_id', 'Selecciona una cuenta activa de acopiador sin bloqueo.');
                }
            }
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $resumen = $this->input('resumen_recorrido');
            if ($resumen !== null) {
                $firma = collect($this->input('paradas'))->map(fn (array $parada) => sprintf('%.6F,%.6F', $parada['latitud'], $parada['longitud']))->implode(';');
                if ($firma !== $resumen['firma_paradas'] || count($this->input('trazado')) < 2) {
                    $validator->errors()->add('trazado', 'Las paradas cambiaron después de trazar el camino. Vuelve a calcular o dibujar el recorrido.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'paradas.required' => 'Agrega al menos una parada antes de guardar la ruta.',
            'paradas.min' => 'Agrega al menos una parada antes de guardar la ruta.',
            'paradas.array' => 'El formato de las paradas no es válido.',
            'trazado.array' => 'El formato del recorrido no es válido.',
            'paradas.*.proveedor_id.exists' => 'Cada proveedor debe estar activo y pertenecer al sector de esta ruta.',
            'paradas.*.proveedor_id.distinct' => 'Un proveedor no puede repetirse en la misma ruta.',
        ];
    }
}
