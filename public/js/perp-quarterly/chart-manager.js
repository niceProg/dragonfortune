/**
 * Perp-Quarterly Spread Chart Manager
 * Handles Chart.js operations - creation, updates, cleanup
 */

import { PerpQuarterlyUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
    }

    /**
     * Create or update chart smoothly
     * Same pattern as funding-rate chart-manager.js
     */
    updateChart(data, chartType = 'line') {
        if (!data || data.length === 0) {
            console.warn('⚠️ No data provided to chart');
            return;
        }
        
        // If data is too small (less than 2 points), always do full render
        // Chart.js can have issues with single-point updates
        if (data.length < 2) {
            console.log('📊 Data too small for update, doing full render');
            this.renderChart(data, chartType);
            return;
        }
        
        // If chart exists, try to update it smoothly
        if (this.chart && this.chart.canvas) {
            try {
                const sorted = [...data].sort((a, b) => 
                    new Date(a.date || a.ts) - new Date(b.date || b.ts)
                );
                const labels = sorted.map(d => d.date || new Date(d.ts).toISOString());
                const spreadValues = sorted.map(d => parseFloat(d.spread || d.value || 0));
                
                // Update chart data
                this.chart.data.labels = labels;
                if (this.chart.data.datasets[0]) {
                    this.chart.data.datasets[0].data = spreadValues;
                }
                
                // Smooth update without animation
                this.chart.update('none');
                console.log('📊 Chart updated smoothly');
                return;
            } catch (error) {
                console.warn('⚠️ Chart update failed, re-rendering:', error);
                // Fall through to full render
            }
        }
        
        // Full render (new chart or update failed)
        this.renderChart(data, chartType);
    }

    /**
     * Full chart render with cleanup
     */
    renderChart(data, chartType = 'line') {
        // Cleanup old chart
        this.destroy();

        // Verify Chart.js loaded
        if (typeof Chart === 'undefined') {
            console.warn('⚠️ Chart.js not loaded, retrying...');
            setTimeout(() => this.renderChart(data, chartType), 100);
            return;
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
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Prepare data
        const sorted = [...data].sort((a, b) => 
            new Date(a.date || a.ts) - new Date(b.date || b.ts)
        );

        const labels = sorted.map(d => d.date || new Date(d.ts).toISOString());
        const spreadValues = sorted.map(d => parseFloat(d.spread || d.value || 0));
        
        // Render based on chart type
        if (chartType === 'line') {
            this.renderLineChart(sorted, labels, spreadValues);
            return;
        } else if (chartType === 'bar') {
            this.renderBarChart(sorted, labels, spreadValues);
            return;
        }
        
        // Default: render line chart
        this.renderLineChart(sorted, labels, spreadValues);
    }

    /**
     * Render dual-axis line chart (Coinglass-style)
     * Left axis: Spread (USD) - green/red based on positive/negative
     * Right axis: Price (USD) - perp_price (yellow) and quarterly_price (blue)
     */
    renderLineChart(sorted, labels, spreadValues) {
        // Extract price data
        const perpPrices = sorted.map(d => parseFloat(d.perpPrice || d.perp_price || 0));
        const quarterlyPrices = sorted.map(d => parseFloat(d.quarterlyPrice || d.quarterly_price || 0));
        
        // Determine spread colors (green for positive, red for negative)
        const spreadColors = spreadValues.map(value => 
            value >= 0 ? 'rgba(34, 197, 94, 1)' : 'rgba(239, 68, 68, 1)'
        );
        
        // Determine average spread to set fill color
        const avgSpread = spreadValues.reduce((a, b) => a + b, 0) / spreadValues.length;
        const fillColor = avgSpread >= 0 
            ? 'rgba(34, 197, 94, 0.1)' 
            : 'rgba(239, 68, 68, 0.1)';
        
        // Create datasets for dual-axis
        const datasets = [
            {
                label: 'Spread (USD)',
                data: spreadValues,
                borderColor: '#3b82f6', // Default, will be overridden by segment
                backgroundColor: (ctx) => {
                    // Dynamic fill color: green for positive, red for negative
                    // This creates the shaded area below the line (like in the image)
                    const index = ctx.dataIndex;
                    const value = spreadValues[index];
                    if (value >= 0) {
                        return 'rgba(34, 197, 94, 0.2)'; // Green fill for positive (darker for visibility)
                    } else {
                        return 'rgba(239, 68, 68, 0.2)'; // Red fill for negative (darker for visibility)
                    }
                },
                borderWidth: 2,
                fill: true, // Enable fill to create shaded area below line
                // Note: Chart.js will fill to the bottom of the chart area by default
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
                yAxisID: 'y', // Left axis for spread
                segment: {
                    borderColor: (ctx) => {
                        // Dynamic color based on spread value (positive = green, negative = red)
                        const value = ctx.p1.raw;
                        return value >= 0 ? 'rgba(34, 197, 94, 1)' : 'rgba(239, 68, 68, 1)';
                    }
                }
            },
            {
                label: 'Perp Price',
                data: perpPrices,
                borderColor: '#f59e0b', // Yellow/gold
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 1.5,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 3,
                yAxisID: 'y1', // Right axis for price
                order: 2 // Render after spread line
            },
            {
                label: 'Quarterly Price',
                data: quarterlyPrices,
                borderColor: '#3b82f6', // Blue
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 1.5,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 3,
                yAxisID: 'y1', // Right axis for price
                order: 3 // Render last
            },
            // Reference line at zero (dotted white line like in the image)
            {
                label: 'Zero Reference',
                data: Array(spreadValues.length).fill(0),
                borderColor: 'rgba(255, 255, 255, 0.6)',
                backgroundColor: 'transparent',
                borderWidth: 1,
                borderDash: [5, 5], // Dotted line
                fill: false,
                pointRadius: 0,
                pointHoverRadius: 0,
                yAxisID: 'y',
                order: 4, // Render on top
                tension: 0
            }
        ];

        console.log('📊 Dual-axis chart data prepared:', {
            spread: spreadValues.length,
            perpPrice: perpPrices.length,
            quarterlyPrice: quarterlyPrices.length
        });

        const chartOptions = this.getChartOptions(true, spreadValues); // Enable dual-axis, pass spread values for padding
        
        // Update tooltip to match format (light theme)
        const originalTooltipFilter = chartOptions.plugins.tooltip?.filter;
        chartOptions.plugins.tooltip = {
            ...chartOptions.plugins.tooltip,
            backgroundColor: 'rgba(255, 255, 255, 0.98)', // Light background
            titleColor: '#1e293b', // Dark text
            bodyColor: '#334155', // Dark text
            borderColor: 'rgba(59, 130, 246, 0.3)', // Light border
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            filter: (tooltipItem) => {
                // Hide zero reference line from tooltip (it's just a visual guide)
                if (tooltipItem.datasetIndex === 3) return false;
                // Apply original filter if exists
                if (originalTooltipFilter) {
                    return originalTooltipFilter(tooltipItem);
                }
                return true;
            },
            callbacks: {
                ...chartOptions.plugins.tooltip.callbacks,
                title: (items) => {
                    const date = new Date(items[0].label);
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${year}-${month}-${day} ${hours}:${minutes}`;
                },
                label: (context) => {
                    // Skip zero reference line
                    if (context.datasetIndex === 3) return [];
                    
                    const index = context.dataIndex;
                    const item = sorted[index];
                    
                    if (context.datasetIndex === 0) {
                        // Spread dataset
                        const spread = context.parsed.y;
                        const spreadBps = item?.spreadBps || 0;
                        return [
                            `Spread: ${PerpQuarterlyUtils.formatSpread(spread)} USD`,
                            `BPS: ${PerpQuarterlyUtils.formatSpreadBPS(spreadBps)}`
                        ];
                    } else if (context.datasetIndex === 1) {
                        // Perp Price dataset
                        const price = context.parsed.y;
                        return `Perp Price: ${PerpQuarterlyUtils.formatPrice(price)}`;
                    } else {
                        // Quarterly Price dataset
                        const price = context.parsed.y;
                        return `Quarterly Price: ${PerpQuarterlyUtils.formatPrice(price)}`;
                    }
                }
            }
        };
        
        // Enable legend
        chartOptions.plugins.legend = {
            display: true,
            position: 'top',
            labels: {
                usePointStyle: true,
                padding: 15,
                font: {
                    size: 11
                },
                generateLabels: (chart) => {
                    return [
                        {
                            text: 'Spread (USD)',
                            fillStyle: spreadValues[spreadValues.length - 1] >= 0 
                                ? 'rgba(34, 197, 94, 1)' 
                                : 'rgba(239, 68, 68, 1)',
                            strokeStyle: spreadValues[spreadValues.length - 1] >= 0 
                                ? 'rgba(34, 197, 94, 1)' 
                                : 'rgba(239, 68, 68, 1)',
                            datasetIndex: 0
                        },
                        {
                            text: 'Perp Price',
                            fillStyle: '#f59e0b',
                            strokeStyle: '#f59e0b',
                            datasetIndex: 1
                        },
                        {
                            text: 'Quarterly Price',
                            fillStyle: '#3b82f6',
                            strokeStyle: '#3b82f6',
                            datasetIndex: 2
                        }
                        // Note: Zero reference line (datasetIndex: 3) is intentionally hidden from legend
                    ];
                },
                filter: (legendItem) => {
                    // Hide zero reference line from legend (it's just a visual guide)
                    return legendItem.datasetIndex !== 3;
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
     * Render bar chart for spread (color-coded positive/negative)
     * Plus line overlay for prices (dual-axis)
     */
    renderBarChart(sorted, labels, spreadValues) {
        // Extract price data
        const perpPrices = sorted.map(d => parseFloat(d.perpPrice || d.perp_price || 0));
        const quarterlyPrices = sorted.map(d => parseFloat(d.quarterlyPrice || d.quarterly_price || 0));
        
        // Create datasets for dual-axis (bar + line)
        const datasets = [
            {
                label: 'Spread (USD)',
                type: 'bar',
                data: spreadValues,
                backgroundColor: spreadValues.map(value => 
                    value >= 0 ? 'rgba(34, 197, 94, 0.8)' : 'rgba(239, 68, 68, 0.8)'
                ),
                borderColor: spreadValues.map(value => 
                    value >= 0 ? 'rgba(34, 197, 94, 1)' : 'rgba(239, 68, 68, 1)'
                ),
                borderWidth: 1,
                borderRadius: 2,
                borderSkipped: false,
                yAxisID: 'y', // Left axis for spread
                order: 3 // Render first
            },
            {
                label: 'Perp Price',
                type: 'line',
                data: perpPrices,
                borderColor: '#f59e0b', // Yellow/gold
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 1.5,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 3,
                yAxisID: 'y1', // Right axis for price
                order: 1 // Render after bars
            },
            {
                label: 'Quarterly Price',
                type: 'line',
                data: quarterlyPrices,
                borderColor: '#3b82f6', // Blue
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 1.5,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 3,
                yAxisID: 'y1', // Right axis for price
                order: 2 // Render last
            }
        ];

        console.log('📊 Bar chart data prepared:', {
            spread: spreadValues.length,
            perpPrice: perpPrices.length,
            quarterlyPrice: quarterlyPrices.length
        });

        const chartOptions = this.getChartOptions(true, spreadValues); // Enable dual-axis, pass spread values for padding
        
        // Update tooltip (light theme)
        chartOptions.plugins.tooltip = {
            ...chartOptions.plugins.tooltip,
            backgroundColor: 'rgba(255, 255, 255, 0.98)', // Light background
            titleColor: '#1e293b', // Dark text
            bodyColor: '#334155', // Dark text
            borderColor: 'rgba(59, 130, 246, 0.3)', // Light border
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            callbacks: {
                ...chartOptions.plugins.tooltip.callbacks,
                title: (items) => {
                    const date = new Date(items[0].label);
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${year}-${month}-${day} ${hours}:${minutes}`;
                },
                label: (context) => {
                    const index = context.dataIndex;
                    const item = sorted[index];
                    
                    if (context.datasetIndex === 0) {
                        // Spread dataset (bar)
                        const spread = context.parsed.y;
                        const spreadBps = item?.spreadBps || 0;
                        return [
                            `Spread: ${PerpQuarterlyUtils.formatSpread(spread)} USD`,
                            `BPS: ${PerpQuarterlyUtils.formatSpreadBPS(spreadBps)}`
                        ];
                    } else if (context.datasetIndex === 1) {
                        // Perp Price dataset
                        const price = context.parsed.y;
                        return `Perp Price: ${PerpQuarterlyUtils.formatPrice(price)}`;
                    } else {
                        // Quarterly Price dataset
                        const price = context.parsed.y;
                        return `Quarterly Price: ${PerpQuarterlyUtils.formatPrice(price)}`;
                    }
                }
            }
        };
        
        // Enable legend
        chartOptions.plugins.legend = {
            display: true,
            position: 'top',
            labels: {
                usePointStyle: true,
                padding: 15,
                font: {
                    size: 11
                },
                generateLabels: (chart) => {
                    return [
                        {
                            text: 'Spread (USD)',
                            fillStyle: spreadValues[spreadValues.length - 1] >= 0 
                                ? 'rgba(34, 197, 94, 1)' 
                                : 'rgba(239, 68, 68, 1)',
                            strokeStyle: spreadValues[spreadValues.length - 1] >= 0 
                                ? 'rgba(34, 197, 94, 1)' 
                                : 'rgba(239, 68, 68, 1)',
                            datasetIndex: 0
                        },
                        {
                            text: 'Perp Price',
                            fillStyle: '#f59e0b',
                            strokeStyle: '#f59e0b',
                            datasetIndex: 1
                        },
                        {
                            text: 'Quarterly Price',
                            fillStyle: '#3b82f6',
                            strokeStyle: '#3b82f6',
                            datasetIndex: 2
                        }
                    ];
                }
            }
        };

        const canvas = document.getElementById(this.canvasId);
        const ctx = canvas.getContext('2d');
        
        // Chart.js 4.x supports mixed chart types by specifying type per dataset
        // Main chart type is 'bar', but datasets can override with 'type' property
        this.chart = new Chart(ctx, {
            type: 'bar', // Base type
            data: {
                labels: labels,
                datasets: datasets // Mixed: bar + line
            },
            options: chartOptions
        });

        console.log('✅ Bar chart (mixed with lines) rendered successfully');
    }

    /**
     * Render candlestick chart (using spread as OHLC)
     */
    renderCandlestickChart(sorted, labels) {
        // For spread data, we'll use spread as the main value
        // Since API doesn't provide OHLC for spread, we'll create a simplified candlestick
        const datasets = [
            {
                label: 'Spread',
                data: sorted.map(d => d.spread || d.value),
                backgroundColor: 'transparent',
                borderColor: 'transparent',
                borderWidth: 0,
                pointRadius: 0,
                pointHoverRadius: 0,
                yAxisID: 'y'
            }
        ];

        console.log('📊 Candlestick chart data prepared:', sorted.length, 'points');

        const chartOptions = this.getChartOptions();
        
        // Custom plugin to draw spread as bars (simplified candlestick)
        const spreadBarPlugin = {
            id: 'spreadBar',
            afterDatasetsDraw: (chart) => {
                const { ctx, scales, data } = chart;
                const xScale = scales.x;
                const yScale = scales.y;
                
                const categoryCount = data.labels.length;
                const categoryWidth = categoryCount > 0 ? xScale.width / categoryCount : 20;
                const barWidth = categoryWidth * 0.6;
                
                ctx.save();
                ctx.lineWidth = 1;
                
                sorted.forEach((item, index) => {
                    const x = xScale.getPixelForValue(index);
                    const spread = item.spread || item.value || 0;
                    const y = yScale.getPixelForValue(spread);
                    const zeroY = yScale.getPixelForValue(0);
                    
                    const height = Math.abs(y - zeroY);
                    const isPositive = spread >= 0;
                    
                    ctx.fillStyle = isPositive 
                        ? 'rgba(34, 197, 94, 0.8)' 
                        : 'rgba(239, 68, 68, 0.8)';
                    
                    ctx.fillRect(
                        x - barWidth / 2,
                        isPositive ? zeroY : y,
                        barWidth,
                        height
                    );
                });
                
                ctx.restore();
            }
        };

        // Update tooltip
        chartOptions.plugins.tooltip = {
            ...chartOptions.plugins.tooltip,
            callbacks: {
                title: (context) => {
                    const date = new Date(context[0].label);
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${year}-${month}-${day} ${hours}:${minutes}`;
                },
                label: (context) => {
                    const index = context.dataIndex;
                    const item = sorted[index];
                    const spread = item.spread || item.value || 0;
                    const spreadBps = item.spreadBps || 0;
                    const perpPrice = item.perpPrice || 0;
                    const quarterlyPrice = item.quarterlyPrice || 0;
                    
                    return [
                        `Spread: ${PerpQuarterlyUtils.formatSpread(spread)} USD`,
                        `BPS: ${PerpQuarterlyUtils.formatSpreadBPS(spreadBps)}`,
                        `Perp Price: ${PerpQuarterlyUtils.formatPrice(perpPrice)}`,
                        `Quarterly Price: ${PerpQuarterlyUtils.formatPrice(quarterlyPrice)}`
                    ];
                }
            }
        };
        
        const canvas = document.getElementById(this.canvasId);
        const ctx = canvas.getContext('2d');
        
        try {
            this.chart = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                options: chartOptions,
                plugins: [spreadBarPlugin]
            });
            
            console.log('✅ Candlestick chart rendered successfully');
        } catch (error) {
            console.error('❌ Chart creation error:', error);
            this.chart = null;
        }
    }

    /**
     * Get chart configuration options
     */
    getChartOptions(hasPriceOverlay = false, dataValues = []) {
        // Calculate min/max from data to add padding/spacing
        let suggestedMin = undefined;
        let suggestedMax = undefined;
        
        if (dataValues && dataValues.length > 0) {
            const minValue = Math.min(...dataValues);
            const maxValue = Math.max(...dataValues);
            const dataRange = maxValue - minValue;
            
            // Calculate padding based on user's requirement:
            // - Negative data: use ~25% space below 0 (total height for negative = 25%)
            // - Positive data: use ~75% space above 0, but highest point at ~55% (20% space above highest point)
            
            if (minValue < 0 && maxValue > 0) {
                // Data spans both positive and negative
                const negativeRange = Math.abs(minValue);
                const positiveRange = maxValue;
                
                // User wants: negative = 25% height, positive = 75% height
                // Highest positive point at ~55% of positive space (which is 75% of total)
                // So highest point should be at: 55% * 75% = 41.25% from bottom, or 58.75% from top
                // But simpler: if maxValue is at 55% of positive range (75%), add 20% space above
                
                // Calculate total chart height:
                // Negative needs: negativeRange / 0.25 = 4 * negativeRange
                // Positive needs: positiveRange / 0.55 * 1.20 = positiveRange * (1.20 / 0.55) = positiveRange * 2.18
                // Total = negative + positive
                
                const negativeSpace = negativeRange / 0.25; // 25% of total for negative
                const positiveSpaceWithPadding = positiveRange / 0.55 * 1.20; // 75% of total, maxValue at 55%, +20% padding
                
                // Total chart spans from negativeSpace (below 0) to positiveSpaceWithPadding (above 0)
                const totalHeight = negativeSpace + positiveSpaceWithPadding;
                
                suggestedMin = -negativeSpace;
                suggestedMax = positiveSpaceWithPadding;
                
            } else if (minValue >= 0) {
                // Only positive data
                // Highest point at ~55% of range, 20% space above
                // If maxValue should be at 55% from bottom: totalSpace = maxValue / 0.55
                // Then add 20% more: suggestedMax = (maxValue / 0.55) * 1.20
                // This makes maxValue at: maxValue / ((maxValue / 0.55) * 1.20) = 0.55 / 1.20 = 45.8% from bottom
                // Actually, let's recalculate: we want maxValue at 55% from bottom
                // So: maxValue = 0.55 * totalPositiveSpace
                // totalPositiveSpace = maxValue / 0.55
                // Add 20% padding above: suggestedMax = totalPositiveSpace * 1.20
                const totalPositiveSpace = maxValue / 0.55; // Space where maxValue is at 55%
                suggestedMax = totalPositiveSpace * 1.20; // Add 20% space above
                
                // Small padding below for visual clarity
                suggestedMin = Math.max(0, minValue - (dataRange * 0.05));
            } else {
                // Only negative data
                // Use ~25% space below minimum (negative data uses 25% of chart height)
                // If minValue should be near top of negative range:
                // totalNegativeSpace = Math.abs(minValue) / 0.75 (if min is at 75% from top)
                // Actually: negative range = 25% of total, so total = Math.abs(minValue) / 0.25
                const totalNegativeSpace = Math.abs(minValue) / 0.25;
                suggestedMin = -totalNegativeSpace;
                suggestedMax = 0; // Cap at 0
            }
            
            console.log('📊 Y-axis padding calculation:', {
                minValue,
                maxValue,
                dataRange,
                suggestedMin,
                suggestedMax,
                topPadding: suggestedMax ? suggestedMax - maxValue : 0,
                topPaddingPercent: suggestedMax ? ((suggestedMax - maxValue) / (suggestedMax - (suggestedMin || minValue))) * 100 : 0
            });
        }
        
        const scales = {
            x: {
                ticks: {
                    color: '#94a3b8',
                    font: { size: 10 },
                    maxRotation: 45,
                    minRotation: 45,
                    callback: function (value, index) {
                        const labels = this.chart.data.labels;
                        if (!labels || labels.length === 0) return '';
                        
                        const dates = labels.map(label => new Date(label));
                        const firstDate = dates[0];
                        const lastDate = dates[dates.length - 1];
                        
                        let isHourlyData = false;
                        if (dates.length > 1) {
                            const timeSpanHours = (lastDate - firstDate) / (1000 * 60 * 60);
                            const avgIntervalHours = timeSpanHours / (dates.length - 1);
                            isHourlyData = avgIntervalHours <= 12;
                        }
                        
                        const totalLabels = labels.length;
                        let showEvery;
                        
                        if (isHourlyData) {
                            if (totalLabels <= 48) {
                                showEvery = 1;
                            } else if (totalLabels <= 96) {
                                showEvery = 2;
                            } else if (totalLabels <= 200) {
                                showEvery = 3;
                            } else {
                                showEvery = Math.ceil(totalLabels / 40);
                            }
                        } else {
                            if (totalLabels <= 24) {
                                showEvery = 1;
                            } else if (totalLabels <= 100) {
                                showEvery = Math.ceil(totalLabels / 20);
                            } else {
                                showEvery = Math.ceil(totalLabels / 25);
                            }
                        }
                        
                        if (index === 0 || index === totalLabels - 1 || index % showEvery === 0) {
                            const currentDate = new Date(labels[index]);
                            
                            if (isHourlyData) {
                                const year = currentDate.getFullYear();
                                const month = String(currentDate.getMonth() + 1).padStart(2, '0');
                                const day = String(currentDate.getDate());
                                const hours = String(currentDate.getHours()).padStart(2, '0');
                                const minutes = String(currentDate.getMinutes()).padStart(2, '0');
                                return `${year}-${month}-${day} ${hours}:${minutes}`;
                            } else {
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
                    color: 'rgba(148, 163, 184, 0.15)', // Lighter grid for light theme
                    borderColor: 'rgba(148, 163, 184, 0.2)'
                }
            },
            y: {
                type: 'linear',
                position: 'left',
                suggestedMin: suggestedMin,
                suggestedMax: suggestedMax,
                ticks: {
                    color: '#64748b', // Darker text for better contrast on light background
                    font: { size: 11 },
                    callback: (value) => PerpQuarterlyUtils.formatSpread(value) + ' USD'
                },
                grid: {
                    color: 'rgba(148, 163, 184, 0.15)', // Lighter grid for light theme
                    borderColor: 'rgba(148, 163, 184, 0.2)'
                },
                title: {
                    display: true,
                    text: 'Spread (USD)',
                    color: '#475569', // Darker text for better readability
                    font: { size: 11, weight: '500' }
                }
            }
        };
        
        // Add right Y-axis for price if overlay enabled
        if (hasPriceOverlay) {
            scales.y1 = {
                type: 'linear',
                position: 'right',
                ticks: {
                    color: '#64748b', // Darker text for better contrast on light background
                    font: { size: 11 },
                    callback: (value) => PerpQuarterlyUtils.formatPrice(value)
                },
                grid: {
                    display: false // Don't show grid for right axis to avoid clutter
                },
                title: {
                    display: true,
                    text: 'Price (USD)',
                    color: '#475569', // Darker text for better readability
                    font: { size: 11, weight: '500' }
                }
            };
        }
        
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
                    display: false // Will be set in renderLineChart
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.98)', // Light background for tooltip
                    titleColor: '#1e293b', // Dark text for light theme
                    bodyColor: '#334155', // Dark text for light theme
                    borderColor: 'rgba(59, 130, 246, 0.3)', // Light border
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                    filter: (tooltipItem) => {
                        // Hide zero reference line from tooltip (will be overridden in renderLineChart if needed)
                        return true; // Default: show all
                    }
                }
            },
            scales: scales
        };
    }

    /**
     * Destroy chart instance
     */
    destroy() {
        if (this.chart) {
            try {
                this.chart.stop();
                this.chart.destroy();
            } catch (error) {
                console.warn('⚠️ Error destroying chart:', error);
            }
            this.chart = null;
            console.log('🗑️ Chart destroyed');
        }
    }
}

