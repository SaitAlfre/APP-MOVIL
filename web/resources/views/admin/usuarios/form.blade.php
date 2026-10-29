@extends('layouts.admin')
@section('titulo', $usuario === null ? 'Nuevo usuario' : 'Editar usuario')
@section('contenido')
@php
$esNuevo = $usuario === null;
$rolesMarcados = collect(old('roles', $base ? [$base] : ($usuario ? array_map(fn($rol) => $rol->value, $usuario->roles) : [])));
$funcionesMoviles = ['acopiador' => 'Acopiador', 'calidad' => 'Calidad'];
$perfilSinBase = $perfil !== null && $perfil->base === null;
$funcionActual = $perfilSinBase ? old('funcion_movil', collect($usuario?->roles ?? [])->map(fn($rol) => $rol->value)->first(fn($valor) => isset($funcionesMoviles[$valor]))) : null;
$rolBase = $perfilSinBase ? $funcionActual : ($perfil?->base ?? $rolesMarcados->first());
$rolNombre = $perfil?->nombre ?? \App\Domain\Usuarios\Rol::tryFrom($rolBase)?->etiqueta();
$camposPersonalizados = $perfil ? json_decode($perfil->campos, true) : [];
$identidadPersonalizada = \App\Application\Usuarios\CamposIdentidadFormulario::reconocer($camposPersonalizados);
$usaZona = in_array($rolBase, ['calidad','acopiador'], true);
@endphp
<div class="mx-auto w-full max-w-3xl">
<x-ui.page-header :title="$esNuevo ? 'Nuevo usuario' : 'Editar usuario'" description="Completa la identidad, los datos del rol y el acceso a la cuenta." :breadcrumbs="[['label'=>'Usuarios y accesos','url'=>route('admin.usuarios.index')],['label'=>$esNuevo ? 'Nuevo usuario' : $usuario->nombres]]" />
@if(!$esNuevo && $puedeAsignarRol)
<x-ui.card padding="p-4 sm:p-5" class="mb-4">
    <form method="GET" action="{{ route('admin.usuarios.edit',$usuario->id) }}" class="flex flex-wrap items-end gap-3">
        <div class="min-w-48 flex-1"><x-ui.select name="rol" label="Rol del usuario" :options="$opcionesRol" :selected="$seleccionRol" /></div>
        <x-ui.btn type="submit" variant="secondary">Aplicar rol al formulario</x-ui.btn>
    </form>
    <p class="mt-2 text-xs text-eh-text-muted">Elige el rol para revisar sus campos. La asignación se actualiza al guardar los cambios.</p>
