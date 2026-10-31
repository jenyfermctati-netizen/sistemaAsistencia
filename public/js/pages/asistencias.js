document.addEventListener('DOMContentLoaded', function () {
    const dialog = document.getElementById('asistenciaDetalleDialog');

    if (!dialog) {
        return;
    }

    const closeButton = dialog.querySelector('[data-asistencia-close]');
    let lastTrigger = null;

    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value || '—';
        }
    }

    function valueWithEstimate(value, estimated) {
        if (!value || value === '—') {
            return '—';
        }

        return estimated === 'true' ? `${value} · estimada` : value;
    }

    function openDetail(trigger) {
        const data = trigger.dataset;
        const title = document.getElementById('asistenciaDetalleTitulo');
        const description = document.getElementById('asistenciaDetalleDescripcion');
        const status = document.getElementById('asistenciaDetalleEstado');

        lastTrigger = trigger;

        if (title) {
            title.textContent = data.worker || 'Detalle de asistencia';
        }

        if (description) {
            description.textContent = `Jornada procesada del ${data.date || 'día seleccionado'}.`;
        }

        setText('asistenciaDetalleFecha', data.date);
        setText('asistenciaDetalleTrabajador', data.worker);
        setText('asistenciaDetalleDni', `DNI ${data.dni || '—'}`);
        setText('asistenciaDetalleArea', data.area);
        setText('asistenciaDetalleHorario', data.schedule);
        setText('asistenciaDetalleControl', data.control);
        setText('asistenciaDetalleOrigen', data.origin);
        setText('asistenciaDetalleTolerancia', data.tolerance);
        setText('asistenciaDetalleProgramado', data.programmed);
        setText('asistenciaDetalleEntrada', data.entry);
        setText('asistenciaDetalleRefrigerio', data.lunchOut);
        setText('asistenciaDetalleRetorno', valueWithEstimate(data.lunchIn, data.lunchInEstimated));
        setText('asistenciaDetalleSalida', valueWithEstimate(data.exit, data.exitEstimated));
        setText('asistenciaDetalleTrabajado', data.worked);
        setText('asistenciaDetalleTardanza', data.delay);
        setText('asistenciaDetalleProcesado', data.processed);
        setText('asistenciaDetalleObservacion', data.observation);

        if (status) {
            status.textContent = data.status || 'Sin estado';
            status.className = `attendance-status attendance-status--${data.statusKey || 'sin-registro'}`;
        }

        if (!dialog.open) {
            dialog.showModal();
        }

        closeButton?.focus();
    }

    function closeDetail() {
        if (dialog.open) {
            dialog.close();
        }
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-asistencia-detail]');

        if (trigger) {
            openDetail(trigger);
        }
    });

    closeButton?.addEventListener('click', closeDetail);

    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) {
            closeDetail();
        }
    });

    dialog.addEventListener('close', function () {
        lastTrigger?.focus();
    });
});
