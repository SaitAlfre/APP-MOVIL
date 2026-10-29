<dialog id="preferencias-interfaz" data-modal data-preferences data-i18n aria-labelledby="preferencias-interfaz-titulo" class="border-l border-eh-border bg-eh-surface p-0 text-eh-text shadow-2xl backdrop:bg-black/40">
    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-eh-border p-5">
        <div><h2 id="preferencias-interfaz-titulo" tabindex="-1" class="font-semibold">Personalizar mi vista</h2><p class="mt-1 text-xs text-eh-text-muted">Ajusta la apariencia a tu comodidad.</p></div>
        <button type="button" data-modal-close data-i18n-attrs="aria-label" aria-label="Cerrar preferencias" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl hover:bg-eh-surface-alt"><x-icon name="xMark" class="h-4 w-4" /></button>
    </div>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-5">
        <fieldset class="border-b border-eh-border pb-5"><legend class="mb-3 text-sm font-semibold">Tema</legend><div class="grid grid-cols-3 gap-2">
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-theme" value="light" data-preference="theme" class="sr-only">
                <x-icon name="sun" class="h-5 w-5" />
                <span>Claro</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-theme" value="dark" data-preference="theme" class="sr-only">
                <x-icon name="moon" class="h-5 w-5" />
                <span>Oscuro</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-theme" value="system" data-preference="theme" class="sr-only">
                <x-icon name="cog" class="h-5 w-5" />
                <span>Sistema</span>
            </label>
        </div></fieldset>
        <fieldset class="border-b border-eh-border pb-5"><legend class="mb-3 text-sm font-semibold">Color principal</legend><div class="grid grid-cols-3 gap-2">
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-accent" value="coral" data-preference="accent" class="sr-only">
                <span class="preference-swatch swatch-coral h-5 w-5 rounded-full" aria-hidden="true"></span>
                <span>Coral</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-accent" value="green" data-preference="accent" class="sr-only">
                <span class="preference-swatch swatch-green h-5 w-5 rounded-full" aria-hidden="true"></span>
                <span>Verde</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-accent" value="blue" data-preference="accent" class="sr-only">
                <span class="preference-swatch swatch-blue h-5 w-5 rounded-full" aria-hidden="true"></span>
                <span>Azul</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-accent" value="violet" data-preference="accent" class="sr-only">
                <span class="preference-swatch swatch-violet h-5 w-5 rounded-full" aria-hidden="true"></span>
                <span>Violeta</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-accent" value="slate" data-preference="accent" class="sr-only">
                <span class="preference-swatch swatch-slate h-5 w-5 rounded-full" aria-hidden="true"></span>
                <span>Pizarra</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-accent" value="orange" data-preference="accent" class="sr-only">
                <span class="preference-swatch swatch-orange h-5 w-5 rounded-full" aria-hidden="true"></span>
                <span>Naranja</span>
            </label>
        </div></fieldset>
        <fieldset class="border-b border-eh-border pb-5"><legend class="mb-3 text-sm font-semibold">Espaciado</legend><div class="grid grid-cols-3 gap-2">
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-density" value="compact" data-preference="density" class="sr-only">
                <span>Compacto</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-density" value="comfortable" data-preference="density" class="sr-only">
                <span>Cómodo</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-density" value="spacious" data-preference="density" class="sr-only">
                <span>Amplio</span>
            </label>
        </div></fieldset>
        <fieldset class="border-b border-eh-border pb-5"><legend class="mb-3 text-sm font-semibold">Menú lateral</legend><div class="grid grid-cols-2 gap-2">
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-sidebar" value="expanded" data-preference="sidebar" class="sr-only">
                <x-icon name="bars3" class="h-5 w-5" />
                <span>Expandido</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-sidebar" value="compact" data-preference="sidebar" class="sr-only">
                <x-icon name="chevronRight" class="h-5 w-5" />
                <span>Solo iconos</span>
            </label>
        </div></fieldset>
        <fieldset class="border-b border-eh-border pb-5"><legend class="mb-3 text-sm font-semibold">Ancho del contenido</legend><div class="grid grid-cols-2 gap-2">
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-container" value="boxed" data-preference="container" class="sr-only">
                <span>Centrado</span>
            </label>
            <label class="preference-option relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-eh-border p-3 text-center text-xs hover:bg-eh-surface-alt">
                <input type="radio" name="vista-container" value="fluid" data-preference="container" class="sr-only">
                <span>Completo</span>
            </label>
        </div></fieldset>
        <fieldset><legend class="mb-3 text-sm font-semibold">Idioma</legend>
            <div class="grid grid-cols-2 gap-2">
                <label class="preference-option cursor-pointer rounded-xl border border-eh-border p-3 text-sm"><input type="radio" name="vista-language" data-language checked class="mr-2" value="es"><span lang="es" data-no-translate>Español</span></label>
                <label class="preference-option cursor-pointer rounded-xl border border-eh-border p-3 text-sm"><input type="radio" name="vista-language" data-language class="mr-2" value="qu"><span lang="qu-PE" data-no-translate>Quechua · Runa simi</span></label>
            </div>
        </fieldset>
        <button type="button" data-preferences-reset class="w-full rounded-xl border border-eh-border-strong px-4 py-3 text-sm font-medium hover:bg-eh-surface-alt">Restablecer apariencia</button>
        <p data-preferences-status role="status" aria-live="polite" class="text-xs text-eh-text-muted"></p>
    </div>
</dialog>
