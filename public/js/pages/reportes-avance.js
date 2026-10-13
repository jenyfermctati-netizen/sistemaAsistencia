document.addEventListener('DOMContentLoaded', function () {
    const reportesDataElement = document.getElementById('reportesData');
    const reportesData = reportesDataElement ? JSON.parse(reportesDataElement.textContent) : {};

    const templateActividad = document.getElementById('templateActividad');
    const templatePendiente = document.getElementById('templatePendiente');

    const actividadesContainer = document.getElementById('actividadesContainer');
    const pendientesContainer = document.getElementById('pendientesContainer');
    const editarActividadesContainer = document.getElementById('editarActividadesContainer');
    const editarPendientesContainer = document.getElementById('editarPendientesContainer');

    const agregarActividad = document.getElementById('agregarActividad');
    const agregarPendiente = document.getElementById('agregarPendiente');
    const agregarActividadEditar = document.getElementById('agregarActividadEditar');
    const agregarPendienteEditar = document.getElementById('agregarPendienteEditar');

    const formEditar = document.getElementById('formEditarReporte');
    const formRevisar = document.getElementById('formRevisarReporte');

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
        if (!sinPendientes || !pendientesContainer) return;

        sinPendientes.style.display = pendientesContainer.children.length ? 'none' : 'block';
    }

    if (agregarActividad && actividadesContainer) {
        agregarActividad.addEventListener('click', function () {
            crearActividad(actividadesContainer);
        });
    }

    if (agregarPendiente && pendientesContainer) {
        agregarPendiente.addEventListener('click', function () {
            crearPendiente(pendientesContainer);
        });
    }

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

            if (!reporte.actividades.length) {
                crearActividad(editarActividadesContainer);
            }

            window.AppModal.open('modalEditarReporte');
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

                    item.innerHTML = `
                        <div class="detalle-pendiente__header">
                            <strong>${escapeHtml(pendiente.descripcion)}</strong>
                            <span class="badge ${pendiente.estado === 'RESUELTO' ? 'badge--success' : 'badge--warning'}">
                                ${pendiente.estado === 'RESUELTO' ? 'Resuelto' : 'Pendiente'}
                            </span>
                        </div>
                        ${pendiente.detalle ? `<p>${escapeHtml(pendiente.detalle)}</p>` : ''}
                        ${pendiente.ruta_archivos
                            ? `<div class="detalle-ruta"><span>Archivos:</span><code>${escapeHtml(pendiente.ruta_archivos)}</code></div>`
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

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    actualizarNombresActividad(actividadesContainer);
    actualizarEstadoSinPendientes();
});