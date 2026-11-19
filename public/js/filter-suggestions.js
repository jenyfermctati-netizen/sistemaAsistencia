(() => {
    const normalize = (value) => (value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('es');

    const readSuggestions = (input) => {
        const scope = input.closest('.panel, .sa-panel, .table-container, .sa-table-wrap') || document;
        const rows = [...scope.querySelectorAll('table tbody tr')];
        const values = new Map();

        rows.forEach((row) => {
            [...row.cells].forEach((cell) => {
                if (cell.dataset.label?.toLocaleLowerCase('es').includes('acciones')) return;

                cell.innerText.split(/[\r\n]+/).forEach((part) => {
                    const value = part
                        .replace(/^\s*(DNI|correo|biom[eé]trico|c[oó]digo)\s*:\s*/i, '')
                        .replace(/\s+/g, ' ')
                        .trim();
                    const key = normalize(value);
                    if (value.length >= 2 && value.length <= 90 && key && !values.has(key)) {
                        values.set(key, value);
                    }
                });
            });
        });

        return [...values.values()];
    };

    const makeItem = (label, onSelect, extraClass = '') => {
        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('role', 'option');
        button.className = `filter-suggestions__option ${extraClass}`.trim();
        button.textContent = label;
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', onSelect);
        return button;
    };

    document.querySelectorAll('form input[name="buscar"], form input[type="search"]').forEach((input, inputIndex) => {
        const form = input.closest('form');
        if (!form || !['get', ''].includes((form.method || 'get').toLowerCase())) return;

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('aria-autocomplete', 'list');

        const menu = document.createElement('div');
        menu.className = 'filter-suggestions';
        menu.id = `filter-suggestions-${inputIndex}`;
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        document.body.append(menu);
        input.setAttribute('aria-controls', menu.id);

        const candidates = readSuggestions(input);
        let activeIndex = -1;

        const close = () => {
            menu.hidden = true;
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        };

        const positionMenu = () => {
            const rect = input.getBoundingClientRect();
            const maxHeight = Math.min(320, window.innerHeight * 0.45);
            const showAbove = rect.bottom + maxHeight + 8 > window.innerHeight && rect.top > maxHeight;
            menu.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - rect.width - 8))}px`;
            menu.style.width = `${Math.min(rect.width, window.innerWidth - 16)}px`;
            menu.style.top = showAbove
                ? `${Math.max(8, rect.top - Math.min(menu.scrollHeight, maxHeight) - 5)}px`
                : `${rect.bottom + 5}px`;
        };

        const selectValue = (value) => {
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            close();
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        };

        const render = () => {
            const query = input.value.trim();
            menu.replaceChildren();
            activeIndex = -1;
            if (!query) return close();

            const needle = normalize(query);
            const matches = candidates
                .filter((value) => normalize(value).includes(needle))
                .sort((a, b) => Number(!normalize(a).startsWith(needle)) - Number(!normalize(b).startsWith(needle)))
                .slice(0, 7);

            matches.forEach((value, index) => {
                const option = makeItem(value, () => selectValue(value));
                option.id = `${menu.id}-option-${index}`;
                menu.append(option);
            });

            const searchAll = makeItem(`Buscar “${query}” en todos los resultados`, () => {
                close();
                if (typeof form.requestSubmit === 'function') form.requestSubmit();
                else form.submit();
            }, 'filter-suggestions__search-all');
            searchAll.id = `${menu.id}-option-${matches.length}`;
            menu.append(searchAll);
            menu.hidden = false;
            positionMenu();
        };

        input.addEventListener('input', render);
        input.addEventListener('keydown', (event) => {
            if (menu.hidden) return;
            const options = [...menu.querySelectorAll('[role="option"]')];
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = (activeIndex + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length;
                options.forEach((option, index) => option.setAttribute('aria-selected', index === activeIndex ? 'true' : 'false'));
                input.setAttribute('aria-activedescendant', options[activeIndex].id);
            } else if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                options[activeIndex].click();
            } else if (event.key === 'Escape') {
                close();
            }
        });
        input.addEventListener('blur', () => window.setTimeout(close, 120));
        window.addEventListener('resize', () => { if (!menu.hidden) positionMenu(); });
        window.addEventListener('scroll', () => { if (!menu.hidden) positionMenu(); }, true);
    });
})();
