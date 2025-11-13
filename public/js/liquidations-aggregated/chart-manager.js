/**
 * Liquidations Aggregated Chart Manager
 * Manages Chart.js instance for liquidations visualization
 */

import { LiquidationsAggregatedUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
    }

    /**
     * Create or update chart (matching Open Interest style)
     */
    updateChart(data) {
        const canvas = document.getElementById(this.canvasId);
        if (!canvas) {
            console.error('❌ Canvas not found:', this.canvasId);
            return;
        }

        const ctx = canvas.getContext('2d');

        // Destroy existing chart
        if (this.chart) {
            this.chart.destroy();
        }

        // Prepare data
        const labels = data.map(item => new Date(item.time).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        const longData = data.map(item => item.long_liquidation_usd || 0);
        const shortData = data.map(item => item.short_liquidation_usd || 0);

        // Create new chart (bar chart with Open Interest styling)
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Long Liquidations',
                        data: longData,
                        backgroundColor: 'rgba(239, 68, 68, 0.8)', // Red
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1,
                        barPercentage: 0.75,
                        categoryPercentage: 0.85
                    },
                    {
                        label: 'Short Liquidations',
                        data: shortData,
                        backgroundColor: 'rgba(34, 197, 94, 0.8)', // Green
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 1,
                        barPercentage: 0.75,
                        categoryPercentage: 0.85
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                layout: {
                    padding: {
                        left: 25,
                        right: 25,
                        top: 15,
                        bottom: 15
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#6b7280',
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'rect'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleColor: '#f3f4f6',
                        bodyColor: '#f3f4f6',
                        borderColor: 'rgba(59, 130, 246, 0.3)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = LiquidationsAggregatedUtils.formatValue(context.parsed.y);
                                return `${label}: $${value}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: false,
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: {
                                size: 11
                            },
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(156, 163, 175, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: {
                                size: 11
                            },
                            callback: function(value) {
                                return '$' + LiquidationsAggregatedUtils.formatValue(value);
                            }
                        }
                    }
                }
            }
        });

        console.log('✅ Chart updated successfully');
    }

    /**
     * Destroy chart
     */
    destroy() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}
