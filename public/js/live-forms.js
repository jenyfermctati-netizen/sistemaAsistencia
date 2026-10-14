(function () {
    const regionesActualizando = new Set();

    function mostrarAlerta(mensaje, tipo) {
        const contenedor = document.querySelector('.main-content');

        if (!contenedor) {
            return;
        }

        contenedor.querySelectorAll('.alert[data-live-alert]').forEach(function (alerta) {
            alerta.remove();
        });

        const alerta = document.createElement('div');
        alerta.className = `alert alert--${tipo === 'success' ? 'success' : 'danger'}`;
        alerta.dataset.liveAlert = 'true';
        alerta.setAttribute('role', tipo === 'success' ? 'status' : 'alert');

        const texto = document.createElement('span');
        texto.textContent = mensaje;

        const cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.className = 'alert__close';
        cerrar.textContent = '×';
        cerrar.setAttribute('aria-label', 'Cerrar mensaje');
        cerrar.addEventListener('click', function () {
            alerta.remove();
        });

        alerta.append(texto, cerrar);
        contenedor.prepend(alerta);

        if (tipo === 'success') {
            window.setTimeout(function () {
                alerta.remove();
            }, 5000);
        }
    }

    function limpiarErrores(formulario) {
        formulario.querySelector('[data-live-errors]')?.remove();
        formulario.querySelectorAll('[aria-invalid="true"]').forEach(function (campo) {
            campo.removeAttribute('aria-invalid');
        });
    }

    function mostrarErrores(formulario, datos) {
        limpiarErrores(formulario);

        const errores = datos.errors
            ? Object.values(datos.errors).flat()
            : [datos.message || 'No se pudo completar la operación.'];
        const caja = document.createElement('div');
        caja.className = 'live-form-errors';
        caja.dataset.liveErrors = 'true';
        caja.setAttribute('role', 'alert');

        const titulo = document.createElement('strong');
        titulo.textContent = 'Revisa la información ingresada:';
        const lista = document.createElement('ul');

        errores.forEach(function (mensaje) {
            const item = document.createElement('li');
            item.textContent = mensaje;
            lista.append(item);
        });

        caja.append(titulo, lista);
        formulario.prepend(caja);

        if (datos.errors) {
            Object.keys(datos.errors).forEach(function (nombre) {
                const campo = formulario.elements.namedItem(nombre);

                if (campo instanceof HTMLElement) {
                    campo.setAttribute('aria-invalid', 'true');
                }
            });
        }
    }

    async function actualizarRegion(nombre, url, opciones) {
        const configuracion = opciones || {};
        const regionActual = document.querySelector(`[data-live-region="${nombre}"]`);

        if (!regionActual) {
            return;
        }

        if (regionesActualizando.has(nombre)) {
            if (configuracion.silencioso) {
                return;
            }

            await new Promise(function (resolver) {
                window.setTimeout(resolver, 120);
            });

            return actualizarRegion(nombre, url, configuracion);
        }

        regionesActualizando.add(nombre);

        if (!configuracion.silencioso) {
            regionActual.classList.add('live-region--loading');
        }

        try {
            const respuesta = await fetch(url, {
                cache: 'no-store',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!respuesta.ok) {
                throw new Error('No se pudo actualizar el listado.');
            }

            const documento = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
            const regionNueva = documento.querySelector(`[data-live-region="${nombre}"]`);

            if (!regionNueva) {
                throw new Error('La respuesta no contiene el listado actualizado.');
            }

            if (configuracion.silencioso && regionActual.innerHTML === regionNueva.innerHTML) {
                return;
            }

            regionActual.replaceWith(regionNueva);

            if (!configuracion.silencioso) {
                window.history.replaceState({}, '', url);
            }

            document.dispatchEvent(new CustomEvent('live:updated', {
                detail: { region: nombre },
            }));
        } finally {
            regionesActualizando.delete(nombre);
            document.querySelector(`[data-live-region="${nombre}"]`)
                ?.classList.remove('live-region--loading');
        }
    }

    document.addEventListener('submit', async function (evento) {
        const formulario = evento.target.closest('form[data-live-form]');

        if (!formulario) {
            return;
        }

        evento.preventDefault();

        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }

        limpiarErrores(formulario);

        const boton = evento.submitter || formulario.querySelector('[type="submit"]');
        const textoOriginal = boton?.textContent;

        if (boton) {
            boton.disabled = true;
            boton.textContent = 'Procesando…';
        }

        try {
            const respuesta = await fetch(formulario.action, {
                method: formulario.method || 'POST',
                body: new FormData(formulario),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const tipo = respuesta.headers.get('content-type') || '';
            const datos = tipo.includes('application/json')
                ? await respuesta.json()
                : { message: 'El servidor devolvió una respuesta inesperada.' };

            if (!respuesta.ok) {
                mostrarErrores(formulario, datos);
                return;
            }

            const modal = formulario.closest('.modal');

            if (modal && window.AppModal) {
                window.AppModal.close(modal.id);
            }

            if (formulario.hasAttribute('data-reset-on-success')) {
                formulario.reset();
            }

            const destino = formulario.dataset.liveTarget;
            const url = datos.refresh_url || window.location.href;

            if (destino) {
                await actualizarRegion(destino, url);
            }

            mostrarAlerta(datos.message || 'Cambios guardados correctamente.', 'success');
        } catch (error) {
            mostrarAlerta(error.message || 'No se pudo conectar con el servidor.', 'error');
        } finally {
            if (boton) {
                boton.disabled = false;
                boton.textContent = textoOriginal;
            }
        }
    });

    document.querySelectorAll('[data-live-region][data-live-poll]').forEach(function (region) {
        const nombre = region.dataset.liveRegion;
        const intervalo = Number(region.dataset.livePoll);

        if (!nombre || !Number.isFinite(intervalo) || intervalo < 3000) {
            return;
        }

        window.setInterval(async function () {
            if (document.hidden) {
                return;
            }

            try {
                await actualizarRegion(nombre, window.location.href, { silencioso: true });
            } catch (error) {
                // La siguiente consulta reintentará sin interrumpir al usuario.
            }
        }, intervalo);
    });

    window.LiveForms = {
        refresh: actualizarRegion,
        alert: mostrarAlerta,
    };
})();
