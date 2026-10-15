document.addEventListener('DOMContentLoaded',()=>{
    const $=id=>document.getElementById(id);

    document.addEventListener('click',e=>{
        const anular=e.target.closest('[data-anular-marcacion]');
        if(anular){
            const form=$('formAnularMarcacion'),info=$('anularMarcacionInfo');
            if(!form||!info)return;
            form.action=anular.dataset.url;
            info.replaceChildren();

            const trabajador=document.createElement('strong');
            trabajador.textContent=anular.dataset.trabajador||'';

            const fecha=document.createElement('span');
            fecha.textContent=anular.dataset.fecha||'';

            info.append(trabajador,fecha);
            window.AppModal?.open('modalAnularMarcacion');
            return;
        }

        const toggle=e.target.closest('#toggleRegistroEspecial');
        if(toggle){
            const panel=$('registroEspecial');
            if(!panel)return;
            panel.hidden=!panel.hidden;
            toggle.textContent=panel.hidden?'Registro especial':'Cerrar registro especial';
        }
    });
});