document.addEventListener('DOMContentLoaded', function () {

    const formEditar =
        document.getElementById('formEditarReporte');

    document
        .querySelectorAll('[data-editar-reporte]')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                formEditar.action =
                    button.dataset.url;

                document.getElementById(
                    'editarPeriodoInicio'
                ).value =
                    button.dataset.periodoInicio ?? '';

                document.getElementById(
                    'editarPeriodoFin'
                ).value =
                    button.dataset.periodoFin ?? '';

                document.getElementById(
                    'editarDescripcion'
                ).value =
                    button.dataset.descripcion ?? '';

                document.getElementById(
                    'editarPorcentaje'
                ).value =
                    button.dataset.porcentaje ?? '';

                window.AppModal.open(
                    'modalEditarReporte'
                );
            });

        });


    document
        .querySelectorAll('[data-ver-reporte]')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                document.getElementById(
                    'detalleTrabajador'
                ).textContent =
                    button.dataset.trabajador ?? '-';

                document.getElementById(
                    'detallePeriodo'
                ).textContent =
                    button.dataset.periodo ?? '-';

                document.getElementById(
                    'detalleDescripcion'
                ).textContent =
                    button.dataset.descripcion ?? '-';

                document.getElementById(
                    'detallePorcentaje'
                ).textContent =
                    button.dataset.porcentaje
                        ? button.dataset.porcentaje + '%'
                        : '-';

                document.getElementById(
                    'detalleEstado'
                ).textContent =
                    button.dataset.estado ?? '-';

                document.getElementById(
                    'detalleComentario'
                ).textContent =
                    button.dataset.comentario || 'Sin comentario';

                window.AppModal.open(
                    'modalVerReporte'
                );
            });

        });


    const formRevisar =
        document.getElementById('formRevisarReporte');

    document
        .querySelectorAll('[data-revisar-reporte]')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                formRevisar.action =
                    button.dataset.url;

                document.getElementById(
                    'revisarTrabajador'
                ).textContent =
                    button.dataset.trabajador;

                window.AppModal.open(
                    'modalRevisarReporte'
                );
            });

        });

});