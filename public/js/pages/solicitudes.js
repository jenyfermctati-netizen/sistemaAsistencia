document.addEventListener('DOMContentLoaded', () => {
    const $ = id => document.getElementById(id);
    const txt = (id, value, fallback = '-') => { const el = $(id); if (el) el.textContent = value || fallback; };
    const formatearTipo = tipo => ({TARDANZA:'Justificación de tardanza',FALTA:'Justificación de falta',PERMISO:'Permiso',OMISION_MARCACION:'Omisión de marcación',OTRO:'Otro'})[tipo] || tipo || '-';

    function abrirEditar(data) {
        const form = $('formEditarSolicitud'); if (!form) return;
        form.action = data.url || data.editarUrl || '';
        $('editarSolicitudTipo').value = data.tipo || data.tipoCodigo || '';
        $('editarSolicitudInicio').value = data.inicio || data.fechaInicioEdit || '';
        $('editarSolicitudFin').value = data.fin || data.fechaFinEdit || '';
        $('editarSolicitudHoraInicio').value = data.horaInicio || '';
        $('editarSolicitudHoraFin').value = data.horaFin || '';
        $('editarSolicitudMotivo').value = data.motivo || '';
        window.AppModal?.open('modalEditarSolicitud');
    }

    function abrirCancelar(url) {
        const form = $('formCancelarSolicitud'); if (!form) return;
        form.action = url;
        window.AppModal?.open('modalCancelarSolicitud');
    }

    function abrirRevision(data) {
        const form = $('formRevisarSolicitud'); if (!form) return;
        form.action = data.url || data.revisarUrl || '';
        txt('revisionTrabajador', data.trabajador);
        txt('revisionTipo', data.tipoCodigo ? formatearTipo(data.tipoCodigo) : data.tipo);
        if ($('revisionDecision')) $('revisionDecision').value = '';
        window.AppModal?.open('modalRevisarSolicitud');
    }

    function abrirDetalle(row) {
        const d = row.dataset, trabajador = d.trabajador || '-', tipoCodigo = d.tipoCodigo || '', estadoCodigo = d.estadoCodigo || '', archivo = d.archivo || '';
        txt('detalleSolicitudTrabajador', trabajador);
        txt('detalleSolicitudDni', `DNI: ${d.dni || '-'}`);
        txt('detalleSolicitudArea', d.area);
        txt('detalleSolicitudAvatar', trabajador.trim().charAt(0).toUpperCase() || '?');

        const tipo = $('detalleSolicitudTipo');
        if (tipo) { tipo.textContent = d.tipo || '-'; tipo.className = `solicitud-tipo solicitud-tipo--${tipoCodigo.toLowerCase().replaceAll('_','-')}`; }

        const estado = $('detalleSolicitudEstado');
        if (estado) { estado.textContent = d.estado || '-'; estado.className = `solicitud-estado solicitud-estado--${estadoCodigo.toLowerCase()}`; }

        txt('detalleSolicitudInicio', d.fechaInicio);
        txt('detalleSolicitudFin', d.fechaFin || d.fechaInicio);
        txt('detalleSolicitudHoraInicio', d.horaInicio, 'No especificada');
        txt('detalleSolicitudHoraFin', d.horaFin, 'No especificada');
        txt('detalleSolicitudMotivo', d.motivo);

        const revision = $('detalleSolicitudRevision');
        if (revision) {
            const tieneRevision = d.revisadoPor || d.fechaRevision || d.comentario;
            revision.hidden = !tieneRevision;
            if (tieneRevision) {
                txt('detalleSolicitudRevisadoPor', d.revisadoPor);
                txt('detalleSolicitudFechaRevision', d.fechaRevision);
                txt('detalleSolicitudComentario', d.comentario, 'Sin comentario.');
            }
        }

        configurarDocumento(archivo);
        configurarAcciones(row);
        window.AppModal?.open('modalDetalleSolicitud');
    }

    function configurarDocumento(archivo) {
        const viewer = $('solicitudDocumentoViewer'), vacio = $('solicitudDocumentoVacio'), frame = $('solicitudDocumentoFrame'), imagen = $('solicitudDocumentoImagen'), abrir = $('solicitudDocumentoAbrir'), descargar = $('solicitudDocumentoDescargar');
        if (!viewer || !vacio) return;

        if (frame) { frame.hidden = true; frame.removeAttribute('src'); }
        if (imagen) { imagen.hidden = true; imagen.removeAttribute('src'); }

        if (!archivo) {
            viewer.hidden = true; vacio.hidden = false;
            if (abrir) abrir.href = '#';
            if (descargar) descargar.href = '#';
            return;
        }

        viewer.hidden = false; vacio.hidden = true;
        const limpio = archivo.split('?')[0].toLowerCase();

        if (/\.(jpg|jpeg|png|webp)$/.test(limpio) && imagen) {
            imagen.src = archivo; imagen.hidden = false;
        } else if (frame) {
            frame.src = archivo; frame.hidden = false;
        }

        if (abrir) abrir.href = archivo;
        if (descargar) descargar.href = archivo;
    }

    function configurarAcciones(row) {
        const d = row.dataset, admin = $('detalleAdminActions'), worker = $('detalleWorkerActions');

        if (admin) {
            const puede = d.estadoCodigo === 'PENDIENTE' && d.revisarUrl;
            admin.hidden = !puede;
            if (puede && $('detalleSolicitudRevisar')) $('detalleSolicitudRevisar').onclick = () => {
                window.AppModal?.close('modalDetalleSolicitud');
                abrirRevision(d);
            };
        }

        if (worker) {
            const puede = d.estadoCodigo === 'PENDIENTE' && d.editarUrl && d.cancelarUrl;
            worker.hidden = !puede;
            if (!puede) return;

            $('detalleSolicitudEditar').onclick = () => {
                window.AppModal?.close('modalDetalleSolicitud');
                abrirEditar(d);
            };

            $('detalleSolicitudCancelar').onclick = () => {
                window.AppModal?.close('modalDetalleSolicitud');
                abrirCancelar(d.cancelarUrl);
            };
        }
    }

    document.addEventListener('click', e => {
        const editar = e.target.closest('[data-editar-solicitud]');
        if (editar) { abrirEditar(editar.dataset); return; }

        const cancelar = e.target.closest('[data-cancelar-solicitud]');
        if (cancelar) { abrirCancelar(cancelar.dataset.url); return; }

        const revisar = e.target.closest('[data-revisar-solicitud]');
        if (revisar) { abrirRevision(revisar.dataset); return; }

        const botonDetalle = e.target.closest('[data-ver-solicitud-button]');
        if (botonDetalle) {
            const row = botonDetalle.closest('[data-ver-solicitud]');
            if (row) abrirDetalle(row);
            return;
        }

        const row = e.target.closest('[data-ver-solicitud]');
        if (row && !e.target.closest('a,button,form,input,select,textarea')) abrirDetalle(row);
    });

    document.addEventListener('keydown', e => {
        const row = e.target.closest('[data-ver-solicitud]');
        if (!row || !['Enter',' '].includes(e.key)) return;
        e.preventDefault();
        abrirDetalle(row);
    });
});
