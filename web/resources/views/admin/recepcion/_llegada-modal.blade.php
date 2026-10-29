{{-- Formulario de llegada a planta. Recibe: $jornadaId, $viaje, $litrosRecolectados, $recepcion y opcionalmente $volver. El motivo pasa a ser obligatorio cuando hay diferencia. --}}
<x-ui.modal :id="'recepcion-'.$jornadaId" :title="$recepcion === null ? 'Registrar llegada a planta' : 'Corregir recepción'">
    <form method="POST" action="{{ route('admin.recepcion.llegada.store', $jornadaId) }}" class="space-y-4" data-once>
        @csrf
        @isset($volver)
            <input type="hidden" name="volver" value="{{ $volver }}">
        @endisset

        <div class="rounded-xl bg-eh-surface-alt p-4 text-sm">
            <div class="flex justify-between">
                <span class="text-eh-text-muted">Viaje</span>
                <span class="font-medium text-eh-text">{{ $viaje }}</span>
            </div>
            <div class="mt-2 flex justify-between">
                <span class="text-eh-text-muted">Litros recolectados en campo</span>
                <span class="mono font-bold text-eh-text">{{ number_format($litrosRecolectados, 1) }} L</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-1">
                <label for="llegada-{{ $jornadaId }}" class="text-sm font-medium text-eh-text">
                    Fecha y hora de llegada <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                </label>
                <input id="llegada-{{ $jornadaId }}" name="llegada_en" type="datetime-local" required
                    max="{{ now()->format('Y-m-d\TH:i') }}"
                    value="{{ old('llegada_en', $recepcion?->llegadaEn->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                    class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
            </div>
            <div class="flex flex-col gap-1">
                <label for="litros-{{ $jornadaId }}" class="text-sm font-medium text-eh-text">
                    Litros medidos en planta <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                </label>
                <div class="relative">
                    <input id="litros-{{ $jornadaId }}" name="litros_medidos" type="number" min="0" step="0.1" required inputmode="decimal"
                        value="{{ old('litros_medidos', number_format($recepcion?->litrosMedidos ?? $litrosRecolectados, 1, '.', '')) }}"
                        data-recepcion-medidos data-recolectado="{{ $litrosRecolectados }}" data-jornada="{{ $jornadaId }}"
                        class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 pr-8 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-eh-text-muted">L</span>
                </div>
            </div>
        </div>

        <p data-recepcion-diferencia="{{ $jornadaId }}" class="rounded-xl bg-eh-surface-alt px-3 py-2 text-sm text-eh-text-muted" aria-live="polite">
            Ingresa los litros medidos para ver la diferencia.
        </p>

        <div class="flex flex-col gap-1">
            <label for="motivo-{{ $jornadaId }}" class="text-sm font-medium text-eh-text">
                Motivo de la diferencia <span data-recepcion-motivo-obligatorio="{{ $jornadaId }}" hidden><span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span></span>
            </label>
            <textarea id="motivo-{{ $jornadaId }}" name="motivo_diferencia" maxlength="255" rows="2" data-recepcion-motivo="{{ $jornadaId }}"
                placeholder="Ejemplo: derrame al pasar la trocha"
                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">{{ old('motivo_diferencia', $recepcion?->motivoDiferencia) }}</textarea>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
            <x-ui.btn type="submit">Guardar recepción</x-ui.btn>
        </div>
    </form>
</x-ui.modal>
