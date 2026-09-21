@extends('layouts.admin')

@section('titulo', $proveedor ? 'Editar proveedor' : 'Nuevo proveedor')

@section('contenido')
    @php
        $esNuevo = $proveedor === null;
        $usuarioVinculado = $proveedor?->usuarioId
            ? collect($usuariosProveedor)->firstWhere('id', $proveedor->usuarioId)
            : null;
    @endphp

    <x-ui.page-header :title="$esNuevo ? 'Nuevo proveedor' : 'Editar proveedor'"
        description="Datos del productor, su zona de acopio y la capacidad de sus tachos."
        :breadcrumbs="[['label' => 'Proveedores', 'url' => route('admin.proveedores.index')], ['label' => $esNuevo ? 'Nuevo proveedor' : $proveedor->nombres]]">
        @unless ($esNuevo)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.proveedores.show', $proveedor->id)" variant="secondary" size="sm" icon="eye">Ver ficha</x-ui.btn>
            </x-slot:actions>
        @endunless
    </x-ui.page-header>

    <div class="grid max-w-5xl grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card padding="p-6" class="lg:col-span-2">
            <form method="POST"
                action="{{ $esNuevo ? route('admin.proveedores.store') : route('admin.proveedores.update', $proveedor->id) }}"
                class="space-y-6" data-once>
                @csrf
                @unless ($esNuevo)
                    @method('PUT')
                @endunless

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.field name="codigo" label="Código" required maxlength="30" :value="$proveedor?->codigo" placeholder="PRV-009" />
                    <x-ui.field name="dni" label="DNI" required maxlength="20" :value="$proveedor?->dni" placeholder="01234580" />
                    <x-ui.field name="nombres" label="Nombre del proveedor o finca" required maxlength="150" :value="$proveedor?->nombres"
                        class="sm:col-span-2" placeholder="Juan Pérez Quispe" />
                    <x-ui.field name="telefono" label="Celular" maxlength="30" :value="$proveedor?->telefono" placeholder="95XXXXXXX" />
                    <x-ui.select name="zona_id" label="Zona" required placeholder="Selecciona una zona" :selected="$proveedor?->zonaId"
                        :options="collect($zonas)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
                    <x-ui.field name="direccion" label="Dirección o referencia" maxlength="255" :value="$proveedor?->direccion"
                        class="sm:col-span-2" placeholder="Sector Quispe, Huata" />
                    <x-ui.field name="tachos" label="Cantidad de tachos" type="number" min="1" required :value="$proveedor?->tachos ?? 1" />
                    <x-ui.field name="capacidad_tacho_l" label="Capacidad por tacho" type="number" step="0.01" min="0.01" required unit="L"
                        :value="$proveedor?->capacidadTachoL ?? 40" hint="Se usa para validar los litros de cada entrega." />
                </div>

                <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                    <x-ui.btn :href="route('admin.proveedores.index')" variant="ghost">Cancelar</x-ui.btn>
                    <x-ui.btn type="submit">{{ $esNuevo ? 'Guardar proveedor' : 'Guardar cambios' }}</x-ui.btn>
                </div>
            </form>
        </x-ui.card>

        @unless ($esNuevo)
            <div class="space-y-4">
                <x-ui.card padding="p-4">
                    <h2 class="mb-3 text-sm font-semibold text-eh-text">Estado del proveedor</h2>
                    <p class="mb-3"><x-ui.estado :estado="$proveedor->estado->value" /></p>
                    <form method="POST" action="{{ route('admin.proveedores.estado', $proveedor->id) }}" class="space-y-3" data-once>
                        @csrf
                        @method('PATCH')
                        <x-ui.select name="estado" label="Cambiar a" :selected="$proveedor->estado->value"
                            :options="collect(\App\Domain\Proveedores\EstadoProveedor::cases())->mapWithKeys(fn ($estado) => [$estado->value => $estado->etiqueta()])->all()" />
                        <x-ui.btn type="submit" variant="secondary" class="w-full">Actualizar estado</x-ui.btn>
                    </form>
                    <p class="mt-3 text-xs text-eh-text-muted">
                        Un proveedor suspendido o retirado deja de aparecer al registrar entregas nuevas.
                    </p>
                </x-ui.card>

                <x-ui.card padding="p-4">
                    <h2 class="mb-3 text-sm font-semibold text-eh-text">Cuenta vinculada</h2>
                    @if ($proveedor->usuarioId)
                        <div class="mb-3 flex items-center gap-3 rounded-xl bg-eh-surface-alt p-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-eh-primary-soft text-eh-primary">
                                <x-icon name="userCircle" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="mono truncate text-sm font-medium text-eh-text">{{ $usuarioVinculado->username ?? 'Usuario #'.$proveedor->usuarioId }}</p>
                                <p class="truncate text-xs text-eh-text-muted">{{ $usuarioVinculado->nombres ?? 'Acceso propio del proveedor' }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.proveedores.desvincular', $proveedor->id) }}"
                            data-confirm="¿Desvincular la cuenta de {{ $proveedor->nombres }}? Perderá el acceso a la app móvil.">
                            @csrf
                            <x-ui.btn type="submit" variant="ghost" class="w-full">Desvincular cuenta</x-ui.btn>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.proveedores.vincular', $proveedor->id) }}" class="space-y-3" data-once>
                            @csrf
                            <x-ui.select name="usuario_id" label="Cuenta con rol proveedor" required placeholder="Selecciona una cuenta"
                                :options="collect($usuariosProveedor)->mapWithKeys(fn ($usuario) => [$usuario->id => $usuario->username.' · '.$usuario->nombres])->all()" />
                            <x-ui.btn type="submit" variant="secondary" class="w-full">Vincular cuenta</x-ui.btn>
                        </form>
                        <p class="mt-3 text-xs text-eh-text-muted">
                            Vincular una cuenta permite al proveedor consultar sus entregas y pagos desde la app móvil.
                        </p>
                    @endif
                </x-ui.card>

                <x-ui.card padding="p-4">
                    <h2 class="mb-3 text-sm font-semibold text-eh-text">Código QR</h2>
                    <p class="mb-3 text-xs text-eh-text-muted">El acopiador lo escanea para identificar al proveedor en campo.</p>
                    <x-ui.btn :href="route('admin.proveedores.qr', $proveedor->id)" target="_blank" rel="noopener" variant="outline" icon="qrCode" class="w-full">
                        Ver código QR
                    </x-ui.btn>
                </x-ui.card>
            </div>
        @endunless
    </div>
@endsection
