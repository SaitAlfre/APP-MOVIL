<div data-ingrediente class="grid grid-cols-1 items-end gap-3 rounded-xl border border-eh-border p-3 sm:grid-cols-3">
    <div class="space-y-1">
        <label for="material-{{ $indice }}" class="text-xs font-semibold">Material y unidad</label>
        <select id="material-{{ $indice }}" name="ingredientes[{{ $indice }}][material_id]" required class="w-full rounded-xl border border-eh-border bg-eh-surface p-2.5 text-sm">
            <option value="">Selecciona un material</option>
            @foreach ($materiales as $material)
                <option value="{{ $material->id }}" @selected(($ingrediente['material_id'] ?? null) == $material->id)>{{ $material->nombre }} · {{ $material->unidad }}</option>
            @endforeach
        </select>
    </div>
    <div class="space-y-1">
        <label for="cantidad-{{ $indice }}" class="text-xs font-semibold">Cantidad por unidad producida</label>
        <input id="cantidad-{{ $indice }}" name="ingredientes[{{ $indice }}][cantidad]" type="number" min="0.001" max="999999" step="0.001" required value="{{ $ingrediente['cantidad'] ?? '' }}" class="w-full rounded-xl border border-eh-border bg-eh-surface p-2.5 text-sm">
    </div>
    <x-ui.btn type="button" variant="ghost" data-quitar-ingrediente>Quitar ingrediente</x-ui.btn>
</div>
