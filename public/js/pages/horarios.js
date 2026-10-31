document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formHorarioGeneral');
    const selectorFormato = document.getElementById('formatoHora');
    const indicadorFormato = document.getElementById('formatoIndicador');
    const copiarLunes = document.getElementById('copiarLunes');
    const estadoCopia = document.getElementById('horarioCopyStatus');
    const vistaMovil = window.matchMedia('(max-width: 700px)');

    if (!form || !selectorFormato) {
        return;
    }

    function hora12Desde24(valor) {
        const partes = /^(\d{2}):(\d{2})$/.exec(valor || '');

        if (!partes) {
            return { hora: '', periodo: 'AM' };
        }

        const hora24 = Number(partes[1]);
        const minutos = partes[2];
        const periodo = hora24 >= 12 ? 'PM' : 'AM';
        const hora12 = hora24 % 12 || 12;

        return {
            hora: `${String(hora12).padStart(2, '0')}:${minutos}`,
            periodo: periodo,
        };
    }

    function hora24Desde12(valor, periodo) {
        const partes = /^(\d{1,2}):(\d{2})$/.exec((valor || '').trim());

        if (!partes) {
            return null;
        }

        let hora = Number(partes[1]);
        const minutos = Number(partes[2]);

        if (hora < 1 || hora > 12 || minutos < 0 || minutos > 59) {
            return null;
        }

        if (periodo === 'AM') {
            hora = hora === 12 ? 0 : hora;
        } else {
            hora = hora === 12 ? 12 : hora + 12;
        }

        return `${String(hora).padStart(2, '0')}:${String(minutos).padStart(2, '0')}`;
    }

    function hora24Valida(valor) {
        const partes = /^(\d{2}):(\d{2})$/.exec((valor || '').trim());

        if (!partes) {
            return false;
        }

        return Number(partes[1]) <= 23 && Number(partes[2]) <= 59;
    }

    function sincronizarDesde24(editor) {
        const campo24 = editor.querySelector('[data-hora-24]');
        const normalizada = editor.querySelector('[data-hora-normalizada]');

        if (!hora24Valida(campo24.value)) {
            campo24.setCustomValidity('Ingresa una hora válida entre 00:00 y 23:59.');
            return false;
        }

        campo24.setCustomValidity('');
        normalizada.value = campo24.value.trim();
        actualizarVistasHora(editor);
        return true;
    }

    function actualizarVistasHora(editor) {
        const normalizada = editor.querySelector('[data-hora-normalizada]');
        const campo24 = editor.querySelector('[data-hora-24]');
        const campo12 = editor.querySelector('[data-hora-12-texto]');
        const periodo = editor.querySelector('[data-hora-periodo]');
        const conversion = hora12Desde24(normalizada.value);

        campo24.value = normalizada.value;
        campo12.value = conversion.hora;
        periodo.value = conversion.periodo;
        campo12.setCustomValidity('');
    }

    function sincronizarDesde12(editor) {
        const campo12 = editor.querySelector('[data-hora-12-texto]');
        const periodo = editor.querySelector('[data-hora-periodo]');
        const normalizada = editor.querySelector('[data-hora-normalizada]');
        const campo24 = editor.querySelector('[data-hora-24]');
        const valor = hora24Desde12(campo12.value, periodo.value);

        if (valor === null) {
            campo12.setCustomValidity('Ingresa una hora válida entre 01:00 y 12:59.');
            return false;
        }

        campo12.setCustomValidity('');
        normalizada.value = valor;
        campo24.value = valor;
        return true;
    }

    function configurarDia(fila) {
        const laborable = fila.querySelector('.dia-laborable').checked;
        const formato12 = selectorFormato.value === '12';
        const estado = fila.querySelector('.dia-editor__estado');
        const selectorJornada = fila.querySelector('[data-tipo-jornada]');
        const esMediaJornada = selectorJornada?.value === 'MEDIA';

        fila.classList.toggle('dia-editor--descanso', !laborable);

        if (estado) {
            estado.textContent = laborable
                ? (esMediaJornada ? 'Media jornada' : 'Completa')
                : 'Descanso';
        }

        if (selectorJornada) {
            selectorJornada.disabled = !laborable;
        }

        fila.querySelectorAll('[data-hora-editor]').forEach(function (editor) {
            const campo24 = editor.querySelector('[data-hora-24]');
            const campo12 = editor.querySelector('[data-hora-12-texto]');
            const periodo = editor.querySelector('[data-hora-periodo]');
            const esHoraRefrigerio = ['salida_refrigerio', 'retorno_refrigerio']
                .includes(editor.dataset.horaTipo);
            const habilitado = laborable && !(esMediaJornada && esHoraRefrigerio);

            editor.classList.toggle('hora-editor--no-aplica', laborable && esMediaJornada && esHoraRefrigerio);
            campo24.disabled = !habilitado || formato12;
            campo12.disabled = !habilitado || !formato12;
            periodo.disabled = !habilitado || !formato12;
            campo24.required = habilitado && !formato12;
            campo12.required = habilitado && formato12;
        });

        const tolerancia = fila.querySelector('.tolerancia');
        tolerancia.disabled = !laborable;
    }

    function aplicarFormato() {
        const formato12 = selectorFormato.value === '12';
        form.classList.toggle('formato-12', formato12);
        form.classList.toggle('formato-24', !formato12);

        if (indicadorFormato) {
            indicadorFormato.textContent = formato12
                ? 'Formato activo: 12 horas (AM/PM)'
                : 'Formato activo: 24 horas';
        }

        form.querySelectorAll('[data-hora-editor]').forEach(actualizarVistasHora);
        form.querySelectorAll('.dia-editor').forEach(configurarDia);
    }

    form.querySelectorAll('[data-hora-editor]').forEach(function (editor) {
        const normalizada = editor.querySelector('[data-hora-normalizada]');
        const campo24 = editor.querySelector('[data-hora-24]');
        const campo12 = editor.querySelector('[data-hora-12-texto]');
        const periodo = editor.querySelector('[data-hora-periodo]');

        campo24.addEventListener('input', function () {
            campo24.setCustomValidity('');
        });
        campo24.addEventListener('change', function () {
            sincronizarDesde24(editor);
        });

        campo12.addEventListener('input', function () {
            campo12.setCustomValidity('');
        });
        campo12.addEventListener('change', function () {
            sincronizarDesde12(editor);
        });
        periodo.addEventListener('change', function () {
            sincronizarDesde12(editor);
        });
    });

    form.querySelectorAll('.dia-laborable').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            configurarDia(checkbox.closest('.dia-editor'));
        });
    });

    form.querySelectorAll('[data-tipo-jornada]').forEach(function (selector) {
        selector.addEventListener('change', function () {
            configurarDia(selector.closest('.dia-editor'));
        });
    });

    selectorFormato.addEventListener('change', aplicarFormato);

    copiarLunes?.addEventListener('click', function () {
        const lunes = form.querySelector('.dia-editor[data-dia="1"]');

        if (!lunes) {
            return;
        }

        form.querySelectorAll('.dia-editor').forEach(function (destino) {
            const numeroDia = Number(destino.dataset.dia);

            if (numeroDia < 2 || numeroDia > 5) {
                return;
            }

            destino.querySelector('.dia-laborable').checked = lunes.querySelector('.dia-laborable').checked;
            destino.querySelector('[data-tipo-jornada]').value = lunes.querySelector('[data-tipo-jornada]').value;
            destino.querySelector('.tolerancia').value = lunes.querySelector('.tolerancia').value;

            lunes.querySelectorAll('[data-hora-editor]').forEach(function (origenHora) {
                const tipo = origenHora.dataset.horaTipo;
                const destinoHora = destino.querySelector(`[data-hora-editor][data-hora-tipo="${tipo}"]`);

                if (!destinoHora) {
                    return;
                }

                destinoHora.querySelector('[data-hora-normalizada]').value =
                    origenHora.querySelector('[data-hora-normalizada]').value;
                actualizarVistasHora(destinoHora);
            });

            configurarDia(destino);
        });

        if (estadoCopia) {
            estadoCopia.textContent = 'El horario del lunes se copió de martes a viernes.';
        }

        const textoOriginal = copiarLunes.innerHTML;
        copiarLunes.textContent = 'Horario copiado';
        window.setTimeout(function () {
            copiarLunes.innerHTML = textoOriginal;
        }, 1800);
    });

    function prepararDiasMovil() {
        form.querySelectorAll('.dia-editor').forEach(function (fila, indice) {
            let boton = fila.querySelector('.ux-day-toggle');
            const nombre = fila.querySelector('.dia-editor__nombre > span')?.textContent?.trim() || 'día';

            if (!boton) {
                boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'ux-day-toggle';
                boton.innerHTML = '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
                fila.querySelector('.dia-editor__config')?.appendChild(boton);
                boton.addEventListener('click', function () {
                    const colapsado = fila.classList.toggle('is-collapsed');
                    boton.setAttribute('aria-expanded', String(!colapsado));
                    boton.setAttribute('aria-label', `${colapsado ? 'Mostrar' : 'Ocultar'} configuración de ${nombre}`);
                });
            }

            fila.classList.toggle('ux-day-collapsible', vistaMovil.matches);

            if (vistaMovil.matches) {
                const colapsado = indice !== 0;
                fila.classList.toggle('is-collapsed', colapsado);
                boton.setAttribute('aria-expanded', String(!colapsado));
                boton.setAttribute('aria-label', `${colapsado ? 'Mostrar' : 'Ocultar'} configuración de ${nombre}`);
            } else {
                fila.classList.remove('is-collapsed');
                boton.setAttribute('aria-expanded', 'true');
            }
        });
    }

    vistaMovil.addEventListener?.('change', prepararDiasMovil);

    form.addEventListener('submit', function (event) {
        let valido = true;

        form.querySelectorAll('.dia-editor').forEach(function (fila) {
            if (!fila.querySelector('.dia-laborable').checked) {
                return;
            }

            fila.querySelectorAll('[data-hora-editor]').forEach(function (editor) {
                const campoActivo = selectorFormato.value === '12'
                    ? editor.querySelector('[data-hora-12-texto]')
                    : editor.querySelector('[data-hora-24]');

                if (campoActivo.disabled) {
                    return;
                }

                if (selectorFormato.value === '12') {
                    valido = sincronizarDesde12(editor) && valido;
                } else {
                    valido = sincronizarDesde24(editor) && valido;
                }
            });
        });

        if (!valido || !form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });

    aplicarFormato();
    prepararDiasMovil();
});
