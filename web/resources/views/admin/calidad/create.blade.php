@extends('layouts.admin')

@section('titulo', 'Nueva prueba LactoScan')

@section('contenido')
    @php
        use App\Domain\Calidad\ParametrosCalidad;

        $zonaInicial = old('zona_id');
        $proveedorInicial = old('proveedor_id');
        $reglasJs = collect(ParametrosCalidad::PARAMETROS)->map(fn ($p, $clave) => [
            'nombre' => $p[0], 'min' => $p[2], 'max' => $p[3], 'referencia' => ParametrosCalidad::referencia($clave),
        ]);
    @endphp

    <x-ui.page-header title="Nueva prueba LactoScan" eyebrow="Control de calidad"
        description="Elige la zona y el proveedor, e ingresa los resultados del analizador. Se califica igual que en la app."
        :breadcrumbs="[['label' => 'Calidad', 'url' => route('admin.calidad.index')], ['label' => 'Nueva prueba']]" />

    <form method="POST" action="{{ route('admin.calidad.store') }}" data-once id="form-analisis">
        @csrf
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <x-ui.card padding="p-6">
                    <h2 class="mb-4 text-sm font-semibold text-eh-text">1 · Zona y proveedor</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.select id="zona_id" name="zona_id" label="Zona" required placeholder="Elige la zona"
                            :options="$zonas->pluck('nombre', 'id')->all()" :selected="$zonaInicial" />
                        <div class="flex flex-col gap-1">
                            <label for="proveedor_id" class="text-[11px] font-semibold text-eh-text">Proveedor <span class="text-eh-red" aria-hidden="true">*</span></label>
                            <select id="proveedor_id" name="proveedor_id" required
                                class="w-full rounded-xl border border-eh-border-strong bg-eh-surface px-3.5 py-2.5 text-[13px] text-eh-text focus:border-eh-sage focus:outline-none focus:ring-[3px] focus:ring-eh-sage/15">
                                <option value="">Primero elige la zona</option>
                                @foreach ($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" data-zona="{{ $proveedor->zona_id }}" @selected((string) $proveedorInicial === (string) $proveedor->id)>
                                        {{ $proveedor->nombres }} · {{ $proveedor->codigo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('proveedor_id')<p class="text-xs text-eh-red">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field name="fecha" label="Fecha del análisis" type="date" required :value="$fecha" />
                        <x-ui.field name="hora" label="Hora" type="time" required :value="$hora" />
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-eh-text">2 · Resultados del analizador</h2>
                        <span class="text-xs text-eh-text-muted"><span id="completados" class="mono">0</span> de {{ count(ParametrosCalidad::PARAMETROS) }} parámetros</span>
                    </div>
                    @error('valores')<x-ui.alert type="error" class="mb-4">{{ $message }}</x-ui.alert>@enderror
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach (ParametrosCalidad::PARAMETROS as $clave => $parametro)
                            <div class="rounded-xl border border-eh-border p-3" data-parametro="{{ $clave }}">
                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                    <label for="valor-{{ $clave }}" class="text-[11px] font-semibold text-eh-text">{{ $parametro[0] }}</label>
                                    <span class="text-[11px] font-semibold" data-marca></span>
                                </div>
                                <div class="relative">
                                    <input id="valor-{{ $clave }}" name="valores[{{ $clave }}]" type="text" inputmode="decimal" autocomplete="off"
                                        value="{{ old('valores.'.$clave) }}" placeholder="{{ $parametro[4] }}"
                                        class="mono w-full rounded-xl border border-eh-border-strong bg-eh-surface px-3.5 py-2 pr-14 text-[13px] text-eh-text focus:border-eh-sage focus:outline-none focus:ring-[3px] focus:ring-eh-sage/15">
                                    @if ($parametro[1])<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-eh-text-muted" data-unidad>{{ $parametro[1] }}</span>@endif
                                </div>
                                <p class="mt-1 text-[11px] text-eh-text-muted">Referencia: <span class="mono" data-referencia>{{ ParametrosCalidad::referencia($clave) }}</span></p>
                                @error('valores.'.$clave)<p class="mt-1 text-xs text-eh-red">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-6">
                    <h2 class="mb-4 text-sm font-semibold text-eh-text">3 · Analizador y observaciones</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-ui.field name="serial" label="Serial del analizador" maxlength="60" placeholder="Opcional" />
                        <x-ui.field name="modo" label="Modo" maxlength="60" placeholder="Ej. Leche de vaca" />
                        <x-ui.select name="unidad_congelacion" label="Unidad del punto de congelación" required
                            :options="['°C' => '°C', '°H' => '°H (Hortvet)']" :selected="old('unidad_congelacion', '°C')" />
                    </div>
                    <div class="mt-4 flex flex-col gap-1">
                        <label for="observaciones" class="text-[11px] font-semibold text-eh-text">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="3" maxlength="1000" placeholder="Opcional…"
                            class="w-full rounded-xl border border-eh-border-strong bg-eh-surface px-3.5 py-2.5 text-[13px] text-eh-text focus:border-eh-sage focus:outline-none focus:ring-[3px] focus:ring-eh-sage/15">{{ old('observaciones') }}</textarea>
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-4">
                <x-ui.card padding="p-5" class="lg:sticky lg:top-4">
                    <p class="text-xs font-medium text-eh-text-muted">Resultado según los valores ingresados</p>
                    <p id="estado-preview" class="mt-2 text-2xl font-semibold text-eh-text-muted">Sin datos</p>
                    <p id="estado-detalle" class="mt-1 text-xs text-eh-text-muted">Ingresa al menos un resultado del análisis.</p>
                    <ul id="alertas-preview" class="mt-3 space-y-1 text-xs text-eh-red"></ul>
                    <div class="mt-5 flex flex-col gap-2 border-t border-eh-border pt-4">
                        <x-ui.btn type="submit">Guardar análisis</x-ui.btn>
                        <x-ui.btn :href="route('admin.calidad.index')" variant="ghost">Cancelar</x-ui.btn>
                    </div>
                    <p class="mt-4 text-[11px] text-eh-text-muted">
                        Agua añadida mayor a 0 % → rechazado. Cualquier otro parámetro fuera de referencia → observado.
                        El análisis califica las entregas del proveedor de ese día y se ve también en la app.
                    </p>
                </x-ui.card>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const reglas = @json($reglasJs);
            const zona = document.getElementById('zona_id');
            const proveedor = document.getElementById('proveedor_id');
            const unidad = document.getElementById('unidad_congelacion');
            const preview = document.getElementById('estado-preview');
            const detalle = document.getElementById('estado-detalle');
            const listaAlertas = document.getElementById('alertas-preview');
            const completados = document.getElementById('completados');

            function filtrarProveedores() {
                const elegida = zona.value;
                let visibles = 0;
                for (const opcion of proveedor.options) {
                    if (!opcion.value) { opcion.textContent = elegida ? 'Elige el proveedor' : 'Primero elige la zona'; continue; }
                    const coincide = opcion.dataset.zona === elegida;
                    opcion.hidden = !coincide;
                    opcion.disabled = !coincide;
                    if (coincide) visibles++;
                }
                if (proveedor.selectedOptions[0]?.disabled) proveedor.value = '';
                if (elegida && visibles === 0) proveedor.options[0].textContent = 'Sin proveedores activos en esta zona';
            }

            const numero = (texto) => {
                const limpio = texto.trim().replace(',', '.');
                if (limpio === '') return null;
                const n = Number(limpio);
                return Number.isFinite(n) ? n : NaN;
            };

            function evaluar() {
                const enHortvet = unidad.value === '°H';
                const alertas = [];
                let medidos = 0;
                let invalidos = 0;
                let agua = null;
                for (const [clave, regla] of Object.entries(reglas)) {
                    const caja = document.querySelector(`[data-parametro="${clave}"]`);
                    const valor = numero(caja.querySelector('input').value);
                    const marca = caja.querySelector('[data-marca]');
                    if (clave === 'congelacion') {
                        caja.querySelector('[data-referencia]').textContent = enHortvet ? 'Pendiente de configurar en °H' : regla.referencia;
                        const u = caja.querySelector('[data-unidad]'); if (u) u.textContent = unidad.value;
                    }
                    caja.classList.remove('border-eh-red', 'border-eh-success');
                    marca.textContent = '';
                    if (valor === null) continue;
                    if (Number.isNaN(valor) || (clave !== 'congelacion' && valor < 0)) {
                        invalidos++; caja.classList.add('border-eh-red'); marca.textContent = 'Formato inválido'; marca.className = 'text-[11px] font-semibold text-eh-red';
                        continue;
                    }
                    medidos++;
                    if (clave === 'agua') agua = valor;
                    const correcto = !(clave === 'congelacion' && enHortvet) && valor >= regla.min && valor <= regla.max;
                    caja.classList.add(correcto ? 'border-eh-success' : 'border-eh-red');
                    marca.textContent = correcto ? '✓ En referencia' : '✗ Fuera de referencia';
                    marca.className = 'text-[11px] font-semibold ' + (correcto ? 'text-eh-success' : 'text-eh-red');
                    if (!correcto) alertas.push(`${regla.nombre}: ${valor}`);
                }
                completados.textContent = medidos;
                listaAlertas.replaceChildren(...alertas.map((texto) => Object.assign(document.createElement('li'), { textContent: '• ' + texto })));
                let estado, clase;
                if (medidos === 0) { estado = 'Sin datos'; clase = 'text-eh-text-muted'; }
                else if (agua !== null && agua > 0) { estado = 'Rechazado'; clase = 'text-eh-red'; }
                else if (alertas.length > 0) { estado = 'Observado'; clase = 'text-eh-gold'; }
                else { estado = 'Aprobado'; clase = 'text-eh-success'; }
                preview.textContent = estado;
                preview.className = 'mt-2 text-2xl font-semibold ' + clase;
                detalle.textContent = medidos === 0 ? 'Ingresa al menos un resultado del análisis.'
                    : `${medidos} medidos · ${alertas.length} fuera de referencia` + (invalidos ? ` · ${invalidos} con formato inválido` : '');
            }

            zona.addEventListener('change', filtrarProveedores);
            unidad.addEventListener('change', evaluar);
            document.querySelectorAll('[data-parametro] input').forEach((input) => input.addEventListener('input', evaluar));
            if (!zona.value && proveedor.value) {
                zona.value = proveedor.selectedOptions[0]?.dataset.zona ?? '';
            }
            filtrarProveedores();
            evaluar();
        })();
    </script>
@endsection
