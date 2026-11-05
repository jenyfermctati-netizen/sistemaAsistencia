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

        const sidebarClose =
            sidebar?.querySelector(
                '[data-sidebar-close]'
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


        const desktop = window.matchMedia('(width > 1100px)');
        const app = sidebar?.closest('.app');

        function updateSidebarToggle(isOpen) {
            toggle?.setAttribute('aria-expanded', String(isOpen));
            toggle?.setAttribute('aria-label', isOpen ? 'Ocultar menú principal' : 'Mostrar menú principal');
        }

        function setSidebarOpen(isOpen) {

            if (!sidebar || !overlay) {
                return;
            }

            sidebar.classList.toggle('open', isOpen);
            sidebar.classList.toggle('is-open', isOpen);
            overlay.classList.toggle('active', isOpen);
            overlay.classList.toggle('is-visible', isOpen);
            document.body.classList.toggle('sidebar-visible', isOpen);
            updateSidebarToggle(desktop.matches ? !app?.classList.contains('sidebar-collapsed') : isOpen);

        }


        updateSidebarToggle(desktop.matches);
        desktop.addEventListener('change', () => setSidebarOpen(false));

        toggle?.addEventListener(
            'click',
            function () {
                if (desktop.matches) {
                    app?.classList.remove('sidebar-collapsed');
                    updateSidebarToggle(true);
                    sidebarClose?.focus();
                    return;
                }

                setSidebarOpen(!sidebar?.classList.contains('open'));
            }
        );


        overlay?.addEventListener(
            'click',
            function () {

                setSidebarOpen(false);

            }
        );


        sidebarClose?.addEventListener(
            'click',
            function () {
                if (desktop.matches) {
                    app?.classList.add('sidebar-collapsed');
                    updateSidebarToggle(false);
                } else {
                    setSidebarOpen(false);
                }
                toggle?.focus();
            }
        );


        sidebar?.querySelectorAll('a').forEach(
            function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 1100) {
                        setSidebarOpen(false);
                    }
                });
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

            const profileName = profileMenuTrigger.querySelector('.navbar__user-info strong')?.textContent?.trim()
                || 'usuario';
            profileMenuTrigger.setAttribute(
                'aria-label',
                `${isOpen ? 'Cerrar' : 'Abrir'} menú de ${profileName}`
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

                if (event.key === 'Escape') {
                    if (profileMenuTrigger?.getAttribute('aria-expanded') === 'true') {
                        setProfileMenuOpen(false);
                        profileMenuTrigger.focus();
                    }

                    if (sidebar?.classList.contains('open')) {
                        setSidebarOpen(false);
                        toggle?.focus();
                    }
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
