(function () {
    const openers = new WeakMap();

    function focusableElements(modal) {
        return Array.from(modal.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(function (element) {
            return element.offsetParent !== null && element.getAttribute('aria-hidden') !== 'true';
        });
    }

    function setOpenState(modal, isOpen) {
        modal.classList.toggle('open', isOpen);
        modal.setAttribute('aria-hidden', String(!isOpen));
        document.body.classList.toggle('modal-open', isOpen || Boolean(document.querySelector('.modal.open')));
    }

    function openModal(id, opener) {
        const modal = document.getElementById(id);

        if (!modal) {
            return;
        }

        const current = document.querySelector('.modal.open');
        if (current && current !== modal) {
            closeModal(current, false);
        }

        openers.set(modal, opener || document.activeElement);
        setOpenState(modal, true);

        window.requestAnimationFrame(function () {
            const preferred = modal.querySelector('[autofocus]');
            const target = preferred || focusableElements(modal)[0] || modal.querySelector('.modal__content');
            target?.focus({ preventScroll: true });
        });
    }

    function closeModal(modal, restoreFocus = true) {
        if (!modal) {
            return;
        }

        setOpenState(modal, false);

        if (restoreFocus) {
            const opener = openers.get(modal);
            if (opener instanceof HTMLElement && document.contains(opener)) {
                opener.focus({ preventScroll: true });
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (event) {
            const openButton = event.target.closest('[data-modal-open]');

            if (openButton) {
                openModal(openButton.dataset.modalOpen, openButton);
                return;
            }

            const closeButton = event.target.closest('[data-modal-close]');
            if (closeButton) {
                closeModal(closeButton.closest('.modal'));
            }
        });

        document.addEventListener('keydown', function (event) {
            const modal = document.querySelector('.modal.open');

            if (!modal) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal(modal);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusable = focusableElements(modal);
            if (!focusable.length) {
                event.preventDefault();
                modal.querySelector('.modal__content')?.focus();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    });

    window.AppModal = {
        open(id) {
            openModal(id);
        },
        close(id) {
            closeModal(document.getElementById(id));
        },
    };
})();
