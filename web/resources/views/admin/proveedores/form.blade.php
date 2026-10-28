@extends('layouts.admin')

@section('titulo', $proveedor ? 'Editar proveedor' : 'Nuevo proveedor')

@section('contenido')
    @php
        $esNuevo = $proveedor === null;
        $origen = $origen ?? 'proveedores';
        $desdeUsuarios = $esNuevo && $origen === 'usuarios';
        $puedeCrearCuentas = $puedeCrearCuentas ?? false;
        // Desde Usuarios el rol Proveedor ya implica su acceso; desde Proveedores se habilita a demanda.
        $crearCuenta = $esNuevo && $puedeCrearCuentas && ($desdeUsuarios || (bool) old('crear_cuenta', ($crearCuentaInicial ?? false) ? '1' : '0'));
        $cuentaVinculada = $cuentaVinculada ?? null;
        $cuentasCandidatas = $cuentasCandidatas ?? [];
        $migas = $desdeUsuarios
            ? [['label' => 'Usuarios y accesos', 'url' => route('admin.usuarios.index')], ['label' => 'Nuevo usuario', 'url' => route('admin.usuarios.create')], ['label' => 'Proveedor']]
            : [['label' => 'Proveedores', 'url' => route('admin.proveedores.index')], ['label' => $esNuevo ? 'Nuevo proveedor' : $proveedor->nombres]];
        $cancelar = $desdeUsuarios ? route('admin.usuarios.index') : route('admin.proveedores.index');
    @endphp

    <div @class(['mx-auto w-full', 'max-w-3xl' => $esNuevo, 'max-w-6xl' => !$esNuevo])>
    <x-ui.page-header :title="$esNuevo ? 'Nuevo proveedor' : 'Editar proveedor'"
        :description="$esNuevo ? 'Registro único: ficha productiva y, si lo necesitas, su cuenta para la app móvil.' : 'Datos del productor y su zona de acopio.'"
        :breadcrumbs="$migas">
        @unless ($esNuevo)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.proveedores.show', $proveedor->id)" variant="secondary" size="sm" icon="eye">Ver ficha</x-ui.btn>
            </x-slot:actions>
        @endunless
    </x-ui.page-header>

    <div @class(['grid grid-cols-1 gap-6', 'w-full max-w-3xl' => $esNuevo, 'max-w-6xl lg:grid-cols-3' => ! $esNuevo])>
        <x-ui.card padding="p-5 sm:p-6" :class="$esNuevo ? '' : 'lg:col-span-2'">
            <form method="POST"
                action="{{ $esNuevo ? route('admin.proveedores.store') : route('admin.proveedores.update', $proveedor->id) }}"
                class="space-y-6" data-once data-proveedor-form @if ($esNuevo) data-registro-proveedor @endif
                data-dni-url="{{ route('admin.proveedores.consultar-dni') }}"
                data-dni-disponible="{{ filled(config('services.decolecta.token')) ? 'true' : 'false' }}"
                data-duplicados-url="{{ route('admin.proveedores.duplicados') }}" data-proveedor-id="{{ $proveedor?->id }}">
                @csrf
                @unless ($esNuevo)
                    @method('PUT')
                @endunless

                @if ($esNuevo)
                    <input type="hidden" name="origen" value="{{ $origen }}">
                    <input type="hidden" name="accion_registro" value="{{ old('accion_registro') }}" data-accion-registro>
                    @if ($errors->any())
                        <x-ui.alert type="error" title="Revisa el registro antes de guardar" tabindex="-1" data-registro-errores>
                            @foreach (['dni', 'username', 'codigo'] as $campo)
                                @error($campo)<p>{{ $message }}</p>@enderror
                            @endforeach
                            @unless ($errors->hasAny(['dni', 'username', 'codigo']))<p>Hay campos marcados con errores.</p>@endunless
                        </x-ui.alert>
                    @endif
                @endif

                <section aria-labelledby="seccion-proveedor" class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-eh-border pb-4">
                    <h2 id="seccion-proveedor" class="text-sm font-semibold text-eh-text">{{ $esNuevo ? 'Datos del proveedor' : 'Editar datos del proveedor' }}</h2>
                    <span class="rounded-lg bg-eh-surface-alt px-3 py-1.5 text-xs text-eh-text-muted">{{ $esNuevo ? 'Código y QR automáticos al guardar' : $proveedor->codigo }}</span>
                </div>
                <div class="grid grid-cols-1 items-start gap-5 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <div class="space-y-3">
                            <div class="min-w-0 flex-1 basis-48">
                                <x-ui.field name="dni" label="DNI del proveedor" required maxlength="{{ $esNuevo ? 8 : 20 }}" inputmode="numeric" autocomplete="off" :value="$proveedor?->dni ?? ($dniInicial ?? null)" placeholder="8 dígitos" />
                            </div>
                            <x-ui.btn type="button" variant="outline" icon="search" data-consultar-dni :disabled="! filled(config('services.decolecta.token'))">Consultar DNI</x-ui.btn>
                        </div>
                        <p class="text-xs text-eh-text-muted">Al completar el DNI, buscamos sus nombres y comprobamos si ya tiene ficha o cuenta.</p>
                        <div data-dni-resultado role="status" aria-live="polite" class="space-y-2 text-sm text-eh-text-muted">@if (! filled(config('services.decolecta.token')))Consulta no disponible. Puedes completar los nombres manualmente.@endif</div>
                        <div data-proveedor-duplicados role="status" aria-live="polite" @if ($esNuevo) hidden @endif></div>
                        @if ($esNuevo)
                            <div data-registro-situacion role="status" aria-live="polite" class="space-y-3" hidden></div>
                        @endif
                    </div>
                    <x-ui.field name="nombres" label="Nombres y apellidos" required maxlength="150" :value="$proveedor?->nombres" class="sm:col-span-2" placeholder="Nombres y apellidos del proveedor" autocomplete="name" />
                    <x-ui.field name="telefono" label="Celular (opcional)" type="tel" autocomplete="tel" maxlength="30" :value="$proveedor?->telefono" placeholder="Número de contacto" />
                    <x-ui.select name="zona_id" label="Zona de acopio" required placeholder="Selecciona una zona" :selected="$proveedor?->zonaId" :options="collect($zonas)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" />
                </div>
                @if (! $esNuevo && ($proveedor->dueno || $proveedor->direccion))
                    <details class="rounded-xl border border-eh-border p-4">
                        <summary class="cursor-pointer text-sm font-medium">Datos adicionales existentes</summary>
                        <div class="mt-4 grid gap-4">
                            <x-ui.field name="dueno" label="Responsable registrado" maxlength="150" :value="$proveedor->dueno" />
                            <x-ui.field name="direccion" label="Referencia registrada" maxlength="255" :value="$proveedor->direccion" />
                        </div>
                    </details>
                @endif
                @if (! $esNuevo && $cuentaVinculada)
                    <p class="text-xs text-eh-text-muted">El DNI está vinculado a la cuenta «{{ $cuentaVinculada->username }}». Para cambiarlo, desvincula primero la cuenta.</p>
                @endif
                </section>

                @if ($esNuevo)
                    <section aria-labelledby="seccion-acceso" class="space-y-4 border-t border-eh-border pt-5" data-seccion-cuenta data-modo-acceso="{{ $desdeUsuarios ? 'obligatorio' : 'opcional' }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 id="seccion-acceso" class="text-sm font-semibold text-eh-text">Acceso a la aplicación móvil</h2>
                                <p class="mt-1 text-xs text-eh-text-muted">
                                    {{ $desdeUsuarios ? 'Credenciales personales del proveedor. La cuenta se crea con rol Proveedor y queda vinculada a su ficha.' : 'Opcional. La ficha ya aparece en acopio y en la sincronización móvil; el acceso solo permite al proveedor iniciar sesión con su usuario.' }}
                                </p>
                            </div>
                            <x-ui.badge variant="blue">Rol: Proveedor</x-ui.badge>
                        </div>
                        @if ($puedeCrearCuentas)
                            <input type="hidden" name="crear_cuenta" value="{{ $crearCuenta ? '1' : '0' }}" data-crear-cuenta>
                            @unless ($desdeUsuarios)
                                <x-ui.btn type="button" variant="outline" icon="plus" data-habilitar-acceso aria-controls="campos-cuenta" aria-expanded="{{ $crearCuenta ? 'true' : 'false' }}" :hidden="$crearCuenta">Habilitar acceso móvil</x-ui.btn>
                            @endunless
                            <div id="campos-cuenta" data-campos-cuenta class="space-y-4" @unless ($crearCuenta) hidden @endunless>
                                <div class="space-y-2">
                                    <x-ui.field name="username" label="Usuario de acceso" required minlength="3" maxlength="30" pattern="[a-z0-9._]{3,30}" hint="De 3 a 30 caracteres: minúsculas, números, punto o guion bajo." placeholder="Ej. juan.perez" autocomplete="off" spellcheck="false" :disabled="! $crearCuenta" />
                                    <button type="button" data-sugerir-usuario class="inline-flex min-h-10 items-center gap-1.5 text-sm font-medium text-eh-primary underline underline-offset-2">
                                        <x-icon name="sparkles" class="h-4 w-4" />Sugerir usuario a partir del nombre
                                    </button>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <x-ui.field name="pin" label="PIN inicial" type="password" required inputmode="numeric" minlength="4" maxlength="4" pattern="[0-9]{4}" hint="Exactamente 4 dígitos. No uses el DNI como PIN." autocomplete="new-password" :disabled="! $crearCuenta" />
                                    <x-ui.field name="pin_confirmation" label="Confirmar PIN" type="password" required inputmode="numeric" minlength="4" maxlength="4" pattern="[0-9]{4}" autocomplete="new-password" :disabled="! $crearCuenta" />
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <button type="button" data-mostrar-pin aria-pressed="false" class="inline-flex min-h-10 items-center gap-1.5 text-sm font-medium text-eh-text-muted hover:text-eh-text">
                                        <x-icon name="eye" class="h-4 w-4" data-icono-mostrar /><x-icon name="eyeOff" class="hidden h-4 w-4" data-icono-ocultar /><span data-texto-pin>Mostrar PIN</span>
                                    </button>
                                    @unless ($desdeUsuarios)
                                        <button type="button" data-quitar-acceso class="inline-flex min-h-10 items-center text-sm font-medium text-eh-text-muted underline underline-offset-2 hover:text-eh-text">Registrar sin acceso móvil</button>
                                    @endunless
                                </div>
                            </div>
                            <p data-cuenta-no-aplica class="text-xs text-eh-text-muted" hidden></p>
                        @else
                            <p class="rounded-xl border border-eh-border p-4 text-sm text-eh-text-muted">Se registrará solo la ficha productiva. Un administrador puede habilitar su acceso móvil desde la edición del proveedor.</p>
                        @endif
                    </section>

                    <p data-resumen-registro role="status" aria-live="polite" class="rounded-xl bg-eh-primary-soft px-4 py-3 text-sm font-medium text-eh-primary">
                        {{ $crearCuenta ? 'Se registrará la ficha productiva y su acceso móvil vinculado.' : 'Se registrará la ficha productiva, con código y QR, sin acceso móvil personal.' }}
                    </p>
                @endif

                <div class="flex flex-wrap justify-end gap-2 border-t border-eh-border pt-4">
                    <x-ui.btn :href="$cancelar" variant="ghost">Cancelar</x-ui.btn>
                    <x-ui.btn type="submit" icon="guardar" data-registro-enviar><span data-texto-enviar>{{ $esNuevo ? ($crearCuenta ? 'Registrar proveedor y acceso' : 'Registrar proveedor') : 'Guardar cambios' }}</span></x-ui.btn>
                </div>
            </form>
        </x-ui.card>

        @unless ($esNuevo)
            <div class="space-y-4">
                <x-ui.card padding="p-4">
                    <h2 class="mb-3 text-sm font-semibold text-eh-text">Estado del proveedor</h2>
                    <p class="mb-3"><x-ui.estado :estado="$proveedor->estado->value" /></p>
                    <form method="POST" action="{{ route('admin.proveedores.estado', $proveedor->id) }}" class="space-y-3" data-once>
                        @csrf
                        @method('PATCH')
                        <x-ui.select name="estado" label="Cambiar a" :selected="$proveedor->estado->value"
                            :options="collect(\App\Domain\Proveedores\EstadoProveedor::cases())->mapWithKeys(fn ($estado) => [$estado->value => $estado->etiqueta()])->all()" />
                        <x-ui.btn type="submit" variant="secondary" class="w-full">Actualizar estado</x-ui.btn>
                    </form>
                    <p class="mt-3 text-xs text-eh-text-muted">
                        Es el estado productivo de la ficha; no bloquea ni desactiva su cuenta móvil. Un proveedor suspendido o retirado deja de aparecer al registrar entregas nuevas.
                    </p>
                </x-ui.card>

                <x-ui.card padding="p-4">
                    <h2 class="mb-3 text-sm font-semibold text-eh-text">Acceso a la app móvil</h2>
                    @if ($proveedor->usuarioId)
                        <div class="mb-3 flex items-center gap-3 rounded-xl bg-eh-surface-alt p-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-eh-primary-soft text-eh-primary">
                                <x-icon name="userCircle" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="mono truncate text-sm font-medium text-eh-text">{{ $cuentaVinculada->username ?? 'Usuario #'.$proveedor->usuarioId }}</p>
                                <p class="truncate text-xs text-eh-text-muted">{{ $cuentaVinculada->nombres ?? 'Acceso propio del proveedor' }}</p>
                            </div>
                            @if ($cuentaVinculada)
                                <x-ui.estado :estado="$cuentaVinculada->activo ? 'activo' : 'inactivo'" />
                            @endif
                        </div>
                        @if ($cuentaVinculada && $cuentaVinculada->nombres !== $proveedor->nombres)
                            <p class="mb-3 text-xs text-eh-gold">El nombre de la cuenta difiere del de la ficha. Revísalo en ambos registros; no se sincronizan automáticamente.</p>
                        @endif
                        @if (($puedeVerCuentas ?? false) && $cuentaVinculada)
                            <x-ui.btn :href="route('admin.usuarios.edit', $cuentaVinculada->id)" variant="outline" size="sm" icon="usuarios" class="mb-2 w-full">Ver cuenta en Usuarios y accesos</x-ui.btn>
                        @endif
                        <form method="POST" action="{{ route('admin.proveedores.desvincular', $proveedor->id) }}"
                            data-confirm="¿Desvincular la cuenta de {{ $proveedor->nombres }}? Perderá el acceso a la app móvil.">
                            @csrf
                            <x-ui.btn type="submit" variant="ghost" class="w-full">Desvincular cuenta</x-ui.btn>
                        </form>
                    @elseif ($cuentasCandidatas !== [])
                        <form method="POST" action="{{ route('admin.proveedores.vincular', $proveedor->id) }}" class="space-y-3" data-once>
                            @csrf
                            <x-ui.select name="usuario_id" label="Cuenta de proveedor con el mismo DNI" required placeholder="Selecciona una cuenta"
                                :options="collect($cuentasCandidatas)->mapWithKeys(fn ($usuario) => [$usuario->id => $usuario->username.' · '.$usuario->nombres])->all()" />
                            <x-ui.btn type="submit" variant="secondary" class="w-full">Vincular cuenta existente</x-ui.btn>
                        </form>
                        <p class="mt-3 text-xs text-eh-text-muted">Ya existe una cuenta con rol Proveedor y el DNI {{ $proveedor->dni }}. Vincúlala en lugar de crear otra.</p>
                    @elseif ($puedeCrearCuentas ?? false)
                        <form method="POST" action="{{ route('admin.proveedores.cuenta', $proveedor->id) }}" class="space-y-3" data-once data-cuenta-ficha>
                            @csrf
                            <p class="text-xs text-eh-text-muted">Este proveedor aún no tiene acceso móvil. Crea su cuenta con rol Proveedor; el código y el QR no cambian.</p>
                            <x-ui.field name="username" id="cuenta-username" label="Usuario de acceso" required minlength="3" maxlength="30" pattern="[a-z0-9._]{3,30}" autocomplete="off" spellcheck="false" placeholder="Ej. juan.perez" />
                            <x-ui.field name="pin" id="cuenta-pin" label="PIN inicial" type="password" required inputmode="numeric" minlength="4" maxlength="4" pattern="[0-9]{4}" autocomplete="new-password" />
                            <x-ui.field name="pin_confirmation" id="cuenta-pin-confirmacion" label="Confirmar PIN" type="password" required inputmode="numeric" minlength="4" maxlength="4" pattern="[0-9]{4}" autocomplete="new-password" />
                            <x-ui.btn type="submit" variant="secondary" class="w-full">Crear cuenta móvil</x-ui.btn>
                        </form>
                    @else
                        <p class="text-xs text-eh-text-muted">Sin cuenta móvil. {{ ($puedeVerCuentas ?? false) ? 'El DNI coincide con una cuenta que no se puede vincular automáticamente; revísala en Usuarios y accesos.' : 'Solo un administrador puede crear o vincular su acceso.' }}</p>
                    @endif
                </x-ui.card>

                <x-ui.card padding="p-4">
                    <h2 class="mb-3 text-sm font-semibold text-eh-text">Código QR</h2>
                    <p class="mb-3 text-xs text-eh-text-muted">El acopiador lo escanea para identificar al proveedor en campo.</p>
                    <x-ui.btn type="button" data-proveedor-qr :data-qr-url="route('admin.proveedores.qr', $proveedor->id)" :data-qr-nombre="$proveedor->nombres" :data-qr-codigo="$proveedor->codigo" aria-haspopup="dialog" aria-controls="proveedor-qr-modal" variant="outline" icon="qrCode" class="w-full">
                        Ver código QR
                    </x-ui.btn>
                </x-ui.card>
            </div>
        @endunless
    </div>
    </div>
    @unless ($esNuevo)
        @include('admin.proveedores.qr-modal')
    @endunless
@endsection
