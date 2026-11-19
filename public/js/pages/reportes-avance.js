document.addEventListener('DOMContentLoaded', function () {
    const reportesDataElement = document.getElementById('reportesData');
    const reportesData = reportesDataElement ? JSON.parse(reportesDataElement.textContent) : {};

    const templateActividad = document.getElementById('templateActividad');

    const actividadesContainer = document.getElementById('actividadesContainer');
    const actividadesAdicionalesContainer = document.getElementById('actividadesAdicionalesContainer');
    const editarActividadesContainer = document.getElementById('editarActividadesContainer');
    const sinActividades = document.getElementById('sinActividades');
    const sinActividadesEditar = document.getElementById('sinActividadesEditar');

    const agregarActividad = document.getElementById('agregarActividad');
    const agregarActividadAdicional = document.getElementById('agregarActividadAdicional');
    const agregarActividadEditar = document.getElementById('agregarActividadEditar');

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

    function configurarEliminar(item, container) {
        const boton = item.querySelector('[data-eliminar-item]');
        if (!boton) return;

        boton.addEventListener('click', function () {
            item.remove();

            actualizarNombresActividad(container);
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

        configurarEliminar(item, container);
        container.appendChild(fragmento);
        actualizarNombresActividad(container);
        actualizarEstadoSinActividades();
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
        configurarEliminar(item, actividadesContainer);
    });

    if (agregarActividadAdicional && actividadesAdicionalesContainer) {
        agregarActividadAdicional.addEventListener('click', function () {
            crearActividad(actividadesAdicionalesContainer);
        });
    }

    actividadesAdicionalesContainer?.querySelectorAll('.actividad-form-item').forEach(function (item) {
        configurarEliminar(item, actividadesAdicionalesContainer);
    });

    if (agregarActividadEditar && editarActividadesContainer) {
        agregarActividadEditar.addEventListener('click', function () {
            crearActividad(editarActividadesContainer);
        });
    }


    document.querySelectorAll('[data-editar-reporte]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!formEditar) return;

            const reporte = reportesData[button.dataset.reporteId];
            if (!reporte) return;

            formEditar.action = button.dataset.url;
            editarActividadesContainer.innerHTML = '';

            reporte.actividades.forEach(function (actividad) {
                crearActividad(editarActividadesContainer, actividad);
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

    actualizarNombresActividad(actividadesContainer);
    actualizarNombresActividad(actividadesAdicionalesContainer);
    actualizarEstadoSinActividades();
});