</x-ui.card>
@endif
<x-ui.card padding="p-5 sm:p-6">
<form method="POST" action="{{ $esNuevo ? route('admin.usuarios.store') : route('admin.usuarios.update', $usuario->id) }}" class="space-y-6" data-once data-usuario-form data-base="{{ $rolBase }}" data-dni-url="{{ route('admin.usuarios.consultar-dni') }}" data-usuario-id="{{ $usuario?->id }}">
@csrf @unless($esNuevo) @method('PUT') @endunless
@if($errors->any())<div role="alert" tabindex="-1" data-usuario-errores class="rounded-xl border border-eh-red p-4 text-sm text-eh-red">Revisa los campos marcados antes de guardar. @error('roles')<p>{{ $message }}</p>@enderror @error('perfil_rol_id')<p>{{ $message }}</p>@enderror</div>@endif
<section aria-labelledby="rol-titulo" class="rounded-xl border border-eh-border bg-eh-surface-alt p-4">
<div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="rol-titulo" class="text-sm font-medium text-eh-text-muted">Rol del usuario</h2><p class="mt-1 font-semibold">{{ $rolNombre }}</p></div>@if($esNuevo && $puedeAsignarRol)<x-ui.btn :href="route('admin.usuarios.create')" variant="ghost">Cambiar rol</x-ui.btn>@endif</div>
@if($perfil)<input type="hidden" name="perfil_rol_id" value="{{ $perfil->id }}">
@if(! $perfilSinBase)<input type="hidden" name="roles[]" value="{{ $perfil->base }}"><p class="mt-2 text-xs text-eh-text-muted">Rol creado antes de separar los permisos web: conserva las funciones móviles de {{ \App\Domain\Usuarios\Rol::from($perfil->base)->etiqueta() }}.</p>
@elseif($puedeAsignarRol)<div class="mt-4"><x-ui.select name="funcion_movil" label="Funciones en la app móvil" placeholder="Ninguna (solo panel web)" :options="$funcionesMoviles" :selected="$funcionActual" hint="El rol personalizado solo define permisos del panel web. Asigna una función móvil únicamente si esta persona trabajará con la app." data-funcion-movil /></div>
@else<p class="mt-2 text-xs text-eh-text-muted">Funciones en la app móvil: {{ $funcionActual ? $funcionesMoviles[$funcionActual] : 'ninguna' }}. Solo un administrador puede cambiarlas.</p>
@endif
@elseif($esNuevo)<input type="hidden" name="roles[]" value="{{ $rolBase }}">
@else
@foreach($rolesMarcados as $rol)<input type="hidden" name="roles[]" value="{{ $rol }}">@endforeach
@if($rolesMarcados->count()>1)<p class="mt-2 text-xs text-eh-text-muted">Se conserva la combinación de roles anterior. Para usar un único rol, selecciónalo arriba.</p>@endif
@endif
</section>
@if(!$esNuevo && ($fichaProveedor ?? null))
<section aria-labelledby="ficha-titulo" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-eh-border p-4" data-ficha-vinculada>
<div class="min-w-0"><h2 id="ficha-titulo" class="text-sm font-medium text-eh-text-muted">Proveedor asociado</h2><p class="mt-1 font-semibold">{{ $fichaProveedor->nombres }} <span class="mono text-xs font-normal text-eh-text-muted">· {{ $fichaProveedor->codigo }}</span></p>
@if($fichaProveedor->nombres !== $usuario->nombres)<p class="mt-1 text-xs text-eh-gold">El nombre de la ficha difiere del de la cuenta. Revisa ambos registros; no se sincronizan automáticamente.</p>@endif
<p class="mt-1 text-xs text-eh-text-muted">El DNI se mantiene igual en la cuenta y en la ficha mientras estén vinculadas.</p></div>
<x-ui.btn :href="route('admin.proveedores.show', $fichaProveedor->id)" variant="outline" size="sm" icon="proveedores">Ver ficha productiva</x-ui.btn>
</section>
@elseif(!$esNuevo && $usuario->tieneRol(\App\Domain\Usuarios\Rol::Proveedor))
<section aria-label="Ficha productiva" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-eh-gold/30 bg-eh-gold-soft p-4 text-sm" data-ficha-pendiente>
@if($fichaMismoDni ?? null)
<p class="min-w-0 flex-1">Existe la ficha {{ $fichaMismoDni->codigo }} con este DNI{{ $fichaMismoDni->usuarioId ? ', pero está vinculada a otra cuenta' : ' sin vincular' }}.</p>
<x-ui.btn :href="route('admin.proveedores.edit', $fichaMismoDni->id)" variant="outline" size="sm">Revisar ficha</x-ui.btn>
@else
<p class="min-w-0 flex-1">Esta cuenta de proveedor aún no tiene ficha productiva.</p>
<x-ui.btn :href="route('admin.proveedores.create', ['dni' => $usuario->dni, 'origen' => 'usuarios'])" variant="outline" size="sm" icon="plus">Registrar ficha y asociar</x-ui.btn>
@endif
</section>
@endif
<section aria-label="Datos del usuario" class="space-y-4">
@php $identidadMostrada = []; @endphp
@if(count($camposPersonalizados))
<fieldset class="space-y-4"><legend class="mb-4 text-base font-semibold">{{ $perfil->nombre }}</legend>
@foreach($camposPersonalizados as $clave => $campo)
    @php $destino = $identidadPersonalizada[$clave] ?? null; @endphp
    @if($destino)
        @if(!in_array($destino, $identidadMostrada, true))
            @include('admin.usuarios.campo-identidad', ['destino' => $destino, 'etiqueta' => $campo['etiqueta'], 'clave' => $clave])
            @php $identidadMostrada[] = $destino; @endphp
        @endif
    @else
        <x-ui.field :name="'datos_formulario['.$clave.']'" :id="'dato-'.$clave" :label="$campo['etiqueta']" :type="match($campo['tipo']) {'numero'=>'number','fecha'=>'date',default=>'text'}" :required="$campo['obligatorio']" :value="old('datos_formulario.'.$clave,$registro?->perfil_rol_id == $perfil?->id ? ($registro?->datos_formulario[$clave] ?? null) : null)" :error="$errors->first('datos_formulario.'.$clave)" :step="$campo['tipo']==='numero' ? 'any' : null" maxlength="1000" />
    @endif
