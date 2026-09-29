document.addEventListener(
    'DOMContentLoaded',
    function () {

        const sidebar =
            document.getElementById(
                'sidebar'
            );

        const toggle =
            document.getElementById(
                'sidebarToggle'
            );

        const overlay =
            document.getElementById(
                'sidebarOverlay'
            );


        toggle?.addEventListener(
            'click',
            function () {

                sidebar.classList.toggle(
                    'open'
                );

                overlay.classList.toggle(
                    'active'
                );

            }
        );


        overlay?.addEventListener(
            'click',
            function () {

                sidebar.classList.remove(
                    'open'
                );

                overlay.classList.remove(
                    'active'
                );

            }
        );


        document
            .querySelectorAll(
                '.alert__close'
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        'click',
                        function () {

                            button
                                .closest(
                                    '.alert'
                                )
                                ?.remove();

                        }
                    );

                }
            );

    }
);