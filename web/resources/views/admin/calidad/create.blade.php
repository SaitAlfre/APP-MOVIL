@extends('layouts.admin')

@section('titulo', 'Nuevo control de calidad')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-blue focus:ring-eh-blue';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <h1 class="mb-6 text-[22px] font-bold text-eh-text">Nuevo control de calidad</h1>

    @if ($errors->any())
        <div class="mb-4 max-w-xl rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($pendientes->isEmpty())
        <div class="max-w-xl rounded-2xl border border-dashed border-eh-border bg-eh-surface p-8 text-center">
            <p class="text-[13.5px] font-semibold text-eh-text">No hay entregas pendientes de evaluar</p>
            <p class="mt-1 text-[12.5px] text-eh-text-muted">Todas las entregas registradas ya tienen un control de calidad.</p>
        </div>
    @else
        <form method="POST" action="{{ route('admin.calidad.store') }}" class="max-w-xl space-y-4 rounded-2xl border border-eh-border bg-eh-surface p-6 shadow-sm">
            @csrf

            <div>
                <label for="entrega_id" class="{{ $labelClass }}">Entrega</label>
                <select id="entrega_id" name="entrega_id" required class="{{ $inputClass }}">
                    @foreach ($pendientes as $fila)
                        <option value="{{ $fila['entrega']->id }}" @selected(old('entrega_id') == $fila['entrega']->id)>
                            {{ $fila['proveedor']?->nombres ?? 'Proveedor #'.$fila['entrega']->proveedorId }}
                            — {{ number_format($fila['entrega']->litros, 1) }} L · {{ $fila['entrega']->registradoEn->format('d/m/Y H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="resultado" class="{{ $labelClass }}">Resultado</label>
                <select id="resultado" name="resultado" required class="{{ $inputClass }}">
                    <option value="aprobado" @selected(old('resultado') == 'aprobado')>Aprobado</option>
                    <option value="observado" @selected(old('resultado') == 'observado')>Observado</option>
                    <option value="rechazado" @selected(old('resultado') == 'rechazado')>Rechazado</option>
                </select>
                <p id="sugerencia-calidad" hidden class="mt-1.5 text-[12px] font-medium"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="temperatura_c" class="{{ $labelClass }}">Temperatura (°C)</label>
                    <input id="temperatura_c" name="temperatura_c" type="number" step="0.1" min="-5" max="60" value="{{ old('temperatura_c') }}" class="{{ $inputClass }}">
                    <p class="mt-1 text-[11.5px] text-eh-text-muted">Cadena de frío recomendada: ≤ 4°C</p>
                </div>
                <div>
                    <label for="acidez" class="{{ $labelClass }}">Acidez (°D)</label>
                    <input id="acidez" name="acidez" type="number" step="0.1" min="0" max="50" value="{{ old('acidez') }}" class="{{ $inputClass }}">
                    <p class="mt-1 text-[11.5px] text-eh-text-muted">Rango normal: 14–18°D</p>
                </div>
            </div>

            <div>
                <label for="observaciones" class="{{ $labelClass }}">Observaciones</label>
                <input id="observaciones" name="observaciones" type="text" value="{{ old('observaciones') }}" class="{{ $inputClass }}">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="flex h-11 items-center rounded-xl bg-eh-blue px-5 text-[13.5px] font-semibold text-white hover:opacity-90">
                    Guardar evaluación
                </button>
                <a href="{{ route('admin.calidad.index') }}" class="text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Cancelar</a>
            </div>
        </form>

        <script>
            (function () {
                const temperaturaInput = document.getElementById('temperatura_c');
                const acidezInput = document.getElementById('acidez');
                const sugerencia = document.getElementById('sugerencia-calidad');

                const colores = {
                    aprobado: 'text-eh-primary',
                    observado: 'text-eh-gold',
                    rechazado: 'text-eh-red',
                };
                const etiquetas = { aprobado: 'Aprobado', observado: 'Observado', rechazado: 'Rechazado' };

                function sugerirResultado(temperatura, acidez) {
                    if (temperatura === null && acidez === null) return null;
                    if ((temperatura !== null && temperatura > 8) || (acidez !== null && (acidez < 12 || acidez > 20))) return 'rechazado';
                    if ((temperatura !== null && temperatura > 4) || (acidez !== null && (acidez < 14 || acidez > 18))) return 'observado';
                    return 'aprobado';
                }

                function actualizar() {
                    const temperatura = temperaturaInput.value !== '' ? parseFloat(temperaturaInput.value) : null;
                    const acidez = acidezInput.value !== '' ? parseFloat(acidezInput.value) : null;
                    const resultado = sugerirResultado(temperatura, acidez);

                    if (resultado === null) {
                        sugerencia.hidden = true;
                        return;
                    }

                    sugerencia.hidden = false;
                    sugerencia.textContent = 'Sugerencia según los valores ingresados: ' + etiquetas[resultado] + ' (no vinculante)';
                    sugerencia.className = 'mt-1.5 text-[12px] font-medium ' + colores[resultado];
                }

                temperaturaInput.addEventListener('input', actualizar);
                acidezInput.addEventListener('input', actualizar);
            })();
        </script>
    @endif
@endsection
