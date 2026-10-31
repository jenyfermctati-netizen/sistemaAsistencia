document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formHorarioGeneral');
    const selectorFormato = document.getElementById('formatoHora');
    const indicadorFormato = document.getElementById('formatoIndicador');
    const copiarLunes = document.getElementById('copiarLunes');
    const estadoCopia = document.getElementById('horarioCopyStatus');
    const resumenSemana = document.getElementById('horarioResumenSemana');
    const resumenGuardado = document.getElementById('horarioResumenGuardado');
    const estadoFormulario = document.getElementById('horarioFormStatus');
    const botonGuardar = form?.querySelector('[data-horario-submit]');
    const vistaMovil = window.matchMedia('(max-width: 700px)');
    let formularioInicializado = false;

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

    function horaLegible(valor) {
        if (!valor) {
            return '--:--';
        }

        if (selectorFormato.value === '24') {
            return valor;
        }

        const conversion = hora12Desde24(valor);
        return `${conversion.hora} ${conversion.periodo}`;
    }

    function valorHora(fila, tipo) {
        return fila.querySelector(`[data-hora-editor][data-hora-tipo="${tipo}"] [data-hora-normalizada]`)?.value || '';
    }

    function actualizarResumenDia(fila) {
        if (!fila) {
            return;
        }

        const resumen = fila.querySelector('[data-dia-summary]');
        const laborable = fila.querySelector('.dia-laborable')?.checked;

        if (!resumen) {
            return;
        }

        if (!laborable) {
            resumen.textContent = 'Día no laborable';
            return;
        }

        const esMediaJornada = fila.querySelector('[data-tipo-jornada]')?.value === 'MEDIA';
        const entrada = horaLegible(valorHora(fila, 'entrada'));
        const salida = horaLegible(valorHora(fila, 'salida'));
        const tolerancia = fila.querySelector('.tolerancia')?.value || '0';

        if (esMediaJornada) {
            resumen.textContent = `${entrada}–${salida} · ${tolerancia} min de tolerancia`;
            return;
        }

        const inicioRefrigerio = horaLegible(valorHora(fila, 'salida_refrigerio'));
        const finRefrigerio = horaLegible(valorHora(fila, 'retorno_refrigerio'));
        resumen.textContent = `${entrada}–${inicioRefrigerio} · ${finRefrigerio}–${salida} · ${tolerancia} min de tolerancia`;
    }

    function actualizarResumenSemanal() {
        const filas = Array.from(form.querySelectorAll('.dia-editor'));
        const laborables = filas.filter(function (fila) {
            return fila.querySelector('.dia-laborable')?.checked;
        });
        const completas = laborables.filter(function (fila) {
            return fila.querySelector('[data-tipo-jornada]')?.value === 'COMPLETA';
        }).length;
        const medias = laborables.length - completas;
        const descansos = filas.length - laborables.length;
        const detalle = [
            `${laborables.length} ${laborables.length === 1 ? 'día laborable' : 'días laborables'}`,
            completas ? `${completas} ${completas === 1 ? 'jornada completa' : 'jornadas completas'}` : null,
            medias ? `${medias} ${medias === 1 ? 'media jornada' : 'medias jornadas'}` : null,
            `${descansos} ${descansos === 1 ? 'descanso' : 'descansos'}`,
        ].filter(Boolean).join(' · ');

        if (resumenSemana) {
            resumenSemana.textContent = detalle;
        }

        if (resumenGuardado) {
            resumenGuardado.textContent = laborables.length
                ? `${laborables.length} días laborables y ${descansos} ${descansos === 1 ? 'descanso' : 'descansos'}`
                : 'La semana no tiene días laborables';
        }
    }

    function marcarCambios() {
        if (!formularioInicializado || !estadoFormulario) {
            return;
        }

        estadoFormulario.textContent = 'Hay cambios sin guardar.';
        estadoFormulario.classList.add('has-unsaved-changes');
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
        actualizarResumenDia(editor.closest('.dia-editor'));
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
        actualizarResumenDia(editor.closest('.dia-editor'));
        return true;
    }

    function campoSalidaActivo(fila) {
        const editor = fila.querySelector('[data-hora-editor][data-hora-tipo="salida"]');
        return selectorFormato.value === '12'
            ? editor?.querySelector('[data-hora-12-texto]')
            : editor?.querySelector('[data-hora-24]');
    }

    function limpiarErrorSecuencia(fila) {
        const campoSalida = campoSalidaActivo(fila);

        if (campoSalida?.dataset.sequenceError === 'true') {
            campoSalida.setCustomValidity('');
            delete campoSalida.dataset.sequenceError;
        }

        fila.classList.remove('is-invalid');
        const feedback = fila.querySelector('[data-dia-feedback]');
        if (feedback) {
            feedback.hidden = true;
            feedback.textContent = '';
        }
    }

    function mostrarErrorSecuencia(fila, mensaje) {
        const campoSalida = campoSalidaActivo(fila);
        const feedback = fila.querySelector('[data-dia-feedback]');

        if (campoSalida) {
            campoSalida.dataset.sequenceError = 'true';
            campoSalida.setCustomValidity(mensaje);
        }

        fila.classList.add('is-invalid');
        if (feedback) {
            feedback.textContent = mensaje;
            feedback.hidden = false;
        }
    }

    function validarSecuenciaDia(fila) {
        limpiarErrorSecuencia(fila);

        if (!fila.querySelector('.dia-laborable')?.checked) {
            return true;
        }

        const entrada = valorHora(fila, 'entrada');
        const salida = valorHora(fila, 'salida');
        const esMediaJornada = fila.querySelector('[data-tipo-jornada]')?.value === 'MEDIA';
        let mensaje = '';

        if (esMediaJornada) {
            if (entrada && salida && entrada >= salida) {
                mensaje = 'La salida debe ser posterior a la hora de entrada.';
            }
        } else {
            const inicioRefrigerio = valorHora(fila, 'salida_refrigerio');
            const finRefrigerio = valorHora(fila, 'retorno_refrigerio');

            if (entrada && inicioRefrigerio && finRefrigerio && salida && !(
                entrada < inicioRefrigerio
                && inicioRefrigerio < finRefrigerio
                && finRefrigerio < salida
            )) {
                mensaje = 'Revisa el orden: entrada, inicio del refrigerio, fin del refrigerio y salida.';
            }
        }

        if (mensaje) {
            mostrarErrorSecuencia(fila, mensaje);
            return false;
        }

        return true;
    }

    function configurarDia(fila) {
        const laborable = fila.querySelector('.dia-laborable').checked;
        const formato12 = selectorFormato.value === '12';
        const estado = fila.querySelector('.dia-editor__estado');
        const selectorJornada = fila.querySelector('[data-tipo-jornada]');
        const esMediaJornada = selectorJornada?.value === 'MEDIA';

        fila.classList.toggle('dia-editor--descanso', !laborable);
        fila.classList.toggle('dia-editor--media', laborable && esMediaJornada);

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
        actualizarResumenDia(fila);
        actualizarResumenSemanal();
        validarSecuenciaDia(fila);
    }

    function aplicarFormato() {
        const formato12 = selectorFormato.value === '12';
        form.classList.toggle('formato-12', formato12);
        form.classList.toggle('formato-24', !formato12);

        if (indicadorFormato) {
            indicadorFormato.textContent = formato12
                ? 'Vista: 12 horas (AM/PM)'
                : 'Vista: 24 horas';
        }

        form.querySelectorAll('[data-hora-editor]').forEach(actualizarVistasHora);
        form.querySelectorAll('.dia-editor').forEach(configurarDia);
    }

    form.querySelectorAll('[data-hora-editor]').forEach(function (editor) {
        const campo24 = editor.querySelector('[data-hora-24]');
        const campo12 = editor.querySelector('[data-hora-12-texto]');
        const periodo = editor.querySelector('[data-hora-periodo]');

        campo24.addEventListener('input', function () {
            campo24.setCustomValidity('');
            limpiarErrorSecuencia(editor.closest('.dia-editor'));
        });
        campo24.addEventListener('change', function () {
            if (sincronizarDesde24(editor)) {
                validarSecuenciaDia(editor.closest('.dia-editor'));
            }
        });

        campo12.addEventListener('input', function () {
            campo12.setCustomValidity('');
            limpiarErrorSecuencia(editor.closest('.dia-editor'));
        });
        campo12.addEventListener('change', function () {
            if (sincronizarDesde12(editor)) {
                validarSecuenciaDia(editor.closest('.dia-editor'));
            }
        });
        periodo.addEventListener('change', function () {
            if (sincronizarDesde12(editor)) {
                validarSecuenciaDia(editor.closest('.dia-editor'));
            }
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

    form.querySelectorAll('.tolerancia').forEach(function (campo) {
        campo.addEventListener('input', function () {
            actualizarResumenDia(campo.closest('.dia-editor'));
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
            estadoCopia.textContent = 'Listo: el lunes se copió de martes a viernes.';
        }

        marcarCambios();
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
            boton.hidden = !vistaMovil.matches;

            if (vistaMovil.matches) {
                const colapsado = fila.dataset.mobileReady === 'true'
                    ? fila.classList.contains('is-collapsed')
                    : indice !== 0;
                fila.dataset.mobileReady = 'true';
                fila.classList.toggle('is-collapsed', colapsado);
                boton.setAttribute('aria-expanded', String(!colapsado));
                boton.setAttribute('aria-label', `${colapsado ? 'Mostrar' : 'Ocultar'} configuración de ${nombre}`);
            } else {
                fila.classList.remove('is-collapsed');
                delete fila.dataset.mobileReady;
                boton.setAttribute('aria-expanded', 'true');
            }
        });
    }

    function expandirDiaInvalido(fila) {
        if (!vistaMovil.matches || !fila.classList.contains('is-collapsed')) {
            return;
        }

        fila.classList.remove('is-collapsed');
        const boton = fila.querySelector('.ux-day-toggle');
        const nombre = fila.querySelector('.dia-editor__nombre > span')?.textContent?.trim() || 'día';
        boton?.setAttribute('aria-expanded', 'true');
        boton?.setAttribute('aria-label', `Ocultar configuración de ${nombre}`);
    }

    vistaMovil.addEventListener?.('change', prepararDiasMovil);
    form.addEventListener('input', marcarCambios);
    form.addEventListener('change', marcarCambios);

    form.addEventListener('submit', function (event) {
        let valido = true;

        form.querySelectorAll('.dia-editor').forEach(function (fila) {
            if (!fila.querySelector('.dia-laborable').checked) {
                limpiarErrorSecuencia(fila);
                return;
            }

            let formatoDiaValido = true;
            fila.querySelectorAll('[data-hora-editor]').forEach(function (editor) {
                const campoActivo = selectorFormato.value === '12'
                    ? editor.querySelector('[data-hora-12-texto]')
                    : editor.querySelector('[data-hora-24]');

                if (campoActivo.disabled) {
                    return;
                }

                const horaValida = selectorFormato.value === '12'
                    ? sincronizarDesde12(editor)
                    : sincronizarDesde24(editor);
                formatoDiaValido = horaValida && formatoDiaValido;
            });

            const secuenciaValida = formatoDiaValido ? validarSecuenciaDia(fila) : false;
            if (!formatoDiaValido || !secuenciaValida) {
                valido = false;
                expandirDiaInvalido(fila);
            }
        });

        if (!valido || !form.checkValidity()) {
            event.preventDefault();
            estadoFormulario.textContent = 'Hay datos por corregir antes de guardar.';
            estadoFormulario.classList.add('has-errors');
            form.reportValidity();
            return;
        }

        if (botonGuardar) {
            botonGuardar.disabled = true;
            botonGuardar.querySelector('span').textContent = 'Guardando…';
        }
        estadoFormulario.textContent = 'Guardando y aplicando el horario…';
    });

    aplicarFormato();
    prepararDiasMovil();
    formularioInicializado = true;
});
