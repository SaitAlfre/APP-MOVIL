<div class="border-b border-eh-border px-4 py-3 text-sm text-eh-text-muted">
    El personal reporta el hallazgo y administración confirma o descarta el caso. Solo las infracciones confirmadas cuentan para el castigo y el retiro.
</div>
@if ($incidencias->isEmpty())
    <x-ui.empty icon="check" title="No hay casos con estos filtros" description="Abre el detalle de un análisis para reportar un posible caso de adulteración." />
@else
    <x-ui.table :headers="['Proveedor', 'Muestra y técnico', 'Caso', 'Infracción', 'Castigo semanal', '']" caption="Casos de adulteración y castigos semanales">
        @foreach ($incidencias as $caso)
            @php
                $tonoCaso = ['pendiente' => 'yellow', 'confirmada' => 'red', 'descartada' => 'gray'][$caso->estado] ?? 'gray';
                $etiquetaCaso = ['pendiente' => 'Por revisar', 'confirmada' => 'Confirmada', 'descartada' => 'Descartada'][$caso->estado] ?? $caso->estado;
            @endphp
            <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                <td class="px-4 py-3">
                    <p class="text-sm font-medium text-eh-text">{{ $caso->proveedor?->nombres ?? 'Proveedor' }}</p>
                    <p class="text-xs text-eh-text-muted">{{ $caso->zona?->nombre ?? 'Sin zona' }}</p>
                </td>
                <td class="px-4 py-3 text-xs">
                    <p class="mono text-eh-text">{{ $caso->analisis?->codigo_muestra ?? '—' }}</p>
                    <p class="text-eh-text-muted">{{ $caso->registradoPor?->nombres ?? '—' }}</p>
                </td>
                <td class="px-4 py-3"><x-ui.badge :variant="$tonoCaso" :label="$etiquetaCaso" /></td>
                <td class="px-4 py-3 text-sm text-eh-text">
                    @if ($caso->numero_infraccion)
                        {{ $caso->numero_infraccion }} de 3
                        @if ($caso->numero_infraccion >= 3)<span class="block text-xs text-eh-red">Retiro por reincidencia</span>@endif
                    @else
                        <span class="text-xs text-eh-text-muted">No suma al historial</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-eh-text">
                    @if ($caso->estado === 'confirmada')
                        <p class="mono font-medium">S/ {{ number_format((float) $caso->descuento_litro, 2) }} por litro</p>
                        <p class="mt-1 text-eh-text-muted">{{ $caso->semana_inicio?->format('d/m/Y') }} al {{ $caso->semana_fin?->format('d/m/Y') }}</p>
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    @if ($caso->analisis)
                        <x-ui.btn :href="route('admin.calidad.show', $caso->analisis->uuid).'#caso-adulteracion'" variant="ghost" size="sm">{{ $caso->estado === 'pendiente' ? 'Revisar caso' : 'Ver decisión' }}</x-ui.btn>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-ui.table>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
        <span>Mostrando {{ $incidencias->count() }} de {{ $incidencias->total() }} casos</span>
        <div>{{ $incidencias->onEachSide(1)->links() }}</div>
    </div>
@endif
