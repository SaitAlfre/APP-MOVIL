const defaults = { theme: 'light', accent: 'coral', density: 'comfortable', container: 'boxed', sidebar: 'expanded' };
const choices = { theme: ['light', 'dark', 'system'], accent: ['coral', 'green', 'blue', 'violet', 'slate', 'orange'], density: ['compact', 'comfortable', 'spacious'], container: ['boxed', 'fluid'], sidebar: ['expanded', 'compact'] };

export function bindThemeToggle() {
    const current = { ...defaults };
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    for (const key of Object.keys(defaults)) {
        try {
            const saved = key === 'sidebar' ? (localStorage.getItem('ecolecta.sidebar.compact') === 'true' ? 'compact' : 'expanded') : localStorage.getItem(`eh-${key}`);
            if (choices[key].includes(saved)) { current[key] = saved; }
        } catch {}
    }
    const apply = () => {
        const dark = current.theme === 'dark' || (current.theme === 'system' && system.matches);
        document.documentElement.classList.toggle('dark', dark);
        for (const key of ['accent', 'density', 'container']) { document.documentElement.dataset[key] = current[key]; }
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#100e0d' : '#f8f7f6');
        document.querySelectorAll('[data-preference]').forEach((input) => { input.checked = input.value === current[input.dataset.preference]; });
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-label', dark ? 'Activar modo claro' : 'Activar modo oscuro');
            button.querySelectorAll('[data-theme-icon]').forEach((icon) => { icon.hidden = icon.dataset.themeIcon !== (dark ? 'sun' : 'moon'); });
            const label = button.querySelector('[data-theme-label]');
            if (label) { label.textContent = dark ? 'Modo claro' : 'Modo oscuro'; }
        });
    };
    const save = () => {
        try { for (const key of ['theme', 'accent', 'density', 'container']) { localStorage.setItem(`eh-${key}`, current[key]); } } catch {}
    };
    apply();
    system.addEventListener('change', () => { if (current.theme === 'system') { apply(); } });
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => button.addEventListener('click', () => {
        current.theme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        apply(); save();
    }));
    document.querySelectorAll('[data-preference]').forEach((input) => input.addEventListener('change', () => {
        const key = input.dataset.preference;
        if (!input.checked || !choices[key]?.includes(input.value)) { return; }
        current[key] = input.value;
        if (key === 'sidebar') { document.dispatchEvent(new CustomEvent('ecolecta:sidebar', { detail: { compact: input.value === 'compact' } })); }
        apply(); save();
        const status = document.querySelector('[data-preferences-status]');
        if (status) { status.textContent = 'Preferencias guardadas en este navegador.'; }
    }));
    document.addEventListener('ecolecta:sidebar-updated', (event) => {
        current.sidebar = event.detail.compact ? 'compact' : 'expanded';
        document.querySelectorAll('[data-preference="sidebar"]').forEach((input) => { input.checked = input.value === current.sidebar; });
    });
    document.querySelector('[data-preferences-reset]')?.addEventListener('click', () => {
        Object.assign(current, defaults);
        document.dispatchEvent(new CustomEvent('ecolecta:sidebar', { detail: { compact: false } }));
        apply(); save();
        document.querySelector('[data-preferences-status]').textContent = 'Se restableció la apariencia original.';
    });
}
