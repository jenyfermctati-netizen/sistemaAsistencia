document.addEventListener(
    'DOMContentLoaded',
    function () {

        document.addEventListener(
            'click',
            function (event) {

                const openButton =
                    event.target.closest(
                        '[data-modal-open]'
                    );

                if (openButton) {

                    const modalId =
                        openButton.dataset.modalOpen;

                    openModal(modalId);

                }


                if (
                    event.target.closest(
                        '[data-modal-close]'
                    )
                ) {

                    const modal =
                        event.target.closest(
                            '.modal'
                        );

                    closeModal(modal);

                }

            }
        );


        document.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Escape') {

                    const modal =
                        document.querySelector(
                            '.modal.open'
                        );

                    if (modal) {

                        closeModal(modal);

                    }

                }

            }
        );

    }
);


function openModal(id) {

    const modal =
        document.getElementById(id);

    if (!modal) {
        return;
    }

    modal.classList.add('open');

    document.body.classList.add(
        'modal-open'
    );

}


function closeModal(modal) {

    if (!modal) {
        return;
    }

    modal.classList.remove('open');

    document.body.classList.remove(
        'modal-open'
    );

}


window.AppModal = {

    open(id) {

        openModal(id);

    },

    close(id) {

        const modal =
            document.getElementById(id);

        closeModal(modal);

    }

};