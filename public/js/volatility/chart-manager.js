/**
 * Volatility Chart Manager
 * Manages Chart.js operations for OHLC/Candlestick visualization
 * Blueprint: ETF Flows Chart Manager (with race condition protection)
 */

export class VolatilityChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false;
    }

    /**
     * Render OHLC candlestick chart with price data
     * 
     * @param {Array} data - OHLC data array
     * @param {Object} options - Chart configuration options
     */
    renderChart(data, options = {}) {
        if (this.isRendering) {
            console.warn('⚠️ Chart render already in progress, skipping');
            return;
        }
        
        this.isRendering = true;
        
        try {
            // Cleanup old chart
            this.destroy();

            // ⚡ Verify Chart.js loaded
            if (typeof Chart === 'undefined') {
                console.warn('⚠️ Chart.js not loaded, aborting render');
                return;
            }

            // ⚡ Enhanced canvas validation
            const canvas = document.getElementById(this.canvasId);
            if (!canvas || !canvas.isConnected) {
                console.warn('⚠️ Canvas not available or not connected to DOM');
                return;
            }

            // ⚡ Validate context before rendering
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('⚠️ Cannot get 2D context');
                return;
            }

            // ⚡ Clear canvas before rendering
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Prepare data
            const sortedData = [...data].sort((a, b) => a.time - b.time);
            
            // Debug: Log sample data
            if (sortedData.length > 0) {
                console.log('📊 Volatility Chart Debug:');
                console.log('- Sample data point:', sortedData[0]);
                console.log('- Timestamp:', sortedData[0].time);
                console.log('- Date:', new Date(sortedData[0].time));
                console.log('- Total data points:', sortedData.length);
            }
            
            // Transform data for line chart (Close prices)
            const labels = sortedData.map(d => d.time);
            const closePrices = sortedData.map(d => d.close);
            const highPrices = sortedData.map(d => d.high);
            const lowPrices = sortedData.map(d => d.low);

            // Store reference for tooltip callbacks
            this.currentData = sortedData;

            // Create line chart with high/low shading
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Close Price',
                        data: closePrices,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.1,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#3b82f6',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // ⚡ Disabled for race condition stability
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
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: '#334155',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                title: (items) => {
                                    if (items.length === 0) return 'Date unavailable';
                                    const dataIndex = items[0].dataIndex;
                                    const dataPoint = this.currentData[dataIndex];
                                    if (!dataPoint) return 'Date unavailable';
                                    
                                    const date = new Date(dataPoint.time);
                                    return date.toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                        hour: dataPoint.time % 86400000 !== 0 ? '2-digit' : undefined,
                                        minute: dataPoint.time % 86400000 !== 0 ? '2-digit' : undefined
                                    });
                                },
                                label: (context) => {
                                    const dataIndex = context.dataIndex;
                                    const dataPoint = this.currentData[dataIndex];
                                    if (!dataPoint) return '';
                                    
                                    const lines = [
                                        `Open:  $${this.formatPrice(dataPoint.open)}`,
                                        `High:  $${this.formatPrice(dataPoint.high)}`,
                                        `Low:   $${this.formatPrice(dataPoint.low)}`,
                                        `Close: $${this.formatPrice(dataPoint.close)}`,
                                    ];
                                    
                                    if (dataPoint.volume_usd && dataPoint.volume_usd > 0) {
                                        lines.push(`Volume: $${this.formatVolume(dataPoint.volume_usd)}`);
                                    }
                                    
                                    // Calculate change
                                    const change = dataPoint.close - dataPoint.open;
                                    const changePercent = dataPoint.open > 0 ? (change / dataPoint.open) * 100 : 0;
                                    const changeText = change >= 0 ? `+$${this.formatPrice(Math.abs(change))}` : `-$${this.formatPrice(Math.abs(change))}`;
                                    const changePercentText = changePercent >= 0 ? `+${changePercent.toFixed(2)}%` : `${changePercent.toFixed(2)}%`;
                                    
                                    lines.push(`Change: ${changeText} (${changePercentText})`);
                                    
                                    return lines;
                                }
                            }
                        },
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: this.getTimeUnit(options.interval),
                                displayFormats: this.getDisplayFormats(options.interval)
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    size: 10,
                                    weight: '500'
                                },
                                maxRotation: 45,
                                autoSkip: true,
                                maxTicksLimit: 20
                            },
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        },
                        y: {
                            type: 'linear',
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Price (USD)',
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
                                callback: (value) => '$' + this.formatPrice(value)
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.15)',
                                lineWidth: 1,
                                drawBorder: false
                            }
                        }
                    }
                }
            });

            console.log('✅ Volatility chart rendered successfully');

        } catch (error) {
            console.error('Chart render error:', error);
        } finally {
            this.isRendering = false;
        }
    }

    /**
     * Enhanced destruction to prevent race conditions
     */
    destroy() {
        if (this.chart) {
            try {
                // ⚡ Stop animations before destroying
                if (this.chart.options && this.chart.options.animation) {
                    this.chart.options.animation = false;
                }
                
                // ⚡ Stop chart updates
                this.chart.stop();
                
                this.chart.destroy();
                console.log('🗑️ Chart destroyed');
            } catch (error) {
                console.warn('⚠️ Chart destroy error:', error);
            }
            this.chart = null;
        }
    }

    /**
     * Format price for display
     */
    formatPrice(value) {
        if (value >= 1000) {
            return value.toLocaleString('en-US', { maximumFractionDigits: 2 });
        } else if (value >= 1) {
            return value.toFixed(2);
        } else {
            return value.toFixed(4);
        }
    }

    /**
     * Format volume for display
     */
    formatVolume(value) {
        if (value >= 1e9) {
            return (value / 1e9).toFixed(2) + 'B';
        } else if (value >= 1e6) {
            return (value / 1e6).toFixed(2) + 'M';
        } else if (value >= 1e3) {
            return (value / 1e3).toFixed(2) + 'K';
        }
        return value.toFixed(0);
    }

    /**
     * Get time unit for x-axis based on interval
     */
    getTimeUnit(interval) {
        if (!interval) return 'hour';
        
        if (['1m', '3m', '5m', '15m', '30m'].includes(interval)) {
            return 'minute';
        } else if (['1h', '4h', '6h', '8h', '12h'].includes(interval)) {
            return 'hour';
        } else if (interval === '1d') {
            return 'day';
        } else if (interval === '1w') {
            return 'week';
        }
        
        return 'hour';
    }

    /**
     * Get display formats for x-axis based on interval
     */
    getDisplayFormats(interval) {
        if (!interval) {
            return {
                minute: 'HH:mm',
                hour: 'MMM dd HH:mm',
                day: 'MMM dd',
                week: 'MMM dd'
            };
        }
        
        if (['1m', '3m', '5m'].includes(interval)) {
            return {
                minute: 'HH:mm'
            };
        } else if (['15m', '30m', '1h'].includes(interval)) {
            return {
                minute: 'MMM dd HH:mm',
                hour: 'MMM dd HH:mm'
            };
        } else if (['4h', '6h', '8h', '12h'].includes(interval)) {
            return {
                hour: 'MMM dd HH:mm'
            };
        } else if (interval === '1d') {
            return {
                day: 'MMM dd'
            };
        } else if (interval === '1w') {
            return {
                week: 'MMM dd'
            };
        }
        
        return {
            minute: 'HH:mm',
            hour: 'MMM dd HH:mm',
            day: 'MMM dd',
            week: 'MMM dd'
        };
    }
}

