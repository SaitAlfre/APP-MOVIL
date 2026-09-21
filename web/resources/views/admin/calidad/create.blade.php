@extends('layouts.admin')

@section('titulo', 'Nuevo control de calidad')

@section('contenido')
    <x-ui.page-header title="Registrar control de calidad"
        description="Evalúa una entrega pendiente y registra los valores medidos."
        :breadcrumbs="[['label' => 'Calidad', 'url' => route('admin.calidad.index')], ['label' => 'Nuevo control']]" />

    @if ($pendientes->isEmpty())
        <x-ui.card class="max-w-2xl">
            <x-ui.empty icon="check" title="No hay entregas pendientes de evaluar"
                description="Todas las entregas registradas ya tienen un control de calidad.">
                <x-slot:action>
                    <x-ui.btn :href="route('admin.calidad.index')" variant="secondary" size="sm">Ver controles</x-ui.btn>
                </x-slot:action>
            </x-ui.empty>
        </x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-ui.card padding="p-6" class="lg:col-span-2">
                <form method="POST" action="{{ route('admin.calidad.store') }}" class="space-y-6" data-once>
                    @csrf

                    <x-ui.select name="entrega_id" label="Entrega a evaluar" required
                        :options="$pendientes->mapWithKeys(fn ($fila) => [
                            $fila['entrega']->id => ($fila['proveedor']?->nombres ?? 'Proveedor #'.$fila['entrega']->proveedorId)
                                .' — '.number_format($fila['entrega']->litros, 1).' L · '.$fila['entrega']->registradoEn->format('d/m/Y H:i'),
                        ])->all()" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field name="temperatura_c" label="Temperatura" type="number" step="0.1" min="-5" max="60" unit="°C"
                            hint="Cadena de frío recomendada: ≤ 4 °C" placeholder="4.2" />
                        <x-ui.field name="acidez" label="Acidez" type="number" step="0.1" min="0" max="50" unit="°D"
                            hint="Rango normal: 14 – 18 °D" placeholder="16.0" />
                    </div>

                    <div>
                        <x-ui.select name="resultado" label="Resultado" required
                            :options="['aprobado' => 'Aprobado', 'observado' => 'Observado', 'rechazado' => 'Rechazado']" :selected="old('resultado', 'aprobado')" />
                        <p id="sugerencia-calidad" hidden class="mt-1.5 text-xs font-medium"></p>
                    </div>

                    <x-ui.field name="observaciones" label="Observaciones" maxlength="255" placeholder="Opcional…" />

                    <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                        <x-ui.btn :href="route('admin.calidad.index')" variant="ghost">Cancelar</x-ui.btn>
                        <x-ui.btn type="submit">Guardar evaluación</x-ui.btn>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card padding="p-4">
                <h2 class="mb-3 text-sm font-semibold text-eh-text">Rangos de referencia</h2>
                <div class="grid grid-cols-1 gap-3">
                    <div class="rounded-xl border border-eh-primary/20 bg-eh-primary-soft p-3">
                        <p class="text-xs text-eh-text-muted">Temperatura</p>
                        <p class="mono mt-0.5 text-sm font-bold text-eh-primary">≤ 4.0 °C</p>
                        <p class="mt-0.5 text-[10px] text-eh-text-muted">Observado hasta 8 °C · Rechazado por encima</p>
                    </div>
                    <div class="rounded-xl border border-eh-primary/20 bg-eh-primary-soft p-3">
                        <p class="text-xs text-eh-text-muted">Acidez</p>
                        <p class="mono mt-0.5 text-sm font-bold text-eh-primary">14.0 – 18.0 °D</p>
                        <p class="mt-0.5 text-[10px] text-eh-text-muted">Observado 12–20 °D · Rechazado fuera de ese rango</p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-eh-text-muted">
                    Un valor fuera de rango no se oculta ni se corrige: se señala y se conserva junto al resultado que registre el técnico.
                </p>
            </x-ui.card>
        </div>

        <script>
            (function () {
                const temperaturaInput = document.getElementById('temperatura_c');
                const acidezInput = document.getElementById('acidez');
                const sugerencia = document.getElementById('sugerencia-calidad');

                if (!temperaturaInput || !acidezInput || !sugerencia) {
                    return;
                }

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
                    sugerencia.className = 'mt-1.5 text-xs font-medium ' + colores[resultado];
                }

                temperaturaInput.addEventListener('input', actualizar);
                acidezInput.addEventListener('input', actualizar);
            })();
        </script>
    @endif
@endsection
