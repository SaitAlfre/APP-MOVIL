@extends('layouts.admin')

@section('titulo', 'Design System')

@section('contenido')
    @php
        $colores = [
            ['nombre' => 'Verde principal', 'hex' => '#2E7D46'],
            ['nombre' => 'Verde oscuro', 'hex' => '#1E5631'],
            ['nombre' => 'Verde claro', 'hex' => '#E8F4EC', 'borde' => true],
            ['nombre' => 'Fondo crema', 'hex' => '#F7F6F1', 'borde' => true],
            ['nombre' => 'Texto principal', 'hex' => '#25342C'],
            ['nombre' => 'Texto secundario', 'hex' => '#66736C'],
            ['nombre' => 'Azul informativo', 'hex' => '#3783B5'],
            ['nombre' => 'Amarillo advertencia', 'hex' => '#E8B339'],
            ['nombre' => 'Rojo error', 'hex' => '#C94A4A'],
            ['nombre' => 'Gris bordes', 'hex' => '#DDE4DF', 'borde' => true],
        ];

        $iconos = ['home', 'users', 'truck', 'map', 'droplets', 'beaker', 'banknotes', 'factory', 'cube', 'shoppingCart',
            'megaphone', 'chartBar', 'arrowUpTray', 'shield', 'cog', 'logout', 'plus', 'pencil', 'trash', 'eye',
            'check', 'xMark', 'exclamation', 'info', 'search', 'filter', 'download', 'printer', 'bell', 'userCircle',
            'clipboardList', 'qrCode', 'play', 'stop', 'clock', 'arrowPath', 'documentText', 'camera', 'tableCells'];
    @endphp

    <x-ui.page-header title="Design System" description="Componentes y tokens visuales de Ecolactea Digital" />

    <div class="space-y-8">
        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Paleta de colores</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($colores as $color)
                    <div class="flex flex-col gap-2">
                        <div class="h-14 rounded-xl {{ ($color['borde'] ?? false) ? 'border border-eh-border' : '' }}" style="background: {{ $color['hex'] }}"></div>
                        <div>
                            <p class="text-xs font-medium text-eh-text">{{ $color['nombre'] }}</p>
                            <p class="mono text-xs text-eh-text-muted">{{ $color['hex'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-eh-text-muted">
                En modo oscuro cada token cambia de valor manteniendo el mismo nombre, por eso las pantallas se ven correctas en ambos temas.
            </p>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Tipografía</h2>
            <x-ui.card padding="p-6" class="space-y-4">
                <p class="text-2xl font-bold text-eh-text">Título principal — Instrument Sans 700</p>
                <p class="text-xl font-semibold text-eh-text">Subtítulo — Instrument Sans 600</p>
                <p class="text-base font-medium text-eh-text">Cuerpo destacado — Instrument Sans 500</p>
                <p class="text-sm text-eh-text">Texto normal — Instrument Sans 400</p>
                <p class="text-xs text-eh-text-muted">Texto pequeño / Etiqueta — Instrument Sans 400</p>
                <p class="mono text-sm text-eh-text">Datos numéricos: 1,234.56 L · JetBrains Mono 400</p>
                <p class="mono text-lg font-semibold text-eh-primary">Total: S/ 597.40 — Mono 600</p>
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Botones</h2>
            <x-ui.card padding="p-6">
                <div class="flex flex-wrap gap-2">
                    <x-ui.btn>Primario</x-ui.btn>
                    <x-ui.btn variant="secondary">Secundario</x-ui.btn>
                    <x-ui.btn variant="accent">Acento</x-ui.btn>
                    <x-ui.btn variant="ghost">Ghost</x-ui.btn>
                    <x-ui.btn variant="danger">Peligro</x-ui.btn>
                    <x-ui.btn variant="outline">Contorno</x-ui.btn>
                    <x-ui.btn icon="plus">Con icono</x-ui.btn>
                    <x-ui.btn disabled>Deshabilitado</x-ui.btn>
                    <x-ui.btn size="sm" icon="download">Pequeño</x-ui.btn>
                </div>
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Insignias de estado</h2>
            <x-ui.card padding="p-6">
                <div class="flex flex-wrap gap-2">
                    @foreach (['activo', 'inactivo', 'pendiente', 'aprobado', 'observado', 'rechazado', 'abierta', 'cerrada', 'pagada', 'anulada', 'en_proceso', 'finalizado'] as $estado)
                        <x-ui.estado :estado="$estado" />
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-eh-text-muted">
                    El color nunca comunica el estado por sí solo: cada insignia lleva siempre su texto.
                </p>
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Tarjetas de indicador</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <x-ui.kpi label="Litros hoy" value="1,340" unit="L" icon="droplets" color="green" trend="8% vs ayer" />
                <x-ui.kpi label="Proveedores atendidos" value="24" unit="de 34" icon="userCircle" color="blue" />
                <x-ui.kpi label="Controles pendientes" value="3" icon="beaker" color="yellow" />
                <x-ui.kpi label="Liquidaciones pendientes" value="3" icon="banknotes" color="red" trend="2 más que ayer" :trend-up="false" />
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Formularios</h2>
            <x-ui.card padding="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.field name="ds_nombre" id="ds_nombre" label="Nombre completo" placeholder="Juan Pérez Quispe" />
                    <x-ui.field name="ds_litros" id="ds_litros" label="Litros" type="number" unit="L" placeholder="0.0" />
                    <x-ui.select name="ds_estado" id="ds_estado" label="Estado" placeholder="Seleccionar…" :options="['activo' => 'Activo', 'inactivo' => 'Inactivo']" />
                    <x-ui.field name="ds_error" id="ds_error" label="Campo con error" error="Este campo es obligatorio." />
                    <div class="flex flex-col gap-1 md:col-span-2">
                        <label for="ds_textarea" class="text-sm font-medium text-eh-text">Observaciones</label>
                        <textarea id="ds_textarea" rows="2" placeholder="Textarea"
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary"></textarea>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 md:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-eh-text">
                            <input type="checkbox" checked class="rounded border-eh-border text-eh-primary focus:ring-eh-primary"> Checkbox activo
                        </label>
                        <label class="flex items-center gap-2 text-sm text-eh-text">
                            <input type="checkbox" class="rounded border-eh-border text-eh-primary focus:ring-eh-primary"> Checkbox inactivo
                        </label>
                        <label class="flex items-center gap-2 text-sm text-eh-text">
                            <input type="radio" name="ds_radio" checked class="border-eh-border text-eh-primary focus:ring-eh-primary"> Radio A
                        </label>
                        <label class="flex items-center gap-2 text-sm text-eh-text">
                            <input type="radio" name="ds_radio" class="border-eh-border text-eh-primary focus:ring-eh-primary"> Radio B
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.search name="ds_buscar" id="ds_buscar" label="Buscar" placeholder="Buscar proveedor, código o DNI…" />
                    </div>
                </div>
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Iconos (Heroicons lineales)</h2>
            <x-ui.card padding="p-6">
                <div class="grid grid-cols-4 gap-4 sm:grid-cols-8 lg:grid-cols-13">
                    @foreach ($iconos as $icono)
                        <div class="flex flex-col items-center gap-1.5 text-eh-text-muted" title="{{ $icono }}">
                            <x-icon :name="$icono" class="h-5 w-5" />
                            <span class="mono truncate text-[9px]">{{ $icono }}</span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Alertas</h2>
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <x-ui.alert type="success">Proveedor registrado correctamente.</x-ui.alert>
                <x-ui.alert type="error" title="Revisa los datos ingresados.">El DNI ya está registrado en otro proveedor.</x-ui.alert>
                <x-ui.alert type="warning">Esta acción es irreversible: la jornada quedará cerrada.</x-ui.alert>
                <x-ui.alert type="info">La zona filtra litros recolectados, recibidos y merma.</x-ui.alert>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Diálogos</h2>
            <x-ui.card padding="p-6">
                <x-ui.btn type="button" data-modal-open="ds-modal">Abrir diálogo de confirmación</x-ui.btn>
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Tabla y estado vacío</h2>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-ui.card>
                    <x-ui.table :headers="['Proveedor', 'Litros', 'Estado']" caption="Ejemplo de tabla">
                        @foreach ([['Celestino Apaza', '48.5', 'aprobado'], ['Felipa Mamani', '32.0', 'observado'], ['Juan Lope', '45.0', 'rechazado']] as $fila)
                            <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                                <td class="px-4 py-3 text-sm font-medium text-eh-text">{{ $fila[0] }}</td>
                                <td class="mono px-4 py-3 text-sm text-eh-text">{{ $fila[1] }} L</td>
                                <td class="px-4 py-3"><x-ui.estado :estado="$fila[2]" /></td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.empty icon="droplets" title="Sin entregas registradas"
                        description="Cuando el acopiador registre una entrega aparecerá aquí.">
                        <x-slot:action>
                            <x-ui.btn size="sm" icon="plus">Registrar entrega</x-ui.btn>
                        </x-slot:action>
                    </x-ui.empty>
                </x-ui.card>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-eh-text-muted">Gráficos</h2>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-ui.card padding="p-4">
                    <h3 class="mb-4 text-sm font-semibold text-eh-text">Barras verticales</h3>
                    <x-ui.chart-bars :height="160" unit="L" description="Ejemplo de gráfico de barras."
                        :data="[
                            ['label' => 'Jue', 'value' => 1120],
                            ['label' => 'Vie', 'value' => 1240],
                            ['label' => 'Sáb', 'value' => 980],
                            ['label' => 'Dom', 'value' => 1310],
                            ['label' => 'Lun', 'value' => 1180],
                            ['label' => 'Mar', 'value' => 1340],
                        ]" />
                </x-ui.card>
                <x-ui.card padding="p-4">
                    <h3 class="mb-4 text-sm font-semibold text-eh-text">Distribución (dona)</h3>
                    <x-ui.chart-donut :size="150" description="Ejemplo de distribución por resultado."
                        :data="[
                            ['label' => 'Aprobado', 'value' => 72, 'color' => '#2E7D46'],
                            ['label' => 'Observado', 'value' => 20, 'color' => '#E8B339'],
                            ['label' => 'Rechazado', 'value' => 8, 'color' => '#C94A4A'],
                        ]" />
                </x-ui.card>
            </div>
        </section>
    </div>

    <x-ui.modal id="ds-modal" title="Confirmar acción" size="sm">
        <p class="text-sm text-eh-text-muted">
            Así se ven los diálogos de confirmación del sistema. Siempre explican el efecto de la acción antes de ejecutarla.
        </p>
        <div class="mt-4 flex justify-end gap-2">
            <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
            <x-ui.btn type="button" data-modal-close>Confirmar</x-ui.btn>
        </div>
    </x-ui.modal>
@endsection
