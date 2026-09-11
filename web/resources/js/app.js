import { iniciarSeguimiento } from './seguimiento';
import { iniciarEscanerQr } from './qr-scanner';
import { bindThemeToggle } from './theme';

document.addEventListener('DOMContentLoaded', () => {
    bindThemeToggle();
    iniciarSeguimiento();
    iniciarEscanerQr();
});
