@extends('layouts.admin')

@section('titulo', 'Jornada de acopio')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('acopiadores', 'gestionar');
        $abierta = $jornada->estaAbierta();
        $duplicado = session('duplicado');
    @endphp

    <x-ui.page-header :title="($abierta ? 'Jornada en curso — ' : 'Jornada — ').($zona?->nombre ?? 'Sin zona')"
        :description="($acopiador?->nombres ?? 'Acopiador').' · '.($vehiculo?->nombre ?? 'Sin vehículo').($vehiculo ? ' ('.$vehiculo->placa.')' : '').' · '.$jornada->fecha->format('d/m/Y')"
        :breadcrumbs="[['label' => 'Jornadas de acopio', 'url' => route('admin.jornadas.index')], ['label' => 'Jornada #'.$jornada->id]]">
        <x-slot:actions>
            @if ($abierta && $puedeGestionar)
                <x-ui.btn type="button" variant="danger" size="sm" data-modal-open="cerrar-jornada">Cerrar jornada</x-ui.btn>
            @endif
            <x-ui.btn :href="route('admin.jornadas.index')" variant="ghost" size="sm" icon="arrowLeft">Volver</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <x-ui.card padding="p-5">
            <p class="mb-1 text-xs text-eh-text-muted">Total registrado</p>
            <p class="mono text-4xl font-bold text-eh-primary">{{ number_format($litros, 1) }}</p>
            <p class="mt-1 text-sm text-eh-text-muted">litros</p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="mb-1 text-xs text-eh-text-muted">Entregas registradas</p>
            <p class="mono text-2xl font-bold text-eh-text">{{ $entregas }}</p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="mb-1 text-xs text-eh-text-muted">Hora de apertura</p>
            <p class="mono text-2xl font-bold text-eh-text">{{ $jornada->abiertaEn->format('H:i') }}</p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="mb-1 text-xs text-eh-text-muted">Estado</p>
            <div class="mt-1 flex items-center gap-2">
                <span @class(['h-2 w-2 rounded-full', 'bg-eh-primary' => $abierta, 'bg-eh-text-muted' => ! $abierta])></span>
                <span @class(['text-sm font-medium', 'text-eh-primary' => $abierta, 'text-eh-text-muted' => ! $abierta])>
                    {{ $abierta ? 'Jornada abierta' : 'Jornada cerrada' }}
                </span>
            </div>
            @if (! $abierta && $jornada->cerradaEn)
                <p class="mt-1 text-xs text-eh-text-muted">Cerrada a las {{ $jornada->cerradaEn->format('H:i') }}</p>
            @endif
        </x-ui.card>
    </div>

    @if ($duplicado)
        <x-ui.alert type="warning" title="Este proveedor ya tiene una entrega en esta jornada" class="mb-4">
            <p class="mt-1">
                Ya hay {{ number_format($duplicado['litros_existentes'], 1) }} L en {{ $duplicado['tachos_existentes'] }} tacho(s).
                ¿Sumar la nueva cantidad a la existente o registrarla aparte?
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.acopiadores.entregas.sumar') }}" data-once>
                    @csrf
                    <input type="hidden" name="entrega_id" value="{{ $duplicado['entrega_id'] }}">
                    <input type="hidden" name="litros" value="{{ $duplicado['litros_existentes'] + $duplicado['litros_nuevos'] }}">
                    <input type="hidden" name="tachos" value="{{ $duplicado['tachos_existentes'] + $duplicado['tachos_nuevos'] }}">
                    <x-ui.btn type="submit" size="sm">Sumar a la existente</x-ui.btn>
                </form>
                <form method="POST" action="{{ route('admin.acopiadores.entregas.store', $jornada->id) }}" data-once>
                    @csrf
                    <input type="hidden" name="proveedor_id" value="{{ old('proveedor_id') }}">
                    <input type="hidden" name="litros" value="{{ $duplicado['litros_nuevos'] }}">
                    <input type="hidden" name="tachos" value="{{ $duplicado['tachos_nuevos'] }}">
                    <input type="hidden" name="observaciones" value="{{ old('observaciones') }}">
                    <input type="hidden" name="forzar" value="1">
                    <x-ui.btn type="submit" variant="outline" size="sm">Registrar aparte</x-ui.btn>
                </form>
            </div>
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <div class="flex items-center justify-between border-b border-eh-border px-4 py-3">
                <h2 class="text-sm font-semibold text-eh-text">Entregas registradas</h2>
                <span class="text-xs text-eh-text-muted">{{ count($recientes) }} más recientes</span>
            </div>

            @if (count($recientes) === 0)
                <x-ui.empty icon="droplets" title="Aún no hay entregas en esta jornada"
                    description="Registra la primera entrega con el formulario de la derecha." />
            @else
                <x-ui.table :headers="['Proveedor', 'Litros', 'Tachos', 'Hora', 'Estado', '']" caption="Entregas registradas en la jornada">
                    @foreach ($recientes as $entrega)
                        <tr @class(['border-b border-eh-border last:border-0 hover:bg-eh-surface-alt', 'opacity-60' => $entrega->anulada])>
                            <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $proveedores->get($entrega->proveedorId)?->nombres ?? 'Proveedor #'.$entrega->proveedorId }}</td>
                            <td class="mono px-4 py-3 text-sm font-bold text-eh-text">{{ number_format($entrega->litros, 1) }} L</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $entrega->tachos }}</td>
                            <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $entrega->registradoEn->format('H:i') }}</td>
                            <td class="px-4 py-3">
                                <x-ui.estado :estado="$entrega->anulada ? 'anulada' : 'registrada'" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (! $entrega->anulada && $abierta && $puedeGestionar)
                                    <button type="button" data-modal-open="anular-{{ $entrega->id }}" aria-label="Anular entrega de {{ $proveedores->get($entrega->proveedorId)?->nombres ?? 'proveedor' }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-red-soft hover:text-eh-red">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>

        @if ($abierta && $puedeGestionar)
            <x-ui.card padding="p-4">
                <h2 class="mb-4 text-sm font-semibold text-eh-text">Registrar entrega</h2>
                <form method="POST" action="{{ route('admin.acopiadores.entregas.store', $jornada->id) }}" class="space-y-4" data-once>
                    @csrf
                    <x-ui.select name="proveedor_id" label="Proveedor" required placeholder="Selecciona un proveedor"
                        :options="collect($proveedores)->mapWithKeys(fn ($proveedor) => [$proveedor->id => $proveedor->codigo.' · '.$proveedor->nombres])->all()" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.field name="litros" label="Litros" type="number" step="0.01" min="0.01" required unit="L" />
                        <x-ui.field name="tachos" label="Tachos" type="number" min="1" :value="1" required />
                    </div>
                    <x-ui.field name="observaciones" label="Observaciones" placeholder="Opcional…" />
                    <x-ui.btn type="submit" icon="plus" class="w-full">Guardar entrega</x-ui.btn>
                </form>
            </x-ui.card>
        @else
            <x-ui.card padding="p-4">
                <h2 class="mb-2 text-sm font-semibold text-eh-text">Jornada cerrada</h2>
                <p class="text-sm text-eh-text-muted">
                    Esta jornada ya no admite nuevas entregas. Su información se conserva completa para trazabilidad
                    en recepción, calidad y liquidaciones.
                </p>
            </x-ui.card>
        @endif
    </div>

    @if ($abierta && $puedeGestionar)
        <x-ui.modal id="cerrar-jornada" title="Cerrar jornada">
            <form method="POST" action="{{ route('admin.acopiadores.jornadas.cerrar', $jornada->id) }}" class="space-y-4" data-once>
                @csrf
                @method('PATCH')
                <div class="space-y-2 rounded-xl bg-eh-surface-alt p-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-eh-text-muted">Litros registrados en campo</span>
                        <span class="mono font-bold text-eh-text">{{ number_format($litros, 1) }} L</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-eh-text-muted">Entregas registradas</span>
                        <span class="mono font-bold text-eh-text">{{ $entregas }}</span>
                    </div>
                </div>
                <x-ui.alert type="warning">
                    Esta acción es irreversible: la jornada quedará cerrada y no podrás agregar más entregas.
                    Los litros medidos en planta se registran después, desde Recepción en planta.
                </x-ui.alert>
                <div class="flex justify-end gap-2">
                    <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                    <x-ui.btn type="submit" variant="danger">Confirmar cierre</x-ui.btn>
                </div>
            </form>
        </x-ui.modal>

        @foreach ($recientes as $entrega)
            @if (! $entrega->anulada)
                <x-ui.modal :id="'anular-'.$entrega->id" title="Anular entrega" size="sm">
                    <form method="POST" action="{{ route('admin.acopiadores.entregas.anular', $entrega->id) }}" class="space-y-4" data-once>
                        @csrf
                        <p class="text-sm text-eh-text-muted">
                            Vas a anular la entrega de
                            <strong class="text-eh-text">{{ $proveedores->get($entrega->proveedorId)?->nombres ?? 'este proveedor' }}</strong>
                            ({{ number_format($entrega->litros, 1) }} L). La entrega se conserva marcada como anulada y queda registrada en auditoría.
                        </p>
                        <div class="flex flex-col gap-1">
                            <label for="motivo-entrega-{{ $entrega->id }}" class="text-sm font-medium text-eh-text">
                                Motivo <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                            </label>
                            <input id="motivo-entrega-{{ $entrega->id }}" type="text" name="motivo" required maxlength="255"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        </div>
                        <div class="flex justify-end gap-2">
                            <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                            <x-ui.btn type="submit" variant="danger">Anular entrega</x-ui.btn>
                        </div>
                    </form>
                </x-ui.modal>
            @endif
        @endforeach
    @endif
@endsection
