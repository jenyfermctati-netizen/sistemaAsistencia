document.addEventListener("DOMContentLoaded", function () {
    const formCrear = document.querySelector("[data-form-crear-feriado]");

    if (formCrear?.dataset.reopenOnError === "1" && window.AppModal) {
        window.AppModal.open("modalCrearFeriado");
    }

    const formEditar = document.getElementById("formEditarFeriado");
    const editarFecha = document.getElementById("editarFeriadoFecha");
    const editarNombre = document.getElementById("editarFeriadoNombre");
    const editarTipo = document.getElementById("editarFeriadoTipo");
    const editarArea = document.getElementById("editarFeriadoArea");
    const editarDescripcion = document.getElementById(
        "editarFeriadoDescripcion",
    );

    document
        .querySelectorAll("[data-editar-feriado]")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                if (!formEditar) {
                    return;
                }

                formEditar.action = button.dataset.url || "";
                editarFecha.value = button.dataset.fecha || "";
                editarNombre.value = button.dataset.nombre || "";
                editarTipo.value = button.dataset.tipo || "NACIONAL";
                editarArea.value = button.dataset.area || "";
                editarDescripcion.value = button.dataset.descripcion || "";

                if (window.AppModal) {
                    window.AppModal.open("modalEditarFeriado");
                }
            });
        });

    const formEstado = document.getElementById("formEstadoFeriado");
    const mensajeEstado = document.getElementById("estadoFeriadoMensaje");
    const botonEstado = document.getElementById("btnEstadoFeriado");

    document
        .querySelectorAll("[data-cambiar-estado-feriado]")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                if (!formEstado || !mensajeEstado || !botonEstado) {
                    return;
                }

                const estaActivo = button.dataset.activo === "1";
                const nombre = button.dataset.nombre || "este feriado";

                formEstado.action = button.dataset.url || "";
                mensajeEstado.textContent = estaActivo
                    ? `¿Deseas desactivar el feriado "${nombre}"?`
                    : `¿Deseas activar el feriado "${nombre}"?`;
                botonEstado.textContent = estaActivo
                    ? "Desactivar"
                    : "Activar";
                botonEstado.className = estaActivo
                    ? "button button--danger"
                    : "button button--primary";

                if (window.AppModal) {
                    window.AppModal.open("modalEstadoFeriado");
                }
            });
        });
});