@endforeach
</fieldset>
@endif
@foreach(['dni'=>'DNI', 'nombres'=>'Nombre completo'] as $destino=>$etiqueta)
    @unless(in_array($destino, $identidadMostrada, true))
        @include('admin.usuarios.campo-identidad', ['destino'=>$destino, 'etiqueta'=>$etiqueta, 'clave'=>null])
    @endunless
@endforeach
<div data-usuario-zona @if(!$usaZona) hidden @endif>
<x-ui.select name="zona_id" label="Zona de trabajo" :selected="$usuario?->zonaId" :options="collect($zonas)->mapWithKeys(fn($zona)=>[$zona->id=>$zona->nombre])->all()" placeholder="Selecciona una zona" :disabled="!$usaZona" :required="$rolBase === 'calidad' && $esNuevo" hint="Obligatoria para Calidad; opcional para Acopiador." />
</div>
<input type="hidden" name="zona_id" value="" data-usuario-zona-vacia @disabled($usaZona)>
</section>
<section aria-labelledby="acceso-titulo" class="space-y-4 border-t border-eh-border pt-5"><h2 id="acceso-titulo" class="font-semibold">Acceso al sistema</h2>
@if($esNuevo)
<x-ui.field name="username" label="Usuario de acceso" required minlength="3" maxlength="30" pattern="[a-z0-9._]{3,30}" hint="De 3 a 30 caracteres: minúsculas, números, punto o guion bajo." placeholder="Ej. juan.perez…" autocomplete="off" spellcheck="false" />
<button type="button" data-sugerir-usuario class="text-sm font-medium text-eh-primary underline">Sugerir usuario a partir del nombre</button>
<div class="grid gap-4 sm:grid-cols-2"><x-ui.field name="pin" label="PIN inicial" type="password" required inputmode="numeric" minlength="4" maxlength="4" pattern="[0-9]{4}" hint="Exactamente 4 dígitos. No uses el DNI como PIN." autocomplete="new-password" /><x-ui.field name="pin_confirmation" label="Repite el PIN" type="password" required inputmode="numeric" minlength="4" maxlength="4" pattern="[0-9]{4}" autocomplete="new-password" /></div>
<label class="flex min-h-10 items-center gap-2 text-sm"><input type="checkbox" data-mostrar-pin> Mostrar PIN mientras lo reviso</label>
<p class="text-sm text-eh-text-muted">La cuenta se creará ACTIVA. Podrás desactivarla o restablecer su PIN desde Usuarios.</p>
@else
<div class="flex flex-wrap items-center gap-3"><span class="font-medium">{{ $usuario->username }}</span><x-ui.estado-cuenta :usuario="$usuario" :productivo="($fichaProveedor ?? null)?->estado->value" /></div><p class="text-sm text-eh-text-muted">Para cambiar el PIN o el estado de la cuenta, usa las acciones del listado de Usuarios.</p>
@endif
</section>
<div class="flex flex-wrap justify-end gap-3 border-t border-eh-border pt-4"><x-ui.btn :href="route('admin.usuarios.index')" variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">{{ $esNuevo ? 'Crear usuario' : 'Guardar cambios' }}</x-ui.btn></div>
</form>
</x-ui.card>
</div>
@endsection
