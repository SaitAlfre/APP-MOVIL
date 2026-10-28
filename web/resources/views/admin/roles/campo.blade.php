@php $enUso = in_array($campo['clave'] ?? '', $camposEnUso ?? [], true); @endphp
<fieldset data-campo-fila class="min-w-0 rounded-xl border border-eh-border bg-eh-surface-alt p-4">
<legend class="px-1 text-sm font-semibold" data-campo-titulo>Campo {{ is_numeric($indice) ? $indice + 1 : '' }}</legend>
<input type="hidden" name="campos[{{ $indice }}][clave]" value="{{ $campo['clave'] ?? '' }}" data-campo-clave>
<div class="space-y-3">
<label class="block text-sm font-medium">Nombre del campo <span aria-hidden="true">*</span><input name="campos[{{ $indice }}][etiqueta]" value="{{ $campo['etiqueta'] ?? '' }}" data-campo-etiqueta required maxlength="100" autocomplete="off" class="mt-1 block w-full rounded-lg border border-eh-border bg-eh-surface p-2.5 focus-visible:outline-2 focus-visible:outline-eh-primary" placeholder="Ej. Teléfono de contacto…" @error('campos.'.$indice.'.etiqueta') aria-invalid="true" @enderror></label>
@error('campos.'.$indice.'.etiqueta')<p class="text-sm text-eh-red">{{ $message }}</p>@enderror
@error('campos.'.$indice.'.clave')<p class="text-sm text-eh-red">{{ $message }}</p>@enderror
<label class="block text-sm font-medium">Tipo de respuesta<select name="campos[{{ $indice }}][tipo]" data-campo-tipo class="mt-1 block w-full rounded-lg border border-eh-border bg-eh-surface p-2.5 focus-visible:outline-2 focus-visible:outline-eh-primary" @disabled($enUso)>@foreach(['texto'=>'Texto — nombres, teléfonos o direcciones', 'numero'=>'Número — cantidades o medidas', 'fecha'=>'Fecha — día, mes y año'] as $tipo=>$etiqueta)<option value="{{ $tipo }}" @selected(($campo['tipo'] ?? 'texto') === $tipo)>{{ $etiqueta }}</option>@endforeach</select></label>
@if($enUso)<input type="hidden" name="campos[{{ $indice }}][tipo]" value="{{ $campo['tipo'] }}"><p class="text-xs text-eh-text-muted">El tipo se conserva porque este campo ya tiene respuestas.</p>@endif
@error('campos.'.$indice.'.tipo')<p class="text-sm text-eh-red">{{ $message }}</p>@enderror
<label class="flex min-h-10 cursor-pointer items-center gap-2 text-sm"><input type="checkbox" name="campos[{{ $indice }}][obligatorio]" data-campo-obligatorio value="1" @checked($campo['obligatorio'] ?? false)> Respuesta obligatoria</label>
<div class="flex flex-wrap gap-2 border-t border-eh-border pt-3">
<button type="button" data-subir-campo class="rounded-lg border border-eh-border px-3 py-2 text-sm hover:bg-eh-surface focus-visible:outline-2 focus-visible:outline-eh-primary disabled:opacity-40">Subir</button>
<button type="button" data-bajar-campo class="rounded-lg border border-eh-border px-3 py-2 text-sm hover:bg-eh-surface focus-visible:outline-2 focus-visible:outline-eh-primary disabled:opacity-40">Bajar</button>
<button type="button" data-quitar-campo class="ml-auto rounded-lg px-3 py-2 text-sm text-eh-red hover:bg-eh-surface focus-visible:outline-2 focus-visible:outline-eh-primary">Quitar campo</button>
</div></div>
</fieldset>
