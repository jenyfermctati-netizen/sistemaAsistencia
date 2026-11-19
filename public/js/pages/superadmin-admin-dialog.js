(() => {
    document.querySelectorAll('[data-open-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.openDialog);
            if (dialog && !dialog.open) dialog.showModal();
        });
    });

    document.querySelectorAll('.sa-admin-dialog').forEach((dialog) => {
        dialog.querySelectorAll('[data-close-dialog]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });

        if (dialog.dataset.openOnLoad === 'true') dialog.showModal();
    });

    if (window.location.hash === '#configuracion') {
        const companyDialog = document.getElementById('company-config-dialog');
        if (companyDialog && !companyDialog.open) companyDialog.showModal();
    }

    document.querySelectorAll('[data-user-search]').forEach((search) => {
        const form = search.closest('form');
        const select = form.querySelector('[data-user-select]');
        const emptyMessage = form.querySelector('[data-user-search-empty]');
        const options = [...select.options].slice(1);

        search.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase();
            let visibleOptions = 0;

            options.forEach((option) => {
                const matches = (option.dataset.search || option.textContent).toLocaleLowerCase().includes(query);
                option.hidden = !matches;
                if (!matches && option.selected) select.value = '';
                if (matches) visibleOptions += 1;
            });

            emptyMessage.hidden = visibleOptions > 0;
        });
    });
})();
