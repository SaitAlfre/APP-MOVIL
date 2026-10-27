@extends('layouts.admin')
@section('titulo', 'Jornada de acopio')
@section('contenido')
    @php $puedeGestionar = auth('operador')->user()->puede('acopiadores', 'gestionar'); $abierta = $jornada->cerrada_en === null; $duplicado = session('duplicado');
@endphp

    <x-ui.page-header :title="'Jornada de '.$jornada->usuario?->nombres" :description="($jornada->zona?->nombre ?? 'Sin zona').' · Placa '.($jornada->vehiculo?->placa ?? 'Sin vehículo').' · '.$jornada->fecha->format('d/m/Y')">

<x-slot:actions>

            <x-ui.btn :href="route('admin.jornadas.index')" variant="outline" icon="arrowLeft">Volver a jornadas</x-ui.btn>
            @if($abierta && $puedeGestionar)<x-ui.btn data-modal-open="cerrar-jornada" variant="danger">Terminar jornada</x-ui.btn>
@endif


</x-slot:actions>

    </x-ui.page-header>
    <div class="mb-5 grid gap-3 sm:grid-cols-4">
        <x-ui.card padding="p-4"><p class="text-sm text-eh-text-muted">Litros de esta jornada</p><p class="mono text-2xl font-bold text-eh-primary">{{ number_format($litros, 1) }} L</p></x-ui.card>
        <x-ui.card padding="p-4"><p class="text-sm text-eh-text-muted">Recojos registrados</p><p class="mono text-2xl font-bold">{{ $entregas }}</p></x-ui.card>
        <x-ui.card padding="p-4"><p class="text-sm text-eh-text-muted">Inicio (Perú)</p><p class="mono text-xl font-semibold">{{ $jornada->abierta_en?->timezone('America/Lima')->format('H:i') }}</p></x-ui.card>
        <x-ui.card padding="p-4"><p class="text-sm text-eh-text-muted">{{ $abierta ? 'Estado' : 'Final (Perú)' }}</p><p class="text-xl font-semibold">{{ $jornada->cerrada_en?->timezone('America/Lima')->format('H:i') ?? 'En curso' }}</p></x-ui.card>
    </div>
    <x-ui.alert type="info" class="mb-5">Esta página muestra lo recibido por el servidor. Un teléfono sin internet puede tener registros pendientes de enviar. Las correcciones y anulaciones se registran con su motivo y se descargan en el móvil.</x-ui.alert>
    @if($duplicado)
        <x-ui.alert type="warning" title="Ya existe un recojo de este proveedor" class="mb-4">
            <p>Ya hay {{ number_format($duplicado['litros_existentes'], 1) }} L. Confirma si es un segundo recojo real.</p>
            <form method="POST" action="{{ route('admin.acopiadores.entregas.store', $jornada) }}" class="mt-3" data-once>
@csrf

                <input type="hidden" name="proveedor_id" value="{{ old('proveedor_id') }}"><input type="hidden" name="litros" value="{{ $duplicado['litros_nuevos'] }}"><input type="hidden" name="forzar" value="1"><input type="hidden" name="observaciones" value="{{ old('observaciones') }}">
                <x-ui.btn type="submit" size="sm">Confirmar segundo recojo</x-ui.btn>
            </form>
        </x-ui.alert>

@endif

    <x-ui.card class="mb-5">
        <div class="border-b border-eh-border p-4"><h2 class="font-semibold">Hoja de ruta</h2><p class="mt-1 text-sm text-eh-text-muted">{{ $hoja['atendidos'] }} de {{ $hoja['total'] }} atendidos · {{ $hoja['pendientes'] }} pendientes · {{ $hoja['noAcopiados'] }} no acopiados. Incluye el apoyo de otros acopiadores de la zona.</p>
            @foreach($hoja['rutas'] as $ruta)<a class="mt-2 inline-block text-sm font-semibold text-eh-primary underline focus-visible:ring-2" href="{{ route('admin.mapa-rutas.show', $ruta) }}">{{ $ruta->nombre }}</a>
