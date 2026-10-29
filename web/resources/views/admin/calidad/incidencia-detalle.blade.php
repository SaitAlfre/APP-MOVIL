@php
    $estadoCaso = $incidencia?->estado;
    $etiquetaCaso = ['pendiente' => 'Por revisar', 'confirmada' => 'Infracción confirmada', 'descartada' => 'Caso descartado'][$estadoCaso] ?? 'Sin caso reportado';
    $varianteCaso = ['pendiente' => 'yellow', 'confirmada' => 'red', 'descartada' => 'gray'][$estadoCaso] ?? 'gray';
    $siguienteInfraccion = $infraccionesConfirmadas + 1;
    $inicioCastigo = \Illuminate\Support\Carbon::parse($semanaCastigo['inicio'])->format('d/m/Y');
    $finCastigo = \Illuminate\Support\Carbon::parse($semanaCastigo['fin'])->format('d/m/Y');
    $nombreProveedor = $analisis->proveedor?->nombres ?? ($visita['proveedorNombre'] ?? 'Proveedor');
    $nombreZona = $incidencia ? $incidencia->zona?->nombre : $analisis->proveedor?->zona?->nombre;
@endphp
<section id="caso-adulteracion" aria-labelledby="titulo-caso-calidad" class="mb-4 scroll-mt-4">
    <x-ui.card padding="p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="titulo-caso-calidad" class="text-sm font-semibold text-eh-text">Caso de adulteración</h2>
                <p class="mt-1 text-xs text-eh-text-muted">Historial del proveedor: <strong class="text-eh-text">{{ $infraccionesConfirmadas }} {{ $infraccionesConfirmadas === 1 ? 'infracción confirmada' : 'infracciones confirmadas' }}</strong>. El conteo no se reinicia.</p>
            </div>
            <x-ui.badge :variant="$varianteCaso" :label="$etiquetaCaso" />
        </div>

        @if ($incidencia)
            <div class="mt-4 border-t border-eh-border pt-4 text-sm">
                <p class="font-medium text-eh-text">Hallazgo reportado</p>
                <p class="mt-1 whitespace-pre-wrap text-eh-text-muted">{{ $incidencia->motivo }}</p>
                <p class="mt-2 text-xs text-eh-text-muted">Reportado por {{ $incidencia->registradoPor?->nombres ?? 'Personal de calidad' }} · {{ $incidencia->created_at->timezone('America/Lima')->format('d/m/Y H:i') }}</p>
            </div>
        @endif

        @if ($estadoCaso === 'confirmada')
            <dl class="mt-4 grid gap-4 sm:grid-cols-3 text-sm">
                <div><dt class="text-xs text-eh-text-muted">Infracción acumulada</dt><dd class="mt-1 font-semibold">{{ $incidencia->numero_infraccion }} de 3</dd></div>
                <div><dt class="text-xs text-eh-text-muted">Descuento por litro</dt><dd class="mt-1 mono font-semibold">S/ {{ number_format((float) $incidencia->descuento_litro, 2) }}</dd></div>
                <div><dt class="text-xs text-eh-text-muted">Semana del castigo</dt><dd class="mt-1">{{ $incidencia->semana_inicio->format('d/m/Y') }} al {{ $incidencia->semana_fin->format('d/m/Y') }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-eh-text-muted">Confirmada por {{ $incidencia->confirmadoPor?->nombres ?? 'Administración' }} · {{ $incidencia->confirmado_en?->timezone('America/Lima')->format('d/m/Y H:i') }}</p>
            @if ($incidencia->respuesta)<p class="mt-3 whitespace-pre-wrap text-sm text-eh-text">{{ $incidencia->respuesta }}</p>@endif
            <p class="mt-3 text-xs text-eh-text-muted">Si coinciden varios castigos en una semana, se aplica el mayor descuento por litro, sin sumarlos. Los avisos se publican en la app; su publicación no confirma que hayan sido leídos.</p>
            @if ($incidencia->numero_infraccion >= 3)
                <x-ui.alert type="warning" class="mt-4" title="Retiro por tercera infracción">
                    Se registró el retiro del proveedor y se conservó su historial. El retiro suspende el acceso de una cuenta que solo tiene el rol Proveedor; entrega el aviso también de forma presencial si ya no puede entrar a la app.
                </x-ui.alert>
            @endif
        @elseif ($estadoCaso === 'descartada')
            <p class="mt-4 text-sm text-eh-text">Este caso no cuenta como infracción y no genera castigo ni aviso de adulteración a la zona.</p>
            @if ($incidencia->respuesta)<p class="mt-2 whitespace-pre-wrap text-sm text-eh-text-muted">{{ $incidencia->respuesta }}</p>@endif
        @elseif ($estadoCaso === 'pendiente')
            @if ($puedeResolverIncidencias)
                <div class="mt-4 grid gap-5 lg:grid-cols-2">
                    <form method="POST" action="{{ route('admin.calidad.incidencias.resolver', $incidencia) }}" class="space-y-4" data-once
                        data-confirm="{{ $siguienteInfraccion >= 3 ? 'Al confirmar se registrará la tercera infracción, se retirará al proveedor y se publicarán los avisos individual y de zona. ¿Confirmar?' : 'Se registrará una infracción, el castigo semanal y los avisos individual y de zona. ¿Confirmar?' }}">
                        @csrf
                        <input type="hidden" name="decision" value="confirmar">
                        <h3 class="text-sm font-semibold text-eh-text">Confirmar adulteración</h3>
                        <p class="text-xs text-eh-text-muted">Será la infracción <strong>{{ $siguienteInfraccion }} de 3</strong>. Semana: <strong>{{ $inicioCastigo }} al {{ $finCastigo }}</strong>.</p>
                        @if ($siguienteInfraccion >= 3)
                            <x-ui.alert type="warning">Al confirmar, el proveedor quedará <strong>Retirado</strong> por reincidencia.</x-ui.alert>
                        @endif
                        <x-ui.field name="descuento_litro" id="descuento-caso" type="number" label="Descuento por litro" min="0.20" max="0.50" step="0.01" required unit="S/" hint="De S/ 0,20 a S/ 0,50 según la gravedad; se aplica a los litros de toda la semana. Si ya hay otro castigo, prevalece el mayor." />
                        <div class="space-y-1.5">
                            <label for="respuesta-confirmar-caso" class="block text-xs font-medium text-eh-text">Fundamento de la decisión <span class="text-eh-red" aria-hidden="true">*</span></label>
                            <textarea id="respuesta-confirmar-caso" name="respuesta" required maxlength="1000" rows="3" class="w-full rounded-lg border border-eh-border-strong bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-sage focus:outline-none focus:ring-2 focus:ring-eh-sage/15">{{ old('respuesta') }}</textarea>
                            @error('respuesta')<p class="text-xs text-eh-red" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="rounded-xl bg-eh-surface-alt p-3 text-xs text-eh-text-muted">
                            <p class="font-medium text-eh-text">Avisos al confirmar</p>
                            <p class="mt-1">Individual: se publicará un aviso para {{ $nombreProveedor }} con la decisión y su castigo en la app.</p>
                            <p class="mt-1">Zona: se publicará el <strong class="text-eh-text">nombre de {{ $nombreProveedor }}</strong> y la adulteración confirmada para los proveedores de {{ $nombreZona ?? 'la zona del caso' }}.</p>
                        </div>
                        @if (! $nombreZona)<x-ui.alert type="warning">El proveedor no tiene zona asignada. Revisa la zona del caso antes de confirmar para dirigir el aviso correctamente.</x-ui.alert>@endif
                        <x-ui.btn type="submit">{{ $siguienteInfraccion >= 3 ? 'Confirmar infracción y retiro' : 'Confirmar infracción y castigo' }}</x-ui.btn>
                    </form>
                    <form method="POST" action="{{ route('admin.calidad.incidencias.resolver', $incidencia) }}" class="space-y-4 border-t border-eh-border pt-4 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0" data-once data-confirm="¿Descartar este caso sin infracción, castigo ni aviso de adulteración a la zona?">
                        @csrf
                        <input type="hidden" name="decision" value="descartar">
                        <h3 class="text-sm font-semibold text-eh-text">Descartar el caso</h3>
                        <p class="text-xs text-eh-text-muted">Conserva el análisis y el hallazgo reportado, sin sumar una infracción al proveedor.</p>
                        <div class="space-y-1.5">
                            <label for="respuesta-descartar-caso" class="block text-xs font-medium text-eh-text">Motivo del descarte <span class="text-eh-red" aria-hidden="true">*</span></label>
                            <textarea id="respuesta-descartar-caso" name="respuesta" required maxlength="1000" rows="3" class="w-full rounded-lg border border-eh-border-strong bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-sage focus:outline-none focus:ring-2 focus:ring-eh-sage/15">{{ old('respuesta') }}</textarea>
                        </div>
                        <x-ui.btn type="submit" variant="ghost">Descartar con motivo</x-ui.btn>
                    </form>
                </div>
            @else
                <p class="mt-4 text-sm text-eh-text-muted">Administración revisará el caso. El resultado técnico se conserva y todavía no cuenta como infracción.</p>
            @endif
        @elseif ($analisis->estado === \App\Domain\Calidad\EstadoAnalisis::Aprobado)
            <p class="mt-4 text-sm text-eh-text-muted">Este análisis no tiene un hallazgo técnico que permita reportar adulteración. Conserva sus resultados y registra otra prueba si necesitas verificar la muestra.</p>
        @elseif (auth('operador')->user()->puede('calidad', 'crear'))
            <details class="mt-4" @if ($errors->has('motivo')) open @endif>
                <summary class="cursor-pointer text-sm font-medium text-eh-primary">Reportar posible adulteración</summary>
                <form method="POST" action="{{ route('admin.calidad.incidencias.store', $analisis->uuid) }}" class="mt-3 max-w-2xl space-y-3" data-once>
                    @csrf
                    <p class="text-xs text-eh-text-muted">Describe el hallazgo y la evidencia de este control sorpresa. Reportarlo no confirma una infracción ni publica un aviso a la zona.</p>
                    <div class="space-y-1.5">
                        <label for="motivo-caso" class="block text-xs font-medium text-eh-text">Hallazgo a revisar <span class="text-eh-red" aria-hidden="true">*</span></label>
                        <textarea id="motivo-caso" name="motivo" required maxlength="1000" rows="3" class="w-full rounded-lg border border-eh-border-strong bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-sage focus:outline-none focus:ring-2 focus:ring-eh-sage/15">{{ old('motivo') }}</textarea>
                        @error('motivo')<p class="text-xs text-eh-red" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <x-ui.btn type="submit" variant="secondary">Enviar a revisión</x-ui.btn>
                </form>
            </details>
        @endif
    </x-ui.card>
</section>
