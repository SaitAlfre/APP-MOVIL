<div class="mb-5 grid gap-4 lg:grid-cols-3">
    <x-ui.card padding="p-5" class="lg:col-span-1">
        <h2 class="text-sm font-semibold">Leche disponible del acopio</h2>
        <p class="mono mt-2 text-2xl font-bold text-eh-primary">{{ number_format(collect($lecheDisponible)->sum('litrosDisponibles'), 3) }} L</p>
        <p class="mt-2 text-xs text-eh-text-muted">Saldo no asignado de los últimos 14 días, según acopio y producción. Los borradores reservan leche; al finalizar se libera el sobrante.</p>
        @foreach ($lecheDisponible as $dia)
            <p class="mt-2 flex justify-between text-xs"><span>{{ $dia['fecha']->format('d/m/Y') }}</span><span>{{ number_format($dia['litrosDisponibles'], 3) }} L</span></p>
        @endforeach
    </x-ui.card>
    <x-ui.card padding="p-5" class="lg:col-span-2">
        <h2 class="mb-3 text-sm font-semibold">Materiales para tus recetas</h2>
        <x-ui.table :headers="['Material', 'Existencia', 'Unidad', 'Estado']">
            @forelse ($materiales as $material)
                <tr class="border-b border-eh-border">
                    <td class="p-3 text-sm font-medium">{{ $material->nombre }}</td>
                    <td class="p-3 mono">{{ number_format($material->existencia, 3) }}</td>
                    <td class="p-3 text-sm">{{ $material->unidad }}</td>
                    <td class="p-3"><x-ui.badge :variant="$material->existencia > 0 ? 'green' : 'yellow'" :label="$material->existencia > 0 ? 'Disponible' : 'Sin stock'" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-5 text-sm text-eh-text-muted">Crea materiales como sal, cuajo, azúcar o cultivos para agregarlos a tus recetas.</td></tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</div>
@if ($puedeGestionar)
    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card padding="p-5">
            <h2 class="mb-4 text-sm font-semibold">Crear material</h2>
            <form method="POST" action="{{ route('admin.inventario.materiales.store') }}" class="space-y-4" data-once>
                @csrf
                <x-ui.field name="nombre" label="Nombre del material" placeholder="Sal, cuajo, azúcar…" maxlength="100" required />
                <x-ui.select name="unidad" label="Unidad de inventario y receta" required :options="['kg' => 'Kilogramos (kg)', 'g' => 'Gramos (g)', 'L' => 'Litros (L)', 'mL' => 'Mililitros (mL)', 'unidad' => 'Unidades']" />
                <p class="text-xs text-eh-text-muted">El stock empieza en cero. Elige una unidad y úsala también en las entradas y recetas de este material. La leche ya está vinculada al acopio.</p>
                <x-ui.btn type="submit">Crear material</x-ui.btn>
            </form>
        </x-ui.card>
        <x-ui.card padding="p-5">
            <h2 class="mb-4 text-sm font-semibold">Registrar entrada o salida de material</h2>
            <form method="POST" action="{{ route('admin.inventario.materiales.movimientos.store') }}" class="space-y-4" data-once>
                @csrf
                <x-ui.select name="material_id" label="Material" placeholder="Selecciona un material" required :options="$materiales->mapWithKeys(fn ($m) => [$m->id => $m->nombre.' · '.$m->unidad])->all()" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select name="operacion" label="Operación" required :options="['entrada' => 'Entrada / reposición', 'salida' => 'Salida / merma']" />
                    <x-ui.field name="cantidad" label="Cantidad en la unidad del material" type="number" min="0.001" max="999999999" step="0.001" required />
                </div>
                <x-ui.field name="motivo" label="Motivo o referencia" placeholder="Compra, inventario inicial, merma…" maxlength="200" required />
                <x-ui.btn type="submit" :disabled="$materiales->isEmpty()">Guardar movimiento</x-ui.btn>
            </form>
        </x-ui.card>
    </div>
@endif
