@extends('layouts.admin')
@section('titulo', 'Compradores')
@section('contenido')
    @php
        $esProveedor = $tipo === 'proveedores';
        $pestanas = collect(['clientes' => 'Clientes', 'mayoristas' => 'Mayoristas', 'proveedores' => 'Proveedores'])->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'url' => route('admin.compradores.index', ['tipo' => $key])])->values()->all();
    @endphp
    <x-ui.page-header title="Compradores">
        <x-slot:actions>
            <x-ui.btn :href="route('admin.ventas.index')" variant="ghost">Ver ventas</x-ui.btn>
            @if (! $esProveedor)<x-ui.btn icon="plus" data-modal-open="nuevo-comprador">{{ $tipo === 'mayoristas' ? 'Nuevo mayorista' : 'Nuevo cliente' }}</x-ui.btn>@endif
        </x-slot:actions>
    </x-ui.page-header>
    <x-ui.tabs :tabs="$pestanas" :active="$tipo" />
    <form method="GET" action="{{ route('admin.compradores.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
        <input type="hidden" name="tipo" value="{{ $tipo }}">
        <div class="min-w-64 flex-1"><x-ui.field name="q" label="Buscar comprador" :value="$busqueda" placeholder="Nombre, código o documento" maxlength="120" /></div>
        <x-ui.select name="estado" label="Estado" :selected="$estadoFiltro" :options="['todos' => 'Todos', 'activos' => 'Activos', 'inactivos' => 'Inactivos']" />
        <x-ui.btn type="submit" variant="secondary" icon="search">Buscar</x-ui.btn>
    </form>
    <x-ui.card>
        @if ($compradores->isEmpty())
            <x-ui.empty icon="users" title="No hay compradores para mostrar" />
        @else
            <x-ui.table :headers="['Código', 'Comprador', 'Documento', 'Contacto', 'Estado', 'Acciones']" caption="Directorio de compradores">
                @foreach ($compradores as $comprador)
                    @php $activo = $esProveedor ? $comprador->estado->value === 'activo' : $comprador->activo; @endphp
                    <tr class="border-b border-eh-border last:border-0">
                        <td class="mono px-4 py-3 text-xs">{{ $comprador->codigo }}</td>
                        <td class="px-4 py-3"><p class="text-sm font-semibold">{{ $esProveedor ? $comprador->nombres : $comprador->nombre }}</p>@if (! $esProveedor)<p class="text-xs text-eh-text-muted">{{ $comprador->ciudad }}</p>@endif</td>
                        <td class="mono px-4 py-3 text-sm">{{ $esProveedor ? $comprador->dni : $comprador->documento }}</td>
                        <td class="px-4 py-3 text-sm">{{ ($esProveedor ? $comprador->telefono : $comprador->celular) ?: '—' }}</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$activo ? 'activo' : 'inactivo'" /></td>
                        <td class="px-4 py-3"><div class="flex justify-end gap-2">
                            @if ($esProveedor)
                                <x-ui.btn :href="route('admin.proveedores.show', $comprador)" variant="secondary" size="sm">Ver proveedor</x-ui.btn>
                            @else
                                <x-ui.btn variant="secondary" size="sm" icon="pencil" :data-modal-open="'comprador-'.$comprador->id">Editar</x-ui.btn>
                                @if(! $activo || auth('operador')->user()->puede('ventas', 'eliminar'))<form method="POST" action="{{ route('admin.compradores.estado', $comprador) }}" data-once>
                                    @csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $activo ? 0 : 1 }}">
                                    <x-ui.btn type="submit" variant="ghost" size="sm">{{ $activo ? 'Desactivar' : 'Activar' }}</x-ui.btn>
                                </form>@endif
                            @endif
                        </div></td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="p-4">{{ $compradores->links() }}</div>
        @endif
    </x-ui.card>
    @if (! $esProveedor)
        @foreach (collect([null])->concat($compradores->items()) as $comprador)
            <x-ui.modal :id="$comprador ? 'comprador-'.$comprador->id : 'nuevo-comprador'" :title="$comprador ? 'Editar comprador' : ($tipo === 'mayoristas' ? 'Nuevo mayorista' : 'Nuevo cliente')">
                <form method="POST" action="{{ $comprador ? route('admin.compradores.update', $comprador) : route('admin.compradores.store') }}" class="space-y-4" data-once>
                    @csrf
                    @if ($comprador) @method('PUT') @endif
                    <x-ui.select name="tipo" :id="'tipo-comprador-'.($comprador?->id ?? 'nuevo')" label="Tipo" required :selected="$comprador?->tipo ?? ($tipo === 'mayoristas' ? 'mayorista' : 'minorista')" :options="['minorista' => 'Cliente', 'mayorista' => 'Mayorista', 'restaurante' => 'Restaurante', 'institucional' => 'Institución']" />
                    <x-ui.field name="nombre" :id="'nombre-comprador-'.($comprador?->id ?? 'nuevo')" label="Nombre o razón social" required maxlength="150" :value="$comprador?->nombre" />
                    <x-ui.field name="documento" :id="'documento-comprador-'.($comprador?->id ?? 'nuevo')" label="DNI o RUC" required maxlength="20" :value="$comprador?->documento" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field name="celular" :id="'celular-comprador-'.($comprador?->id ?? 'nuevo')" label="Celular" maxlength="20" :value="$comprador?->celular" />
                        <x-ui.field name="ciudad" :id="'ciudad-comprador-'.($comprador?->id ?? 'nuevo')" label="Ciudad" maxlength="80" :value="$comprador?->ciudad" />
                    </div>
                    <div class="flex justify-end gap-2"><x-ui.btn variant="ghost" data-modal-close>Cancelar</x-ui.btn><x-ui.btn type="submit">Guardar comprador</x-ui.btn></div>
                </form>
            </x-ui.modal>
        @endforeach
    @endif
@endsection
