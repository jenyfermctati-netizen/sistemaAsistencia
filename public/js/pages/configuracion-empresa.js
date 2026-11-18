document.addEventListener('DOMContentLoaded', () => {
    const settings = document.querySelector('[data-company-settings]');
    if (!settings) return;
    const native = settings.querySelector('[data-company-color]');
    const hex = settings.querySelector('[data-company-hex]');
    const preview = settings.querySelector('[data-company-preview]');
    const name = settings.querySelector('[data-company-name]');
    const swatches = settings.querySelectorAll('[data-company-swatch]');
    const updateColor = (value) => {
        if (!/^#[0-9a-f]{6}$/i.test(value)) return;
        native.value = value;
        hex.value = value.toUpperCase();
        preview.style.setProperty('--preview-color', value);
        const rgb = value.slice(1).match(/.{2}/g).map(v => parseInt(v, 16) / 255)
            .map(v => v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4);
        const luminance = .2126 * rgb[0] + .7152 * rgb[1] + .0722 * rgb[2];
        preview.style.setProperty('--preview-contrast', luminance > .179 ? '#102a35' : '#ffffff');
        swatches.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.companySwatch.toLowerCase() === value.toLowerCase())));
    };
    native.addEventListener('input', () => updateColor(native.value));
    hex.addEventListener('input', () => updateColor(hex.value));
    swatches.forEach(button => button.addEventListener('click', () => updateColor(button.dataset.companySwatch)));
    name.addEventListener('input', () => {
        settings.querySelector('[data-company-preview-name]').textContent = name.value.trim() || preview.dataset.fallbackName;
    });
    updateColor(hex.value);
});
