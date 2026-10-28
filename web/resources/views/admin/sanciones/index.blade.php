@extends('layouts.admin')

@section('titulo', 'Sanciones y reclamos')

@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('sanciones', 'gestionar');
        $puedeResolverReclamos = $operador->tieneRol('admin');
        $severidades = ['alta' => 'red', 'media' => 'yellow', 'baja' => 'gray'];
    @endphp

    <x-ui.page-header title="Sanciones y reclamos" description="Sanciones por calidad y reclamos enviados por proveedores" />

    <div class="space-y-6">
        <section>
            <h2 class="mb-3 text-sm font-semibold text-eh-text">Propuestas de sanción</h2>
            <x-ui.card>
                @if ($sanciones->total() === 0)
                    <x-ui.empty icon="exclamation" title="No hay propuestas de sanción"
                        description="Se crean automáticamente a partir de los controles de calidad observados o rechazados." />
                @else
                    <x-ui.table :headers="['Proveedor', 'Control', 'Tipo', 'Severidad', 'Descuento', 'Motivo', 'Estado', '']" caption="Propuestas de sanción por calidad">
                        @foreach ($sanciones as $sancion)
                            <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                                <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $sancion->proveedor?->nombres ?? '—' }}</td>
                                <td class="mono px-4 py-3 text-xs text-eh-text-muted">
    CQ-{{ str_pad((string) $sancion->control_calidad_id, 3, '0', STR_PAD_LEFT) }}
    @if ($sancion->controlCalidad?->entrega_id)
        <span class="block text-[11px] text-eh-primary font-sans font-medium">Entrega #{{ $sancion->controlCalidad->entrega_id }}</span>
    @endif
