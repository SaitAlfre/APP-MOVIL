@extends('layouts.admin')

@section('titulo', 'Usuarios y roles')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('usuarios', 'gestionar');
        $hayFiltros = $busqueda || $rolFiltro || $activoFiltro !== null;
    @endphp

    <x-ui.page-header title="Usuarios y roles" description="Gestión de accesos al sistema">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.usuarios.create')" icon="plus">Nuevo usuario</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <div class="flex-1">
                <x-ui.search name="buscar" :value="$busqueda" label="Buscar usuarios" placeholder="Buscar por nombre, usuario o DNI…" />
            </div>
            <x-ui.select name="rol" placeholder="Todos los roles" class="sm:w-52" aria-label="Filtrar por rol"
                :options="collect($roles)->mapWithKeys(fn ($rol) => [$rol->value => $rol->etiqueta()])->all()" :selected="$rolFiltro?->value" />
            <x-ui.select name="activo" placeholder="Todos los estados" class="sm:w-44" aria-label="Filtrar por estado"
                :options="['1' => 'Activos', '0' => 'Inactivos']" :selected="$activoFiltro === null ? null : ($activoFiltro ? '1' : '0')" />
            <x-ui.btn type="submit" variant="secondary" icon="filter">Filtrar</x-ui.btn>
            @if ($hayFiltros)
                <x-ui.btn :href="route('admin.usuarios.index')" variant="ghost" icon="xMark">Limpiar</x-ui.btn>
            @endif
        </form>
    </x-ui.card>

    <x-ui.card>
        @if ($usuarios->total() === 0)
            <x-ui.empty icon="users"
                :title="$hayFiltros ? 'No hay usuarios que coincidan con el filtro' : 'Aún no hay usuarios registrados'"
                :description="$hayFiltros ? 'Prueba con otros filtros o limpia la búsqueda.' : 'Crea la primera cuenta con el botón «Nuevo usuario».'" />
        @else
            <x-ui.table :headers="['Usuario', 'Nombre', 'DNI', 'Roles', 'Estado', 'Acceso web', '']" caption="Cuentas con acceso al sistema">
                @foreach ($usuarios as $usuario)
                    @php
                        $bloqueoTemporal = $usuario->bloqueadoHasta && $usuario->bloqueadoHasta > now();
                        $accesoWeb = collect($usuario->roles)->contains(fn ($rol) => $rol->accesoWeb());
                    @endphp
                    <tr class="border-b border-eh-border align-top last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3"><span class="mono text-xs font-medium text-eh-text">{{ $usuario->username }}</span></td>
                        <td class="px-4 py-3 font-medium text-eh-text">{{ $usuario->nombres }}</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $usuario->dni }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($usuario->roles as $rol)
                                    <x-ui.badge variant="blue" :label="$rol->etiqueta()" />
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if (! $usuario->activo)
                                <x-ui.badge variant="gray" label="Inactivo" />
                            @elseif ($usuario->bloqueadoManualmente)
                                <x-ui.badge variant="red" label="Bloqueado" />
                                <p class="mt-1 max-w-44 break-words text-[11px] text-eh-text-muted">{{ $usuario->motivoBloqueo }}</p>
                            @elseif ($bloqueoTemporal)
                                <x-ui.badge variant="yellow" label="Bloqueo temporal" />
                                <p class="mt-1 text-[11px] text-eh-text-muted">Hasta {{ $usuario->bloqueadoHasta->format('d/m H:i') }}</p>
                            @else
                                <x-ui.badge variant="green" label="Activo" />
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $accesoWeb ? 'Sí' : 'Solo app móvil' }}</td>
                        <td class="px-4 py-3">
                            @if ($puedeGestionar)
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.usuarios.edit', $usuario->id) }}" aria-label="Editar {{ $usuario->nombres }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </a>

                                    <form method="POST" action="{{ route('admin.usuarios.estado', $usuario->id) }}"
                                        data-confirm="{{ $usuario->activo ? '¿Desactivar la cuenta de '.$usuario->nombres.'? No podrá iniciar sesión hasta reactivarla.' : '¿Reactivar la cuenta de '.$usuario->nombres.'?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="activo" value="{{ $usuario->activo ? '0' : '1' }}">
                                        <button type="submit" aria-label="{{ $usuario->activo ? 'Desactivar' : 'Activar' }} {{ $usuario->nombres }}"
                                            class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                            <x-icon :name="$usuario->activo ? 'xMark' : 'check'" class="h-4 w-4" />
                                        </button>
                                    </form>

                                    @if ($usuario->bloqueadoManualmente)
                                        <form method="POST" action="{{ route('admin.usuarios.desbloquear', $usuario->id) }}"
                                            data-confirm="¿Desbloquear la cuenta de {{ $usuario->nombres }}?">
                                            @csrf
                                            <button type="submit" aria-label="Desbloquear {{ $usuario->nombres }}"
                                                class="rounded-lg p-1.5 text-eh-primary hover:bg-eh-primary-soft">
                                                <x-icon name="shield" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" data-modal-open="bloquear-{{ $usuario->id }}" aria-label="Bloquear {{ $usuario->nombres }}"
                                            class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-red-soft hover:text-eh-red">
                                            <x-icon name="exclamation" class="h-4 w-4" />
                                        </button>
                                    @endif

                                    <button type="button" data-modal-open="pin-{{ $usuario->id }}" aria-label="Restablecer PIN de {{ $usuario->nombres }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                        <x-icon name="arrowPath" class="h-4 w-4" />
                                    </button>
                                </div>
                            @else
                                <span class="text-xs text-eh-text-muted">Solo lectura</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>Mostrando {{ $usuarios->count() }} de {{ $usuarios->total() }} usuarios</span>
                <div>{{ $usuarios->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>

    @if ($puedeGestionar)
        @foreach ($usuarios as $usuario)
            @if (! $usuario->bloqueadoManualmente)
                <x-ui.modal :id="'bloquear-'.$usuario->id" title="Bloquear cuenta" size="sm">
                    <form method="POST" action="{{ route('admin.usuarios.bloquear', $usuario->id) }}" class="space-y-4" data-once>
                        @csrf
                        <p class="text-sm text-eh-text-muted">
                            Vas a bloquear la cuenta de <strong class="text-eh-text">{{ $usuario->nombres }}</strong>.
                            No podrá iniciar sesión hasta que se desbloquee y la acción queda registrada en auditoría.
                        </p>
                        <div class="flex flex-col gap-1">
                            <label for="motivo-{{ $usuario->id }}" class="text-sm font-medium text-eh-text">
                                Motivo <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                            </label>
                            <textarea id="motivo-{{ $usuario->id }}" name="motivo" required maxlength="255" rows="3"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary"></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                            <x-ui.btn type="submit" variant="danger">Confirmar bloqueo</x-ui.btn>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

            <x-ui.modal :id="'pin-'.$usuario->id" title="Restablecer PIN" size="sm">
                <form method="POST" action="{{ route('admin.usuarios.restablecer', $usuario->id) }}" class="space-y-4" data-once>
                    @csrf
                    <p class="text-sm text-eh-text-muted">
                        Se asignará un PIN nuevo a <strong class="text-eh-text">{{ $usuario->nombres }}</strong> y se cerrarán sus sesiones abiertas.
                        El PIN anterior no se muestra en ningún momento.
                    </p>
                    <div class="flex flex-col gap-1">
                        <label for="pin-nuevo-{{ $usuario->id }}" class="text-sm font-medium text-eh-text">
                            Nuevo PIN <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                        </label>
                        <input id="pin-nuevo-{{ $usuario->id }}" type="password" inputmode="numeric" name="pin" required minlength="4" maxlength="8" pattern="\d{4,8}"
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        <p class="text-xs text-eh-text-muted">Entre 4 y 8 dígitos numéricos.</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                        <x-ui.btn type="submit">Restablecer PIN</x-ui.btn>
                    </div>
                </form>
            </x-ui.modal>
        @endforeach
    @endif
@endsection
