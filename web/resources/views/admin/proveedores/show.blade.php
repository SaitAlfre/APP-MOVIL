@extends('layouts.admin')

@section('titulo', $proveedor->nombres)

@section('contenido')
    @php
        $usuario = auth('operador')->user();
        $puedeGestionar = $usuario->puede('proveedores', 'gestionar');
        $totalEstimado = $litrosSemana * $precioLitro;

        $pestanas = collect([
            ['key' => 'resumen', 'label' => 'Resumen'],
            ['key' => 'entregas', 'label' => 'Entregas'],
            ['key' => 'calidad', 'label' => 'Calidad'],
            ['key' => 'liquidaciones', 'label' => 'Liquidaciones'],
            ['key' => 'auditoria', 'label' => 'Auditoría'],
        ])->map(fn ($tab) => $tab + ['url' => route('admin.proveedores.show', ['proveedor' => $proveedor->id, 'tab' => $tab['key']])])->all();

        $ultimaEntrega = collect($entregas)->first();
    @endphp

    <x-ui.page-header :title="$proveedor->nombres"
        :description="'Código: '.$proveedor->codigo.' · DNI: '.$proveedor->dni"
        :breadcrumbs="[['label' => 'Proveedores', 'url' => route('admin.proveedores.index')], ['label' => $proveedor->nombres]]">
        <x-slot:actions>
            <x-ui.btn :href="route('admin.proveedores.qr', $proveedor->id)" target="_blank" rel="noopener" variant="ghost" size="sm" icon="qrCode">Ver QR</x-ui.btn>
            @if ($puedeGestionar)
                <x-ui.btn :href="route('admin.proveedores.edit', $proveedor->id)" variant="secondary" size="sm" icon="pencil">Editar</x-ui.btn>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-ui.card padding="p-4">
            <p class="mb-1 text-xs text-eh-text-muted">Litros semana actual</p>
            <p class="mono text-2xl font-bold text-eh-text">{{ number_format($litrosSemana, 1) }} <span class="text-sm font-medium text-eh-text-muted">L</span></p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="mb-1 text-xs text-eh-text-muted">Precio base vigente</p>
            <p class="mono text-2xl font-bold text-eh-text">
                @if ($precioLitro > 0)
                    S/ {{ number_format($precioLitro, 2) }} <span class="text-sm font-medium text-eh-text-muted">/L</span>
                @else
                    <span class="text-sm font-medium text-eh-text-muted">Sin precio configurado</span>
                @endif
            </p>
        </x-ui.card>
        <x-ui.card padding="p-4">
            <p class="mb-1 text-xs text-eh-text-muted">Total estimado de la semana</p>
            <p class="mono text-2xl font-bold text-eh-primary">
                @if ($precioLitro > 0)
                    S/ {{ number_format($totalEstimado, 2) }}
                @else
                    <span class="text-sm font-medium text-eh-text-muted">—</span>
                @endif
            </p>
            <p class="mt-1 text-[11px] text-eh-text-muted">Referencial: la liquidación oficial se calcula al generarla.</p>
        </x-ui.card>
    </div>

    <x-ui.card>
        <x-ui.tabs :tabs="$pestanas" :active="$pestana" class="mb-0 px-2" />

        <div class="px-6 py-6">
            @if ($pestana === 'resumen')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-eh-text-muted">Datos generales</h2>
                        <dl class="space-y-2">
                            @foreach ([
                                'Código' => $proveedor->codigo,
                                'DNI' => $proveedor->dni,
                                'Celular' => $proveedor->telefono ?: '—',
                                'Dirección' => $proveedor->direccion ?: '—',
                                'Zona' => $zona?->nombre ?? '—',
                                'Tachos' => $proveedor->tachos.' × '.number_format($proveedor->capacidadTachoL, 1).' L',
                                'Cuenta vinculada' => $cuenta?->username ?? 'Sin cuenta',
                            ] as $clave => $valor)
                                <div class="flex justify-between gap-4 border-b border-eh-surface-alt pb-2 text-sm">
                                    <dt class="text-eh-text-muted">{{ $clave }}</dt>
                                    <dd class="text-right font-medium text-eh-text">{{ $valor }}</dd>
                                </div>
                            @endforeach
                            <div class="flex justify-between gap-4 border-b border-eh-surface-alt pb-2 text-sm">
                                <dt class="text-eh-text-muted">Estado</dt>
                                <dd><x-ui.estado :estado="$proveedor->estado->value" /></dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-eh-text-muted">Última entrega</h2>
                        @if ($ultimaEntrega)
                            @php $controlUltima = $controles[$ultimaEntrega->id] ?? null; @endphp
                            <div class="rounded-xl bg-eh-surface-alt p-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-eh-primary-soft text-eh-primary">
                                        <x-icon name="droplets" class="h-5 w-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-eh-text">{{ $ultimaEntrega->registradoEn->format('d/m/Y H:i') }}</p>
                                        <p class="text-xs text-eh-text-muted">
                                            <span class="mono">{{ number_format($ultimaEntrega->litros, 1) }} L</span> ·
                                            {{ $ultimaEntrega->tachos }} tacho(s) ·
                                            Calidad: {{ $controlUltima?->resultado->value ? ucfirst($controlUltima->resultado->value) : 'Pendiente' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="rounded-xl bg-eh-surface-alt px-4 py-8 text-center text-sm text-eh-text-muted">Este proveedor todavía no registra entregas.</p>
                        @endif
                    </div>
                </div>

            @elseif ($pestana === 'entregas')
                @if (count($entregas) === 0)
                    <x-ui.empty icon="droplets" title="Sin entregas registradas" description="Cuando el acopiador registre una entrega de este proveedor aparecerá aquí." />
                @else
                    <x-ui.table :headers="['Fecha', 'Litros', 'Tachos', 'Calidad', 'Observaciones']" caption="Últimas entregas del proveedor">
                        @foreach ($entregas as $entrega)
                            @php $control = $controles[$entrega->id] ?? null; @endphp
                            <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                                <td class="px-4 py-3 text-sm text-eh-text">{{ $entrega->registradoEn->format('d/m/Y H:i') }}</td>
                                <td class="mono px-4 py-3 text-sm font-medium text-eh-text">{{ number_format($entrega->litros, 1) }} L</td>
                                <td class="mono px-4 py-3 text-sm text-eh-text-muted">{{ $entrega->tachos }}</td>
                                <td class="px-4 py-3">
                                    @if ($control)
                                        <x-ui.estado :estado="$control->resultado->value" />
                                    @else
                                        <x-ui.badge variant="yellow" label="Pendiente" />
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $entrega->observaciones ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif

            @elseif ($pestana === 'calidad')
                @php $conControl = collect($entregas)->filter(fn ($entrega) => ($controles[$entrega->id] ?? null) !== null); @endphp
                @if ($conControl->isEmpty())
                    <x-ui.empty icon="beaker" title="Sin controles de calidad" description="Las entregas de este proveedor todavía no fueron evaluadas." />
                @else
                    <x-ui.table :headers="['Evaluado', 'Resultado', 'Temperatura', 'Acidez', 'Observaciones']" caption="Controles de calidad del proveedor">
                        @foreach ($conControl as $entrega)
                            @php $control = $controles[$entrega->id]; @endphp
                            <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                                <td class="px-4 py-3 text-sm text-eh-text">{{ $control->evaluadoEn->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3"><x-ui.estado :estado="$control->resultado->value" /></td>
                                <td class="mono px-4 py-3 text-sm text-eh-text">{{ $control->temperaturaC !== null ? number_format($control->temperaturaC, 1).' °C' : '—' }}</td>
                                <td class="mono px-4 py-3 text-sm text-eh-text">{{ $control->acidez !== null ? number_format($control->acidez, 1).' °D' : '—' }}</td>
                                <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $control->observaciones ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif

            @elseif ($pestana === 'liquidaciones')
                @if (count($liquidaciones) === 0)
                    <x-ui.empty icon="banknotes" title="Sin liquidaciones" description="Aún no se ha generado ninguna liquidación para este proveedor." />
                @else
                    <x-ui.table :headers="['Periodo', 'Litros', 'Precio/L', 'Importe', 'Estado', 'Generada']" caption="Liquidaciones del proveedor">
                        @foreach ($liquidaciones as $liquidacion)
                            <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                                <td class="px-4 py-3 text-sm text-eh-text">{{ $liquidacion->periodoInicio->format('d/m/Y') }} — {{ $liquidacion->periodoFin->format('d/m/Y') }}</td>
                                <td class="mono px-4 py-3 text-sm text-eh-text">{{ number_format($liquidacion->litrosTotales, 1) }} L</td>
                                <td class="mono px-4 py-3 text-sm text-eh-text-muted">S/ {{ number_format($liquidacion->precioLitro, 2) }}</td>
                                <td class="mono px-4 py-3 text-sm font-semibold text-eh-text">S/ {{ number_format($liquidacion->montoTotal, 2) }}</td>
                                <td class="px-4 py-3"><x-ui.estado :estado="$liquidacion->estado->value" /></td>
                                <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $liquidacion->generadaEn->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif

            @else
                @if ($auditoria->total() === 0)
                    <x-ui.empty icon="shield" title="Sin movimientos auditados" description="Los cambios sobre este proveedor quedarán registrados aquí." />
                @else
                    <x-ui.table :headers="['Fecha', 'Acción', 'Motivo', 'Usuario']" caption="Auditoría del proveedor">
                        @foreach ($auditoria as $registro)
                            <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                                <td class="px-4 py-3 text-sm text-eh-text">{{ $registro->ocurridoEn->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3"><x-ui.badge variant="blue" :label="ucfirst(str_replace('_', ' ', $registro->accion->value))" /></td>
                                <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $registro->motivo ?: '—' }}</td>
                                <td class="mono px-4 py-3 text-xs text-eh-text-muted">#{{ $registro->usuarioId ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                    <div class="mt-3">{{ $auditoria->appends(['tab' => 'auditoria'])->links() }}</div>
                @endif
            @endif
        </div>
    </x-ui.card>
@endsection
