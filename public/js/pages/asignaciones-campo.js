document.addEventListener('DOMContentLoaded', function () {
    const formEditar = document.getElementById('formEditarAsignacion');
    const trabajador = document.getElementById('editarAsignacionTrabajador');
    const inicio = document.getElementById('editarAsignacionInicio');
    const fin = document.getElementById('editarAsignacionFin');
    const lugar = document.getElementById('editarAsignacionLugar');
    const actividad = document.getElementById('editarAsignacionActividad');
    const observacion = document.getElementById('editarAsignacionObservacion');

    document.querySelectorAll('[data-editar-asignacion]').forEach(function (button) {
        button.addEventListener('click', function () {
            formEditar.action = button.dataset.url;
            trabajador.value = button.dataset.trabajador;
            inicio.value = button.dataset.inicio;
            fin.value = button.dataset.fin;
            lugar.value = button.dataset.lugar || '';
            actividad.value = button.dataset.actividad || '';
            observacion.value = button.dataset.observacion || '';

            if (window.AppModal) {
                window.AppModal.open('modalEditarAsignacion');
            }
        });
    });

    const formCancelar = document.getElementById('formCancelarAsignacion');
    const mensajeCancelar = document.getElementById('cancelarAsignacionMensaje');

    document.querySelectorAll('[data-cancelar-asignacion]').forEach(function (button) {
        button.addEventListener('click', function () {
            formCancelar.action = button.dataset.url;
            mensajeCancelar.textContent = `¿Deseas cancelar la asignación de ${button.dataset.trabajador}?`;

            if (window.AppModal) {
                window.AppModal.open('modalCancelarAsignacion');
            }
        });
    });
});