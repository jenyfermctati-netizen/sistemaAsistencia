document.addEventListener('DOMContentLoaded', () => {
    const $ = id => document.getElementById(id);
    const txt = (id,value,fallback='-') => { const el=$(id); if(el) el.textContent=value||fallback; };

    function editar(d) {
        const form=$('formEditarSolicitud'); if(!form)return;
        form.action=d.url||d.editarUrl||'';
        $('editarSolicitudTipo').value=d.tipo||d.tipoCodigo||'';
        $('editarSolicitudInicio').value=d.inicio||d.fechaInicioEdit||'';
        $('editarSolicitudFin').value=d.fin||d.fechaFinEdit||'';
        $('editarSolicitudHoraInicio').value=d.horaInicio||'';
        $('editarSolicitudHoraFin').value=d.horaFin||'';
        $('editarSolicitudMotivo').value=d.motivo||'';
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