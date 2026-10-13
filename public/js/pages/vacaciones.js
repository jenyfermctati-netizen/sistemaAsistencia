document.addEventListener("DOMContentLoaded", function () {
    function calcularDias(inicio, fin) {
        if (!inicio || !fin) {
            return null;
        }

        const fechaInicio = new Date(inicio + "T00:00:00");
        const fechaFin = new Date(fin + "T00:00:00");

        if (fechaFin < fechaInicio) {
            return null;
        }

        const diferencia = fechaFin.getTime() - fechaInicio.getTime();

        return Math.floor(diferencia / 86400000) + 1;
    }

    function actualizarContador(inputInicio, inputFin, elemento) {
        if (!inputInicio || !inputFin || !elemento) {
            return;
        }

        const dias = calcularDias(inputInicio.value, inputFin.value);

        if (!dias) {
            elemento.textContent = "—";
            return;
        }

        elemento.textContent = dias === 1 ? "1 día" : dias + " días";
    }

    const crearInicio = document.querySelector(
        '#modalCrearVacaciones [name="fecha_inicio"]',
    );
    const crearFin = document.querySelector(
        '#modalCrearVacaciones [name="fecha_fin"]',
    );
    const crearDias = document.getElementById("crearVacacionesDias");

    if (crearInicio && crearFin) {
        crearInicio.addEventListener("change", function () {
            if (crearFin.value && crearFin.value < crearInicio.value) {
                crearFin.value = "";
            }

            crearFin.min = crearInicio.value;

            actualizarContador(crearInicio, crearFin, crearDias);
        });

        crearFin.addEventListener("change", function () {
            actualizarContador(crearInicio, crearFin, crearDias);
        });
    }

    const formEditar = document.getElementById("formEditarVacaciones");
    const editarInicio = document.getElementById("editarVacacionesInicio");
    const editarFin = document.getElementById("editarVacacionesFin");
    const editarObservacion = document.getElementById(
        "editarVacacionesObservacion",
    );
    const editarDias = document.getElementById("editarVacacionesDias");

    document
        .querySelectorAll("[data-editar-vacacion]")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                if (!formEditar) {
                    return;
                }

                formEditar.action = button.dataset.url;
                editarInicio.value = button.dataset.inicio || "";
                editarFin.value = button.dataset.fin || "";
                editarObservacion.value = button.dataset.observacion || "";
                editarFin.min = editarInicio.value;

                actualizarContador(editarInicio, editarFin, editarDias);

                if (window.AppModal) {
                    window.AppModal.open("modalEditarVacaciones");
                }
            });
        });

    if (editarInicio && editarFin) {
        editarInicio.addEventListener("change", function () {
            editarFin.min = editarInicio.value;

            if (editarFin.value && editarFin.value < editarInicio.value) {
                editarFin.value = "";
            }

            actualizarContador(editarInicio, editarFin, editarDias);
        });

        editarFin.addEventListener("change", function () {
            actualizarContador(editarInicio, editarFin, editarDias);
        });
    }

    const formRevisar = document.getElementById("formRevisarVacaciones");
    const revisionTrabajador = document.getElementById(
        "revisionVacacionesTrabajador",
    );
    const revisionPeriodo = document.getElementById(
        "revisionVacacionesPeriodo",
    );
    const revisionDecision = document.getElementById(
        "revisionVacacionesDecision",
    );

    document
        .querySelectorAll("[data-revisar-vacacion]")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                if (!formRevisar) {
                    return;
                }

                formRevisar.action = button.dataset.url;
                revisionTrabajador.textContent =
                    button.dataset.trabajador || "-";
                revisionPeriodo.textContent = button.dataset.periodo || "-";
                revisionDecision.value = "";

                if (window.AppModal) {
                    window.AppModal.open("modalRevisarVacaciones");
                }
            });
        });

    const formReprogramar = document.getElementById(
        "formReprogramarVacaciones",
    );
    const reprogramarTrabajador = document.getElementById(
        "reprogramarVacacionesTrabajador",
    );
    const reprogramarInicio = document.getElementById(
        "reprogramarVacacionesInicio",
    );
    const reprogramarFin = document.getElementById("reprogramarVacacionesFin");
    const reprogramarDias = document.getElementById(
        "reprogramarVacacionesDias",
    );

    document
        .querySelectorAll("[data-reprogramar-vacacion]")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                if (!formReprogramar) {
                    return;
                }

                formReprogramar.action = button.dataset.url;
                reprogramarTrabajador.textContent =
                    button.dataset.trabajador || "-";
                reprogramarInicio.value = button.dataset.inicio || "";
                reprogramarFin.value = button.dataset.fin || "";
                reprogramarFin.min = reprogramarInicio.value;

                actualizarContador(
                    reprogramarInicio,
                    reprogramarFin,
                    reprogramarDias,
                );

                if (window.AppModal) {
                    window.AppModal.open("modalReprogramarVacaciones");
                }
            });
        });

    if (reprogramarInicio && reprogramarFin) {
        reprogramarInicio.addEventListener("change", function () {
            reprogramarFin.min = reprogramarInicio.value;

            if (
                reprogramarFin.value &&
                reprogramarFin.value < reprogramarInicio.value
            ) {
                reprogramarFin.value = "";
            }

            actualizarContador(
                reprogramarInicio,
                reprogramarFin,
                reprogramarDias,
            );
        });

        reprogramarFin.addEventListener("change", function () {
            actualizarContador(
                reprogramarInicio,
                reprogramarFin,
                reprogramarDias,
            );
        });
    }

    const formCancelar = document.getElementById("formCancelarVacaciones");
    const cancelarPeriodo = document.getElementById(
        "cancelarVacacionesPeriodo",
    );

    document
        .querySelectorAll("[data-cancelar-vacacion]")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                if (!formCancelar) {
                    return;
                }

                formCancelar.action = button.dataset.url;
                cancelarPeriodo.textContent =
                    button.dataset.periodo || "seleccionado";

                if (window.AppModal) {
                    window.AppModal.open("modalCancelarVacaciones");
                }
            });
        });
});
