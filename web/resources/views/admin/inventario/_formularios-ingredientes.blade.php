@if ($puedeGestionar)
    <x-ui.modal id="nuevo-ingrediente" title="Nuevo ingrediente">
        <form method="POST" action="{{ route('admin.inventario.materiales.store') }}" class="space-y-4" data-once>
            @csrf
            <x-ui.field name="nombre" id="nombre-ingrediente" label="Nombre" placeholder="Sal, cuajo, azúcar…" maxlength="100" required />
            <x-ui.select name="unidad" id="unidad-ingrediente" label="Unidad" required :options="['kg' => 'Kilogramos (kg)', 'g' => 'Gramos (g)', 'L' => 'Litros (L)', 'mL' => 'Mililitros (mL)', 'unidad' => 'Unidades']" />
            <x-ui.field name="cantidad_inicial" label="Cantidad recibida (opcional)" type="number" min="0.001" max="999999999" step="0.001" />
            <div class="flex justify-end gap-2 pt-2"><x-ui.btn variant="ghost" data-modal-close>Cancelar</x-ui.btn><x-ui.btn type="submit" icon="plus">Crear ingrediente</x-ui.btn></div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="movimiento-ingrediente" title="Registrar compra de ingrediente">
        <form method="POST" action="{{ route('admin.inventario.materiales.movimientos.store') }}" class="space-y-4" data-once data-stock-editor>
            @csrf
            <label for="material-movimiento" class="block text-xs font-semibold">Ingrediente</label>
            <select id="material-movimiento" name="material_id" required class="min-h-11 w-full rounded-xl border border-eh-border-strong bg-eh-surface px-3 text-sm">
                @foreach ($catalogoMateriales as $material)<option value="{{ $material->id }}" data-balance="{{ $material->existencia }}" data-unit="{{ $material->unidad }}">{{ $material->nombre }} · {{ $material->unidad }}</option>@endforeach
            </select>
            <input type="hidden" name="operacion" value="entrada">
            <x-ui.field name="cantidad" id="cantidad-movimiento" label="Cantidad" type="number" min="0.001" max="999999999" step="0.001" required inputmode="decimal" />
            <div class="stock-preview grid grid-cols-2 gap-3 rounded-2xl bg-eh-surface-alt p-4" aria-live="polite">
                <div><p class="text-xs text-eh-text-muted">Disponible</p><p data-stock-current class="mono mt-1 text-xl font-semibold">—</p></div>
                <div><p class="text-xs text-eh-text-muted">Después de la compra</p><p data-stock-next class="mono mt-1 text-xl font-semibold text-eh-primary">—</p></div>
            </div>
            <x-ui.field name="motivo" id="motivo-movimiento" label="Referencia de compra" placeholder="Factura, boleta o detalle de compra" maxlength="200" required />
            <div class="flex justify-end gap-2 pt-2"><x-ui.btn variant="ghost" data-modal-close>Cancelar</x-ui.btn><x-ui.btn type="submit" :disabled="$catalogoMateriales->isEmpty()">Registrar compra</x-ui.btn></div>
        </form>
    </x-ui.modal>
@endif
