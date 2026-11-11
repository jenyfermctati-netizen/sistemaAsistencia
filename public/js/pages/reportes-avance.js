document.addEventListener('DOMContentLoaded', function () {
    const reportesDataElement = document.getElementById('reportesData');
    const reportesData = reportesDataElement ? JSON.parse(reportesDataElement.textContent) : {};

    const templateActividad = document.getElementById('templateActividad');
    const templatePendiente = document.getElementById('templatePendiente');

    const actividadesContainer = document.getElementById('actividadesContainer');
    const pendientesContainer = document.getElementById('pendientesContainer');
    const actividadesAdicionalesContainer = document.getElementById('actividadesAdicionalesContainer');
    const pendientesAdicionalesContainer = document.getElementById('pendientesAdicionalesContainer');
    const editarActividadesContainer = document.getElementById('editarActividadesContainer');
    const editarPendientesContainer = document.getElementById('editarPendientesContainer');
    const sinActividades = document.getElementById('sinActividades');
    const sinActividadesEditar = document.getElementById('sinActividadesEditar');

    const agregarActividad = document.getElementById('agregarActividad');
    const agregarPendiente = document.getElementById('agregarPendiente');
    const agregarActividadAdicional = document.getElementById('agregarActividadAdicional');
    const agregarPendienteAdicional = document.getElementById('agregarPendienteAdicional');
    const agregarActividadEditar = document.getElementById('agregarActividadEditar');
    const agregarPendienteEditar = document.getElementById('agregarPendienteEditar');

    const formEditar = document.getElementById('formEditarReporte');
    const formEditarPendiente = document.getElementById('formEditarPendiente');
    const formRevisar = document.getElementById('formRevisarReporte');
    const formObservarPendiente = document.getElementById('formObservarPendiente');

    function actualizarTitulos(container, tipo) {
        if (!container) return;

        container.querySelectorAll('.item-dinamico').forEach((item, index) => {
            const titulo = item.querySelector('.item-titulo');
            if (titulo) titulo.textContent = `${tipo} ${index + 1}`;
        });
    }

    function actualizarNombresActividad(container, prefijo = 'actividades') {
        if (!container) return;

        container.querySelectorAll('.actividad-form-item').forEach((item, index) => {
            const id = item.querySelector('[data-campo="id"]');
            const descripcion = item.querySelector('[data-campo="descripcion"]');
            const archivo = item.querySelector('[data-campo="archivo"]');

            if (id) id.name = `${prefijo}[${index}][id]`;
            if (descripcion) descripcion.name = `${prefijo}[${index}][descripcion]`;
            if (archivo) archivo.name = `${prefijo}[${index}][archivo]`;
        });

        actualizarTitulos(container, 'Actividad');
    }

    function actualizarNombresPendiente(container, prefijo = 'pendientes') {
        if (!container) return;

        container.querySelectorAll('.pendiente-form-item').forEach((item, index) => {
            const id = item.querySelector('[data-campo="id"]');
            const descripcion = item.querySelector('[data-campo="descripcion"]');
            const detalle = item.querySelector('[data-campo="detalle"]');
            const ruta = item.querySelector('[data-campo="ruta_archivos"]');

            if (id) id.name = `${prefijo}[${index}][id]`;
            if (descripcion) descripcion.name = `${prefijo}[${index}][descripcion]`;
            if (detalle) detalle.name = `${prefijo}[${index}][detalle]`;
            if (ruta) ruta.name = `${prefijo}[${index}][ruta_archivos]`;
        });

        actualizarTitulos(container, 'Pendiente');
    }

    function configurarEliminar(item, container, tipo) {
        const boton = item.querySelector('[data-eliminar-item]');
        if (!boton) return;

        boton.addEventListener('click', function () {
            item.remove();

            if (tipo === 'actividad') actualizarNombresActividad(container);
            if (tipo === 'pendiente') actualizarNombresPendiente(container);

            actualizarEstadoSinPendientes();
            actualizarEstadoSinActividades();
        });
    }

    function crearActividad(container, datos = null) {
        if (!templateActividad || !container) return;

        const fragmento = templateActividad.content.cloneNode(true);
        const item = fragmento.querySelector('.actividad-form-item');

        if (datos) {
            const id = item.querySelector('[data-campo="id"]');
            const descripcion = item.querySelector('[data-campo="descripcion"]');
            const archivoActual = item.querySelector('[data-archivo-actual]');

            if (id) id.value = datos.id ?? '';
            if (descripcion) descripcion.value = datos.descripcion ?? '';

            if (datos.archivo_url && archivoActual) {
                archivoActual.innerHTML = `
                    <a href="${datos.archivo_url}" target="_blank" class="archivo-link">
                        Ver archivo actual
                    </a>
                `;
            }
        }

        configurarEliminar(item, container, 'actividad');
        container.appendChild(fragmento);
        actualizarNombresActividad(container);
        actualizarEstadoSinActividades();
    }

    function crearPendiente(container, datos = null) {
        if (!templatePendiente || !container) return;

        const fragmento = templatePendiente.content.cloneNode(true);
        const item = fragmento.querySelector('.pendiente-form-item');

        if (datos) {
            const id = item.querySelector('[data-campo="id"]');
            const descripcion = item.querySelector('[data-campo="descripcion"]');
            const detalle = item.querySelector('[data-campo="detalle"]');
            const ruta = item.querySelector('[data-campo="ruta_archivos"]');

            if (id) id.value = datos.id ?? '';
            if (descripcion) descripcion.value = datos.descripcion ?? '';
            if (detalle) detalle.value = datos.detalle ?? '';
            if (ruta) ruta.value = datos.ruta_archivos ?? '';
        }

        configurarEliminar(item, container, 'pendiente');
        container.appendChild(fragmento);
        actualizarNombresPendiente(container);
        actualizarEstadoSinPendientes();
    }

    function actualizarEstadoSinPendientes() {
        const sinPendientes = document.getElementById('sinPendientes');
        if (sinPendientes && pendientesContainer) {
            sinPendientes.style.display = pendientesContainer.children.length ? 'none' : 'block';
        }

        const sinPendientesAdicionales = document.getElementById('sinPendientesAdicionales');
        if (sinPendientesAdicionales && pendientesAdicionalesContainer) {
            sinPendientesAdicionales.style.display = pendientesAdicionalesContainer.children.length ? 'none' : 'block';
        }
    }

    function actualizarEstadoSinActividades() {
        if (sinActividades && actividadesContainer) {
            sinActividades.style.display = actividadesContainer.children.length ? 'none' : 'block';
        }

        if (sinActividadesEditar && editarActividadesContainer) {
            sinActividadesEditar.style.display = editarActividadesContainer.children.length ? 'none' : 'block';
        }
    }

    if (agregarActividad && actividadesContainer) {
        agregarActividad.addEventListener('click', function () {
            crearActividad(actividadesContainer);
        });
    }

    actividadesContainer?.querySelectorAll('.actividad-form-item').forEach(function (item) {
        configurarEliminar(item, actividadesContainer, 'actividad');
    });

    if (agregarPendiente && pendientesContainer) {
        agregarPendiente.addEventListener('click', function () {
            crearPendiente(pendientesContainer);
        });
    }

    pendientesContainer?.querySelectorAll('.pendiente-form-item').forEach(function (item) {
        configurarEliminar(item, pendientesContainer, 'pendiente');
    });

    if (agregarActividadAdicional && actividadesAdicionalesContainer) {
        agregarActividadAdicional.addEventListener('click', function () {
            crearActividad(actividadesAdicionalesContainer);
        });
    }

    actividadesAdicionalesContainer?.querySelectorAll('.actividad-form-item').forEach(function (item) {
        configurarEliminar(item, actividadesAdicionalesContainer, 'actividad');
    });

    if (agregarPendienteAdicional && pendientesAdicionalesContainer) {
        agregarPendienteAdicional.addEventListener('click', function () {
            crearPendiente(pendientesAdicionalesContainer);
        });
    }

    pendientesAdicionalesContainer?.querySelectorAll('.pendiente-form-item').forEach(function (item) {
        configurarEliminar(item, pendientesAdicionalesContainer, 'pendiente');
    });

    if (agregarActividadEditar && editarActividadesContainer) {
        agregarActividadEditar.addEventListener('click', function () {
            crearActividad(editarActividadesContainer);
        });
    }

    if (agregarPendienteEditar && editarPendientesContainer) {
        agregarPendienteEditar.addEventListener('click', function () {
            crearPendiente(editarPendientesContainer);
        });
    }

    document.querySelectorAll('[data-editar-reporte]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!formEditar) return;

            const reporte = reportesData[button.dataset.reporteId];
            if (!reporte) return;

            formEditar.action = button.dataset.url;
            editarActividadesContainer.innerHTML = '';
            editarPendientesContainer.innerHTML = '';

            reporte.actividades.forEach(function (actividad) {
                crearActividad(editarActividadesContainer, actividad);
            });

            reporte.pendientes
                .filter(pendiente => pendiente.estado === 'PENDIENTE')
                .forEach(function (pendiente) {
                    crearPendiente(editarPendientesContainer, pendiente);
                });

            actualizarEstadoSinActividades();

            window.AppModal.open('modalEditarReporte');
        });
    });

    document.querySelectorAll('[data-editar-pendiente]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!formEditarPendiente) return;

            formEditarPendiente.action = button.dataset.url;

            const descripcion = document.getElementById('editarPendienteDescripcion');
            const detalle = document.getElementById('editarPendienteDetalle');
            const ruta = document.getElementById('editarPendienteRuta');

            if (descripcion) descripcion.value = button.dataset.descripcion ?? '';
            if (detalle) detalle.value = button.dataset.detalle ?? '';
            if (ruta) ruta.value = button.dataset.rutaArchivos ?? '';

            window.AppModal.open('modalEditarPendiente');
        });
    });

    document.querySelectorAll('[data-ver-reporte]').forEach(function (button) {
        button.addEventListener('click', function () {
            const reporte = reportesData[button.dataset.reporteId];
            if (!reporte) return;

            document.getElementById('detalleTrabajador').textContent = reporte.trabajador || '-';
            document.getElementById('detalleFecha').textContent = reporte.fecha || '-';
            document.getElementById('detalleEstado').textContent = reporte.estado || '-';
            document.getElementById('detalleComentario').textContent = reporte.comentario || 'Sin comentario.';

            const actividades = document.getElementById('detalleActividades');
            actividades.innerHTML = '';

            if (!reporte.actividades.length) {
                actividades.innerHTML = '<p class="sin-datos">Sin actividades registradas.</p>';
            } else {
                reporte.actividades.forEach(function (actividad, index) {
                    const item = document.createElement('div');
                    item.className = 'detalle-actividad';

                    item.innerHTML = `
                        <div class="detalle-numero">${index + 1}</div>
                        <div>
                            <p>${escapeHtml(actividad.descripcion)}</p>
                            ${actividad.archivo_url
                            ? `<a href="${actividad.archivo_url}" target="_blank" class="archivo-link">Ver archivo adjunto</a>`
                            : ''
                        }
                        </div>
                    `;

                    actividades.appendChild(item);
                });
            }

            const pendientes = document.getElementById('detallePendientes');
            pendientes.innerHTML = '';

            if (!reporte.pendientes.length) {
                pendientes.innerHTML = '<p class="sin-datos">Sin pendientes registrados.</p>';
            } else {
                reporte.pendientes.forEach(function (pendiente) {
                    const item = document.createElement('div');
                    item.className = 'detalle-pendiente';

                    const estadoPendiente = obtenerEstadoPendiente(pendiente);

                    item.innerHTML = `
                        <div class="detalle-pendiente__header">
                            <strong>${escapeHtml(pendiente.descripcion)}</strong>
                            <span class="badge ${estadoPendiente.clase}">
                                ${estadoPendiente.texto}
                            </span>
                        </div>
                        ${pendiente.detalle ? `<p>${escapeHtml(pendiente.detalle)}</p>` : ''}
                        ${pendiente.ruta_archivos
                            ? `<div class="detalle-ruta"><span>Archivos:</span><code>${escapeHtml(pendiente.ruta_archivos)}</code></div>`
                            : ''
                        }
                        ${pendiente.observacion_revision
                            ? `<div class="pendiente-observacion"><span>Observación del administrador</span><p>${escapeHtml(pendiente.observacion_revision)}</p></div>`
                            : ''
                        }
                    `;

                    pendientes.appendChild(item);
                });
            }

            window.AppModal.open('modalVerReporte');
        });
    });

    document.querySelectorAll('[data-revisar-reporte]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!formRevisar) return;

            formRevisar.action = button.dataset.url;

            const trabajador = document.getElementById('revisarTrabajador');
            if (trabajador) trabajador.textContent = button.dataset.trabajador || '-';

            window.AppModal.open('modalRevisarReporte');
        });
    });

    document.querySelectorAll('[data-observar-pendiente]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!formObservarPendiente) return;

            formObservarPendiente.action = button.dataset.url;

            const url = document.getElementById('pendienteRevisionUrl');
            const nombre = document.getElementById('pendienteObservadoNombre');
            if (url) url.value = button.dataset.url;
            if (nombre) nombre.textContent = button.dataset.pendiente || 'este pendiente';

            window.AppModal.open('modalObservarPendiente');
        });
    });

    function obtenerEstadoPendiente(pendiente) {
        if (pendiente.estado === 'RESUELTO') {
            return { texto: 'Completado', clase: 'badge--success' };
        }

        if (pendiente.estado_revision === 'POR_REVISAR') {
            return { texto: 'Por revisar', clase: 'badge--info' };
        }

        if (pendiente.estado_revision === 'OBSERVADO') {
            return { texto: 'Observado', clase: 'badge--warning' };
        }

        if (pendiente.estado_revision === 'RECHAZADO') {
            return { texto: 'No completado', clase: 'badge--danger' };
        }

        return { texto: 'Pendiente', clase: 'badge--warning' };
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function iniciarAprobacionMasiva() {
        const formMasivo = document.getElementById('formAprobarMasivo');
        const checkboxesReporte = Array.from(document.querySelectorAll('[data-reporte-checkbox]'));
        const maestroGlobal = document.querySelector('[data-seleccionar-todos-reportes]');
        const maestrosArea = Array.from(document.querySelectorAll('[data-seleccionar-area]'));
        const maestrosTrabajador = Array.from(document.querySelectorAll('[data-seleccionar-trabajador]'));
        const resumenSeleccion = document.querySelector('[data-resumen-seleccion]');
        const botonAprobar = document.querySelector('[data-aprobar-seleccionados]');

        if (!formMasivo && !checkboxesReporte.length) {
            return;
        }

        function objetivosGrupo(items) {
            const pendientes = items.filter(cb => cb.dataset.estado === 'PENDIENTE');
            return pendientes.length ? pendientes : items;
        }

        function sincronizarMaestro(maestro, grupo) {
            if (!maestro || !grupo.length) return;
            const objetivos = objetivosGrupo(grupo);
            maestro.checked = objetivos.length > 0 && objetivos.every(cb => cb.checked);
            maestro.indeterminate = !maestro.checked && grupo.some(cb => cb.checked);
        }

        function actualizarEstadoMasivo() {
            const seleccionados = checkboxesReporte.filter(cb => cb.checked);
            const totalReportes = seleccionados.length;
            const totalActividades = seleccionados.reduce(
                (suma, cb) => suma + Number(cb.dataset.actividades || 0),
                0
            );

            if (resumenSeleccion) {
                if (!totalReportes) {
                    resumenSeleccion.textContent = '0 seleccionados';
                } else {
                    const etiquetaR = totalReportes === 1 ? 'reporte' : 'reportes';
                    const etiquetaA = totalActividades === 1 ? 'actividad' : 'actividades';
                    resumenSeleccion.textContent = `${totalReportes} ${etiquetaR} (${totalActividades} ${etiquetaA})`;
                }
            }

            if (botonAprobar) {
                botonAprobar.disabled = totalReportes === 0;
            }

            sincronizarMaestro(maestroGlobal, checkboxesReporte);

            maestrosArea.forEach(maestro => {
                const areaId = maestro.dataset.seleccionarArea;
                sincronizarMaestro(maestro, checkboxesReporte.filter(cb => cb.dataset.areaId === areaId));
            });

            maestrosTrabajador.forEach(maestro => {
                const trabajadorId = maestro.dataset.seleccionarTrabajador;
                sincronizarMaestro(maestro, checkboxesReporte.filter(cb => cb.dataset.trabajadorId === trabajadorId));
            });
        }

        function aplicarSeleccion(grupo, marcar) {
            const lista = marcar ? objetivosGrupo(grupo) : grupo;
            lista.forEach(cb => {
                cb.checked = marcar;
            });
            actualizarEstadoMasivo();
        }

        maestroGlobal?.addEventListener('change', function () {
            aplicarSeleccion(checkboxesReporte, maestroGlobal.checked);
        });

        maestrosArea.forEach(maestro => {
            maestro.addEventListener('change', function () {
                const areaId = maestro.dataset.seleccionarArea;
                aplicarSeleccion(
                    checkboxesReporte.filter(cb => cb.dataset.areaId === areaId),
                    maestro.checked
                );
            });
        });

        maestrosTrabajador.forEach(maestro => {
            maestro.addEventListener('change', function () {
                const trabajadorId = maestro.dataset.seleccionarTrabajador;
                aplicarSeleccion(
                    checkboxesReporte.filter(cb => cb.dataset.trabajadorId === trabajadorId),
                    maestro.checked
                );
            });
        });

        checkboxesReporte.forEach(cb => {
            cb.addEventListener('change', actualizarEstadoMasivo);
        });

        formMasivo?.addEventListener('submit', function (event) {
            const seleccionados = checkboxesReporte.filter(cb => cb.checked);
            if (!seleccionados.length) {
                event.preventDefault();
                return;
            }

            const totalReportes = seleccionados.length;
            const totalActividades = seleccionados.reduce(
                (suma, cb) => suma + Number(cb.dataset.actividades || 0),
                0
            );
            const etiquetaR = totalReportes === 1 ? 'reporte' : 'reportes';
            const etiquetaA = totalActividades === 1 ? 'actividad' : 'actividades';

            if (!window.confirm(`¿Aprobar ${totalReportes} ${etiquetaR} (${totalActividades} ${etiquetaA}) como revisados?`)) {
                event.preventDefault();
            }
        });

        document.querySelectorAll('form[data-confirm-trabajador]').forEach(form => {
            form.addEventListener('submit', function (event) {
                const trabajador = form.dataset.confirmTrabajador || 'este trabajador';
                const totalReportes = Number(form.dataset.confirmReportes || 0);
                const totalActividades = Number(form.dataset.confirmActividades || 0);
                const etiquetaR = totalReportes === 1 ? 'reporte' : 'reportes';
                const etiquetaA = totalActividades === 1 ? 'actividad' : 'actividades';

                if (!window.confirm(`¿Aprobar los ${totalReportes} ${etiquetaR} (${totalActividades} ${etiquetaA}) de ${trabajador}?`)) {
                    event.preventDefault();
                }
            });
        });

        actualizarEstadoMasivo();
    }

    actualizarNombresActividad(actividadesContainer);
    actualizarNombresPendiente(pendientesContainer);
    actualizarNombresActividad(actividadesAdicionalesContainer);
    actualizarNombresPendiente(pendientesAdicionalesContainer);
    actualizarEstadoSinPendientes();
    actualizarEstadoSinActividades();
    iniciarAprobacionMasiva();
});
