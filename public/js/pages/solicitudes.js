document.addEventListener('DOMContentLoaded', () => {
    const $ = id => document.getElementById(id);
    const txt = (id,value,fallback='-') => { const el=$(id); if(el) el.textContent=value||fallback; };

    const tiposSolicitud = {
        TARDANZA: {
            ayuda: 'Justifica una tardanza indicando el día en que ocurrió.',
            fecha: 'Fecha de la tardanza *',
            motivo: 'Explica brevemente el motivo de la tardanza.'
        },
        FALTA: {
            rango: true,
            ayuda: 'Justifica uno o varios días consecutivos de inasistencia.',
            fechas: 'Selecciona el día o el rango continuo de días que faltaste.',
            motivo: 'Indica por qué faltaste durante las fechas seleccionadas.'
        },
        PERMISO: {
            rango: true,
            ayuda: 'Solicita permiso para uno o varios días completos.',
            fechas: 'Selecciona un día o el rango de días completos que necesitas.',
            motivo: 'Describe el motivo y las fechas del permiso solicitado.'
        },
        OMISION_MARCACION: {
            ayuda: 'Registra el día en que olvidaste realizar una marcación.',
            fecha: 'Día de la omisión *',
            motivo: 'Indica qué ocurrió el día que olvidaste marcar.'
        },
        OTRO: {
            ayuda: 'Registra una solicitud que no corresponda a las opciones anteriores.',
            fecha: 'Fecha *',
            motivo: 'Describe el motivo de tu solicitud.'
        }
    };

    function actualizarFormularioSolicitud(form, limpiarCamposOcultos = true) {
        if (!form) return;

        const tipo = form.querySelector('[data-solicitud-tipo]')?.value || '';
        const config = tiposSolicitud[tipo];
        const grupoFechas = form.querySelector('[data-solicitud-fechas]');
        const fechaInicio = form.querySelector('[data-solicitud-fecha-inicio]');
        const grupoFechaFin = form.querySelector('[data-solicitud-fecha-fin-campo]');
        const fechaFin = form.querySelector('[data-solicitud-fecha-fin]');
        const ayudaTipo = form.querySelector('[data-solicitud-ayuda-tipo]');
        const ayudaFechas = form.querySelector('[data-solicitud-ayuda-fechas]');
        const camposComunes = form.querySelectorAll('[data-solicitud-campos-comunes]');

        if (grupoFechas) grupoFechas.hidden = !config;
        if (fechaInicio) {
            fechaInicio.disabled = !config;
            fechaInicio.required = Boolean(config);
        }

        const mostrarRango = Boolean(config?.rango);
        if (grupoFechaFin) grupoFechaFin.hidden = !mostrarRango;
        if (fechaFin) {
            fechaFin.disabled = !mostrarRango;
            fechaFin.min = mostrarRango && fechaInicio?.value ? fechaInicio.value : '';
            if (!mostrarRango && limpiarCamposOcultos) fechaFin.value = '';
        }

        camposComunes.forEach(campo => { campo.hidden = !config; });

        const motivo = form.querySelector('[data-solicitud-motivo]');
        if (motivo) {
            motivo.disabled = !config;
            motivo.required = Boolean(config);
            motivo.placeholder = config?.motivo || 'Describe el motivo de tu solicitud.';
        }

        const etiquetaInicio = form.querySelector('[data-solicitud-fecha-inicio-label]');
        if (etiquetaInicio) {
            etiquetaInicio.textContent = mostrarRango ? 'Fecha de inicio *' : (config?.fecha || 'Fecha *');
        }

        const etiquetaFin = form.querySelector('[data-solicitud-fecha-fin-label]');
        if (etiquetaFin) etiquetaFin.textContent = 'Fecha de fin (opcional)';

        if (ayudaTipo) ayudaTipo.textContent = config?.ayuda || 'Selecciona el tipo para ver los datos que necesitas completar.';
        if (ayudaFechas) {
            ayudaFechas.textContent = tipo === 'FALTA'
                ? config.fechas
                : (mostrarRango ? config.fechas : '');
        }
    }

    document.querySelectorAll('[data-solicitud-form]').forEach(form => {
        actualizarFormularioSolicitud(form, false);
        form.querySelector('[data-solicitud-tipo]')?.addEventListener('change', () => {
            actualizarFormularioSolicitud(form, true);
        });

        const fechaInicio = form.querySelector('[data-solicitud-fecha-inicio]');
        const fechaFin = form.querySelector('[data-solicitud-fecha-fin]');
        fechaInicio?.addEventListener('change', () => {
            if (!fechaFin) return;
            fechaFin.min = fechaInicio.value || '';
            if (fechaFin.value && fechaInicio.value && fechaFin.value < fechaInicio.value) {
                fechaFin.value = '';
            }
        });
    });

    function editar(d) {
        const form=$('formEditarSolicitud'); if(!form)return;
        form.action=d.url||d.editarUrl||'';
        $('editarSolicitudTipo').value=d.tipo||d.tipoCodigo||'';
        $('editarSolicitudInicio').value=d.inicio||d.fechaInicioEdit||'';
        $('editarSolicitudFin').value=d.fin||d.fechaFinEdit||'';
        $('editarSolicitudMotivo').value=d.motivo||'';
        actualizarFormularioSolicitud(form, false);
        window.AppModal?.open('modalEditarSolicitud');
    }

    function cancelar(url) {
        const form=$('formCancelarSolicitud'); if(!form)return;
        form.action=url;
        window.AppModal?.open('modalCancelarSolicitud');
    }

    function configurarDocumento(d) {
        const viewer=$('solicitudDocumentoViewer'), vacio=$('solicitudDocumentoVacio');
        const frame=$('solicitudDocumentoFrame'), img=$('solicitudDocumentoImagen');
        const abrir=$('solicitudDocumentoAbrir'), descargar=$('solicitudDocumentoDescargar');
        if(!viewer||!vacio)return;

        if(frame){frame.hidden=true;frame.removeAttribute('src');}
        if(img){img.hidden=true;img.removeAttribute('src');}

        if(!d.archivo){
            viewer.hidden=true; vacio.hidden=false;
            if(abrir) abrir.href='#';
            if(descargar) descargar.href='#';
            return;
        }

        viewer.hidden=false; vacio.hidden=true;
        const nombre=(d.archivoNombre||d.archivo).toLowerCase();

        if(/\.(jpg|jpeg|png|webp)$/.test(nombre)){
            if(img){img.src=d.archivo;img.hidden=false;}
        }else{
            if(frame){frame.src=d.archivo;frame.hidden=false;}
        }

        if(abrir) abrir.href=d.archivo;
        if(descargar) descargar.href=d.descargar||d.archivo;
    }

    function configurarAdmin(d) {
        const form=$('formDecisionSolicitud'); if(!form)return;
        const pendiente=d.estadoCodigo==='PENDIENTE'&&d.revisarUrl;

        form.hidden=!pendiente;
        form.action=pendiente?d.revisarUrl:'';

        if($('solicitudRechazo')) $('solicitudRechazo').hidden=true;
        if($('accionesDecisionSolicitud')) $('accionesDecisionSolicitud').hidden=false;
        if($('motivoRechazoSolicitud')){
            $('motivoRechazoSolicitud').value='';
            $('motivoRechazoSolicitud').required=false;
        }
        if($('decisionSolicitud')) $('decisionSolicitud').value='';
    }

    function configurarTrabajador(d) {
        const acciones=$('detalleWorkerActions'); if(!acciones)return;
        const pendiente=d.estadoCodigo==='PENDIENTE'&&d.editarUrl&&d.cancelarUrl;
        acciones.hidden=!pendiente;
        if(!pendiente)return;

        $('detalleSolicitudEditar').onclick=()=>{
            window.AppModal?.close('modalDetalleSolicitud');
            editar(d);
        };

        $('detalleSolicitudCancelar').onclick=()=>{
            window.AppModal?.close('modalDetalleSolicitud');
            cancelar(d.cancelarUrl);
        };
    }

    function abrirDetalle(row) {
        const d=row.dataset, trabajador=d.trabajador||'-';
        txt('detalleSolicitudTrabajador',trabajador);
        txt('detalleSolicitudDni',`DNI: ${d.dni||'-'}`);
        txt('detalleSolicitudArea',d.area);
        txt('detalleSolicitudAvatar',trabajador.trim().charAt(0).toUpperCase()||'?');
        txt('detalleSolicitudInicio',d.fechaInicio);
        txt('detalleSolicitudFin',d.fechaFin||d.fechaInicio);
        txt('detalleSolicitudHoraInicio',d.horaInicio,'No especificada');
        txt('detalleSolicitudHoraFin',d.horaFin,'No especificada');
        txt('detalleSolicitudMotivo',d.motivo);

        const tipo=$('detalleSolicitudTipo');
        if(tipo){
            tipo.textContent=d.tipo||'-';
            tipo.className=`solicitud-tipo solicitud-tipo--${(d.tipoCodigo||'').toLowerCase().replaceAll('_','-')}`;
        }

        const estado=$('detalleSolicitudEstado');
        if(estado){
            estado.textContent=d.estado||'-';
            estado.className=`solicitud-estado solicitud-estado--${(d.estadoCodigo||'').toLowerCase()}`;
        }

        const revision=$('detalleSolicitudRevision');
        if(revision){
            const tiene=d.revisadoPor||d.fechaRevision||d.comentario;
            revision.hidden=!tiene;
            if(tiene){
                txt('detalleSolicitudRevisadoPor',d.revisadoPor);
                txt('detalleSolicitudFechaRevision',d.fechaRevision);
                txt('detalleSolicitudComentario',d.comentario,'Sin comentario.');
            }
        }

        configurarDocumento(d);
        configurarAdmin(d);
        configurarTrabajador(d);
        window.AppModal?.open('modalDetalleSolicitud');
    }

    $('aprobarSolicitud')?.addEventListener('click',()=>{
        const form=$('formDecisionSolicitud'); if(!form)return;
        $('decisionSolicitud').value='APROBADA';
        $('motivoRechazoSolicitud').required=false;
        $('motivoRechazoSolicitud').value='';
        form.requestSubmit();
    });

    $('rechazarSolicitud')?.addEventListener('click',()=>{
        $('accionesDecisionSolicitud').hidden=true;
        $('solicitudRechazo').hidden=false;
        $('motivoRechazoSolicitud').required=true;
        $('motivoRechazoSolicitud').focus();
    });

    $('cancelarRechazoSolicitud')?.addEventListener('click',()=>{
        $('solicitudRechazo').hidden=true;
        $('accionesDecisionSolicitud').hidden=false;
        $('motivoRechazoSolicitud').required=false;
        $('motivoRechazoSolicitud').value='';
        $('decisionSolicitud').value='';
    });

    $('confirmarRechazoSolicitud')?.addEventListener('click',()=>{
        const form=$('formDecisionSolicitud'), motivo=$('motivoRechazoSolicitud');
        if(!form||!motivo)return;
        if(!motivo.value.trim()){motivo.reportValidity();return;}
        $('decisionSolicitud').value='RECHAZADA';
        form.requestSubmit();
    });

    document.addEventListener('click',e=>{
        const editarBtn=e.target.closest('[data-editar-solicitud]');
        if(editarBtn){editar(editarBtn.dataset);return;}

        const cancelarBtn=e.target.closest('[data-cancelar-solicitud]');
        if(cancelarBtn){cancelar(cancelarBtn.dataset.url);return;}

        const btn=e.target.closest('[data-ver-solicitud-button]');
        if(btn){
            const row=btn.closest('[data-ver-solicitud]');
            if(row)abrirDetalle(row);
            return;
        }

        const row=e.target.closest('[data-ver-solicitud]');
        if(row&&!e.target.closest('a,button,form,input,select,textarea')) abrirDetalle(row);
    });

    document.addEventListener('keydown',e=>{
        const row=e.target.closest('[data-ver-solicitud]');
        if(!row||!['Enter',' '].includes(e.key))return;
        e.preventDefault();
        abrirDetalle(row);
    });
});