</td>
                                <td class="px-4 py-3"><x-ui.badge variant="yellow" :label="ucfirst($sancion->tipo ?? 'calidad')" /></td>
                                <td class="px-4 py-3"><x-ui.badge :variant="$severidades[$sancion->severidad] ?? 'gray'" :label="ucfirst($sancion->severidad)" /></td>
                                <td class="mono px-4 py-3 text-sm font-medium text-eh-red">-S/ {{ number_format((float) $sancion->descuento, 2) }}</td>
                                <td class="max-w-44 truncate px-4 py-3 text-xs text-eh-text-muted" title="{{ $sancion->motivo }}">{{ $sancion->motivo }}</td>
                                <td class="px-4 py-3"><x-ui.estado :estado="$sancion->estado" /></td>
                                <td class="px-4 py-3 text-right">
                                    @if ($puedeGestionar && $sancion->estado === 'pendiente')
                                        <div class="flex justify-end gap-1">
                                            <form method="POST" action="{{ route('admin.sanciones.resolver', $sancion) }}"
                                                data-confirm="¿Aprobar la sanción de {{ $sancion->proveedor?->nombres }} por S/ {{ number_format((float) $sancion->descuento, 2) }}? Se descontará en su liquidación.">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="estado" value="aprobada">
                                                <x-ui.btn type="submit" size="sm" variant="secondary">Aprobar descuento</x-ui.btn>
                                            </form>
                                            <form method="POST" action="{{ route('admin.sanciones.resolver', $sancion) }}"
                                                data-confirm="¿Rechazar esta propuesta de sanción?">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="estado" value="rechazada">
                                                <x-ui.btn type="submit" size="sm" variant="ghost">Rechazar descuento</x-ui.btn>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                        <span>Mostrando {{ $sanciones->count() }} de {{ $sanciones->total() }} propuestas</span>
                        <div>{{ $sanciones->onEachSide(1)->links() }}</div>
                    </div>
                @endif
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-3 text-sm font-semibold text-eh-text">Reclamos de proveedores</h2>
            <x-ui.card>
                @if ($reclamos->total() === 0)
                    <x-ui.empty icon="documentText" title="Sin reclamos pendientes de revisión" />
                @else
                    <x-ui.table :headers="['Código', 'Proveedor', 'Entrega', 'L. originales', 'L. solicitados', 'Motivo', 'Estado', '']" caption="Reclamos de proveedores">
                        @foreach ($reclamos as $reclamo)
                            <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                                <td class="mono px-4 py-3 text-xs font-medium text-eh-text">REC-{{ str_pad((string) $reclamo->id, 3, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $reclamo->proveedor?->nombres ?? '—' }}</td>
                                <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $reclamo->entrega_id ? 'E-'.$reclamo->entrega_id : 'Sin registrar' }}</td>
                                <td class="mono px-4 py-3 text-xs text-eh-text">{{ number_format((float) $reclamo->litros_originales, 1) }} L</td>
                                <td class="mono px-4 py-3 text-xs font-medium text-eh-primary">{{ number_format((float) $reclamo->litros_solicitados, 1) }} L</td>
                                <td class="max-w-72 px-4 py-3 text-xs text-eh-text-muted">
                                    <p class="font-medium text-eh-text">{{ $reclamo->motivo }}</p>
                                    @if ($reclamo->descripcion)
                                        <p class="mt-1 whitespace-pre-wrap">{{ $reclamo->descripcion }}</p>
                                    @endif
                                    @if ($reclamo->evidencia)
                                        <a class="mt-2 inline-block text-eh-primary underline" href="{{ route('admin.sanciones.reclamos.evidencia', $reclamo) }}" target="_blank" rel="noopener">Ver evidencia fotográfica</a>
                                    @endif
                                    @if ($reclamo->respuesta)
                                        <p class="mt-2 font-medium text-eh-text">Respuesta: {{ $reclamo->respuesta }}</p>
                                        <p>{{ $reclamo->resuelto_en?->format('d/m/Y H:i') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><x-ui.estado :estado="$reclamo->estado" /></td>
                                <td class="px-4 py-3 text-right">
                                    @if ($puedeResolverReclamos && $reclamo->estado === 'pendiente')
                                        <x-ui.btn type="button" size="sm" variant="secondary" data-modal-open="reclamo-{{ $reclamo->id }}">Revisar y responder</x-ui.btn>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                        <span>Mostrando {{ $reclamos->count() }} de {{ $reclamos->total() }} reclamos</span>
                        <div>{{ $reclamos->onEachSide(1)->links() }}</div>
                    </div>
                @endif
            </x-ui.card>
        </section>
    </div>

    @if ($puedeGestionar)
        @foreach ($reclamos as $reclamo)
            @if ($puedeResolverReclamos && $reclamo->estado === 'pendiente')
                <x-ui.modal :id="'reclamo-'.$reclamo->id" title="Resolver reclamo">
                    <form method="POST" action="{{ route('admin.sanciones.reclamos.resolver', $reclamo) }}" class="space-y-4" data-once>
                        @csrf
                        @method('PATCH')
                        <div class="space-y-2 rounded-xl bg-eh-surface-alt p-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-eh-text-muted">Proveedor</span>
                                <span class="font-medium text-eh-text">{{ $reclamo->proveedor?->nombres ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-eh-text-muted">Litros registrados</span>
                                <span class="mono font-bold text-eh-text">{{ number_format((float) $reclamo->litros_originales, 1) }} L</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-eh-text-muted">Litros solicitados</span>
                                <span class="mono font-bold text-eh-primary">{{ number_format((float) $reclamo->litros_solicitados, 1) }} L</span>
                            </div>
                        </div>
                        <x-ui.alert type="warning">
                            Aprobar modifica los litros y recalcula su liquidación pendiente. Si ya fue pagada, el ajuste se bloqueará. La decisión queda en auditoría.
                        </x-ui.alert>
                        @if ($reclamo->entrega_id === null)
                            <x-ui.alert type="info">Registra primero la entrega que falta y selecciónala aquí para aprobar el ajuste. Debe pertenecer a este proveedor.</x-ui.alert>
                            <x-ui.select name="entrega_id" label="Entrega para vincular al aprobar" placeholder="Seleccionar entrega"
                                :options="$entregas->where('proveedor_id', $reclamo->proveedor_id)->mapWithKeys(fn ($e) => [$e->id => 'E-'.$e->id.' · '.number_format((float) $e->litros, 2).' L · '.$e->registrado_en->format('d/m/Y')])->all()" />
                        @endif
                        <x-ui.select name="estado" label="Decisión" required
                            :options="['resuelto' => 'Aprobar el ajuste de litros', 'rechazado' => 'Rechazar el reclamo']" />
                        <div class="flex flex-col gap-1">
                            <label for="respuesta-{{ $reclamo->id }}" class="text-sm font-medium text-eh-text">
                                Respuesta al proveedor <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                            </label>
                            <textarea id="respuesta-{{ $reclamo->id }}" name="respuesta" required maxlength="500" rows="3"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary"></textarea>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                            <x-ui.btn type="submit">Guardar decisión</x-ui.btn>
                        </div>
                    </form>
                </x-ui.modal>
            @endif
        @endforeach
    @endif
    <section class="mt-6">
        <h2 class="mb-3 text-sm font-semibold text-eh-text">Solicitudes de traslado</h2>
        <x-ui.card>
            @if ($solicitudesTraslado->isEmpty())
                <p class="p-4 text-sm text-eh-text-muted">No hay solicitudes de traslado.</p>
            @else
                <x-ui.table :headers="['Proveedor', 'Destino solicitado', 'Motivo', 'Estado', '']" caption="Solicitudes de traslado">
                    @foreach ($solicitudesTraslado as $traslado)
                        <tr class="border-b border-eh-border">
                            <td class="px-4 py-3">{{ $traslado->proveedor?->nombres }}</td>
                            <td class="px-4 py-3">{{ $traslado->destino?->nombre }}</td>
                            <td class="px-4 py-3">
                                <p>{{ $traslado->motivo }}</p>
                                @if ($traslado->respuesta)
                                    <p class="mt-2 text-xs text-eh-text-muted">Respuesta: {{ $traslado->respuesta }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $traslado->estado }}</td>
                            <td class="px-4 py-3">
                                @if ($operador->tieneRol('admin') && $traslado->estado === 'PENDIENTE')
                                    <div class="min-w-60">
                                            <form method="POST" action="{{ route('admin.sanciones.traslados.resolver', $traslado) }}" class="space-y-2" data-once>
                                                @csrf
                                                @method('PATCH')
                                                <x-ui.select name="estado" label="Decisión" required :options="['APROBADA' => 'Aprobar traslado', 'RECHAZADA' => 'Rechazar traslado']" />
                                                <label class="block text-xs" for="respuesta-traslado-{{ $traslado->id }}">Respuesta al proveedor</label>
                                                <textarea id="respuesta-traslado-{{ $traslado->id }}" name="respuesta" required maxlength="500" rows="2" class="w-full rounded-lg border border-eh-border bg-eh-surface p-2 text-sm"></textarea>
                                                <x-ui.btn type="submit" size="sm" variant="secondary">Guardar decisión</x-ui.btn>
                                            </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
                <div class="p-4">{{ $solicitudesTraslado->links() }}</div>
            @endif
        </x-ui.card>
    </section>
@endsection
