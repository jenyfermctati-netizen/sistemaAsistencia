document.addEventListener('DOMContentLoaded', function () {
    // Teléfono: solo números y máximo 9 dígitos
    document.querySelectorAll('.telefono-input').forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });
    });

    // Editar trabajador
    const formEditar = document.getElementById('formEditarTrabajador');

    document.querySelectorAll('[data-editar-trabajador]').forEach(function (button) {
        button.addEventListener('click', function () {
            formEditar.action = button.dataset.updateUrl;

            document.getElementById('editarArea').value = button.dataset.areaId ?? '';
            document.getElementById('editarCodigoBiometrico').value = button.dataset.codigoBiometrico ?? '';
            document.getElementById('editarDni').value = button.dataset.dni ?? '';
            document.getElementById('editarNombres').value = button.dataset.nombres ?? '';
            document.getElementById('editarApellidos').value = button.dataset.apellidos ?? '';
            document.getElementById('editarTipoVinculo').value = button.dataset.tipoVinculo ?? '';
            document.getElementById('editarCargo').value = button.dataset.cargo ?? '';
            document.getElementById('editarTelefono').value = button.dataset.telefono ?? '';
            document.getElementById('editarFechaIngreso').value = button.dataset.fechaIngreso ?? '';
            document.getElementById('editarFechaFin').value = button.dataset.fechaFin ?? '';
            document.getElementById('editarEstado').value = button.dataset.estado ?? 'ACTIVO';
            document.getElementById('editarEmail').value = button.dataset.email ?? '';
            document.getElementById('editarRol').value = button.dataset.rolId ?? '';

            window.AppModal.open('modalEditarTrabajador');
        });
    });

    // Cambiar estado
    const formEstado = document.getElementById('formEstadoTrabajador');
    const mensajeEstado = document.getElementById('estadoTrabajadorMensaje');
    const confirmarEstado = document.getElementById('btnConfirmarEstado');

    document.querySelectorAll('[data-cambiar-estado]').forEach(function (button) {
        button.addEventListener('click', function () {
            formEstado.action = button.dataset.url;

            const nombre = button.dataset.name;
            const estado = button.dataset.estado;
            const esActivo = estado === 'ACTIVO';

            mensajeEstado.textContent = esActivo
                ? `¿Deseas desactivar a ${nombre}? El trabajador dejará de poder iniciar sesión.`
                : `¿Deseas activar a ${nombre}? El trabajador podrá volver a iniciar sesión.`;

            confirmarEstado.textContent = esActivo ? 'Desactivar' : 'Activar';
            confirmarEstado.className = `button ${esActivo ? 'button--danger' : 'button--primary'}`;

            window.AppModal.open('modalEstadoTrabajador');
        });
    });
});