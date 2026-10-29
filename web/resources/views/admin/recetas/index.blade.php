@extends('layouts.admin')
@section('titulo', 'Recetas')
@section('contenido')
    <x-ui.page-header title="Recetas">
        <x-slot:actions><x-ui.btn :href="route('admin.recetas.create')" icon="plus">Nueva receta</x-ui.btn></x-slot:actions>
    </x-ui.page-header>
    <x-ui.card>
        @if ($recetas->isEmpty())
            <x-ui.empty icon="documentText" title="Aún no hay recetas">
                <x-slot:action><x-ui.btn :href="route('admin.recetas.create')" icon="plus">Nueva receta</x-ui.btn></x-slot:action>
            </x-ui.empty>
        @else
            <x-ui.table :headers="['Receta', 'Base', 'Leche', 'Productos vinculados', 'Estado', 'Acciones']" caption="Recetas de planta">
                @foreach ($recetas as $receta)
                    <tr class="border-b border-eh-border last:border-0">
                        <td class="px-4 py-3 font-medium">{{ $receta->nombre }}</td>
                        <td class="px-4 py-3 text-sm">{{ '1 '.$receta->unidad_base }}</td>
                        <td class="mono px-4 py-3 text-sm">{{ number_format((float) $receta->litros_por_unidad, 3) }} L / {{ $receta->unidad_base }}</td>
                        <td class="px-4 py-3 text-sm">{{ $receta->productos_count }}</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$receta->activo ? 'activa' : 'inactiva'" /></td>
                        <td class="px-4 py-3"><div class="flex justify-end gap-2">
                            <x-ui.btn variant="secondary" size="sm" icon="pencil" :href="route('admin.recetas.edit', $receta)">Editar receta</x-ui.btn>
                            @if(! $receta->activo || auth('operador')->user()->puede('recetas', 'eliminar'))<form method="POST" action="{{ route('admin.recetas.estado', $receta) }}" data-once>
                                @csrf @method('PATCH')
                                <input type="hidden" name="receta_activa" value="{{ $receta->activo ? 0 : 1 }}">
                                <x-ui.btn type="submit" variant="ghost" size="sm">{{ $receta->activo ? 'Desactivar receta' : 'Activar receta' }}</x-ui.btn>
                            </form>@endif
                        </div></td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="p-4">{{ $recetas->links() }}</div>
        @endif
    </x-ui.card>
@endsection
