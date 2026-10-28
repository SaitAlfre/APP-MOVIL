    @if ($puedeGestionar)
        @foreach ($cuentasPagina as $usuario)
            @if (! $usuario->bloqueado_manualmente)
                <x-ui.modal :id="'bloquear-'.$usuario->id" title="Bloquear cuenta" size="sm">
                    <form method="POST" action="{{ route('admin.usuarios.bloquear', $usuario->id) }}" class="space-y-4" data-once>
                        @csrf
                        <p class="text-sm text-eh-text-muted">
                            Vas a bloquear la cuenta de <strong class="text-eh-text">{{ $usuario->nombres }}</strong>.
                            No podrá iniciar sesión hasta que se desbloquee y la acción queda registrada en auditoría.
                        </p>
                        <div class="flex flex-col gap-1">
                            <label for="motivo-{{ $usuario->id }}" class="text-sm font-medium text-eh-text">
                                Motivo <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                            </label>
                            <textarea id="motivo-{{ $usuario->id }}" name="motivo" required maxlength="255" rows="3"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary"></textarea>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                            <x-ui.btn type="submit" variant="danger">Confirmar bloqueo</x-ui.btn>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

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
                        <input id="pin-nuevo-{{ $usuario->id }}" type="password" inputmode="numeric" name="pin" required minlength="4" maxlength="4" pattern="\d{4}"
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        <p class="text-xs text-eh-text-muted">Exactamente 4 dígitos numéricos.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                        <x-ui.btn type="submit">Restablecer PIN</x-ui.btn>
                    </div>
                </form>
            </x-ui.modal>
        @endforeach
    @endif
