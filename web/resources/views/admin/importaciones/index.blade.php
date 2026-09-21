@extends('layouts.admin')

@section('titulo', 'Importaciones')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('importaciones', 'gestionar');
        $tipos = ['proveedores' => 'Proveedores', 'rutas' => 'Rutas', 'precios' => 'Precios por litro', 'semanas' => 'Semanas operativas'];
        $variantesEstado = ['validada' => 'green', 'procesada' => 'green', 'con_errores' => 'red'];
    @endphp

    <x-ui.page-header title="Importaciones" description="Carga masiva de datos desde archivos Excel o CSV, con validación previa" />

    {{-- Asistente de dos pasos --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-eh-primary bg-eh-primary text-sm font-bold text-white">1</span>
            <span class="text-sm font-medium text-eh-text">Cargar archivo</span>
        </div>
        <span class="h-px w-16 bg-eh-border"></span>
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-eh-border bg-eh-surface text-sm font-bold text-eh-text-muted">2</span>
            <span class="text-sm font-medium text-eh-text-muted">Vista previa y confirmación</span>
        </div>
    </div>

    @if ($puedeGestionar)
        <form method="POST" action="{{ route('admin.importaciones.validar') }}" enctype="multipart/form-data" class="mb-6 max-w-2xl space-y-4" data-once>
            @csrf

            <x-ui.card padding="p-6">
                <h2 class="mb-4 text-sm font-semibold text-eh-text">Tipo de importación</h2>
                <x-ui.select name="tipo" required placeholder="Seleccionar tipo…" :options="$tipos" aria-label="Tipo de importación" />
            </x-ui.card>

            <x-ui.card padding="p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-eh-text">Archivo</h2>
                    <div class="flex flex-wrap gap-1">
                        <span class="self-center text-xs text-eh-text-muted">Plantillas:</span>
                        @foreach ($tipos as $clave => $etiqueta)
                            <a href="{{ route('admin.importaciones.plantilla', $clave) }}"
                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-eh-primary hover:bg-eh-primary-soft">
                                <x-icon name="download" class="h-3.5 w-3.5" /> {{ $etiqueta }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <label for="archivo" data-dropzone
                    class="flex cursor-pointer flex-col items-center rounded-2xl border-2 border-dashed border-eh-border p-10 text-center transition-colors hover:border-eh-primary hover:bg-eh-surface-alt">
                    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-eh-surface-alt text-eh-text-muted">
                        <x-icon name="arrowUpTray" class="h-6 w-6" />
                    </span>
                    <span class="font-medium text-eh-text" data-dropzone-titulo>Arrastra tu archivo aquí</span>
                    <span class="mt-1 text-sm text-eh-text-muted" data-dropzone-sub>o haz clic para seleccionar</span>
                    <span class="mt-2 text-xs text-eh-text-muted/70">Formatos: .xlsx, .csv · Máx. 5 MB</span>
                    <input id="archivo" type="file" name="archivo" accept=".csv,.xlsx" required class="sr-only">
                </label>
                @error('archivo')
                    <p class="mt-2 text-xs text-eh-red">{{ $message }}</p>
                @enderror
            </x-ui.card>

            <x-ui.btn type="submit" class="w-full">Validar archivo</x-ui.btn>
        </form>
    @endif

    <h2 class="mb-3 text-sm font-semibold text-eh-text">Historial de importaciones</h2>
    <x-ui.card>
        @if ($importaciones->isEmpty())
            <x-ui.empty icon="arrowUpTray" title="Aún no hay importaciones"
                description="Sube un archivo para validarlo antes de incorporar los datos al sistema." />
        @else
            <x-ui.table :headers="['Fecha', 'Archivo', 'Tipo', 'Filas', 'Estado', '']" caption="Importaciones realizadas">
                @foreach ($importaciones as $importacion)
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $importacion->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $importacion->archivo_original }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $tipos[$importacion->tipo] ?? ucfirst($importacion->tipo) }}</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text">
                            {{ $importacion->filas_procesadas }}/{{ $importacion->filas_total }}
                            @if ($importacion->filas_error > 0)
                                <span class="block text-[11px] text-eh-red">{{ $importacion->filas_error }} con error</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$variantesEstado[$importacion->estado] ?? 'gray'"
                                :label="ucfirst(str_replace('_', ' ', $importacion->estado))" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.btn :href="route('admin.importaciones.preview', $importacion)" size="sm" variant="ghost">Ver detalle</x-ui.btn>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>

    @if ($puedeGestionar)
        <script>
            (function () {
                const zona = document.querySelector('[data-dropzone]');
                const input = document.getElementById('archivo');

                if (!zona || !input) {
                    return;
                }

                const titulo = zona.querySelector('[data-dropzone-titulo]');
                const sub = zona.querySelector('[data-dropzone-sub]');
                const activas = ['border-eh-primary', 'bg-eh-primary-soft'];

                const mostrarArchivo = () => {
                    if (input.files.length === 0) {
                        return;
                    }
                    titulo.textContent = input.files[0].name;
                    sub.textContent = 'Archivo listo para validar';
                    zona.classList.add(...activas);
                };

                input.addEventListener('change', mostrarArchivo);

                ['dragenter', 'dragover'].forEach((evento) => {
                    zona.addEventListener(evento, (e) => {
                        e.preventDefault();
                        zona.classList.add(...activas);
                    });
                });

                zona.addEventListener('dragleave', () => {
                    if (input.files.length === 0) {
                        zona.classList.remove(...activas);
                    }
                });

                zona.addEventListener('drop', (e) => {
                    e.preventDefault();
                    if (e.dataTransfer?.files?.length) {
                        input.files = e.dataTransfer.files;
                        mostrarArchivo();
                    }
                });
            })();
        </script>
    @endif
@endsection
