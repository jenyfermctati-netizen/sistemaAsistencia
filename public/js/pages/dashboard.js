/* Visualizaciones nativas: funcionan sin servicios ni librerías externas. */
document.addEventListener("DOMContentLoaded", () => {
    const root = document.getElementById("dashboardModern");
    if (!root) return;

    const colors = {
        success: "#0f9f77",
        warning: "#d58a24",
        danger: "#d65c67",
        info: "#168cab",
    };

    const numberFrom = (value) => {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? Math.max(0, parsed) : 0;
    };

    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };

    const renderEmpty = (host, title, description) => {
        if (!host) return;
        host.removeAttribute("role");
        host.removeAttribute("aria-label");
        const empty = element("div", "dashboard-chart-empty");
        empty.setAttribute("role", "status");
        empty.append(
            element("span", "dashboard-chart-empty__mark", "—"),
            element("strong", "", title),
            element("span", "", description),
        );
        host.replaceChildren(empty);
    };

    const renderDonut = (host, legend, segments, unit, emptyDescription) => {
        if (!host) return;
        const total = segments.reduce((sum, segment) => sum + segment.value, 0);
        if (legend) legend.replaceChildren();

        if (total === 0) {
            renderEmpty(
                host,
                "Aún no hay datos procesados",
                emptyDescription,
            );
            return;
        }

        let accumulated = 0;
        const stops = segments.map((segment) => {
            const start = (accumulated / total) * 100;
            accumulated += segment.value;
            const end = (accumulated / total) * 100;
            return `${segment.color} ${start}% ${end}%`;
        });
        const summary = segments
            .map((segment) => `${segment.label}: ${segment.value}`)
            .join(", ");

        const chart = element("div", "dashboard-donut-chart");
        chart.setAttribute("aria-hidden", "true");
        const ring = element("div", "dashboard-donut-chart__ring");
        ring.style.background = `conic-gradient(${stops.join(", ")})`;
        const center = element("div", "dashboard-donut-chart__center");
        center.append(
            element("strong", "", String(total)),
            element("span", "", unit),
        );
        ring.appendChild(center);
        chart.appendChild(ring);

        host.setAttribute("role", "img");
        host.setAttribute("aria-label", `${total} ${unit}. ${summary}.`);
        host.replaceChildren(chart);

        if (!legend) return;
        segments.forEach((segment) => {
            const item = element("span", "dashboard-chart-legend__item");
            const swatch = element("i", "");
            swatch.style.backgroundColor = segment.color;
            const copy = element("span", "");
            copy.append(
                document.createTextNode(`${segment.label} `),
                element("b", "", String(segment.value)),
            );
            item.append(swatch, copy);
            legend.appendChild(item);
        });
    };

    const renderCoverage = (host, areas) => {
        if (!host) return;
        if (areas.length === 0) {
            renderEmpty(
                host,
                "No hay un área disponible",
                "Vincula un área activa para mostrar su cobertura y procesamiento.",
            );
            return;
        }

        host.removeAttribute("role");
        host.removeAttribute("aria-label");
        const list = element("div", "dashboard-coverage-list");
        list.setAttribute("role", "list");

        areas.forEach((area) => {
            const row = element("article", "dashboard-coverage-row");
            row.setAttribute("role", "listitem");

            const header = element("div", "dashboard-coverage-row__header");
            const title = element("div", "dashboard-coverage-row__title");
            title.append(
                element("strong", "", area.name),
                element(
                    "span",
                    "",
                    area.workers === 1
                        ? "1 trabajador activo"
                        : `${area.workers} trabajadores activos`,
                ),
            );
            header.append(
                title,
                element("strong", "dashboard-coverage-row__value", `${area.coverage}%`),
            );

            const progress = element("div", "dashboard-coverage-row__track");
            progress.setAttribute("role", "progressbar");
            progress.setAttribute("aria-label", `Cobertura de ${area.name}`);
            progress.setAttribute("aria-valuemin", "0");
            progress.setAttribute("aria-valuemax", "100");
            progress.setAttribute("aria-valuenow", String(area.coverage));
            const fill = element("span", "");
            fill.style.width = `${area.coverage}%`;
            progress.appendChild(fill);

            const details = element("div", "dashboard-coverage-row__details");
            details.append(
                element("span", "", `${area.processed} procesados`),
                element("span", "", `${area.pending} pendientes`),
                element("span", "", `${area.justified} justificados`),
            );
            row.append(header, progress, details);
            list.appendChild(row);
        });

        host.replaceChildren(list);
    };

    const renderAreaStackedChart = (host, areas) => {
        if (!host) return;
        if (areas.length === 0) {
            renderEmpty(
                host,
                "No hay áreas activas",
                "El comparativo aparecerá cuando exista al menos un área activa.",
            );
            return;
        }

        const categories = [
            { key: "present", label: "A tiempo / campo", color: colors.success },
            { key: "late", label: "Tardanzas", color: colors.warning },
            { key: "justified", label: "Justificados", color: colors.info },
            { key: "absent", label: "Sin asistencia", color: colors.danger },
            { key: "pending", label: "Sin procesar", color: "#cbd5dc" },
        ];
        const chart = element("div", "dashboard-area-stacked-chart");
        chart.setAttribute("role", "group");
        chart.setAttribute("aria-label", "Comparación del estado de asistencia por área");

        const scale = element("div", "dashboard-area-stacked-chart__scale");
        scale.append(
            element("span", "", "0%"),
            element("span", "", "25%"),
            element("span", "", "50%"),
            element("span", "", "75%"),
            element("span", "", "100%"),
        );
        chart.appendChild(scale);

        const rows = element("div", "dashboard-area-stacked-chart__rows");
        areas.forEach((area) => {
            const row = element("div", "dashboard-area-stacked-chart__row");
            const label = element("div", "dashboard-area-stacked-chart__label");
            label.append(
                element("strong", "", area.name),
                element("span", "", `${area.processed} de ${area.workers} procesados`),
            );

            const values = categories.map((category) => ({
                ...category,
                value: area[category.key],
            }));
            const represented = values.reduce((sum, category) => sum + category.value, 0);
            const denominator = Math.max(1, area.workers, represented);
            const summary = values
                .map((category) => `${category.label}: ${category.value}`)
                .join(", ");
            const bar = element("div", "dashboard-area-stacked-chart__bar");
            bar.setAttribute("role", "img");
            bar.setAttribute("aria-label", `${area.name}. ${summary}.`);

            values.forEach((category) => {
                if (category.value === 0) return;
                const segment = element("span", "dashboard-area-stacked-chart__segment");
                segment.style.width = `${(category.value / denominator) * 100}%`;
                segment.style.backgroundColor = category.color;
                segment.title = `${category.label}: ${category.value}`;
                bar.appendChild(segment);
            });

            if (area.workers === 0) {
                bar.appendChild(
                    element("span", "dashboard-area-stacked-chart__no-data", "Sin trabajadores activos"),
                );
            }

            row.append(
                label,
                bar,
                element("strong", "dashboard-area-stacked-chart__total", String(area.workers)),
            );
            rows.appendChild(row);
        });
        chart.appendChild(rows);

        const legend = element("div", "dashboard-area-stacked-chart__legend");
        categories.forEach((category) => {
            const item = element("span", "");
            const swatch = element("i", "");
            swatch.style.backgroundColor = category.color;
            item.append(swatch, document.createTextNode(category.label));
            legend.appendChild(item);
        });
        chart.appendChild(legend);
        host.replaceChildren(chart);
    };

    const areaNodes = Array.from(
        root.querySelectorAll("#dashboardChartData [data-name]"),
    );
    const areas = areaNodes.map((node) => ({
        name: node.dataset.name || "Área",
        coverage: Math.min(100, numberFrom(node.dataset.coverage)),
        present: numberFrom(node.dataset.present),
        late: numberFrom(node.dataset.late),
        absent: numberFrom(node.dataset.absent),
        justified: numberFrom(node.dataset.justified),
        processed: numberFrom(node.dataset.processed),
        pending: numberFrom(node.dataset.pending),
        workers: numberFrom(node.dataset.workers),
    }));

    if (document.getElementById("dashboardDonut")) {
        renderDonut(
            document.getElementById("dashboardDonut"),
            document.getElementById("dashboardDonutLegend"),
            [
                {
                    label: "A tiempo / campo",
                    value: areas.reduce((sum, area) => sum + area.present, 0),
                    color: colors.success,
                },
                {
                    label: "Tardanzas",
                    value: areas.reduce((sum, area) => sum + area.late, 0),
                    color: colors.warning,
                },
                {
                    label: "Sin asistencia",
                    value: areas.reduce((sum, area) => sum + area.absent, 0),
                    color: colors.danger,
                },
                {
                    label: "Justificados",
                    value: areas.reduce((sum, area) => sum + area.justified, 0),
                    color: colors.info,
                },
            ],
            "registros",
            "La distribución se mostrará cuando se procese la asistencia de hoy.",
        );
        const areaChart = document.getElementById("dashboardAreasChart");
        if (areaChart?.dataset.chartMode === "stacked") {
            renderAreaStackedChart(areaChart, areas);
        } else {
            renderCoverage(areaChart, areas);
        }
    }

    const workerData = document.getElementById("dashboardWorkerChartData");
    if (workerData) {
        const statusColors = [
            colors.success,
            colors.warning,
            colors.info,
            colors.danger,
        ];
        const statusSegments = Array.from(
            workerData.querySelectorAll('[data-kind="status"]'),
        ).map((node, index) => ({
            label: node.dataset.label || "Estado",
            value: numberFrom(node.dataset.value),
            color: statusColors[index] || colors.info,
        }));
        renderDonut(
            document.getElementById("dashboardWorkerDonut"),
            document.getElementById("dashboardWorkerDonutLegend"),
            statusSegments,
            "días",
            "La distribución aparecerá cuando tengas asistencias procesadas este mes.",
        );
    }
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
