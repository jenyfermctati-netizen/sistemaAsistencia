document.addEventListener('DOMContentLoaded', function () {

    const formAnular =
        document.getElementById('formAnularMarcacion');

    const infoAnular =
        document.getElementById('anularMarcacionInfo');


    document
        .querySelectorAll('[data-anular-marcacion]')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                if (!formAnular || !infoAnular) {
                    return;
                }

                formAnular.action =
                    button.dataset.url;

                const trabajador =
                    button.dataset.trabajador ?? '';

                const fecha =
                    button.dataset.fecha ?? '';

                infoAnular.innerHTML = `
                    <strong>${trabajador}</strong>
                    <span>${fecha}</span>
                `;

                window.AppModal.open(
                    'modalAnularMarcacion'
                );

            });

        });

});