document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | EDITAR SOLICITUD
    |--------------------------------------------------------------------------
    */

    const formEditar =
        document.getElementById(
            'formEditarSolicitud'
        );

    const editarTipo =
        document.getElementById(
            'editarSolicitudTipo'
        );

    const editarInicio =
        document.getElementById(
            'editarSolicitudInicio'
        );

    const editarFin =
        document.getElementById(
            'editarSolicitudFin'
        );

    const editarHoraInicio =
        document.getElementById(
            'editarSolicitudHoraInicio'
        );

    const editarHoraFin =
        document.getElementById(
            'editarSolicitudHoraFin'
        );

    const editarMotivo =
        document.getElementById(
            'editarSolicitudMotivo'
        );


    document
        .querySelectorAll(
            '[data-editar-solicitud]'
        )
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    if (!formEditar) {
                        return;
                    }

                    formEditar.action =
                        button.dataset.url;

                    editarTipo.value =
                        button.dataset.tipo || '';

                    editarInicio.value =
                        button.dataset.inicio || '';

                    editarFin.value =
                        button.dataset.fin || '';

                    editarHoraInicio.value =
                        button.dataset.horaInicio || '';

                    editarHoraFin.value =
                        button.dataset.horaFin || '';

                    editarMotivo.value =
                        button.dataset.motivo || '';


                    if (window.AppModal) {

                        window.AppModal.open(
                            'modalEditarSolicitud'
                        );
                    }
                }
            );
        });



    /*
    |--------------------------------------------------------------------------
    | CANCELAR
    |--------------------------------------------------------------------------
    */

    const formCancelar =
        document.getElementById(
            'formCancelarSolicitud'
        );


    document
        .querySelectorAll(
            '[data-cancelar-solicitud]'
        )
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    if (!formCancelar) {
                        return;
                    }

                    formCancelar.action =
                        button.dataset.url;


                    if (window.AppModal) {

                        window.AppModal.open(
                            'modalCancelarSolicitud'
                        );
                    }
                }
            );
        });



    /*
    |--------------------------------------------------------------------------
    | REVISAR
    |--------------------------------------------------------------------------
    */

    const formRevisar =
        document.getElementById(
            'formRevisarSolicitud'
        );

    const revisionTrabajador =
        document.getElementById(
            'revisionTrabajador'
        );

    const revisionTipo =
        document.getElementById(
            'revisionTipo'
        );

    const revisionDecision =
        document.getElementById(
            'revisionDecision'
        );


    document
        .querySelectorAll(
            '[data-revisar-solicitud]'
        )
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    if (!formRevisar) {
                        return;
                    }

                    formRevisar.action =
                        button.dataset.url;

                    revisionTrabajador.textContent =
                        button.dataset.trabajador
                        || '-';

                    revisionTipo.textContent =
                        formatearTipo(
                            button.dataset.tipo
                        );

                    revisionDecision.value = '';


                    if (window.AppModal) {

                        window.AppModal.open(
                            'modalRevisarSolicitud'
                        );
                    }
                }
            );
        });



    function formatearTipo(tipo) {

        const tipos = {
            TARDANZA:
                'Justificación de tardanza',

            FALTA:
                'Justificación de falta',

            PERMISO:
                'Permiso',

            OMISION_MARCACION:
                'Omisión de marcación',

            OTRO:
                'Otro',
        };

        return tipos[tipo] || tipo || '-';
    }

});