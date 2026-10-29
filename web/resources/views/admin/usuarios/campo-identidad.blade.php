@if($destino === 'dni')
<div class="space-y-3">
    <x-ui.field name="dni" :label="$etiqueta" required :maxlength="$esNuevo ? 8 : 20" :pattern="$esNuevo ? '[0-9]{8}' : null" inputmode="numeric" :value="old('datos_formulario.'.$clave, $usuario?->dni ?? ($registro?->datos_formulario[$clave] ?? null))" :error="$errors->first('dni') ?: $errors->first('datos_formulario.'.$clave)" placeholder="Ej. 12345678…" autocomplete="off" />
    <button type="button" data-consultar-dni class="min-h-11 rounded-lg border border-eh-border px-4 py-2 text-sm font-semibold hover:bg-eh-surface-alt focus-visible:outline-2 focus-visible:outline-eh-primary disabled:opacity-50">Consultar DNI</button>
    <p class="text-xs text-eh-text-muted">Consulta el DNI para revisar si ya está registrado y completar el nombre. También puedes escribirlo manualmente.</p>
    @if (! filled(config('services.decolecta.token')))<p class="text-xs text-eh-gold" data-dni-sin-configurar>{{ \App\Application\Proveedores\ConsultarDniUseCase::MENSAJE_SIN_CONFIGURAR }}</p>@endif
    <div data-dni-resultado role="status" aria-live="polite" class="text-sm text-eh-text-muted"></div>
</div>
@else
    <x-ui.field name="nombres" :label="$etiqueta" required maxlength="150" :value="old('datos_formulario.'.$clave, $usuario?->nombres ?? ($registro?->datos_formulario[$clave] ?? null))" :error="$errors->first('nombres') ?: $errors->first('datos_formulario.'.$clave)" placeholder="Ej. Juan Pérez Quispe…" autocomplete="name" />
@endif
