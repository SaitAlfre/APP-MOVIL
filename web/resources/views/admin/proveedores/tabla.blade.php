@php
    $operadorEstado = auth('operador')->user();
    $puedeCambiarEstado = $operadorEstado->puede('proveedores', 'editar');
    $puedeRetirar = $operadorEstado->puede('proveedores', 'eliminar');
    $opcionEstado = [
        'suspendido' => ['etiqueta' => 'Suspender', 'descripcion' => 'No podrá registrar nuevas entregas.', 'icono' => 'clock'],
        'retirado' => ['etiqueta' => 'Retirar', 'descripcion' => 'Baja lógica; conserva su historial.', 'icono' => 'xMark'],
        'activo' => ['etiqueta' => 'Reactivar', 'descripcion' => 'Vuelve a recibir entregas.', 'icono' => 'arrowPath'],
    ];
    // Transiciones válidas según el estado actual y los permisos (Retirar exige Eliminar).
    $destinosEstado = fn ($proveedor) => array_values(array_filter(
        match ($proveedor->estado->value) { 'activo' => ['suspendido', 'retirado'], 'suspendido' => ['activo', 'retirado'], default => ['activo'] },
        fn ($destino) => $destino === 'retirado' ? $puedeRetirar : $puedeCambiarEstado,
    ));
@endphp
<div tabindex="0" role="region" aria-label="Listado de proveedores registrados" class="min-w-0 overflow-x-auto focus-visible:outline-2 focus-visible:outline-eh-primary">
    <table id="tabla-proveedores" data-export-total="{{ $proveedores->total() }}" class="gestion-table w-full text-left text-sm">
        <caption class="sr-only">Listado de proveedores registrados</caption>
        <thead><tr>
            <th scope="col">Proveedor</th>
            <th scope="col" data-column="dni">DNI</th>
            <th scope="col" data-column="zona">Zona</th>
            <th scope="col" data-column="estado">Estado</th>
            <th scope="col" data-column="litros">Litros sem.</th>
            <th scope="col" data-column="ultima">Última entrega</th>
            <th scope="col" data-column="cuenta">Cuenta</th>
            <th scope="col" data-column="acciones" class="text-right"><span class="sr-only">Acciones</span></th>
        </tr></thead>
        <tbody>
            @foreach($proveedores as $proveedor)
                @php
                    $resumen = $resumenSemana[$proveedor->id] ?? ['litros'=>0.0, 'ultima'=>null];
                    $cuenta = $proveedor->usuarioId !== null ? $cuentas->get($proveedor->usuarioId) : null;
                    $iniciales = collect(preg_split('/\s+/u',trim($proveedor->nombres)))->filter()->take(2)->map(fn($nombre)=>mb_strtoupper(mb_substr($nombre,0,1)))->implode('');
                @endphp
                <tr>
                    <th scope="row" class="max-w-80" data-export-value="{{ $proveedor->nombres }} · {{ $proveedor->codigo }}">
                        <div class="flex min-w-48 items-center gap-3">
                            <span aria-hidden="true" class="gestion-avatar grid h-9 w-9 shrink-0 place-items-center rounded-full bg-eh-primary-soft text-xs font-medium text-eh-primary">{{ $iniciales }}</span>
                            <div class="min-w-0">
                                <a href="{{ route('admin.proveedores.show',$proveedor->id) }}" class="gestion-row-title break-words font-medium hover:text-eh-primary focus-visible:outline-2 focus-visible:outline-eh-primary">{{ $proveedor->nombres }}</a>
                                <p class="gestion-row-subtitle mt-0.5 font-mono text-xs font-normal text-eh-text-muted">{{ $proveedor->codigo }}</p>
                            </div>
                        </div>
                    </th>
                    <td data-column="dni" class="whitespace-nowrap font-mono text-xs">{{ $proveedor->dni }}</td>
                    <td data-column="zona" class="max-w-48 break-words text-eh-text-muted">{{ $zonas->get($proveedor->zonaId)?->nombre ?? '—' }}</td>
                    <td data-column="estado" data-export-value="{{ $proveedor->estado->etiqueta() }}"><x-ui.estado-menu :label="$proveedor->estado->etiqueta()" :variant="match($proveedor->estado->value) {'activo'=>'green','suspendido'=>'red',default=>'gray'}" :nombre="$proveedor->nombres"
                        :opciones="collect($destinosEstado($proveedor))->map(fn ($destino) => [...$opcionEstado[$destino], 'datos' => [
                            'data-estado-rapido' => $destino, 'data-url' => route('admin.proveedores.estado', $proveedor->id), 'data-proveedor' => $proveedor->nombres.' · '.$proveedor->codigo,
                            'data-cuenta' => $cuenta && $cuenta->roles === [\App\Domain\Usuarios\Rol::Proveedor] ? $cuenta->username : '',
                            'aria-haspopup' => 'dialog', 'aria-controls' => 'proveedor-estado-modal']])->all()" /></td>
                    <td data-column="litros" class="whitespace-nowrap tabular-nums">{{ number_format($resumen['litros'],1) }} L</td>
                    <td data-column="ultima" class="whitespace-nowrap text-xs text-eh-text-muted">{{ $resumen['ultima'] ? \Illuminate\Support\Carbon::parse($resumen['ultima'])->format('d/m/Y H:i') : 'Sin entregas' }}</td>
                    <td data-column="cuenta" class="text-xs text-eh-text-muted">@if($cuenta)<p>{{ $cuenta->username }}</p>@endif<x-ui.estado-cuenta :usuario="$cuenta" :productivo="$proveedor->estado->value" /></td>
                    <td data-column="acciones">
                        <div data-row-actions class="flex items-start justify-end gap-1">
                            @if($puedeGestionar)
                                <a href="{{ route('admin.proveedores.edit',$proveedor->id) }}" aria-label="Editar {{ $proveedor->nombres }}" title="Editar proveedor" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon name="pencil" class="h-4 w-4" /><span class="sr-only">Editar</span></a>
                            @endif
                            <details class="gestion-action-menu">
                                <summary aria-label="Acciones de {{ $proveedor->nombres }}" title="Más acciones" class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-lg text-eh-text-muted hover:bg-eh-surface-alt focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon name="ellipsis" class="h-4 w-4" /></summary>
                                <div class="space-y-1 rounded-lg border border-eh-border bg-eh-surface p-1 text-xs">
                                    <a href="{{ route('admin.proveedores.show',$proveedor->id) }}" class="flex min-h-9 items-center gap-2 whitespace-nowrap rounded-md px-3 hover:bg-eh-surface-alt focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon name="eye" class="h-4 w-4" />Ver ficha</a>
                                    <button type="button" data-proveedor-qr data-qr-url="{{ route('admin.proveedores.qr', $proveedor->id) }}" data-qr-nombre="{{ $proveedor->nombres }}" data-qr-codigo="{{ $proveedor->codigo }}" aria-haspopup="dialog" aria-controls="proveedor-qr-modal" aria-label="Ver QR de {{ $proveedor->nombres }}" class="flex min-h-9 items-center gap-2 whitespace-nowrap rounded-md px-3 hover:bg-eh-surface-alt focus-visible:outline-2 focus-visible:outline-eh-primary"><x-icon name="qrCode" class="h-4 w-4" />Ver QR</button>
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
