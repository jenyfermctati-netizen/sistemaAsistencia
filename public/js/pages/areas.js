document.addEventListener("DOMContentLoaded", function () {
    // Editar área
    const formEditar = document.getElementById("formEditarArea");

    document.querySelectorAll("[data-editar-area]").forEach(function (button) {
        button.addEventListener("click", function () {
            formEditar.action = button.dataset.updateUrl;

            document.getElementById("editarAreaNombre").value =
                button.dataset.nombre ?? "";
            document.getElementById("editarAreaDescripcion").value =
                button.dataset.descripcion ?? "";
            document.getElementById("editarAreaEstado").value =
                button.dataset.estado;

            window.AppModal.open("modalEditarArea");
        });
    });

    // Cambiar estado
    const formEstado = document.getElementById("formEstadoArea");
    const mensaje = document.getElementById("estadoAreaMensaje");
    const confirmar = document.getElementById("btnConfirmarEstadoArea");

    document
        .querySelectorAll("[data-cambiar-estado-area]")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                formEstado.action = button.dataset.url;

                const nombre = button.dataset.nombre;
                const estado = button.dataset.estado;

                if (estado === "1") {
                    mensaje.textContent = `¿Deseas desactivar el área ${nombre}?`;
                    confirmar.textContent = "Desactivar";
                    confirmar.className = "button button--danger";
                } else {
                    mensaje.textContent = `¿Deseas activar el área ${nombre}?`;
                    confirmar.textContent = "Activar";
                    confirmar.className = "button button--primary";
                }

                window.AppModal.open("modalEstadoArea");
            });
        });
});
