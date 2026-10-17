document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formHorarioGeneral');
    const selectorFormato = document.getElementById('formatoHora');
    const indicadorFormato = document.getElementById('formatoIndicador');

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

        fila.classList.toggle('dia-editor--descanso', !laborable);

        if (estado) {
            estado.textContent = laborable ? 'Laborable' : 'Descanso';
        }

        fila.querySelectorAll('[data-hora-editor]').forEach(function (editor) {
            const campo24 = editor.querySelector('[data-hora-24]');
            const campo12 = editor.querySelector('[data-hora-12-texto]');
            const periodo = editor.querySelector('[data-hora-periodo]');

            campo24.disabled = !laborable || formato12;
            campo12.disabled = !laborable || !formato12;
            periodo.disabled = !laborable || !formato12;
            campo24.required = laborable && !formato12;
            campo12.required = laborable && formato12;
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

    selectorFormato.addEventListener('change', aplicarFormato);

    form.addEventListener('submit', function (event) {
        let valido = true;

        form.querySelectorAll('.dia-editor').forEach(function (fila) {
            if (!fila.querySelector('.dia-laborable').checked) {
                return;
            }

            fila.querySelectorAll('[data-hora-editor]').forEach(function (editor) {
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
});
