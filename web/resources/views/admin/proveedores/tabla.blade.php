            <x-ui.table :headers="['Código', 'Nombre', 'DNI', 'Zona', 'Estado', 'Litros sem.', 'Última entrega', 'Cuenta', 'Acciones']"
                caption="Listado de proveedores registrados">
                @foreach ($proveedores as $proveedor)
                    @php
                        $resumen = $resumenSemana[$proveedor->id] ?? ['litros' => 0.0, 'ultima' => null];
                        $cuenta = $proveedor->usuarioId !== null ? $cuentas->get($proveedor->usuarioId) : null;
                    @endphp
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3"><span class="mono text-xs font-medium text-eh-text">{{ $proveedor->codigo }}</span></td>
                        <td class="px-4 py-3 font-medium text-eh-text">
                            <a href="{{ route('admin.proveedores.show', $proveedor->id) }}" class="hover:text-eh-primary hover:underline">{{ $proveedor->nombres }}</a>
                        </td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $proveedor->dni }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $zonas->get($proveedor->zonaId)?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$proveedor->estado->value" /></td>
                        <td class="mono px-4 py-3 font-medium text-eh-text">{{ number_format($resumen['litros'], 1) }} L</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">
                            {{ $resumen['ultima'] ? \Illuminate\Support\Carbon::parse($resumen['ultima'])->format('d/m/Y H:i') : 'Sin entregas' }}
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if ($cuenta)
                                <span class="mono text-eh-primary">{{ $cuenta->username }}</span>
                            @else
                                <span class="text-eh-text-muted">Sin cuenta</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div data-row-actions class="flex flex-wrap items-center justify-end gap-1">
                                <a href="{{ route('admin.proveedores.show', $proveedor->id) }}" aria-label="Ver ficha de {{ $proveedor->nombres }}"
                                    class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                    <x-icon name="eye" class="h-4 w-4" /><span>Ver ficha</span>
                                </a>
                                <a href="{{ route('admin.proveedores.qr', $proveedor->id) }}" target="_blank" rel="noopener" aria-label="Código QR de {{ $proveedor->nombres }}"
                                    class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                    <x-icon name="qrCode" class="h-4 w-4" /><span>Ver QR</span>
                                </a>
                                @if ($puedeGestionar)
                                    <a href="{{ route('admin.proveedores.edit', $proveedor->id) }}" aria-label="Editar {{ $proveedor->nombres }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                        <x-icon name="pencil" class="h-4 w-4" /><span>Editar</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
