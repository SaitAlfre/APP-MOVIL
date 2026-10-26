@extends('layouts.admin')

@section('titulo', $usuario === null ? 'Nuevo usuario' : 'Editar usuario')

@section('contenido')
    @php
        $esNuevo = $usuario === null;
        $rolesMarcados = collect(old('roles', $usuario ? array_map(fn ($rol) => $rol->value, $usuario->roles) : []));
    @endphp

    <x-ui.page-header :title="$esNuevo ? 'Nuevo usuario' : 'Editar usuario'"
        :description="$esNuevo ? 'El PIN inicial se puede cambiar después con «Restablecer PIN» desde el listado.' : 'Para cambiar el PIN, usa «Restablecer PIN» en el listado de usuarios.'"
        :breadcrumbs="[['label' => 'Usuarios y roles', 'url' => route('admin.usuarios.index')], ['label' => $esNuevo ? 'Nuevo usuario' : $usuario->nombres]]" />

    <x-ui.card padding="p-6" class="max-w-3xl">
        <form method="POST" action="{{ $esNuevo ? route('admin.usuarios.store') : route('admin.usuarios.update', $usuario->id) }}" class="space-y-6" data-once>
            @csrf
            @unless ($esNuevo)
                @method('PUT')
            @endunless

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if ($esNuevo)
                    <x-ui.field name="username" label="Usuario de acceso" required minlength="3" maxlength="30" pattern="[a-z0-9._]{3,30}" hint="De 3 a 30 caracteres: minúsculas, números, punto o guion bajo." placeholder="ej. calidad_faon" autocomplete="off" />
                    <x-ui.field name="pin" label="PIN inicial" type="password" required inputmode="numeric" minlength="4" maxlength="4" pattern="\d{4}"
                        hint="Exactamente 4 dígitos numéricos." placeholder="••••" autocomplete="new-password" />
                @else
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-medium text-eh-text">Usuario de acceso</span>
                        <p class="mono rounded-xl bg-eh-surface-alt px-3 py-2 text-sm text-eh-text-muted">{{ $usuario->username }}</p>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-medium text-eh-text">Estado actual</span>
                        <p class="px-1 py-2"><x-ui.estado :estado="$usuario->activo ? 'activo' : 'inactivo'" /></p>
                    </div>
                @endif

                <x-ui.field name="nombres" label="Nombre completo" required maxlength="150" :value="$usuario?->nombres" class="sm:col-span-2" placeholder="Juan Pérez Quispe" />
                <x-ui.field name="dni" label="DNI" required :maxlength="$esNuevo ? 8 : 20" :pattern="$esNuevo ? '[0-9]{8}' : null" :value="$usuario?->dni" placeholder="12345678" />
                <x-ui.select name="zona_id" label="Zona de trabajo" :selected="$usuario?->zonaId"
                    :options="collect($zonas)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()"
                    placeholder="Sin asignación"
                    hint="Obligatoria para nuevas cuentas de Calidad y opcional para Acopiador. Es la zona de trabajo, no una ruta." />
            </div>

            <fieldset>
                <legend class="mb-2 block text-sm font-medium text-eh-text">
                    Roles <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                </legend>
                @error('roles')
                    <p class="mb-2 text-xs text-eh-red">{{ $message }}</p>
                @enderror
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($roles as $rol)
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-eh-border p-2.5 transition-colors hover:bg-eh-surface-alt">
                            <input type="{{ $esNuevo || $rolesMarcados->count() <= 1 ? 'radio' : 'checkbox' }}" name="roles[]" value="{{ $rol->value }}" @checked($rolesMarcados->contains($rol->value))
                                class="rounded border-eh-border text-eh-primary focus:ring-eh-primary">
                            <span class="text-sm text-eh-text">{{ $rol->etiqueta() }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-eh-text-muted">Cada cuenta nueva recibe un solo rol. Las combinaciones heredadas se conservan hasta su revisión manual; para cambiarlas, selecciona un único rol.</p>
            </fieldset>

            <div class="flex justify-end gap-2 border-t border-eh-border pt-4">
                <x-ui.btn :href="route('admin.usuarios.index')" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">{{ $esNuevo ? 'Crear usuario' : 'Guardar cambios' }}</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
@endsection
