(function () {
    const mobileQuery = window.matchMedia('(max-width: 700px)');
    const filterSelectors = [
        '.areas-filtros',
        '.trabajadores-filtros',
        '.feriados-filtros',
        '.marcaciones-filtros',
        '.asistencias-filtros',
        '.solicitudes-filtros',
        '.vacaciones-filtros',
        '.campo-filtros',
        '.horarios-filtros',
        '.ra-filters',
    ].join(',');

    function associateLabels(root) {
        root.querySelectorAll('.form-group, .filter-field, .login-field').forEach(function (group, index) {
            const field = group.querySelector('input:not([type="hidden"]), select, textarea');
            const label = group.querySelector('label, .form-label, .filter-field__label');

            if (!field || !label || field.labels?.length || field.getAttribute('aria-label') || field.getAttribute('aria-labelledby')) {
                return;
            }

            if (!field.id) {
                field.id = `ux-field-${index}-${Math.random().toString(36).slice(2, 7)}`;
            }

            if (label.tagName === 'LABEL') {
                label.htmlFor = field.id;
            } else {
                if (!label.id) {
                    label.id = `${field.id}-label`;
                }
                field.setAttribute('aria-labelledby', label.id);
            }
        });
    }

    function hasActiveFilters(form) {
        const ignored = new Set(['_token', 'page']);
        return Array.from(new FormData(form).entries()).some(function ([name, value]) {
            return !ignored.has(name) && String(value).trim() !== '';
        });
    }

    function enhanceFilterForm(form, index) {
        if (form.dataset.uxFilterReady === 'true') {
            return;
        }

        form.dataset.uxFilterReady = 'true';
        const id = form.id || `ux-filter-form-${index}`;
        form.id = id;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ux-filter-toggle';
        button.setAttribute('aria-controls', id);
        button.innerHTML = '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M7 12h10M10 19h4"/></svg><span>Filtros</span><span class="ux-filter-toggle__state"></span>';
        form.parentNode.insertBefore(button, form);

        function applyLayout() {
            if (!mobileQuery.matches) {
                form.hidden = false;
                button.setAttribute('aria-expanded', 'true');
                button.querySelector('.ux-filter-toggle__state').textContent = '';
                return;
            }

            const shouldOpen = button.dataset.userToggled === 'true'
                ? button.getAttribute('aria-expanded') === 'true'
                : hasActiveFilters(form);
            form.hidden = !shouldOpen;
            button.setAttribute('aria-expanded', String(shouldOpen));
            button.querySelector('.ux-filter-toggle__state').textContent = shouldOpen ? 'Ocultar' : 'Mostrar';
        }

        button.addEventListener('click', function () {
            button.dataset.userToggled = 'true';
            const expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!expanded));
            form.hidden = expanded;
            button.querySelector('.ux-filter-toggle__state').textContent = expanded ? 'Mostrar' : 'Ocultar';
            if (!expanded) {
                form.querySelector('input, select, textarea')?.focus();
            }
        });

        mobileQuery.addEventListener?.('change', applyLayout);
        applyLayout();
    }

    function enhanceScrollableTables(root) {
        root.querySelectorAll('.table-container, .solicitudes-table-wrap, .manual-table-wrap').forEach(function (container) {
            if (container.scrollWidth <= container.clientWidth || container.hasAttribute('aria-label')) {
                return;
            }

            container.tabIndex = 0;
            container.setAttribute('role', 'region');
            container.setAttribute('aria-label', 'Tabla desplazable horizontalmente');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        associateLabels(document);
        document.querySelectorAll(filterSelectors).forEach(enhanceFilterForm);
        enhanceScrollableTables(document);
    });

    document.addEventListener('live:updated', function (event) {
        const region = event.detail?.region
            ? document.querySelector(`[data-live-region="${event.detail.region}"]`)
            : document;
        if (region) {
            associateLabels(region);
            enhanceScrollableTables(region);
        }
    });
})();
