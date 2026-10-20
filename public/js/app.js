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

        const profileMenu =
            document.querySelector(
                '[data-profile-menu]'
            );

        const profileMenuTrigger =
            profileMenu?.querySelector(
                '[data-profile-menu-trigger]'
            );

        const profileMenuPanel =
            profileMenu?.querySelector(
                '[data-profile-menu-panel]'
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


        function setProfileMenuOpen(isOpen) {

            if (!profileMenuTrigger || !profileMenuPanel) {
                return;
            }

            profileMenuTrigger.setAttribute(
                'aria-expanded',
                String(isOpen)
            );

            profileMenuPanel.hidden = !isOpen;

            profileMenu.classList.toggle(
                'is-open',
                isOpen
            );

        }


        profileMenuTrigger?.addEventListener(
            'click',
            function () {

                const isOpen =
                    profileMenuTrigger.getAttribute(
                        'aria-expanded'
                    ) === 'true';

                setProfileMenuOpen(!isOpen);

            }
        );


        document.addEventListener(
            'click',
            function (event) {

                if (
                    profileMenu &&
                    !profileMenu.contains(event.target)
                ) {
                    setProfileMenuOpen(false);
                }

            }
        );


        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    profileMenuTrigger?.getAttribute('aria-expanded') === 'true'
                ) {
                    setProfileMenuOpen(false);
                    profileMenuTrigger.focus();
                }

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
