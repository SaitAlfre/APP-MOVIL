                                <tr data-sale-line="{{ $indice }}" class="border-b border-eh-border last:border-0">
                                    <td class="px-4 py-2">
                                        <label class="sr-only" for="venta-producto-{{ $indice }}">Producto de la fila {{ is_numeric($indice) ? $indice + 1 : '__NUMBER__' }}</label>
                                        <select id="venta-producto-{{ $indice }}" name="productos[{{ $indice }}][producto_id]" data-sale-product @if ($indice === 0) required @endif
                                            class="w-full rounded-lg border border-eh-border bg-eh-surface px-2 py-1 text-sm text-eh-text focus:border-eh-primary focus:outline-none">
                                            <option value="">Seleccionar…</option>
                                            @foreach ($productos as $producto)
                                                <option value="{{ $producto->id }}" data-precio-cliente="{{ $producto->precio_cliente }}" data-precio-mayorista="{{ $producto->precio_mayorista }}" data-precio-proveedor="{{ $producto->precio_proveedor }}">{{ $producto->nombre }} ({{ number_format((float) $producto->existencia, 2) }} {{ $producto->unidad_produccion }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <label class="sr-only" for="venta-cantidad-{{ $indice }}">Cantidad de la fila {{ is_numeric($indice) ? $indice + 1 : '__NUMBER__' }}</label>
                                        <input id="venta-cantidad-{{ $indice }}" name="productos[{{ $indice }}][cantidad]" data-sale-quantity type="number" min="0.001" step="0.001" @if ($indice === 0) required @endif
                                            class="w-24 rounded-lg border border-eh-border bg-eh-surface px-2 py-1 text-sm text-eh-text focus:border-eh-primary focus:outline-none">
                                    </td>
                                    <td class="px-4 py-2">
                                        <label class="sr-only" for="venta-precio-{{ $indice }}">Precio unitario de la fila {{ is_numeric($indice) ? $indice + 1 : '__NUMBER__' }}</label>
                                        <input id="venta-precio-{{ $indice }}" data-sale-price readonly tabindex="-1" aria-label="Precio unitario de la fila {{ is_numeric($indice) ? $indice + 1 : '__NUMBER__' }}" class="w-28 rounded-lg border-0 bg-eh-surface-alt px-3 py-2 font-semibold text-eh-primary" placeholder="—">
                                        <span data-sale-price-error class="mt-1 block text-xs text-eh-red" hidden>Precio sin configurar</span>
                                    </td>
                                    <td class="px-2 py-2"><button type="button" data-sale-remove aria-label="Quitar producto" class="rounded-lg p-2 text-eh-text-muted hover:bg-eh-red-soft hover:text-eh-red disabled:opacity-30"><x-icon name="xMark" class="h-4 w-4" /></button></td>
                                </tr>
