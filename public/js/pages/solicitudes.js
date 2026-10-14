document.addEventListener('DOMContentLoaded', function () {
    function formatearTipo(tipo) {
        const tipos = {
            TARDANZA: 'Justificación de tardanza',
            FALTA: 'Justificación de falta',
            PERMISO: 'Permiso',
            OMISION_MARCACION: 'Omisión de marcación',
            OTRO: 'Otro',
        };

        return tipos[tipo] || tipo || '-';
    }

    document.addEventListener('click', function (evento) {
        const editar = evento.target.closest('[data-editar-solicitud]');

        if (editar) {
            const formulario = document.getElementById('formEditarSolicitud');

            if (!formulario) {
                return;
            }

            formulario.action = editar.dataset.url;
            document.getElementById('editarSolicitudTipo').value = editar.dataset.tipo || '';
            document.getElementById('editarSolicitudInicio').value = editar.dataset.inicio || '';
            document.getElementById('editarSolicitudFin').value = editar.dataset.fin || '';
            document.getElementById('editarSolicitudHoraInicio').value = editar.dataset.horaInicio || '';
            document.getElementById('editarSolicitudHoraFin').value = editar.dataset.horaFin || '';
            document.getElementById('editarSolicitudMotivo').value = editar.dataset.motivo || '';
            window.AppModal?.open('modalEditarSolicitud');
            return;
        }

        const cancelar = evento.target.closest('[data-cancelar-solicitud]');

        if (cancelar) {
            const formulario = document.getElementById('formCancelarSolicitud');

            if (formulario) {
                formulario.action = cancelar.dataset.url;
                window.AppModal?.open('modalCancelarSolicitud');
            }

            return;
        }

        const revisar = evento.target.closest('[data-revisar-solicitud]');

        if (revisar) {
            const formulario = document.getElementById('formRevisarSolicitud');

            if (!formulario) {
                return;
            }

            formulario.action = revisar.dataset.url;
            document.getElementById('revisionTrabajador').textContent = revisar.dataset.trabajador || '-';
            document.getElementById('revisionTipo').textContent = formatearTipo(revisar.dataset.tipo);
            document.getElementById('revisionDecision').value = '';
            window.AppModal?.open('modalRevisarSolicitud');
        }
    });
});