@endforeach

        </div>
        @if($hoja['filas']->isEmpty())<x-ui.empty title="Sin proveedores en esta ruta" description="Revisa la zona y las paradas asignadas al acopiador." />

@else

            <x-ui.table :headers="['Proveedor', 'Estado del recojo', 'Litros del día', 'Registrado por', '']" caption="Proveedores de la ruta y su estado del día">
                @foreach($hoja['filas'] as $fila)
                    <tr class="border-b border-eh-border last:border-0">
                        <td class="px-4 py-3"><p class="text-sm font-semibold">{{ $fila['numero'] }}. {{ $fila['proveedor']->nombres }}</p><p class="text-xs text-eh-text-muted">{{ $fila['proveedor']->codigo }}</p></td>
                        <td class="px-4 py-3 text-sm">{{ ['atendido' => 'Atendido', 'pendiente' => 'Pendiente', 'no_acopiado' => 'No acopiado'][$fila['estado']] }}@if($fila['estado'] === 'no_acopiado')<p class="text-xs text-eh-text-muted">{{ $fila['marca']->detalle ?? str_replace('_', ' ', $fila['marca']->motivo) }}</p>
@endif
</td>
                        <td class="mono px-4 py-3 text-sm">{{ $fila['estado'] === 'atendido' ? number_format($fila['litros'], 1).' L' : '—' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $fila['ultimo']?->jornada?->usuario?->nombres ?? '—' }}@if($fila['ultimo'])<span class="mono block text-xs text-eh-text-muted">{{ $fila['ultimo']->registrado_en->timezone('America/Lima')->format('H:i') }}</span>
@endif
</td>
                        <td class="px-4 py-3">@if($fila['estado'] === 'no_acopiado' && $puedeGestionar)<x-ui.btn variant="outline" size="sm" data-modal-open="deshacer-marca-{{ $fila['marca']->id }}">Revisar marca</x-ui.btn>
@endif
</td>
                    </tr>

@endforeach

            </x-ui.table>
            <div class="p-4">{{ $hoja['filas']->links() }}</div>

@endif

    </x-ui.card>
    <div class="grid gap-5 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <h2 class="border-b border-eh-border p-4 font-semibold">Recojos de esta jornada</h2>
            @if($recientes->isEmpty())<x-ui.empty title="Todavía no hay recojos recibidos" description="Aparecerán cuando el teléfono los envíe o se registre un recojo desde este panel." />

@else

                <x-ui.table :headers="['Proveedor', 'Litros', 'Hora (Perú)', 'Estado', '']" caption="Recojos de la jornada">
                    @foreach($recientes as $entrega)<tr class="border-b border-eh-border last:border-0">
                        <td class="px-4 py-3 text-sm">{{ $entrega->proveedor?->nombres ?? 'Proveedor' }}</td><td class="mono px-4 py-3 text-sm font-semibold">{{ number_format($entrega->litros, 1) }} L</td><td class="mono px-4 py-3 text-sm">{{ $entrega->registrado_en->timezone('America/Lima')->format('H:i') }}</td><td class="px-4 py-3 text-sm">{{ $entrega->anulada ? 'Anulado' : 'Registrado' }}</td>
                        <td class="px-4 py-3">@if($puedeGestionar && !$entrega->anulada)<div class="flex gap-2"><x-ui.btn variant="outline" size="sm" data-modal-open="corregir-{{ $entrega->id }}">Corregir</x-ui.btn><x-ui.btn variant="ghost" size="sm" data-modal-open="anular-{{ $entrega->id }}">Anular</x-ui.btn></div>
@endif
</td>
                    </tr>
@endforeach

                </x-ui.table><div class="p-4">{{ $recientes->links() }}</div>

