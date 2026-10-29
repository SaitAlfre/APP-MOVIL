<script>
    (function () {
        const umbral = {{ (float) config('ecolecta.umbral_merma_porcentaje') }};

        document.querySelectorAll('[data-recepcion-medidos]').forEach((input) => {
            const jornada = input.dataset.jornada;
            const recolectado = parseFloat(input.dataset.recolectado);
            const salida = document.querySelector('[data-recepcion-diferencia="' + jornada + '"]');
            const motivo = document.querySelector('[data-recepcion-motivo="' + jornada + '"]');
            const marca = document.querySelector('[data-recepcion-motivo-obligatorio="' + jornada + '"]');

            if (!salida) {
                return;
            }

            const exigirMotivo = (exigir) => {
                if (motivo) {
                    motivo.required = exigir;
                }
                if (marca) {
                    marca.hidden = !exigir;
                }
            };

            const actualizar = () => {
                const medido = input.value !== '' ? parseFloat(input.value) : null;

                if (medido === null || Number.isNaN(medido)) {
                    salida.textContent = 'Ingresa los litros medidos para ver la diferencia.';
                    salida.className = 'rounded-xl bg-eh-surface-alt px-3 py-2 text-sm text-eh-text-muted';
                    exigirMotivo(false);
                    return;
                }

                const diferencia = Math.round((medido - recolectado) * 10) / 10;
                const porcentaje = recolectado > 0 ? (Math.abs(diferencia) / recolectado) * 100 : 0;

                if (diferencia === 0) {
                    salida.textContent = 'Sin diferencia: la planta midió lo mismo que se registró en campo.';
                    salida.className = 'rounded-xl bg-eh-primary-soft px-3 py-2 text-sm font-medium text-eh-primary';
                    exigirMotivo(false);
                    return;
                }

                const texto = (diferencia < 0 ? 'Faltan ' : 'Sobran ') + Math.abs(diferencia).toFixed(1) + ' L (' + porcentaje.toFixed(1) + '%). Escribe el motivo para guardar.';
                const alerta = diferencia < 0 && porcentaje > umbral;

                salida.textContent = alerta ? texto + ' Supera el ' + umbral + '% permitido.' : texto;
                salida.className = alerta
                    ? 'rounded-xl bg-eh-red-soft px-3 py-2 text-sm font-medium text-eh-red'
                    : 'rounded-xl bg-eh-gold-soft px-3 py-2 text-sm font-medium text-eh-gold';
                exigirMotivo(true);
            };

            const ocultarErrorAnterior = () => input.closest('dialog')?.querySelector('[data-modal-errors]')?.setAttribute('hidden', '');

            input.addEventListener('input', () => { actualizar(); ocultarErrorAnterior(); });
            motivo?.addEventListener('input', ocultarErrorAnterior);
            actualizar();
        });
    })();
</script>
