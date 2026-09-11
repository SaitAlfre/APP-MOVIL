import { iniciarSeguimiento } from './seguimiento';
import { iniciarEscanerQr } from './qr-scanner';

document.addEventListener('DOMContentLoaded', () => {
    iniciarSeguimiento();
    iniciarEscanerQr();
});
