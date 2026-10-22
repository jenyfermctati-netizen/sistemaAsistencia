document.addEventListener('DOMContentLoaded', function () {
    // Teléfono: solo números y máximo 9 dígitos
    document.querySelectorAll('.telefono-input').forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });
    });

    // Consulta de DNI al registrar un trabajador (API Perú desde Laravel).
    const formCrear = document.getElementById('formCrearTrabajador');
    if (formCrear) {
        const dniInput = document.getElementById('crearDni');
        const nombresInput = document.getElementById('crearNombres');
        const apellidosInput = document.getElementById('crearApellidos');
        const btnConsultar = document.getElementById('btnConsultarDni');
        const mensajeConsulta = document.getElementById('mensajeConsultaDni');
        let controllerConsulta = null;

        function mostrarMensaje(texto, error = false) {
            mensajeConsulta.textContent = texto;
            mensajeConsulta.style.color = error ? '#b91c1c' : '';
        }

        dniInput.addEventListener('input', function () {
            dniInput.value = dniInput.value.replace(/\D/g, '').slice(0, 8);
            controllerConsulta?.abort();
            btnConsultar.disabled = false;
            mostrarMensaje('');
        });

        btnConsultar.addEventListener('click', async function () {
            const dni = dniInput.value.trim();
            if (!/^\d{8}$/.test(dni)) {
                mostrarMensaje('Ingrese un DNI válido de 8 dígitos.', true);
                dniInput.focus();
                return;
            }

            controllerConsulta?.abort();
            controllerConsulta = new AbortController();
            const currentController = controllerConsulta;
            btnConsultar.disabled = true;
            mostrarMensaje('Consultando DNI...');

            try {
                const csrf = formCrear.querySelector('input[name="_token"]')?.value;
                const response = await fetch(formCrear.dataset.consultaDniUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf ?? '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ dni }),
                    signal: currentController.signal,
                });
                const resultado = await response.json();
                if (!response.ok || !resultado.success) {
                    throw new Error(resultado.message || 'No se pudo consultar el DNI.');
                }
                if (dniInput.value !== dni) return;
                nombresInput.value = resultado.data.nombres || '';
                apellidosInput.value = resultado.data.apellidos || '';
                mostrarMensaje('Datos encontrados. Verifique los nombres y apellidos.');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    mostrarMensaje(error.message || 'Error de conexión. Complete los datos manualmente.', true);
                }
            } finally {
                if (controllerConsulta === currentController) {
                    btnConsultar.disabled = false;
                    controllerConsulta = null;
                }
            }
        });
    }

    // Editar trabajador
    const formEditar = document.getElementById('formEditarTrabajador');

    function configurarDesignacion(rol, estado, checkbox) {
        if (!rol || !estado || !checkbox) return;

        const nombreRol = rol.selectedOptions[0]?.dataset.roleNombre ?? '';
        const habilitado = nombreRol === 'TRABAJADOR' && estado.value === 'ACTIVO';

        checkbox.disabled = !habilitado;
        if (!habilitado) checkbox.checked = false;
    }

    const crearRol = document.getElementById('crearRol');
    const crearEstado = document.getElementById('crearEstado');
    const crearEncargado = document.getElementById('crearEncargadoArea');

    [crearRol, crearEstado].forEach(function (campo) {
        campo?.addEventListener('change', function () {
            configurarDesignacion(crearRol, crearEstado, crearEncargado);
        });
    });
    configurarDesignacion(crearRol, crearEstado, crearEncargado);

    const editarRol = document.getElementById('editarRol');
    const editarEstado = document.getElementById('editarEstado');
    const editarEncargado = document.getElementById('editarEncargadoArea');

    [editarRol, editarEstado].forEach(function (campo) {
        campo?.addEventListener('change', function () {
            configurarDesignacion(editarRol, editarEstado, editarEncargado);
        });
    });

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
            editarEncargado.checked = button.dataset.encargadoArea === '1';
            configurarDesignacion(editarRol, editarEstado, editarEncargado);

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

    // Registrar el avance diario desde la ficha del trabajador
    const formAvance = document.getElementById('formRegistrarAvanceTrabajador');

    if (formAvance) {
        const actividadesContainer = document.getElementById('actividadesAvanceTrabajador');
        const pendientesContainer = document.getElementById('pendientesAvanceTrabajador');
        const templateActividad = document.getElementById('templateActividadAvanceTrabajador');
        const templatePendiente = document.getElementById('templatePendienteAvanceTrabajador');
        const trabajadorId = document.getElementById('reporteTrabajadorId');
        const trabajadorNombreValor = document.getElementById('reporteTrabajadorNombreValor');
        const trabajadorAreaValor = document.getElementById('reporteTrabajadorAreaValor');
        const trabajadorNombre = document.getElementById('reporteTrabajadorNombre');
        const trabajadorArea = document.getElementById('reporteTrabajadorArea');
        let indiceActividad = siguienteIndice(actividadesContainer, '[data-actividad-avance]');
        let indicePendiente = siguienteIndice(pendientesContainer, '[data-pendiente-avance]');

        function siguienteIndice(container, selector) {
            const indices = Array.from(container.querySelectorAll(selector))
                .map((item) => Number(item.dataset.index))
                .filter(Number.isFinite);

            return indices.length ? Math.max(...indices) + 1 : 0;
        }

        function agregarDesdeTemplate(container, template, indice) {
            const html = template.innerHTML.replaceAll('__INDEX__', String(indice));
            container.insertAdjacentHTML('beforeend', html);
        }

        function actualizarNumeracion() {
            const actividades = actividadesContainer.querySelectorAll('[data-actividad-avance]');

            actividades.forEach(function (item, indice) {
                item.querySelector('[data-numero-actividad]').textContent = indice + 1;
                item.querySelector('[data-quitar-actividad-avance]').hidden = actividades.length === 1;
            });

            pendientesContainer.querySelectorAll('[data-pendiente-avance]').forEach(function (item, indice) {
                item.querySelector('[data-numero-pendiente]').textContent = indice + 1;
            });
        }

        function reiniciarListas() {
            actividadesContainer.innerHTML = '';
            pendientesContainer.innerHTML = '';
            indiceActividad = 0;
            indicePendiente = 0;
            agregarDesdeTemplate(actividadesContainer, templateActividad, indiceActividad++);
            actualizarNumeracion();
        }

        function configurarTrabajador(id, nombre, area, url) {
            trabajadorId.value = id;
            trabajadorNombreValor.value = nombre;
            trabajadorAreaValor.value = area;
            trabajadorNombre.textContent = nombre;
            trabajadorArea.textContent = area;
            formAvance.action = url;
        }

        document.querySelectorAll('[data-registrar-avance]').forEach(function (button) {
            button.addEventListener('click', function () {
                formAvance.reset();
                reiniciarListas();

                const errores = formAvance.querySelector('.avance-form-errors');
                if (errores) {
                    errores.hidden = true;
                }

                configurarTrabajador(
                    button.dataset.trabajadorId,
                    button.dataset.trabajadorNombre,
                    button.dataset.trabajadorArea,
                    button.dataset.storeUrl
                );

                window.AppModal.open('modalRegistrarAvanceTrabajador');
            });
        });

        document.querySelector('[data-agregar-actividad-avance]')?.addEventListener('click', function () {
            agregarDesdeTemplate(actividadesContainer, templateActividad, indiceActividad++);
            actualizarNumeracion();
        });

        document.querySelector('[data-agregar-pendiente-avance]')?.addEventListener('click', function () {
            agregarDesdeTemplate(pendientesContainer, templatePendiente, indicePendiente++);
            actualizarNumeracion();
        });

        actividadesContainer.addEventListener('click', function (event) {
            const button = event.target.closest('[data-quitar-actividad-avance]');
            if (!button || actividadesContainer.querySelectorAll('[data-actividad-avance]').length === 1) {
                return;
            }

            button.closest('[data-actividad-avance]').remove();
            actualizarNumeracion();
        });

        pendientesContainer.addEventListener('click', function (event) {
            const button = event.target.closest('[data-quitar-pendiente-avance]');
            if (!button) {
                return;
            }

            button.closest('[data-pendiente-avance]').remove();
            actualizarNumeracion();
        });

        actualizarNumeracion();

        if (formAvance.dataset.hasErrors === '1' && trabajadorId.value) {
            const url = formAvance.dataset.storeUrlTemplate.replace('__TRABAJADOR__', trabajadorId.value);
            configurarTrabajador(
                trabajadorId.value,
                trabajadorNombreValor.value,
                trabajadorAreaValor.value,
                url
            );
            window.AppModal.open('modalRegistrarAvanceTrabajador');
        }
    }
});
