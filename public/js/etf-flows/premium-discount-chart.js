/**
 * Premium/Discount Chart Manager
 * Multi-line chart for tracking ETF premium/discount over time
 * Supports multiple ETF comparison
 */

export class PremiumDiscountChartManager {
    constructor(canvasId = 'premiumDiscountChart') {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false;
    }

    /**
     * Render multi-line premium/discount chart
     * @param {Array} datasets - Array of {ticker, data} objects
     */
    renderChart(datasets) {
        if (this.isRendering) {
            console.warn('⚠️ Premium/Discount chart render already in progress');
            return;
        }

        this.isRendering = true;

        try {
            // Destroy existing chart
            this.destroy();

            // Validate Chart.js loaded
            if (typeof Chart === 'undefined') {
                console.warn('⚠️ Chart.js not loaded');
                return;
            }

            // Validate canvas
            const canvas = document.getElementById(this.canvasId);
            if (!canvas || !canvas.isConnected) {
                console.warn('⚠️ Premium/Discount canvas not available');
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('⚠️ Cannot get 2D context');
                return;
            }

            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Prepare data
            const preparedData = this.prepareChartData(datasets);
            
            console.log('📊 Premium/Discount Chart:', {
                datasets: preparedData.datasets.length,
                labels: preparedData.labels.length,
                tickers: preparedData.datasets.map(d => d.label)
            });

            // Create chart
            this.chart = new Chart(ctx, {
                type: 'line',
                data: preparedData,
                options: this.getChartOptions()
            });

            console.log('✅ Premium/Discount chart rendered successfully');

        } catch (error) {
            console.error('❌ Premium/Discount chart error:', error);
        } finally {
            this.isRendering = false;
        }
    }

    /**
     * Prepare chart data from datasets
     */
    prepareChartData(datasets) {
        if (!datasets || datasets.length === 0) {
            return { labels: [], datasets: [] };
        }

        // ETF colors (consistent branding)
        const colors = [
            { border: '#3b82f6', bg: 'rgba(59, 130, 246, 0.1)' },  // IBIT - Blue
            { border: '#ef4444', bg: 'rgba(239, 68, 68, 0.1)' },   // GBTC - Red
            { border: '#10b981', bg: 'rgba(16, 185, 129, 0.1)' },  // FBTC - Green
            { border: '#f59e0b', bg: 'rgba(245, 158, 11, 0.1)' },  // ARKB - Orange
            { border: '#8b5cf6', bg: 'rgba(139, 92, 246, 0.1)' },  // BITB - Purple
            { border: '#ec4899', bg: 'rgba(236, 72, 153, 0.1)' },  // HODL - Pink
        ];

        // Get all unique timestamps (x-axis labels)
        const allTimestamps = new Set();
        datasets.forEach(dataset => {
            dataset.data.forEach(point => allTimestamps.add(point.ts));
        });
        const sortedTimestamps = Array.from(allTimestamps).sort((a, b) => a - b);

        // Create Chart.js datasets
        const chartDatasets = datasets.map((dataset, index) => {
            const color = colors[index % colors.length];
            
            // Map data points to sorted timestamps
            const dataMap = new Map();
            dataset.data.forEach(point => {
                dataMap.set(point.ts, point.premium_discount_bps);
            });

            const data = sortedTimestamps.map(ts => ({
                x: ts,
                y: dataMap.get(ts) || null
            }));

            return {
                label: dataset.ticker,
                data: data,
                borderColor: color.border,
                backgroundColor: color.bg,
                borderWidth: 2,
                pointRadius: 0, // No points for cleaner look
                pointHoverRadius: 5,
                pointHoverBackgroundColor: color.border,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
                tension: 0.1, // Slight curve
                fill: true,
                spanGaps: false // Don't connect null values
            };
        });

        return {
            labels: sortedTimestamps,
            datasets: chartDatasets
        };
    }

    /**
     * Chart.js options
     */
    getChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'start',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        padding: 15,
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        color: '#64748b',
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#f1f5f9',
                    bodyColor: '#cbd5e1',
                    borderColor: 'rgba(148, 163, 184, 0.2)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    boxWidth: 10,
                    boxHeight: 10,
                    boxPadding: 6,
                    usePointStyle: true,
                    callbacks: {
                        title: (items) => {
                            if (!items || items.length === 0) return '';
                            const ts = items[0].parsed.x;
                            return this.formatDate(ts);
                        },
                        label: (context) => {
                            const ticker = context.dataset.label;
                            const value = context.parsed.y;
                            
                            if (value === null) return `${ticker}: No data`;
                            
                            const sign = value >= 0 ? '+' : '';
                            const indicator = value > 0 ? '📈' : value < 0 ? '📉' : '➡️';
                            const status = value > 0 ? 'Premium' : value < 0 ? 'Discount' : 'At NAV';
                            
                            return `${indicator} ${ticker}: ${sign}${value.toFixed(1)} bps (${status})`;
                        },
                        labelColor: (context) => {
                            return {
                                borderColor: context.dataset.borderColor,
                                backgroundColor: context.dataset.borderColor,
                                borderWidth: 2,
                                borderRadius: 2
                            };
                        }
                    }
                },
                annotation: {
                    annotations: {
                        line1: {
                            type: 'line',
                            yMin: 0,
                            yMax: 0,
                            borderColor: 'rgba(100, 116, 139, 0.5)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            label: {
                                content: 'NAV (0 bps)',
                                display: false
                            }
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        displayFormats: {
                            day: 'MMM dd'
                        }
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 10,
                            weight: '500'
                        },
                        maxRotation: 45,
                        autoSkip: true,
                        maxTicksLimit: 15
                    },
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Premium/Discount (BPS)',
                        color: '#475569',
                        font: {
                            size: 12,
                            weight: '600'
                        }
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 11
                        },
                        callback: (value) => {
                            const sign = value >= 0 ? '+' : '';
                            return `${sign}${value}`;
                        }
                    },
                    grid: {
                        color: (context) => {
                            // Emphasize zero line (NAV)
                            if (context.tick.value === 0) {
                                return 'rgba(100, 116, 139, 0.4)';
                            }
                            return 'rgba(148, 163, 184, 0.15)';
                        },
                        lineWidth: (context) => {
                            if (context.tick.value === 0) {
                                return 2;
                            }
                            return 1;
                        },
                        drawBorder: false
                    }
                }
            }
        };
    }

    /**
     * Format timestamp to readable date
     */
    formatDate(ts) {
        const date = new Date(ts);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    /**
     * Destroy chart
     */
    destroy() {
        if (this.chart) {
            try {
                if (this.chart.options && this.chart.options.animation) {
                    this.chart.options.animation = false;
                }
                this.chart.stop();
                this.chart.destroy();
                console.log('🗑️ Premium/Discount chart destroyed');
            } catch (error) {
                console.warn('⚠️ Premium/Discount chart destroy error:', error);
            }
            this.chart = null;
        }
    }

    /**
     * Update chart with new data
     */
    update(datasets) {
        this.renderChart(datasets);
    }
}

