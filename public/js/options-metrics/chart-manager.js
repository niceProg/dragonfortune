/**
 * Open Interest Chart Manager
 * Handles Chart.js operations - creation, updates, cleanup
 */

import { OpenInterestUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false; // ⚡ FIXED: Prevent concurrent renders
    }

    /**
     * Create or update chart smoothly
     * 
     * Note: Always destroys and recreates chart to avoid Chart.js
     * internal stack overflow issues during updates.
     */
    updateChart(data) {
        this.renderChart(data);
    }

    /**
     * Update existing chart data (no re-render) - SAFE & ROBUST
     */
    updateChartData(data, priceData) {
        if (!this.chart || this.isRendering) {
            console.warn('⚠️ Chart not available or rendering in progress, skipping update');
            return false;
        }

        try {
            const sorted = [...data].sort((a, b) => 
                new Date(a.date) - new Date(b.date)
            );

            const labels = sorted.map(d => d.date);
            const oiValues = sorted.map(d => parseFloat(d.value));

            // ⚡ SAFE: Check if chart still exists before updating
            if (!this.chart || !this.chart.data || !this.chart.data.datasets[0]) {
                console.warn('⚠️ Chart structure invalid, cannot update');
                return false;
            }

            // ⚡ Batch update for better performance
            this.chart.data.labels = labels;
            this.chart.data.datasets[0].data = oiValues;

            // Update price overlay if available
            if (priceData.length > 0 && this.chart.data.datasets[1]) {
                const priceMap = new Map(priceData.map(p => [p.date, p.price]));
                const alignedPrices = labels.map(date => priceMap.get(date) || null);
                this.chart.data.datasets[1].data = alignedPrices;
            }

            // ⚡ SAFE: Ultra-fast update with error handling
            this.chart.update('none');
            
            console.log('⚡ Chart updated safely:', oiValues.length, 'points');
            return true;
        } catch (error) {
            console.error('❌ Chart update error:', error);
            return false;
        }
    }

    /**
     * Full chart render with cleanup
     */
    renderChart(data) {
        // ⚡ FIXED: Prevent concurrent renders
        if (this.isRendering) {
            console.warn('⚠️ Chart render already in progress, skipping');
            return;
        }
        
        this.isRendering = true;
        
        try {
            // Cleanup old chart
            this.destroy();

            // Verify Chart.js loaded
            if (typeof Chart === 'undefined') {
                console.warn('⚠️ Chart.js not loaded, aborting render');
                return; // ⚡ FIXED: Don't retry to prevent infinite loop
            }

            // Get canvas
            const canvas = document.getElementById(this.canvasId);
            if (!canvas || !canvas.isConnected) {
                console.warn('⚠️ Canvas not available');
                return;
            }

            // Clear canvas to prevent memory leaks
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('⚠️ Cannot get 2D context');
                return;
            }
        
        // Clear canvas before rendering
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Prepare data
        const sorted = [...data].sort((a, b) => 
            new Date(a.date) - new Date(b.date)
        );

        const labels = sorted.map(d => d.date);
        const oiValues = sorted.map(d => parseFloat(d.value)); // Open Interest values
        
            // Render line chart only
            this.renderLineChart(sorted, labels, oiValues);
        } catch (error) {
            console.error('❌ Chart render error:', error);
            this.chart = null;
        } finally {
            this.isRendering = false; // ⚡ FIXED: Always reset flag
        }
    }

    /**
     * Render simple line chart (easy to read)
     */
    renderLineChart(sorted, labels, oiValues) {
        const datasets = [
            {
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
            }
        ];

        // Price overlay removed

        console.log('📊 Line chart data prepared:', oiValues.length, 'points');

        const chartOptions = this.getChartOptions(false);
        
        // Update tooltip to match OHLC format (same time format)
        chartOptions.plugins.tooltip = {
            ...chartOptions.plugins.tooltip,
            callbacks: {
                ...chartOptions.plugins.tooltip.callbacks,
                title: (items) => {
                    // Use same format as OHLC chart: yyyy-mm-dd HH:mm
                    const date = new Date(items[0].label);
                    // Match exactly with OHLC format (toLocaleString approach)
                    return date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    }).replace(',', '');
                },
                label: (context) => {
                    const value = context.parsed.y;
                    return `Open Interest: ${OpenInterestUtils.formatOI(value)}`;
                }
            }
        };

        const canvas = document.getElementById(this.canvasId);
        const ctx = canvas.getContext('2d');
        
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: chartOptions,
            plugins: []
        });

        console.log('✅ Line chart rendered successfully');
    }

    /**
     * Render candlestick chart (OHLC visualization)
     * Note: Open Interest doesn't have OHLC data, so this renders as line chart
     */
    renderCandlestickChart(sorted, labels, priceData = []) {
        // Open Interest doesn't have OHLC data, so render as line chart instead
        const oiValues = sorted.map(d => parseFloat(d.value));
        this.renderLineChart(sorted, labels, oiValues, priceData);
        return;
        
        /* Original OHLC code commented out - Open Interest doesn't have OHLC
        // Get canvas (already validated in renderChart)
        const canvas = document.getElementById(this.canvasId);
        const ctx = canvas.getContext('2d');
        // Prepare OHLC data for candlestick
        const candlestickData = sorted.map(d => ({
            open: d.open,
            high: d.high,
            low: d.low,
            close: d.close
        }));

        // Determine candlestick colors: Green if close > open (bullish), Red if close < open (bearish)
        const bodyColors = candlestickData.map(candle => {
            if (candle.close > candle.open) {
                return 'rgba(34, 197, 94, 0.9)';   // Green - Bullish
            } else if (candle.close < candle.open) {
                return 'rgba(239, 68, 68, 0.9)';  // Red - Bearish
            } else {
                return 'rgba(148, 163, 184, 0.7)'; // Gray - Neutral
            }
        });

        const borderColors = candlestickData.map(candle => {
            if (candle.close > candle.open) {
                return 'rgba(34, 197, 94, 1)';     // Solid green border
            } else if (candle.close < candle.open) {
                return 'rgba(239, 68, 68, 1)';     // Solid red border
            } else {
                return 'rgba(148, 163, 184, 1)';   // Gray border
            }
        });

        // Prepare candlestick data structure
        // For Chart.js candlestick, we need custom rendering plugin
        const candlestickDataPoints = candlestickData.map((candle, index) => ({
            x: index,
            o: candle.open,
            h: candle.high,
            l: candle.low,
            c: candle.close,
            isBullish: candle.close >= candle.open,
            color: candle.close >= candle.open 
                ? 'rgba(34, 197, 94, 0.9)' 
                : 'rgba(239, 68, 68, 0.9)',
            borderColor: candle.close >= candle.open
                ? 'rgba(34, 197, 94, 1)'
                : 'rgba(239, 68, 68, 1)'
        }));

        // Store candlestick data for custom plugin rendering
        // Create hidden dataset for Chart.js to position correctly
        const datasets = [
            {
                label: 'Funding Rate',
                data: candlestickData.map(c => c.close), // Reference for positioning
                backgroundColor: 'transparent',
                borderColor: 'transparent',
                borderWidth: 0,
                pointRadius: 0,
                pointHoverRadius: 0,
                yAxisID: 'y',
                // Store full OHLC data for custom candlestick rendering
                _candlestickData: candlestickDataPoints,
                _labels: labels
            }
        ];

        console.log('📊 Candlestick chart data prepared with OHLC:', candlestickDataPoints.length, 'candles');

        // Add price overlay if available (for candlestick)

        if (priceData.length > 0) {
            const priceMap = new Map(priceData.map(p => [p.date, p.price]));
            const alignedPrices = labels.map(date => priceMap.get(date) || null);
            datasets.push({
                label: 'Price',
                data: alignedPrices,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 1,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                yAxisID: 'y1',
                hidden: true
            });
        }

        // Create custom candlestick plugin
        const candlestickPlugin = {
            id: 'candlestick',
            afterDatasetsDraw: (chart) => {
                const { ctx, scales, data } = chart;
                const dataset = data.datasets[0];
                const candlestickData = dataset._candlestickData;
                
                if (!candlestickData || candlestickData.length === 0) return;
                
                const xScale = scales.x;
                const yScale = scales.y;
                
                // Calculate candle width based on category spacing
                const categoryCount = data.labels.length;
                const categoryWidth = categoryCount > 0 ? xScale.width / categoryCount : 20;
                const candleWidth = categoryWidth * 0.6; // 60% of category width
                const wickWidth = 1.5;
                
                ctx.save();
                ctx.lineWidth = wickWidth;
                
                candlestickData.forEach((candle, index) => {
                    // Get X position - for category scale, use index
                    const x = xScale.getPixelForValue(index);
                    
                    // Get Y positions for OHLC
                    const yHigh = yScale.getPixelForValue(candle.h);
                    const yLow = yScale.getPixelForValue(candle.l);
                    const yOpen = yScale.getPixelForValue(candle.o);
                    const yClose = yScale.getPixelForValue(candle.c);
                    
                    // Body: rectangle from min(open,close) to max(open,close)
                    const bodyTop = Math.min(yOpen, yClose);
                    const bodyBottom = Math.max(yOpen, yClose);
                    const bodyHeight = Math.max(bodyBottom - bodyTop, 1); // Min 1px if open==close
                    
                    // Draw high-low wick (vertical line through whole range)
                    ctx.strokeStyle = candle.isBullish 
                        ? 'rgba(34, 197, 94, 0.9)' 
                        : 'rgba(239, 68, 68, 0.9)';
                    ctx.beginPath();
                    ctx.moveTo(x, yHigh);
                    ctx.lineTo(x, yLow);
                    ctx.stroke();
                    
                    // Draw body (rectangle)
                    ctx.fillStyle = candle.color;
                    ctx.strokeStyle = candle.borderColor;
                    ctx.lineWidth = 1;
                    ctx.fillRect(x - candleWidth / 2, bodyTop, candleWidth, bodyHeight);
                    ctx.strokeRect(x - candleWidth / 2, bodyTop, candleWidth, bodyHeight);
                });
                
                ctx.restore();
            }
        };
        
        // Create chart with candlestick plugin
        // Use 'line' type as base (bar not needed, we draw candlesticks manually)
        const chartOptions = this.getChartOptions(priceData.length > 0);
        
        // Store candlestick data in datasets for tooltip access
        datasets[0]._candlestickDataForTooltip = candlestickData;
        
        // Update tooltip for candlestick to show OHLC
        chartOptions.plugins.tooltip = {
            ...chartOptions.plugins.tooltip,
            callbacks: {
                title: (context) => {
                    const index = context[0].dataIndex;
                    const label = labels[index];
                    const date = new Date(label);
                    // Format: yyyy-mm-dd HH:mm
                    return date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    }).replace(',', '');
                },
                label: (context) => {
                    const index = context.dataIndex;
                    const dataset = context.chart.data.datasets[0];
                    const candlestickDataForTooltip = dataset._candlestickDataForTooltip;
                    if (!candlestickDataForTooltip || !candlestickDataForTooltip[index]) return [];
                    
                    const candle = candlestickDataForTooltip[index];
                    return [
                        `Open: ${(candle.open * 100).toFixed(4)}%`,
                        `High: ${(candle.high * 100).toFixed(4)}%`,
                        `Low: ${(candle.low * 100).toFixed(4)}%`,
                        `Close: ${(candle.close * 100).toFixed(4)}%`
                    ];
                },
                labelColor: () => ({
                    borderColor: 'transparent',
                    backgroundColor: 'transparent'
                })
            }
        };
        
        try {
            this.chart = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                options: chartOptions,
                plugins: [candlestickPlugin]
            });
            
            console.log('✅ Candlestick chart rendered successfully');
        } catch (error) {
            console.error('❌ Chart creation error:', error);
            this.chart = null;
        }
        */
    }

    /**
     * Get chart configuration options
     */
    getChartOptions(hasPriceOverlay) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: false, // ⚡ Disable all animations for instant updates
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                // Ensure option objects exist for globally-registered plugins
                clipFallback: {},
                zoom: {
                    pan: { enabled: false },
                    zoom: {
                        wheel: { enabled: false },
                        pinch: { enabled: false },
                        drag: { enabled: false }
                    }
                },
                legend: {
                    display: false  // Hide legend for candlestick chart
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.98)',
                    titleColor: '#1e293b',
                    bodyColor: '#334155',
                    borderColor: 'rgba(59, 130, 246, 0.3)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                    callbacks: {
                        title: (items) => {
                            const date = new Date(items[0].label);
                            
                            // Detect if hourly data (same logic as x-axis)
                            const labels = items[0].chart.data.labels;
                            let isHourlyData = false;
                            if (labels && labels.length > 1) {
                                const dates = labels.map(label => new Date(label));
                                const firstDate = dates[0];
                                const lastDate = dates[dates.length - 1];
                                const timeSpanHours = (lastDate - firstDate) / (1000 * 60 * 60);
                                const avgIntervalHours = timeSpanHours / (dates.length - 1);
                                isHourlyData = avgIntervalHours <= 12;
                            }
                            
                            if (isHourlyData) {
                                // Hourly view - format: yyyy-mm-dd HH:mm
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate());
                                const hours = String(date.getHours()).padStart(2, '0');
                                const minutes = String(date.getMinutes()).padStart(2, '0');
                                return `${year}-${month}-${day} ${hours}:${minutes}`;
                            } else {
                                // Daily view - show date only
                                return date.toLocaleDateString('en-US', {
                                    weekday: 'short',
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric'
                                });
                            }
                        },
                        label: (context) => {
                            // Get OHLC data from candlestick dataset
                            const dataset = context.dataset;
                            const candlestickData = dataset._candlestickData;
                            const dataIndex = context.dataIndex;
                            
                            if (candlestickData && candlestickData[dataIndex]) {
                                const candle = candlestickData[dataIndex];
                                const isBullish = candle.isBullish;
                                
                                return [
                                    `  Open:  ${OpenInterestUtils.formatOI(candle.o)}`,
                                    `  High:  ${OpenInterestUtils.formatOI(candle.h)}`,
                                    `  Low:   ${OpenInterestUtils.formatOI(candle.l)}`,
                                    `  Close: ${OpenInterestUtils.formatOI(candle.c)}`
                                ];
                            }
                            
                            // Fallback to simple value
                            const value = context.parsed.y;
                            return [`  Open Interest: ${OpenInterestUtils.formatOI(value)}`];
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: '#64748b',
                        font: { size: 10 },
                        maxRotation: 45,
                        minRotation: 45,
                        callback: function (value, index) {
                            const labels = this.chart.data.labels;
                            if (!labels || labels.length === 0) return '';
                            
                            // Detect if data is hourly based on intervals
                            const dates = labels.map(label => new Date(label));
                            const firstDate = dates[0];
                            const lastDate = dates[dates.length - 1];
                            
                            // Calculate average interval between data points
                            let isHourlyData = false;
                            if (dates.length > 1) {
                                const timeSpanHours = (lastDate - firstDate) / (1000 * 60 * 60);
                                const avgIntervalHours = timeSpanHours / (dates.length - 1);
                                // If average interval is <= 12 hours, treat as hourly data
                                isHourlyData = avgIntervalHours <= 12;
                            }
                            
                            // Determine spacing based on data volume and type
                            const totalLabels = labels.length;
                            let showEvery;
                            
                            if (isHourlyData) {
                                // For hourly data, show ALL labels if possible
                                // Chart.js will handle overlap automatically with rotation
                                if (totalLabels <= 48) {
                                    // ≤48 hourly points (2 days max) - show all labels
                                    showEvery = 1;
                                } else if (totalLabels <= 96) {
                                    // 49-96 hourly points (3-4 days) - show every 2nd
                                    showEvery = 2;
                                } else if (totalLabels <= 200) {
                                    // 97-200 hourly points (4-8 days) - show every 3rd
                                    showEvery = 3;
                                } else {
                                    // >200 hourly points - dynamic spacing
                                    showEvery = Math.ceil(totalLabels / 40);
                                }
                            } else {
                                // Daily data - use spacing to prevent overcrowding
                                if (totalLabels <= 24) {
                                    // Show all for small datasets
                                    showEvery = 1;
                                } else if (totalLabels <= 100) {
                                    // Show ~20-25 labels
                                    showEvery = Math.ceil(totalLabels / 20);
                                } else {
                                    // Large dataset - show ~25 labels max
                                    showEvery = Math.ceil(totalLabels / 25);
                                }
                            }
                            
                            // Always show first and last label, plus regular intervals
                            if (index === 0 || index === totalLabels - 1 || index % showEvery === 0) {
                                const currentDate = new Date(labels[index]);
                                
                                if (isHourlyData) {
                                    // Hourly view - show date + time: yyyy-mm-dd HH:mm (day without padding)
                                    const year = currentDate.getFullYear();
                                    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
                                    const day = String(currentDate.getDate()); // No padding for day (2025-11-1 not 2025-11-01)
                                    const hours = String(currentDate.getHours()).padStart(2, '0');
                                    const minutes = String(currentDate.getMinutes()).padStart(2, '0');
                                    return `${year}-${month}-${day} ${hours}:${minutes}`;
                                } else {
                                    // Daily view - show date only
                                    return currentDate.toLocaleDateString('en-US', {
                                        month: 'short',
                                        day: 'numeric'
                                    });
                                }
                            }
                            
                            return '';
                        }
                    },
                    grid: {
                        display: true,
                        color: 'rgba(148, 163, 184, 0.15)',
                        drawBorder: false
                    }
                },
                y: {
                    type: 'linear',
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
                        color: (context) => {
                            // Highlight zero line for bar chart
                            if (context.tick.value === 0) {
                                return 'rgba(148, 163, 184, 0.3)';
                            }
                            return 'rgba(148, 163, 184, 0.15)';
                        },
                        lineWidth: (context) => {
                            // Thicker zero line
                            if (context.tick.value === 0) {
                                return 2;
                            }
                            return 1;
                        },
                        drawBorder: false
                    }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    display: hasPriceOverlay,
                    title: {
                        display: true,
                        text: 'BTC Price (USD)',
                        color: '#475569',
                        font: { size: 11, weight: '500' }
                    },
                    ticks: {
                        color: '#f59e0b', // Keep orange for price axis to maintain distinction
                        font: { size: 11 },
                        callback: (value) => '$' + value.toLocaleString('en-US', { maximumFractionDigits: 0 })
                    },
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            }
        };
    }

    /**
     * Destroy chart and cleanup
     */
    destroy() {
        if (this.chart) {
            try {
                // Stop all animations before destroying
                if (this.chart.options && this.chart.options.animation) {
                    this.chart.options.animation = false;
                }
                
                // Stop chart updates
                this.chart.stop();
                
                // Destroy chart
                this.chart.destroy();
                console.log('🗑️ Chart destroyed');
            } catch (error) {
                console.warn('⚠️ Chart destroy error:', error);
            }
            this.chart = null;
        }
    }

    /**
     * Check if chart exists
     */
    exists() {
        return this.chart !== null;
    }
}