@endif

        </x-ui.card>
        <x-ui.card padding="p-5">
            <h2 class="mb-3 font-semibold">Registrar recojo de apoyo</h2>
            @if($abierta && $puedeGestionar)
                <p class="mb-4 text-sm text-eh-text-muted">Úsalo cuando debas registrar en nombre del acopiador. Evita volver a registrar lo que ya ingresó desde su teléfono.</p>
                <form method="POST" action="{{ route('admin.acopiadores.entregas.store', $jornada) }}" class="space-y-4" data-once>
@csrf

                    <x-ui.select name="proveedor_id" label="Proveedor" required placeholder="Selecciona un proveedor" :options="$proveedores->pluck('nombres', 'id')->all()" />
                    <x-ui.field name="litros" label="Litros recibidos" type="number" min="0.01" max="18500" step="0.01" unit="L" required />
                    <x-ui.field name="observaciones" label="Observación (opcional)" maxlength="255" />
                    <x-ui.btn type="submit">Guardar recojo</x-ui.btn>
                </form>

@else
<p class="text-sm text-eh-text-muted">{{ $abierta ? 'Tu perfil permite consultar esta jornada.' : 'Esta jornada terminó. Puedes revisar los recojos y realizar correcciones autorizadas con motivo.' }}</p>
@endif

        </x-ui.card>
    </div>
    @if($puedeGestionar)
        @if($abierta)<x-ui.modal id="cerrar-jornada" title="Terminar jornada">
            <form method="POST" action="{{ route('admin.acopiadores.jornadas.cerrar', $jornada) }}" class="space-y-4" data-once>
@csrf
 @method('PATCH')
                <p class="text-sm">Se registrará la hora actual. Conserva los {{ $entregas }} recojos y {{ number_format($litros, 1) }} L recibidos. El acopiador verá el cierre al sincronizar.</p>
                <x-ui.field name="motivo" id="motivo-cierre" label="Motivo del cierre desde administración" required minlength="5" maxlength="255" />
                <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit" variant="danger">Confirmar cierre</x-ui.btn></div>
            </form>
        </x-ui.modal>
@endif

        @foreach($recientes as $entrega)@if(!$entrega->anulada)
            <x-ui.modal id="corregir-{{ $entrega->id }}" title="Corregir recojo">
                <form method="POST" action="{{ route('admin.acopiadores.entregas.corregir', $entrega) }}" class="space-y-4" data-once>
@csrf
 @method('PATCH')
                    <input type="hidden" name="revision" value="{{ $entrega->revisionMovil() }}">
                    <p class="text-sm">{{ $entrega->proveedor?->nombres }}. La corrección llegará al móvil al sincronizar.</p>
                    <x-ui.field name="litros" id="litros-{{ $entrega->id }}" label="Litros correctos" type="number" step="0.01" min="0.01" max="18500" :value="$entrega->litros" required unit="L" />
                    <x-ui.field name="observaciones" id="observaciones-{{ $entrega->id }}" label="Observación (opcional)" :value="$entrega->observaciones" maxlength="255" />
                    <x-ui.field name="motivo" id="motivo-correccion-{{ $entrega->id }}" label="Motivo de la corrección" required minlength="5" maxlength="255" />
                    <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Guardar corrección</x-ui.btn></div>
                </form>
            </x-ui.modal>
            <x-ui.modal id="anular-{{ $entrega->id }}" title="Anular recojo">
                <form method="POST" action="{{ route('admin.acopiadores.entregas.anular', $entrega) }}" class="space-y-4" data-once>
@csrf

                    <p class="text-sm">Se conservará el registro como anulado y dejará de sumar litros.</p>
                    <x-ui.field name="motivo" id="motivo-anulacion-{{ $entrega->id }}" label="Motivo de la anulación" required minlength="5" maxlength="255" />
                    <div class="flex flex-wrap items-center justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit" variant="danger">Anular recojo</x-ui.btn></div>
                </form>
            </x-ui.modal>

@endif

@endforeach

        @foreach($hoja['filas'] as $fila)@if($fila['estado'] === 'no_acopiado')
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

@endsection
