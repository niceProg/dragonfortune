/**
 * Open Interest Chart Manager
 * Handles Chart.js operations for Open Interest visualization
 */

import { OpenInterestUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
    }

    /**
     * Destroy existing chart
     */
    destroy() {
        if (this.chart) {
            try {
                this.chart.stop(); // Stop animations
                this.chart.destroy();
                console.log('🗑️ Chart destroyed:', this.canvasId);
            } catch (error) {
                console.warn('⚠️ Error destroying chart:', error);
            }
            this.chart = null;
        }
    }

    /**
     * Render dual-axis chart (OI + Price overlay)
     * @param {Array} data - OI history data
     * @param {Array} priceData - Price data (optional)
     * @param {string} chartType - 'line' or 'bar' (default: 'line')
     */
    renderChart(data, priceData = [], chartType = 'line') {
        const canvas = document.getElementById(this.canvasId);
        if (!canvas) {
            console.warn('⚠️ Canvas element not found:', this.canvasId);
            return;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.warn('⚠️ Cannot get 2D context');
            return;
        }

        if (!data || data.length === 0) {
            console.warn('⚠️ No data available for chart');
            return;
        }

        const sorted = [...data].sort((a, b) => a.ts - b.ts);
        const labels = sorted.map(d => d.ts);
        const oiValues = sorted.map(d => parseFloat(d.oi_usd || 0));
        const priceValues = sorted.map(d => d.price ? parseFloat(d.price) : null);
        const hasPrice = priceValues.some(p => p !== null);

        // Always destroy and recreate chart for stability (like Funding Rate)
        // In-place update can cause stack overflow errors with large datasets
        if (this.chart) {
            this.destroy();
        }

        // Create new chart
        const datasets = this._buildDatasets(oiValues, hasPrice ? priceValues : null, chartType);
        const chartOptions = this.getChartOptions(!!hasPrice);
        this.chart = new Chart(ctx, {
            type: chartType === 'bar' ? 'bar' : 'line',
            data: { labels, datasets },
            options: chartOptions
        });
    }

    /**
     * Update chart (destroy and recreate for stability)
     */
    updateChart(data, priceData = [], chartType = 'line') {
        this.renderChart(data, priceData, chartType);
    }

    /**
     * Get Chart.js configuration options
     */
    getChartOptions(hasPriceOverlay) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: false, // Disable for stability during auto-refresh
            transitions: { active: { animation: { duration: 0 } } },
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#64748b',
                        font: { size: 11, weight: '500' },
                        boxWidth: 12,
                        boxHeight: 12,
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.98)',
                    titleColor: '#1e293b',
                    bodyColor: '#334155',
                    borderColor: 'rgba(59, 130, 246, 0.3)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        title: (items) => {
                            try {
                                const rawLabel = items[0].label;
                                const timestamp = typeof rawLabel === 'number' ? rawLabel : parseInt(rawLabel, 10);
                                
                                if (isNaN(timestamp)) {
                                    return 'Invalid Date';
                                }
                                
                                const date = new Date(timestamp);
                                
                                // Format date and time in Jakarta timezone (UTC+7)
                                const dateStr = date.toLocaleDateString('en-US', {
                                    weekday: 'short',
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    timeZone: 'Asia/Jakarta'
                                });
                                const timeStr = date.toLocaleTimeString('en-US', {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: false,
                                    timeZone: 'Asia/Jakarta'
                                });
                                return `${dateStr}, ${timeStr} WIB`;
                            } catch (error) {
                                console.error('❌ Error formatting tooltip title:', error);
                                return 'Error';
                            }
                        },
                        label: (context) => {
                            const datasetLabel = context.dataset.label;
                            const value = context.parsed.y;
                            
                            if (datasetLabel.includes('Open Interest')) {
                                return `  ${datasetLabel}: ${OpenInterestUtils.formatOI(value)}`;
                            } else if (datasetLabel.includes('Price')) {
                                return `  ${datasetLabel}: ${OpenInterestUtils.formatPrice(value)}`;
                            }
                            
                            return `  ${datasetLabel}: ${value}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        display: false, // Hide date labels below chart as requested
                        color: '#64748b',
                        font: { size: 10 },
                        maxRotation: 45,
                        minRotation: 45,
                        maxTicksLimit: undefined
                    },
                    grid: {
                        display: true,
                        color: 'rgba(148, 163, 184, 0.15)',
                        drawBorder: false
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Open Interest (USD)',
                        color: '#475569',
                        font: { size: 11, weight: '500' }
                    },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11 },
                        callback: (value) => OpenInterestUtils.formatOI(value)
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.15)',
                        drawBorder: false
                    }
                },
                ...(hasPriceOverlay && {
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Price (USD)',
                            color: '#f59e0b',
                            font: { size: 11, weight: '500' }
                        },
                        ticks: {
                            color: '#f59e0b',
                            font: { size: 11 },
                            callback: (value) => OpenInterestUtils.formatPrice(value)
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                })
            }
        };
    }

    _buildDatasets(oiValues, priceValuesOrNull, chartType) {
        const datasets = [];
        if (chartType === 'bar') {
            datasets.push({
                label: 'Open Interest',
                data: oiValues,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y'
            });
        } else {
            datasets.push({
                label: 'Open Interest',
                data: oiValues,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
                yAxisID: 'y'
            });
        }
        if (priceValuesOrNull) {
            datasets.push({
                type: 'line',
                label: 'Price (USD)',
                data: priceValuesOrNull,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
                yAxisID: 'y1'
            });
        }
        return datasets;
    }
}

