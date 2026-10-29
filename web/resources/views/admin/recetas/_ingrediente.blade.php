<div data-ingrediente class="grid grid-cols-[minmax(0,1fr)_140px_auto] items-end gap-2 border-b border-eh-border py-3 last:border-0">
    <div class="space-y-1">
        <label for="material-{{ $indice }}" class="text-xs font-medium">Insumo</label>
        <select id="material-{{ $indice }}" name="ingredientes[{{ $indice }}][material_id]" required class="min-h-11 w-full rounded-lg border border-eh-border-strong bg-eh-surface px-3 text-[13px]">
            <option value="">Selecciona un insumo</option>
            @foreach ($materiales as $material)
                <option value="{{ $material->id }}" data-name="{{ $material->nombre }}" data-unit="{{ $material->unidad }}" @selected(($ingrediente['material_id'] ?? null) == $material->id)>{{ $material->nombre }} · {{ $material->unidad }}</option>
            @endforeach
        </select>
    </div>
    <div class="space-y-1">
        <label for="cantidad-{{ $indice }}" class="text-xs font-medium">Por tanda</label>
        <input id="cantidad-{{ $indice }}" name="ingredientes[{{ $indice }}][cantidad]" type="number" min="0.001" max="999999" step="0.001" required inputmode="decimal"
            value="{{ isset($ingrediente['cantidad']) ? (float) $ingrediente['cantidad'] : '' }}" class="min-h-11 w-full rounded-lg border border-eh-border-strong bg-eh-surface px-3 text-[13px]">
    </div>
    <button type="button" data-quitar-ingrediente aria-label="Quitar insumo" title="Quitar insumo" class="grid min-h-11 w-11 place-items-center rounded-lg text-eh-text-muted hover:bg-eh-red-soft hover:text-eh-red">
        <x-icon name="trash" class="h-4 w-4" />
    </button>
</div>
