function isDark() {
    return document.documentElement.classList.contains('dark');
}

function setDark(dark) {
    document.documentElement.classList.toggle('dark', dark);
    try {
        localStorage.setItem('eh-theme', dark ? 'dark' : 'light');
    } catch {}
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        const label = btn.querySelector('[data-theme-label]');
        if (label) {
            label.textContent = dark ? 'Modo claro' : 'Modo oscuro';
        }
        btn.querySelectorAll('[data-theme-icon]').forEach((icon) => {
            icon.hidden = icon.dataset.themeIcon !== (dark ? 'sun' : 'moon');
        });
    });
}

export function bindThemeToggle() {
    setDark(isDark());
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => setDark(!isDark()));
    });
}
