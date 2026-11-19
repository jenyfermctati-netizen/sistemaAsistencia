(() => {
    const form = document.querySelector('[data-consulta-ruc-url]');
    if (!form) return;

    const ruc = form.querySelector('[name="ruc"]');
    const razonSocial = form.querySelector('[name="razon_social"]');
    const direccion = form.querySelector('[name="direccion"]');
    const status = form.querySelector('#consulta-ruc-status');
    let timer;
    let controller;
    let lastAutofilled = {};

    const setStatus = (message, state = '') => {
        status.textContent = message;
        status.dataset.state = state;
    };

    const consultar = async () => {
        const numero = ruc.value.trim();
        if (!/^\d{11}$/.test(numero)) {
            setStatus('Escribe los 11 dígitos para consultar los datos en SUNAT.');
            return;
        }

        controller?.abort();
        controller = new AbortController();
        setStatus('Consultando RUC…', 'loading');

        try {
            const response = await fetch(form.dataset.consultaRucUrl, {
                method: 'POST',
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                },
                body: JSON.stringify({ ruc: numero }),
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                setStatus(result.message || 'No se pudo consultar el RUC. Puedes completar los datos manualmente.', 'error');
                return;
            }

            razonSocial.value = result.data.razon_social || '';
            direccion.value = result.data.direccion || '';
            lastAutofilled = {
                razon_social: razonSocial.value,
                direccion: direccion.value,
            };
            razonSocial.dispatchEvent(new Event('input', { bubbles: true }));
            direccion.dispatchEvent(new Event('input', { bubbles: true }));

            const estado = [result.data.estado, result.data.condicion].filter(Boolean).join(' · ');
            setStatus(`Datos encontrados${estado ? `: ${estado}` : ''}. Nombre comercial, correo y teléfono se pueden completar manualmente.`, 'success');
        } catch (error) {
            if (error.name !== 'AbortError') {
                setStatus('No se pudo conectar con el servicio. Puedes completar los datos manualmente.', 'error');
            }
        }
    };

    ruc.addEventListener('input', () => {
        const numero = ruc.value.replace(/\D/g, '').slice(0, 11);
        if (ruc.value !== numero) ruc.value = numero;

        Object.entries(lastAutofilled).forEach(([field, value]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input.value === value) input.value = '';
        });
        lastAutofilled = {};

        clearTimeout(timer);
        controller?.abort();
        if (numero.length === 11) {
            timer = setTimeout(consultar, 350);
        } else {
            setStatus('Escribe los 11 dígitos para consultar los datos en SUNAT.');
        }
    });

    if (/^\d{11}$/.test(ruc.value.trim())) consultar();
})();
