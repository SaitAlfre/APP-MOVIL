@extends('layouts.admin')

@section('titulo', 'Vista previa de importación')

@section('contenido')
    @php
        $errores = $importacion->errores ?? [];
        $vistaPrevia = $importacion->vista_previa ?? [];
        $columnas = array_keys($vistaPrevia[0] ?? []);
        $validas = max(0, $importacion->filas_total - $importacion->filas_error);
        $puedeGestionar = auth('operador')->user()->puede('importaciones', 'gestionar');
    @endphp

    <x-ui.page-header title="Vista previa de la importación"
        :description="$importacion->archivo_original.' · '.$importacion->filas_total.' filas · '.ucfirst($importacion->tipo)"
        :breadcrumbs="[['label' => 'Importaciones', 'url' => route('admin.importaciones.index')], ['label' => 'Vista previa']]" />

    {{-- Asistente de dos pasos --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-eh-primary bg-eh-primary-soft text-eh-primary">
                <x-icon name="check" class="h-4 w-4" />
            </span>
            <span class="text-sm font-medium text-eh-text-muted">Cargar archivo</span>
        </div>
        <span class="h-px w-16 bg-eh-primary"></span>
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-eh-primary bg-eh-primary text-sm font-bold text-white">2</span>
            <span class="text-sm font-medium text-eh-text">Vista previa y confirmación</span>
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi label="Filas válidas" :value="$validas" icon="check" color="green" />
        <x-ui.kpi label="Filas con error" :value="$importacion->filas_error" icon="exclamation" :color="$importacion->filas_error > 0 ? 'red' : 'green'" />
        <x-ui.kpi label="Total en el archivo" :value="$importacion->filas_total" icon="documentText" color="blue" />
    </div>

    @if (! empty($errores))
        <x-ui.alert type="error" title="Corrige el archivo antes de continuar" class="mb-4">
            <p class="mt-1">Ninguna fila se importará mientras el archivo tenga errores.</p>
            <ul class="mt-2 list-disc space-y-0.5 pl-5">
                @foreach ($errores as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @else
        <x-ui.alert type="success" class="mb-4">
            El archivo pasó la validación. Revisa la vista previa y confirma para incorporar los datos.
        </x-ui.alert>
    @endif

    <x-ui.card>
        <div class="border-b border-eh-border px-4 py-3">
            <h2 class="text-sm font-semibold text-eh-text">Vista previa de registros</h2>
            <p class="mt-0.5 text-xs text-eh-text-muted">Se muestran hasta 100 filas del archivo cargado.</p>
        </div>

        @if (empty($vistaPrevia))
            <x-ui.empty icon="documentText" title="El archivo no contiene filas de datos"
                description="Descarga la plantilla del tipo elegido y vuelve a intentarlo." />
        @else
            <x-ui.table :headers="array_map(fn ($columna) => ucfirst(str_replace('_', ' ', $columna)), $columnas)" caption="Filas del archivo importado">
                @foreach ($vistaPrevia as $indice => $fila)
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        @foreach ($columnas as $columna)
                            <td class="px-4 py-3 text-sm text-eh-text">
                                @if (trim((string) ($fila[$columna] ?? '')) === '')
                                    <span class="italic text-eh-red">vacío</span>
                                @else
                                    {{ $fila[$columna] }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>

    <div class="mt-5 flex flex-wrap justify-end gap-2">
        <x-ui.btn :href="route('admin.importaciones.index')" variant="ghost">Cancelar</x-ui.btn>
        @if ($puedeGestionar && $importacion->estado === 'validada')
            <form method="POST" action="{{ route('admin.importaciones.confirmar', $importacion) }}" data-once
                data-confirm="¿Confirmar la importación de {{ $importacion->filas_total }} filas de tipo {{ $importacion->tipo }}? Los registros existentes con el mismo código se actualizarán.">
                @csrf
                <x-ui.btn type="submit">Confirmar importación</x-ui.btn>
            </form>
        @endif
    </div>
@endsection
