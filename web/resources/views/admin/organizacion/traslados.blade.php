@extends('layouts.admin')

@section('titulo', 'Traslados de zona')

@section('contenido')
    @php
        $puedeResolver = auth('operador')->user()->tieneRol('admin') && auth('operador')->user()->puede('zonas_vehiculos', 'gestionar');
        $etiquetas = ['pendiente' => 'Por revisar', 'programado' => 'Programados', 'aplicado' => 'Aplicados', 'rechazado' => 'Rechazados'];
    @endphp

    <x-ui.page-header title="Traslados de zona"
        description="El proveedor pide cambiar de zona con 3 días de aviso. Al aprobar, el cambio se aplica en la fecha efectiva; hasta entonces sigue en su ruta actual." />

    <div class="mb-4">
        <nav aria-label="Filtrar traslados" class="gestion-status-tabs">
            @foreach ($etiquetas as $clave => $texto)
                <a href="{{ route('admin.organizacion.traslados', ['estado' => $clave]) }}" class="gestion-status-tab" @if ($estado === $clave) aria-current="page" @endif>{{ $texto }} · {{ $conteos[$clave] }}</a>
            @endforeach
        </nav>
    </div>

    @if ($errors->has('traslado'))
        <x-ui.alert type="error" class="mb-4">{{ $errors->first('traslado') }}</x-ui.alert>
    @endif

    @if ($solicitudes->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="arrowPath" :title="'No hay traslados '.mb_strtolower($etiquetas[$estado])" description="Las solicitudes llegan desde el celular del proveedor." />
        </x-ui.card>
    @else
        <ul class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($solicitudes as $solicitud)
                @php $minima = \App\Application\Proveedores\TrasladoZonaProveedor::fechaMinima($solicitud); @endphp
                <li class="flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $solicitud->proveedor?->nombres ?? 'Proveedor' }}</p>
                            <p class="text-xs text-eh-text-muted">{{ $solicitud->proveedor?->codigo }}</p>
                        </div>
                        <x-ui.badge :variant="['pendiente' => 'yellow', 'programado' => 'blue', 'aplicado' => 'green', 'rechazado' => 'gray'][$estado]" :label="rtrim($etiquetas[$estado], 's')" />
                    </div>
                    <div class="mt-4 flex items-center gap-2 rounded-xl bg-eh-surface-alt p-3 text-sm">
                        <span class="min-w-0 truncate">{{ $estado === 'aplicado' ? 'Zona anterior' : ($solicitud->proveedor?->zona?->nombre ?? '—') }}</span>
                        <x-icon name="arrowRight" class="h-4 w-4 shrink-0 text-eh-primary" />
                        <span class="min-w-0 truncate font-semibold">{{ $solicitud->destino?->nombre ?? '—' }}</span>
                    </div>
                    <p class="mt-3 text-sm"><span class="text-eh-text-muted">Motivo:</span> {{ $solicitud->motivo }}</p>
                    <dl class="mt-3 space-y-1 text-xs text-eh-text-muted">
                        <div class="flex justify-between"><dt>Solicitado</dt><dd>{{ $solicitud->created_at->timezone('America/Lima')->format('d/m/Y') }}</dd></div>
                        <div class="flex justify-between">
                            <dt>{{ $estado === 'aplicado' ? 'Aplicado' : 'Fecha efectiva' }}</dt>
                            <dd class="font-semibold text-eh-text">{{ $estado === 'aplicado' ? $solicitud->aplicado_en?->timezone('America/Lima')->format('d/m/Y') : ($estado === 'pendiente' ? 'desde el '.$minima->format('d/m/Y') : $solicitud->fecha_efectiva?->format('d/m/Y')) }}</dd>
                        </div>
                    </dl>
                    @if ($solicitud->respuesta)
                        <p class="mt-3 text-xs"><span class="text-eh-text-muted">Respuesta:</span> {{ $solicitud->respuesta }}</p>
                    @endif
                    @if ($estado === 'pendiente' && $puedeResolver)
                        <div class="min-h-4 flex-1"></div>
                        <div class="flex justify-end gap-2 border-t border-eh-border pt-3">
                            <x-ui.btn size="sm" variant="ghost" data-modal-open="rechazar-traslado-{{ $solicitud->id }}">Rechazar</x-ui.btn>
                            <x-ui.btn size="sm" data-modal-open="aprobar-traslado-{{ $solicitud->id }}">Aprobar</x-ui.btn>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $solicitudes->links() }}</div>

        @if ($estado === 'pendiente' && $puedeResolver)
            @foreach ($solicitudes as $solicitud)
                @php $minima = \App\Application\Proveedores\TrasladoZonaProveedor::fechaMinima($solicitud); $hoy = \App\Application\Proveedores\TrasladoZonaProveedor::hoy(); @endphp
                <x-ui.modal :id="'aprobar-traslado-'.$solicitud->id" title="Aprobar traslado">
                    <form method="POST" action="{{ route('admin.organizacion.traslados.resolver', $solicitud) }}" class="space-y-4" data-once>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="APROBADA">
                        <p class="text-sm">{{ $solicitud->proveedor?->nombres }} pasará a <strong>{{ $solicitud->destino?->nombre }}</strong>. Saldrá de su ruta actual y entrará al final de la ruta de la nueva zona.</p>
                        <x-ui.field name="fecha_efectiva" id="fecha-traslado-{{ $solicitud->id }}" type="date" label="Fecha en que se aplica" required
                            :value="($minima->greaterThan($hoy) ? $minima : $hoy)->toDateString()" :min="$minima->toDateString()" hint="Se exigen 3 días de aviso desde la solicitud." />
                        <x-ui.field name="respuesta" id="respuesta-aprobar-{{ $solicitud->id }}" label="Respuesta al proveedor" required maxlength="500"
                            :value="'Traslado autorizado. Desde la fecha indicada te recogerá la ruta de '.$solicitud->destino?->nombre.'.'" />
                        <div class="flex justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Aprobar traslado</x-ui.btn></div>
                    </form>
                </x-ui.modal>
                <x-ui.modal :id="'rechazar-traslado-'.$solicitud->id" title="Rechazar traslado">
                    <form method="POST" action="{{ route('admin.organizacion.traslados.resolver', $solicitud) }}" class="space-y-4" data-once>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="RECHAZADA">
                        <x-ui.field name="respuesta" id="respuesta-rechazar-{{ $solicitud->id }}" label="Motivo del rechazo para el proveedor" required maxlength="500" />
                        <div class="flex justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit" variant="danger">Rechazar</x-ui.btn></div>
                    </form>
                </x-ui.modal>
            @endforeach
        @endif
    @endif
@endsection
