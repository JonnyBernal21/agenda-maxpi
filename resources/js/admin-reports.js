import Chart from 'chart.js/auto';
import { bindDateRangePickers } from './date-range-picker';

const money = (value) =>
    new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);

const initBalanceChart = () => {
    const canvas = document.getElementById('monthlyBalanceChart');

    if (!canvas) {
        return;
    }

    let payload = { labels: [], sales: [], expenses: [] };

    try {
        payload = JSON.parse(canvas.dataset.chart || '{}');
    } catch {
        payload = { labels: [], sales: [], expenses: [] };
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: payload.labels || [],
            datasets: [
                {
                    label: 'Ingresos',
                    data: payload.sales || [],
                    backgroundColor: '#2dd4bf',
                    borderRadius: 4,
                },
                {
                    label: 'Gastos',
                    data: payload.expenses || [],
                    backgroundColor: '#f9a8d4',
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            layout: {
                padding: { top: 4, right: 6, bottom: 0, left: 0 },
            },
            datasets: {
                bar: {
                    categoryPercentage: 0.7,
                    barPercentage: 0.85,
                },
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'center',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 18,
                        boxWidth: 8,
                        color: '#475569',
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${money(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        minRotation: 0,
                        font: { size: 11 },
                        color: '#64748b',
                    },
                    border: { display: false },
                },
                y: {
                    beginAtZero: true,
                    grace: '10%',
                    grid: { color: '#eef2f7' },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11 },
                        callback: (value) =>
                            new Intl.NumberFormat('es-MX', {
                                notation: 'compact',
                                compactDisplay: 'short',
                            }).format(value),
                    },
                    border: { display: false },
                },
            },
        },
    });
};

document.addEventListener('DOMContentLoaded', async () => {
    const yearSelect = document.getElementById('balanceYear');

    yearSelect?.addEventListener('change', () => {
        yearSelect.form?.submit();
    });

    initBalanceChart();
    await bindDateRangePickers();
});
