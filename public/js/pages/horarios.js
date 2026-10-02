document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | HABILITAR / DESHABILITAR HORAS SEGÚN DÍA
    |--------------------------------------------------------------------------
    */

    function configurarDiaLaborable(checkbox) {

        const fila = checkbox.closest('.dia-editor');

        if (!fila) {
            return;
        }

        const campos =
            fila.querySelectorAll('.dia-hora');

        campos.forEach(function (campo) {
            campo.disabled = !checkbox.checked;
        });
    }


    document
        .querySelectorAll('.dia-laborable')
        .forEach(function (checkbox) {

            configurarDiaLaborable(checkbox);

            checkbox.addEventListener(
                'change',
                function () {
                    configurarDiaLaborable(checkbox);
                }
            );
        });



    /*
    |--------------------------------------------------------------------------
    | EDITAR HORARIO
    |--------------------------------------------------------------------------
    */

    const formEditar =
        document.getElementById('formEditarHorario');

    const nombreEditar =
        document.getElementById('editarHorarioNombre');

    const descripcionEditar =
        document.getElementById(
            'editarHorarioDescripcion'
        );


    document
        .querySelectorAll('[data-editar-horario]')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                if (!formEditar) {
                    return;
                }

                formEditar.action =
                    button.dataset.url;

                nombreEditar.value =
                    button.dataset.nombre || '';

                descripcionEditar.value =
                    button.dataset.descripcion || '';

                let dias = [];

                try {
                    dias = JSON.parse(
                        button.dataset.dias || '[]'
                    );
                } catch (error) {
                    dias = [];
                }


                dias.forEach(function (dia) {

                    const fila =
                        document.querySelector(
                            '#editarDiasHorario ' +
                            `[data-dia="${dia.dia_semana}"]`
                        );

                    if (!fila) {
                        return;
                    }


                    const checkbox =
                        fila.querySelector(
                            '.dia-laborable'
                        );

                    const entrada =
                        fila.querySelector(
                            '.hora-entrada'
                        );

                    const salida =
                        fila.querySelector(
                            '.hora-salida'
                        );

                    const tolerancia =
                        fila.querySelector(
                            '.tolerancia'
                        );


                    checkbox.checked =
                        Boolean(dia.es_laborable);

                    entrada.value =
                        dia.hora_entrada || '';

                    salida.value =
                        dia.hora_salida || '';

                    tolerancia.value =
                        dia.tolerancia_minutos ?? 0;


                    configurarDiaLaborable(
                        checkbox
                    );
                });


                if (window.AppModal) {
                    window.AppModal.open(
                        'modalEditarHorario'
                    );
                }

            });
        });



    /*
    |--------------------------------------------------------------------------
    | CAMBIAR ESTADO
    |--------------------------------------------------------------------------
    */

    const formEstado =
        document.getElementById(
            'formEstadoHorario'
        );

    const mensajeEstado =
        document.getElementById(
            'estadoHorarioMensaje'
        );

    const botonEstado =
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

                    if (!formEstado) {
                        return;
                    }

                    const activo =
                        button.dataset.activo === '1';

                    const nombre =
                        button.dataset.nombre || 'este horario';


                    formEstado.action =
                        button.dataset.url;


                    mensajeEstado.textContent =
                        activo
                            ? `¿Deseas desactivar el horario "${nombre}"?`
                            : `¿Deseas activar el horario "${nombre}"?`;


                    botonEstado.textContent =
                        activo
                            ? 'Desactivar'
                            : 'Activar';


                    if (window.AppModal) {
                        window.AppModal.open(
                            'modalEstadoHorario'
                        );
                    }

                }
            );
        });



    /*
    |--------------------------------------------------------------------------
    | PREVISUALIZACIÓN CONTROL INDIVIDUAL
    |--------------------------------------------------------------------------
    */

    const trabajadorIndividual =
        document.getElementById(
            'asignarTrabajador'
        );

    const previewControl =
        document.getElementById(
            'tipoControlPreview'
        );


    function actualizarControlIndividual() {

        if (
            !trabajadorIndividual
            ||
            !previewControl
        ) {
            return;
        }

        const opcion =
            trabajadorIndividual
                .options[
                    trabajadorIndividual.selectedIndex
                ];


        if (
            !opcion
            ||
            !opcion.value
        ) {
            previewControl.hidden = true;
            return;
        }


        const vinculo =
            opcion.dataset.vinculo;


        if (vinculo === 'LOCADOR') {

            previewControl.innerHTML = `
                <strong>Control referencial</strong>
                <span>
                    No genera tardanza ni falta automática.
                </span>
            `;

        } else {

            previewControl.innerHTML = `
                <strong>Control obligatorio</strong>
                <span>
                    Se utilizará para asistencia, tardanza y faltas.
                </span>
            `;
        }


        previewControl.hidden = false;
    }


    trabajadorIndividual?.addEventListener(
        'change',
        actualizarControlIndividual
    );



    /*
    |--------------------------------------------------------------------------
    | ASIGNACIÓN MASIVA
    |--------------------------------------------------------------------------
    */

    const buscador =
        document.getElementById(
            'filtroMasivoBuscar'
        );

    const filtroArea =
        document.getElementById(
            'filtroMasivoArea'
        );

    const filtroVinculo =
        document.getElementById(
            'filtroMasivoVinculo'
        );

    const seleccionarTodos =
        document.getElementById(
            'seleccionarTodosMasivo'
        );

    const contador =
        document.getElementById(
            'contadorSeleccionadosMasivo'
        );

    const botonAsignar =
        document.getElementById(
            'btnAsignarMasivo'
        );

    const sinResultados =
        document.getElementById(
            'sinResultadosMasivo'
        );


    function filasMasivas() {

        return Array.from(
            document.querySelectorAll(
                '[data-trabajador-masivo]'
            )
        );
    }


    function normalizar(texto) {

        return (texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            );
    }


    function actualizarSeleccion() {

        const checkboxes =
            document.querySelectorAll(
                '.trabajador-masivo-checkbox'
            );

        const seleccionados =
            Array.from(checkboxes)
                .filter(function (checkbox) {
                    return checkbox.checked;
                });


        if (contador) {

            contador.textContent =
                `${seleccionados.length} seleccionados`;

        }


        if (botonAsignar) {

            botonAsignar.disabled =
                seleccionados.length === 0;

        }


        const visibles =
            filasMasivas()
                .filter(function (fila) {
                    return !fila.hidden;
                });


        const visiblesMarcados =
            visibles.filter(function (fila) {

                const checkbox =
                    fila.querySelector(
                        '.trabajador-masivo-checkbox'
                    );

                return checkbox?.checked;
            });


        if (seleccionarTodos) {

            seleccionarTodos.checked =
                visibles.length > 0
                &&
                visibles.length
                === visiblesMarcados.length;

        }
    }


    function filtrar() {

        const texto =
            normalizar(
                buscador?.value
            );

        const area =
            filtroArea?.value || '';

        const vinculo =
            filtroVinculo?.value || '';

        let cantidadVisibles = 0;


        filasMasivas().forEach(
            function (fila) {

                const coincideTexto =
                    !texto
                    ||
                    normalizar(
                        fila.dataset.busqueda
                    ).includes(texto);


                const coincideArea =
                    !area
                    ||
                    fila.dataset.area === area;


                const coincideVinculo =
                    !vinculo
                    ||
                    fila.dataset.vinculo
                    === vinculo;


                const visible =
                    coincideTexto
                    &&
                    coincideArea
                    &&
                    coincideVinculo;


                fila.hidden = !visible;


                if (visible) {
                    cantidadVisibles++;
                }

            }
        );


        if (sinResultados) {

            sinResultados.hidden =
                cantidadVisibles > 0;

        }


        actualizarSeleccion();
    }


    buscador?.addEventListener(
        'input',
        filtrar
    );


    filtroArea?.addEventListener(
        'change',
        filtrar
    );


    filtroVinculo?.addEventListener(
        'change',
        filtrar
    );


    seleccionarTodos?.addEventListener(
        'change',
        function () {

            const checked =
                seleccionarTodos.checked;


            filasMasivas()
                .filter(function (fila) {
                    return !fila.hidden;
                })
                .forEach(function (fila) {

                    const checkbox =
                        fila.querySelector(
                            '.trabajador-masivo-checkbox'
                        );

                    if (checkbox) {
                        checkbox.checked =
                            checked;
                    }

                });


            actualizarSeleccion();

        }
    );


    document
        .querySelectorAll(
            '.trabajador-masivo-checkbox'
        )
        .forEach(function (checkbox) {

            checkbox.addEventListener(
                'change',
                actualizarSeleccion
            );

        });


    filtrar();

});