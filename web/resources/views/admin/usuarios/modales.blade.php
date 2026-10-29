    @if ($puedeGestionar)
        @foreach ($cuentasPagina as $usuario)
            @continue(auth('operador')->user()->perfil_rol_id && ($usuario->perfil_rol_id !== auth('operador')->user()->perfil_rol_id || $usuario->tieneRol('admin')))
            @php($suspendida = $usuario->suspendidaAdministrativamente())
            <x-ui.modal :id="'acceso-'.$usuario->id" :title="$suspendida ? 'Restaurar acceso' : 'Suspender acceso'" size="sm">
                <form method="POST" action="{{ route('admin.usuarios.acceso', $usuario->id) }}" class="space-y-4" data-once data-descartar-al-cerrar>
                    @csrf
                    <input type="hidden" name="suspender" value="{{ $suspendida ? 0 : 1 }}">
                    <p class="text-sm text-eh-text-muted">Cuenta: <strong class="text-eh-text">{{ $usuario->nombres }} · {{ $usuario->username }}</strong>.</p>
                    @if ($suspendida)
                        <p class="text-sm">Se restaurará el acceso administrativo y se exigirá iniciar sesión de nuevo. Los bloqueos temporales seguirán vigentes.</p>
                        @if ($usuario->fichaProveedor && $usuario->roles === ['proveedor'] && $usuario->fichaProveedor->estado === \App\Domain\Proveedores\EstadoProveedor::Retirado)
                            <p class="text-sm text-eh-red">La ficha de proveedor está Retirada. Reactívala en Proveedores: la cuenta se restaurará junto con ella.</p>
                        @elseif ($usuario->fichaProveedor)
                            <p class="text-sm text-eh-text-muted">Ficha de proveedor: {{ $usuario->fichaProveedor->estado === \App\Domain\Proveedores\EstadoProveedor::Suspendido && ($usuario->fichaProveedor->suspension_origen === \App\Domain\Proveedores\ProveedorRepositoryInterface::ORIGEN_ACCESO_CUENTA || $usuario->suspension_origen === \App\Domain\Usuarios\UsuarioRepositoryInterface::ORIGEN_FICHA_PROVEEDOR) ? 'se reactivará, porque se suspendió junto con esta cuenta.' : 'conservará su estado actual ('.$usuario->fichaProveedor->estado->etiqueta().').' }}</p>
                        @endif
                    @else
                        <p class="text-sm">Se impedirá el acceso web y móvil y se cerrarán las sesiones abiertas. El historial se conservará.</p>
                        @if ($usuario->tieneRol('proveedor'))
                            <p class="text-sm text-eh-text-muted">{{ ! $usuario->fichaProveedor ? 'No tiene ficha de proveedor vinculada: solo se suspenderá la cuenta.' : ($usuario->fichaProveedor->estado === \App\Domain\Proveedores\EstadoProveedor::Activo ? 'Su ficha de proveedor también quedará Suspendida y no podrá registrar nuevas entregas.' : 'Su ficha de proveedor conservará su estado actual ('.$usuario->fichaProveedor->estado->etiqueta().').') }}</p>
                        @endif
                        <label for="motivo-{{ $usuario->id }}" class="block text-sm font-medium">Motivo (obligatorio)</label>
                        <textarea id="motivo-{{ $usuario->id }}" name="motivo" required maxlength="255" rows="3" class="w-full rounded-xl border border-eh-border bg-eh-surface p-3 text-sm"></textarea>
                    @endif
                    <div class="flex flex-wrap justify-end gap-2">
                        <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                        <x-ui.btn type="submit" :variant="$suspendida ? 'primary' : 'danger'">{{ $suspendida ? 'Confirmar restauración' : 'Confirmar suspensión' }}</x-ui.btn>
                    </div>
                </form>
            </x-ui.modal>

            <x-ui.modal :id="'pin-'.$usuario->id" title="Restablecer PIN" size="sm">
                <form method="POST" action="{{ route('admin.usuarios.restablecer', $usuario->id) }}" class="space-y-4" data-once>
                    @csrf
                    <p class="text-sm text-eh-text-muted">
                        Se asignará un PIN nuevo a <strong class="text-eh-text">{{ $usuario->nombres }}</strong> y se cerrarán sus sesiones abiertas.
                        El PIN anterior no se muestra en ningún momento.
                    </p>
                    <div class="flex flex-col gap-1">
                        <label for="pin-nuevo-{{ $usuario->id }}" class="text-sm font-medium text-eh-text">
                            Nuevo PIN <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                        </label>
                        <input id="pin-nuevo-{{ $usuario->id }}" type="password" inputmode="numeric" name="pin" required minlength="4" maxlength="4" pattern="[0-9]{4}" autocomplete="new-password"
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        <p class="text-xs text-eh-text-muted">Exactamente 4 dígitos numéricos.</p>
                    </div>
                    <x-ui.field :id="'pin-confirmacion-'.$usuario->id" name="pin_confirmation" label="Confirmar PIN" type="password" required inputmode="numeric" minlength="4" maxlength="4" pattern="[0-9]{4}" autocomplete="new-password" />
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                        <x-ui.btn type="submit">Restablecer PIN</x-ui.btn>
                    </div>
                </form>
            </x-ui.modal>
        @endforeach
    @endif
