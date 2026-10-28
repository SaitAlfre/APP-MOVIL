<ul class="grid list-none gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Proveedores en tarjetas">
    @foreach($proveedores as $proveedor)
        @php
            $iniciales = collect(preg_split('/\s+/u', trim($proveedor->nombres)))->filter()->take(2)->map(fn($nombre)=>mb_strtoupper(mb_substr($nombre,0,1)))->implode('');
            $telefono = trim($proveedor->telefono ?? '');
            $numeroTelefono = preg_replace('/[^0-9+]/', '', $telefono);
            $puedeLlamar = (bool) preg_match('/^\+?[0-9]{6,15}$/', $numeroTelefono);
        @endphp
        <li class="min-w-0">
            <article data-proveedor-tarjeta class="flex h-full min-w-0 flex-col rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm transition-colors hover:border-eh-border-strong">
                <div class="flex items-start gap-3">
                    <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-eh-primary-soft text-sm font-semibold text-eh-primary">{{ $iniciales }}</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="break-words text-sm font-semibold"><a href="{{ route('admin.proveedores.show',$proveedor->id) }}" class="rounded hover:text-eh-primary hover:underline focus-visible:outline-2 focus-visible:outline-eh-primary">{{ $proveedor->nombres }}</a></h2>
                        <p class="mt-1 break-all font-mono text-xs text-eh-text-muted">{{ $proveedor->codigo }}</p>
                    </div>
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3"><dt class="text-eh-text-muted">DNI</dt><dd class="break-all text-right font-mono text-xs">{{ $proveedor->dni }}</dd></div>
                    <div class="flex items-start justify-between gap-3"><dt class="text-eh-text-muted">Zona</dt><dd class="max-w-[70%] break-words text-right">{{ $zonas->get($proveedor->zonaId)?->nombre ?? 'Sin zona registrada' }}</dd></div>
                    @if($telefono !== '')
                        <div class="flex items-start justify-between gap-3"><dt class="text-eh-text-muted">Teléfono</dt><dd class="min-w-0 break-words text-right">
                            @if($puedeLlamar)
                                <a href="tel:{{ $numeroTelefono }}" class="rounded text-eh-primary hover:underline focus-visible:outline-2 focus-visible:outline-eh-primary" aria-label="Llamar a {{ $proveedor->nombres }}">{{ $telefono }}</a>
                            @else
                                {{ $telefono }}
                            @endif
                        </dd></div>
                    @endif
                </dl>
                <div class="mt-4"><x-ui.estado :estado="$proveedor->estado->value" /></div>
                <div class="mt-auto pt-5"><div class="flex items-center justify-between gap-3 border-t border-eh-border pt-3 text-xs">
                    <a href="{{ route('admin.proveedores.show',$proveedor->id) }}" aria-label="Ver ficha de {{ $proveedor->nombres }}" class="inline-flex min-h-9 items-center rounded-lg font-semibold text-eh-primary hover:underline focus-visible:outline-2 focus-visible:outline-eh-primary">Ver ficha</a>
                    @if($puedeGestionar)
                        <a href="{{ route('admin.proveedores.edit',$proveedor->id) }}" aria-label="Editar {{ $proveedor->nombres }}" class="inline-flex min-h-9 items-center rounded-lg text-eh-text-muted hover:text-eh-primary hover:underline focus-visible:outline-2 focus-visible:outline-eh-primary">Editar</a>
                    @endif
                </div></div>
            </article>
        </li>
    @endforeach
</ul>
