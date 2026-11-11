document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-color-picker]").forEach((picker) => {
        const native = picker.querySelector("[data-color-native]");
        const hex = picker.querySelector("[data-color-hex]");
        const normalize = (value) => {
            const color = String(value || "").trim();
            return /^#[0-9a-f]{6}$/i.test(color) ? color.toUpperCase() : null;
        };
        const setColor = (value) => {
            const color = normalize(value);
            if (!color) return;
            native.value = color;
            hex.value = color;
        };

        native?.addEventListener("input", () => setColor(native.value));
        hex?.addEventListener("input", () => {
            const color = normalize(hex.value);
            if (color) native.value = color;
        });
        picker.querySelectorAll("[data-color-choice]").forEach((button) => {
            button.addEventListener("click", () => setColor(button.dataset.colorChoice));
        });
    });
});
