@extends('layouts.admin')
@section('titulo', 'Jornada de acopio')
@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->puede('acopiadores', 'gestionar');
        $puedeAnular = $puedeGestionar && $operador->puede('acopiadores', 'eliminar');
        $puedeRecepcion = $operador->puede('recepcion', 'gestionar');
        $puedeResolverReclamos = $operador->tieneRol('admin') && $operador->puede('sanciones', 'gestionar');
        $abierta = $jornada->cerrada_en === null;
        $duplicado = session('duplicado');
        $reclamosPendientes = $reclamos->where('estado', 'pendiente');
        $pasos = ['En ruta', 'Terminada', 'Recibida en planta'];
    @endphp

    <x-ui.page-header :title="'Jornada · '.($jornada->zona?->nombre ?? 'Sin zona')"
        :description="($jornada->usuario?->nombres ?? 'Sin cuenta').' · Placa '.($jornada->vehiculo?->placa ?? 'sin vehículo').' · '.$jornada->fecha->format('d/m/Y')">
        <x-slot:actions>
            <x-ui.btn :href="route('admin.jornadas.index', ['fecha' => $jornada->fecha->format('Y-m-d')])" variant="outline" icon="arrowLeft">Acopio del día</x-ui.btn>
            @if ($abierta && $puedeGestionar)
                <x-ui.btn data-modal-open="cerrar-jornada" variant="danger">Terminar jornada</x-ui.btn>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 flex flex-wrap items-center gap-3">
        <x-ui.badge :variant="$etapa->variante()" :label="$etapa->etiqueta()" />
        <ol class="flex flex-wrap items-center gap-1 text-xs" aria-label="Avance de la jornada">
            @foreach ($pasos as $indice => $paso)
                @php $alcanzado = $indice < $etapa->paso(); @endphp
                <li @class(['flex items-center gap-1 rounded-full px-3 py-1', 'bg-eh-success-soft text-eh-success font-semibold' => $alcanzado, 'border border-dashed border-eh-border text-eh-text-muted' => ! $alcanzado])>
                    @if ($alcanzado)<x-icon name="check" class="h-3 w-3" />@endif
                    {{ $paso }}<span class="sr-only">{{ $alcanzado ? '(completado)' : '(pendiente)' }}</span>
                </li>
                @if (! $loop->last)<x-icon name="chevronRight" class="h-3 w-3 text-eh-text-muted" />@endif
            @endforeach
        </ol>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-ui.kpi label="Litros de campo" :value="number_format($litros, 1)" unit="L" icon="droplets" color="green" hint="Los recojos anulados no suman." />
        <x-ui.kpi label="Recojos" :value="$entregas" icon="clipboardList" color="blue" />
        <x-ui.kpi label="Horario (Perú)" :value="$jornada->abierta_en?->timezone('America/Lima')->format('H:i').' – '.($jornada->cerrada_en?->timezone('America/Lima')->format('H:i') ?? '…')" icon="clock" color="blue" />
        <x-ui.kpi label="Medido en planta" :value="$recepcion ? number_format($recepcion->litrosMedidos, 1) : 'Pendiente'" :unit="$recepcion ? 'L' : null" icon="recepcion"
            :color="$recepcion === null ? 'yellow' : ($recepcion->esMerma() ? 'red' : 'green')" />
    </div>

    @if ($duplicado)
        <x-ui.alert type="warning" title="Ya existe un recojo de este proveedor" class="mb-4">
            <p>Ya hay {{ number_format($duplicado['litros_existentes'], 1) }} L registrados hoy. Confirma solo si es un segundo recojo real.</p>
            <form method="POST" action="{{ route('admin.acopiadores.entregas.store', $jornada) }}" class="mt-3" data-once>
                @csrf
                <input type="hidden" name="proveedor_id" value="{{ old('proveedor_id') }}"><input type="hidden" name="litros" value="{{ $duplicado['litros_nuevos'] }}"><input type="hidden" name="forzar" value="1">
                <x-ui.btn type="submit" size="sm">Confirmar segundo recojo</x-ui.btn>
            </form>
        </x-ui.alert>
    @endif

    <x-ui.card class="mb-5">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-eh-border p-4">
            <div>
                <h2 class="font-semibold">Recojos</h2>
                <p class="text-sm text-eh-text-muted">Lo que llega del celular aparece cuando el teléfono se sincroniza. Corregir y anular piden motivo y quedan en auditoría.</p>
            </div>
            @if ($abierta && $puedeGestionar)
                <x-ui.btn data-modal-open="agregar-recojo" icon="plus">Agregar recojo</x-ui.btn>
            @endif
        </div>
        @if ($recientes->isEmpty())
            <x-ui.empty icon="droplets" title="Todavía no hay recojos" description="Aparecerán cuando el teléfono los envíe o cuando agregues uno desde aquí." />
        @else
            <x-ui.table :headers="['Proveedor', 'Hora (Perú)', 'Litros', 'Registrado desde', '']" caption="Recojos de la jornada">
                @foreach ($recientes as $entrega)
                    @php
                        $historial = $cambios->get($entrega->id, collect());
                        $anulacion = $historial->firstWhere('accion', 'anular');
                        $corregido = $historial->contains('accion', 'corregir');
                    @endphp
                    <tr @class(['border-b border-eh-border last:border-0', 'text-eh-text-muted' => $entrega->anulada])>
                        <td class="px-4 py-3 text-sm">
                            <span @class(['font-medium', 'line-through' => $entrega->anulada])>{{ $entrega->proveedor?->nombres ?? 'Proveedor' }}</span>
                            @if ($entrega->anulada)
                                <p class="text-xs">Anulado{{ $anulacion?->motivo ? ': '.$anulacion->motivo : '' }}</p>
                            @endif
                        </td>
                        <td class="mono px-4 py-3 text-sm">{{ $entrega->registrado_en->timezone('America/Lima')->format('H:i') }}</td>
                        <td class="mono px-4 py-3 text-sm font-semibold">
                            <span @class(['line-through' => $entrega->anulada])>{{ number_format($entrega->litros, 1) }} L</span>
                            @if ($corregido && ! $entrega->anulada)<x-ui.badge variant="yellow" label="Corregido" class="ml-1" />@endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($entrega->uuid_movil)
                                <span class="text-eh-text-muted">Celular</span>
                            @else
                                <x-ui.badge variant="blue" label="Panel web" />
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($puedeGestionar && ! $entrega->anulada)
                                <div class="flex justify-end gap-2">
                                    <x-ui.btn variant="outline" size="sm" icon="pencil" data-modal-open="corregir-{{ $entrega->id }}">Corregir</x-ui.btn>
                                    @if ($puedeAnular)
                                        <x-ui.btn variant="ghost" size="sm" data-modal-open="anular-{{ $entrega->id }}">Anular</x-ui.btn>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="p-4">{{ $recientes->links() }}</div>
        @endif
    </x-ui.card>

    <div class="mb-5 grid gap-5 lg:grid-cols-2">
        <x-ui.card padding="p-5">
            <h2 class="mb-1 font-semibold">Recepción en planta</h2>
            <p class="mb-4 text-sm text-eh-text-muted">La planta vuelve a medir la leche. La diferencia sirve para controlar la ruta; al proveedor se le paga lo registrado en campo.</p>
            <dl class="mb-4 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-eh-surface-alt p-3"><dt class="text-xs text-eh-text-muted">Campo</dt><dd class="mono font-semibold">{{ number_format($litros, 1) }} L</dd></div>
                <div class="rounded-xl bg-eh-surface-alt p-3"><dt class="text-xs text-eh-text-muted">Planta</dt><dd class="mono font-semibold">{{ $recepcion ? number_format($recepcion->litrosMedidos, 1).' L' : 'Pendiente' }}</dd></div>
                <div class="rounded-xl bg-eh-surface-alt p-3">
                    <dt class="text-xs text-eh-text-muted">Diferencia</dt>
                    <dd @class(['mono font-semibold', 'text-eh-red' => $recepcion?->esMerma(), 'text-eh-gold' => $recepcion?->esExcedente()])>
                        @if ($recepcion === null)
                            —
                        @elseif ($recepcion->diferencia() === 0.0)
                            0 L
                        @else
                            {{ $recepcion->esMerma() ? 'Faltan' : 'Sobran' }} {{ number_format(abs($recepcion->diferencia()), 1) }} L
                        @endif
                    </dd>
                </div>
            </dl>
            @if ($recepcion !== null)
                <p class="text-sm"><span class="text-eh-text-muted">Llegó:</span> {{ $recepcion->llegadaEn->format('d/m/Y H:i') }}</p>
                @if ($recepcion->motivoDiferencia)
                    <p class="mt-1 text-sm"><span class="text-eh-text-muted">Motivo:</span> {{ $recepcion->motivoDiferencia }}</p>
                @endif
                @if ($recepcion->litrosRecolectados !== round($litros, 2))
                    <x-ui.alert type="warning" class="mt-3">Los recojos cambiaron después de la recepción (se midieron {{ number_format($recepcion->litrosRecolectados, 1) }} L de campo). Corrige la recepción para actualizar la diferencia.</x-ui.alert>
                @endif
            @endif
            @if ($puedeRecepcion && $litros > 0)
                @if ($abierta && $recepcion === null)
                    <p class="mt-3 text-sm text-eh-text-muted">Registra la llegada cuando el acopiador termine la jornada.</p>
                @else
                    <x-ui.btn class="mt-4" :variant="$recepcion === null ? 'primary' : 'outline'" icon="recepcion" data-modal-open="recepcion-{{ $jornada->id }}">
                        {{ $recepcion === null ? 'Registrar llegada' : 'Corregir recepción' }}
                    </x-ui.btn>
                @endif
            @endif
        </x-ui.card>

        <x-ui.card padding="p-5">
            <h2 class="mb-1 font-semibold">Reclamos de proveedores</h2>
            <p class="mb-4 text-sm text-eh-text-muted">Se resuelven antes del pago del viernes. Si la semana ya se pagó, el ajuste queda bloqueado.</p>
            @if ($reclamos->isEmpty())
                <p class="text-sm text-eh-text-muted">No hay reclamos sobre los recojos de esta jornada.</p>
            @else
                <ul class="divide-y divide-eh-border">
                    @foreach ($reclamos as $reclamo)
                        <li class="py-3 text-sm">
                            <div class="flex items-start justify-between gap-2">
                                <p><span class="font-medium">{{ $reclamo->proveedor?->nombres ?? 'Proveedor' }}</span> pide {{ number_format((float) $reclamo->litros_solicitados, 1) }} L en lugar de {{ number_format((float) $reclamo->litros_originales, 1) }} L</p>
                                <x-ui.estado :estado="$reclamo->estado" />
                            </div>
                            <p class="mt-1 text-xs text-eh-text-muted">{{ $reclamo->motivo }}</p>
                            @if ($reclamo->respuesta)
                                <p class="mt-1 text-xs"><span class="text-eh-text-muted">Respuesta:</span> {{ $reclamo->respuesta }}</p>
                            @endif
                            @if ($puedeResolverReclamos && $reclamo->estado === 'pendiente')
                                <x-ui.btn class="mt-2" size="sm" variant="secondary" data-modal-open="reclamo-{{ $reclamo->id }}">Responder</x-ui.btn>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card>
        <div class="border-b border-eh-border p-4">
            <h2 class="font-semibold">Hoja de ruta</h2>
            <p class="mt-1 text-sm text-eh-text-muted">{{ $hoja['atendidos'] }} de {{ $hoja['total'] }} atendidos · {{ $hoja['pendientes'] }} pendientes · {{ $hoja['noAcopiados'] }} no acopiados. Incluye el apoyo de otros acopiadores de la zona.</p>
            @foreach ($hoja['rutas'] as $ruta)
                <a class="mt-2 inline-block text-sm font-semibold text-eh-primary underline focus-visible:ring-2" href="{{ route('admin.mapa-rutas.show', $ruta) }}">{{ $ruta->nombre }}</a>
            @endforeach
        </div>
        @if ($hoja['filas']->isEmpty())
            <x-ui.empty title="Sin proveedores en esta ruta" description="Revisa la zona y las paradas asignadas al acopiador." />
        @else
            <x-ui.table :headers="['Proveedor', 'Estado del recojo', 'Litros del día', 'Registrado por', '']" caption="Proveedores de la ruta y su estado del día">
                @foreach ($hoja['filas'] as $fila)
                    <tr class="border-b border-eh-border last:border-0">
                        <td class="px-4 py-3"><p class="text-sm font-semibold">{{ $fila['numero'] }}. {{ $fila['proveedor']->nombres }}</p><p class="text-xs text-eh-text-muted">{{ $fila['proveedor']->codigo }}</p></td>
                        <td class="px-4 py-3 text-sm">
                            {{ ['atendido' => 'Atendido', 'pendiente' => 'Pendiente', 'no_acopiado' => 'No acopiado'][$fila['estado']] }}
                            @if ($fila['estado'] === 'no_acopiado')
                                <p class="text-xs text-eh-text-muted">{{ $fila['marca']->detalle ?? str_replace('_', ' ', $fila['marca']->motivo) }}</p>
                            @endif
                        </td>
                        <td class="mono px-4 py-3 text-sm">{{ $fila['estado'] === 'atendido' ? number_format($fila['litros'], 1).' L' : '—' }}</td>
                        <td class="px-4 py-3 text-sm">
                            {{ $fila['ultimo']?->jornada?->usuario?->nombres ?? '—' }}
                            @if ($fila['ultimo'])
                                <span class="mono block text-xs text-eh-text-muted">{{ $fila['ultimo']->registrado_en->timezone('America/Lima')->format('H:i') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($fila['estado'] === 'no_acopiado' && $puedeAnular)
                                <x-ui.btn variant="outline" size="sm" data-modal-open="deshacer-marca-{{ $fila['marca']->id }}">Revisar marca</x-ui.btn>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="p-4">{{ $hoja['filas']->links() }}</div>
        @endif
    </x-ui.card>

    @if ($puedeGestionar)
        @if ($abierta)
            <x-ui.modal id="cerrar-jornada" title="Terminar jornada">
                <form method="POST" action="{{ route('admin.acopiadores.jornadas.cerrar', $jornada) }}" class="space-y-4" data-once>
                    @csrf
                    @method('PATCH')
                    <p class="text-sm">Se registrará la hora actual. Conserva los {{ $entregas }} recojos y {{ number_format($litros, 1) }} L recibidos. El acopiador verá el cierre al sincronizar.</p>
                    <x-ui.field name="motivo" id="motivo-cierre" label="Motivo del cierre desde administración" required minlength="5" maxlength="255" />
                    <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit" variant="danger">Confirmar cierre</x-ui.btn></div>
                </form>
            </x-ui.modal>

            <x-ui.modal id="agregar-recojo" title="Agregar recojo">
                <form method="POST" action="{{ route('admin.acopiadores.entregas.store', $jornada) }}" class="space-y-4" data-once>
                    @csrf
                    <p class="text-sm text-eh-text-muted">Se registra a nombre de esta jornada y queda marcado como «Panel web». Revisa antes que no esté ya en la lista.</p>
                    <x-ui.select name="proveedor_id" label="Proveedor" required placeholder="Selecciona un proveedor" :options="$proveedores->mapWithKeys(fn ($p) => [$p->id => $p->codigo.' · '.$p->nombres])->all()" />
                    <x-ui.field name="litros" label="Litros" type="number" min="0.1" max="18500" step="0.1" inputmode="decimal" unit="L" required />
                    <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Guardar recojo</x-ui.btn></div>
                </form>
            </x-ui.modal>
        @endif

        @foreach ($recientes as $entrega)
            @if (! $entrega->anulada)
                <x-ui.modal id="corregir-{{ $entrega->id }}" title="Corregir recojo">
                    <form method="POST" action="{{ route('admin.acopiadores.entregas.corregir', $entrega) }}" class="space-y-4" data-once>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="revision" value="{{ $entrega->revisionMovil() }}">
                        <p class="text-sm">{{ $entrega->proveedor?->nombres }}. La corrección llegará al celular al sincronizar.</p>
                        <x-ui.field name="litros" id="litros-{{ $entrega->id }}" label="Litros correctos" type="number" step="0.1" min="0.1" max="18500" inputmode="decimal" :value="number_format((float) $entrega->litros, 1, '.', '')" required unit="L" />
                        <x-ui.field name="motivo" id="motivo-correccion-{{ $entrega->id }}" label="Motivo de la corrección" required minlength="5" maxlength="255" />
                        <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Guardar corrección</x-ui.btn></div>
                    </form>
                </x-ui.modal>
                @if ($puedeAnular)
                    <x-ui.modal id="anular-{{ $entrega->id }}" title="Anular recojo">
                        <form method="POST" action="{{ route('admin.acopiadores.entregas.anular', $entrega) }}" class="space-y-4" data-once>
                            @csrf
                            <p class="text-sm">Se conservará el registro como anulado y dejará de sumar litros.</p>
                            <x-ui.field name="motivo" id="motivo-anulacion-{{ $entrega->id }}" label="Motivo de la anulación" required minlength="5" maxlength="255" />
                            <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit" variant="danger">Anular recojo</x-ui.btn></div>
                        </form>
                    </x-ui.modal>
                @endif
            @endif
        @endforeach

        @if ($puedeAnular)
            @foreach ($hoja['filas'] as $fila)
                @if ($fila['estado'] === 'no_acopiado')
                    <x-ui.modal id="deshacer-marca-{{ $fila['marca']->id }}" title="Revisar no acopiado">
                        <form method="POST" action="{{ route('admin.acopiadores.marcas.deshacer', $fila['marca']) }}" class="space-y-4" data-once>
                            @csrf
                            @method('PATCH')
                            <p class="text-sm">{{ $fila['proveedor']->nombres }}. Deshacer conserva el historial y devuelve el proveedor a pendiente si no tiene recojos.</p>
                            <x-ui.field name="motivo" id="motivo-marca-{{ $fila['marca']->id }}" label="Motivo de la revisión" required minlength="5" maxlength="255" />
                            <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Deshacer marca</x-ui.btn></div>
                        </form>
                    </x-ui.modal>
                @endif
            @endforeach
        @endif
    @endif

    @if ($puedeRecepcion && $litros > 0 && ! ($abierta && $recepcion === null))
        @include('admin.recepcion._llegada-modal', [
            'jornadaId' => $jornada->id,
            'viaje' => ($jornada->usuario?->nombres ?? 'Sin cuenta').' · '.($jornada->zona?->nombre ?? 'Sin zona'),
            'litrosRecolectados' => $litros,
            'recepcion' => $recepcion,
            'volver' => 'jornada',
        ])
        @include('admin.recepcion._llegada-script')
    @endif

    @if ($puedeResolverReclamos)
        @foreach ($reclamosPendientes as $reclamo)
            <x-ui.modal :id="'reclamo-'.$reclamo->id" title="Responder reclamo">
                <form method="POST" action="{{ route('admin.sanciones.reclamos.resolver', $reclamo) }}" class="space-y-4" data-once>
                    @csrf
                    @method('PATCH')
                    <div class="space-y-2 rounded-xl bg-eh-surface-alt p-4 text-sm">
                        <div class="flex justify-between"><span class="text-eh-text-muted">Proveedor</span><span class="font-medium">{{ $reclamo->proveedor?->nombres ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-eh-text-muted">Litros registrados</span><span class="mono font-bold">{{ number_format((float) $reclamo->litros_originales, 1) }} L</span></div>
                        <div class="flex justify-between"><span class="text-eh-text-muted">Litros que pide</span><span class="mono font-bold text-eh-primary">{{ number_format((float) $reclamo->litros_solicitados, 1) }} L</span></div>
                    </div>
                    <x-ui.alert type="warning">Aprobar cambia los litros del recojo y recalcula su liquidación pendiente. La decisión queda en auditoría.</x-ui.alert>
                    <x-ui.select name="estado" id="estado-reclamo-{{ $reclamo->id }}" label="Decisión" required
                        :options="['resuelto' => 'Aprobar el ajuste de litros', 'rechazado' => 'Rechazar el reclamo']" />
                    <x-ui.field name="respuesta" id="respuesta-reclamo-{{ $reclamo->id }}" label="Respuesta al proveedor" required maxlength="500" />
                    <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Guardar decisión</x-ui.btn></div>
                </form>
            </x-ui.modal>
        @endforeach
    @endif
@endsection
