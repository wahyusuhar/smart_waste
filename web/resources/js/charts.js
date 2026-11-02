import { BarController, BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js';

// Only the pieces used by the dashboard are registered, which keeps the bundle small.
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

Chart.defaults.font.family = "'Inter Variable', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = '#6b7280';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('reportsChart', ({ labels, values }) => {
        // Kept outside Alpine's reactive state: Chart.js breaks when its instance is wrapped in a proxy.
        let chart = null;

        return {
            init() {
                chart = new Chart(this.$refs.canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Laporan',
                                data: values,
                                backgroundColor: '#0f766e',
                                hoverBackgroundColor: '#115e59',
                                borderRadius: 2,
                                maxBarThickness: 14,
                            },
                        ],
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                displayColors: false,
                                callbacks: { label: (item) => `${item.parsed.y} laporan` },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8, font: { size: 11 } },
                            },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: '#f3f4f6' },
                                ticks: { precision: 0, font: { size: 11 } },
                            },
                        },
                    },
                });
            },
            destroy() {
                chart?.destroy();
            },
        };
    });
});
