/* Visualizaciones del dashboard; no modifica marcaciones ni envía solicitudes */
document.addEventListener("DOMContentLoaded", () => {
    const root = document.getElementById("dashboardModern");
    if (!root) return;
    const charts = [];
    if (typeof Chart === "undefined") return;
    const areas = Array.from(
        root.querySelectorAll("#dashboardChartData [data-name]"),
    ).map((node) => ({
        name: node.dataset.name || "Área",
        coverage: Number(node.dataset.coverage) || 0,
        present: Number(node.dataset.present) || 0,
        late: Number(node.dataset.late) || 0,
        absent: Number(node.dataset.absent) || 0,
        justified: Number(node.dataset.justified) || 0,
    }));
    const donut = document.getElementById("dashboardDonut");
    const bar = document.getElementById("dashboardAreasChart");

    const totals = [
        areas.reduce((x, a) => x + Math.max(0, a.present - a.late), 0),
        areas.reduce((x, a) => x + a.late, 0),
        areas.reduce((x, a) => x + a.absent, 0),
        areas.reduce((x, a) => x + a.justified, 0),
    ];
    const colors = ["#10b981", "#f59e0b", "#fb7185", "#38bdf8"];
    const labels = [
        "A tiempo / campo",
        "Tardanzas",
        "Sin asistencia",
        "Justificados",
    ];
    if (donut) {
        charts.push(
            new Chart(donut, {
                type: "doughnut",
                data: {
                    labels,
                    datasets: [
                        {
                            data: totals,
                            backgroundColor: colors,
                            borderWidth: 0,
                            hoverOffset: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "70%",
                    plugins: { legend: { display: false } },
                },
            }),
        );
        const legend = document.getElementById("dashboardDonutLegend");
        if (legend)
            labels.forEach((label, i) => {
                const span = document.createElement("span");
                const swatch = document.createElement("i");
                swatch.style.backgroundColor = colors[i];
                span.append(
                    swatch,
                    document.createTextNode(`${label}: ${totals[i]}`),
                );
                legend.appendChild(span);
            });
    }
    if (bar)
        charts.push(
            new Chart(bar, {
                type: "bar",
                data: {
                    labels: areas.map((a) => a.name),
                    datasets: [
                        {
                            label: "Cobertura (%)",
                            data: areas.map((a) => a.coverage),
                            backgroundColor: "#6366f1",
                            borderRadius: 6,
                            maxBarThickness: 24,
                        },
                    ],
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            min: 0,
                            max: 100,
                            ticks: {
                                color: "#64748b",
                                callback: (v) => v + "%",
                            },
                            grid: { color: "#e2e8f0" },
                        },
                        y: {
                            ticks: { color: "#64748b" },
                            grid: { display: false },
                        },
                    },
                },
            }),
        );
});

// Consultar los datos ya procesados sin crear ni modificar marcaciones.
document.addEventListener("DOMContentLoaded", () => {
    const overlay = document.getElementById("dashboardDetailOverlay");
    const close = document.getElementById("dashboardDetailClose");
    if (!overlay || !close) return;
    let previousFocus = null;
    const dismiss = () => {
        overlay.hidden = true;
        previousFocus?.focus();
    };
    document.querySelectorAll("[data-dashboard-detail]").forEach((button) => {
        button.addEventListener("click", () => {
            previousFocus = button;
            const data = button.dataset;
            document.getElementById("dashboardDetailHeading").textContent =
                data.name || "Detalle de asistencia";
            ["area", "date", "in", "out", "hours", "method", "status"].forEach(
                (key) => {
                    const target = overlay.querySelector(
                        `[data-detail-field="${key}"]`,
                    );
                    if (target) target.textContent = data[key] || "—";
                },
            );
            overlay.querySelector(
                '[data-detail-field="lunchOut"]',
            ).textContent = data.lunchOut || "—";
            overlay.querySelector('[data-detail-field="lunchIn"]').textContent =
                data.lunchIn || "—";
            overlay.hidden = false;
            close.focus();
        });
    });
    close.addEventListener("click", dismiss);
    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) dismiss();
    });
    document.addEventListener("keydown", (event) => {
        if (overlay.hidden) return;
        if (event.key === "Escape") dismiss();
        if (event.key === "Tab") {
            event.preventDefault();
            close.focus();
        }
    });
});
