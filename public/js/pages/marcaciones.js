document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (evento) {
        const boton = evento.target.closest('[data-anular-marcacion]');

        if (!boton) {
            return;
        }

        const formulario = document.getElementById('formAnularMarcacion');
        const informacion = document.getElementById('anularMarcacionInfo');

        if (!formulario || !informacion) {
            return;
        }

        formulario.action = boton.dataset.url;
        informacion.replaceChildren();

        const trabajador = document.createElement('strong');
        trabajador.textContent = boton.dataset.trabajador || '';
        const fecha = document.createElement('span');
        fecha.textContent = boton.dataset.fecha || '';

        informacion.append(trabajador, fecha);
        window.AppModal?.open('modalAnularMarcacion');
    });
});
