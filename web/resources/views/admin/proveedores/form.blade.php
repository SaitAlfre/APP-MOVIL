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
        description="Datos del productor y su zona de acopio."
        :breadcrumbs="[['label' => 'Proveedores', 'url' => route('admin.proveedores.index')], ['label' => $esNuevo ? 'Nuevo proveedor' : $proveedor->nombres]]">
        @unless ($esNuevo)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.proveedores.show', $proveedor->id)" variant="secondary" size="sm" icon="eye">Ver ficha</x-ui.btn>
            </x-slot:actions>
        @endunless
    </x-ui.page-header>

    <div @class(['grid grid-cols-1 gap-6', 'max-w-3xl' => $esNuevo, 'max-w-6xl lg:grid-cols-3' => ! $esNuevo])>
        <x-ui.card padding="p-5 sm:p-6" :class="$esNuevo ? '' : 'lg:col-span-2'">
            <form method="POST"
                action="{{ $esNuevo ? route('admin.proveedores.store') : route('admin.proveedores.update', $proveedor->id) }}"
                class="space-y-6" data-once data-proveedor-form
                data-dni-url="{{ route('admin.proveedores.consultar-dni') }}"
                data-dni-disponible="{{ filled(config('services.decolecta.token')) ? 'true' : 'false' }}"
                data-duplicados-url="{{ route('admin.proveedores.duplicados') }}" data-proveedor-id="{{ $proveedor?->id }}">
                @csrf
                @unless ($esNuevo)
                    @method('PUT')
                @endunless

                <fieldset class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
                    <legend class="mb-4 text-sm font-semibold text-eh-text">Identificación del proveedor</legend>
                    <x-ui.field name="codigo" label="Código" required maxlength="50" :value="$proveedor?->codigo" placeholder="PRV-009" />
                    <x-ui.field name="dni" label="Documento del proveedor" required maxlength="20" inputmode="numeric" autocomplete="off" :value="$proveedor?->dni" placeholder="01234580" />
                    <x-ui.field name="nombres" label="Nombre del proveedor o finca" required maxlength="150" :value="$proveedor?->nombres"
                        class="sm:col-span-2" placeholder="Juan Pérez Quispe"
                        hint="Nombre con el que se identifica al proveedor. Si usas el nombre de una finca, la consulta de DNI no lo reemplazará automáticamente." />
                    <div class="space-y-2 sm:col-span-2">
                        <x-ui.btn type="button" variant="outline" size="sm" icon="search" data-consultar-dni :disabled="! filled(config('services.decolecta.token'))">Consultar DNI</x-ui.btn>
                        <p class="text-xs text-eh-text-muted">La consulta se realiza al completar un DNI de 8 dígitos. Otros documentos se registran manualmente.</p>
                        <div data-dni-resultado role="status" aria-live="polite" class="space-y-2 text-sm text-eh-text-muted">@if (! filled(config('services.decolecta.token')))La consulta de DNI no está disponible. Puedes completar los datos manualmente.@endif</div>
                    </div>
                    <div class="sm:col-span-2" data-proveedor-duplicados role="status" aria-live="polite"></div>
                </fieldset>

                <fieldset class="grid min-w-0 grid-cols-1 gap-4 border-t border-eh-border pt-5 sm:grid-cols-2">
                    <legend class="pr-3 text-sm font-semibold text-eh-text">Contacto y ubicación</legend>
                    <x-ui.field name="telefono" label="Celular" type="tel" autocomplete="tel" maxlength="30" :value="$proveedor?->telefono" placeholder="95XXXXXXX" />
                    <x-ui.field name="dueno" label="Propietario o responsable (opcional)" maxlength="150" :value="$proveedor?->dueno"
                        hint="Persona responsable de la finca; no cambia la cuenta de acceso vinculada." />
                    <x-ui.select name="zona_id" label="Zona" required placeholder="Selecciona una zona" :selected="$proveedor?->zonaId"
                        :options="collect($zonas)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
                    <x-ui.field name="direccion" label="Dirección o referencia" maxlength="255" :value="$proveedor?->direccion"
                        class="sm:col-span-2" placeholder="Sector Quispe, Huata" />
                </fieldset>

                <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                    <x-ui.btn :href="route('admin.proveedores.index')" variant="ghost">Cancelar</x-ui.btn>
                    <x-ui.btn type="submit" icon="guardar">{{ $esNuevo ? 'Guardar proveedor' : 'Guardar cambios' }}</x-ui.btn>
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
