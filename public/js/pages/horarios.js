document.addEventListener('DOMContentLoaded', function () {

    function configurarDia(container) {

        const checkbox =
            container.querySelector('.dia-laborable');

        const campos =
            container.querySelectorAll('.dia-hora');

        if (!checkbox) {
            return;
        }

        function actualizar() {

            campos.forEach(function (campo) {
                campo.disabled = !checkbox.checked;
            });

        }

        checkbox.addEventListener(
            'change',
            actualizar
        );

        actualizar();
    }


    document
        .querySelectorAll('.dia-editor')
        .forEach(configurarDia);


    // Editar horario
    const formEditar =
        document.getElementById('formEditarHorario');

    document
        .querySelectorAll('[data-editar-horario]')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                formEditar.action =
                    button.dataset.url;

                document.getElementById(
                    'editarHorarioNombre'
                ).value =
                    button.dataset.nombre ?? '';

                document.getElementById(
                    'editarHorarioDescripcion'
                ).value =
                    button.dataset.descripcion ?? '';

                let dias = [];

                try {
                    dias = JSON.parse(
                        button.dataset.dias
                    );
                } catch (error) {
                    console.error(
                        'No se pudieron cargar los días.',
                        error
                    );
                }


                dias.forEach(function (dia) {

                    const container =
                        document.querySelector(
                            '#editarDiasHorario [data-dia="' +
                            dia.dia_semana +
                            '"]'
                        );

                    if (!container) {
                        return;
                    }

                    const check =
                        container.querySelector(
                            '.dia-laborable'
                        );

                    const entrada =
                        container.querySelector(
                            '.hora-entrada'
                        );

                    const salida =
                        container.querySelector(
                            '.hora-salida'
                        );

                    const tolerancia =
                        container.querySelector(
                            '.tolerancia'
                        );


                    check.checked =
                        dia.es_laborable === true
                        ||
                        dia.es_laborable === 1;


                    entrada.value =
                        dia.hora_entrada ?? '';


                    salida.value =
                        dia.hora_salida ?? '';


                    tolerancia.value =
                        dia.tolerancia_minutos ?? 0;


                    entrada.disabled =
                        !check.checked;

                    salida.disabled =
                        !check.checked;

                    tolerancia.disabled =
                        !check.checked;
                });


                window.AppModal.open(
                    'modalEditarHorario'
                );

            });

        });


    // Estado
    const formEstado =
        document.getElementById(
            'formEstadoHorario'
        );

    const mensaje =
        document.getElementById(
            'estadoHorarioMensaje'
        );

    const botonConfirmar =
        document.getElementById(
            'btnEstadoHorario'
        );


    document
        .querySelectorAll(
            '[data-cambiar-estado-horario]'
        )
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    formEstado.action =
                        button.dataset.url;

                    const nombre =
                        button.dataset.nombre;

                    const estado =
                        button.dataset.estado;


                    if (estado === '1') {

                        mensaje.textContent =
                            `¿Deseas desactivar el horario "${nombre}"?`;

                        botonConfirmar.textContent =
                            'Desactivar';

                        botonConfirmar.className =
                            'button button--danger';

                    } else {

                        mensaje.textContent =
                            `¿Deseas activar el horario "${nombre}"?`;

                        botonConfirmar.textContent =
                            'Activar';

                        botonConfirmar.className =
                            'button button--primary';

                    }


                    window.AppModal.open(
                        'modalEstadoHorario'
                    );

                }
            );

        });

});