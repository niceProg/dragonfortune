/**
 * Exchange Inflow CDD Chart Manager
 * Copied from Open Interest - proven to work!
 */

import { CDDUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false; // ⚡ Prevent concurrent renders
    }

    /**
     * Update existing chart data (no re-render) - SAFE & ROBUST
     */
    updateChartData(data) {
        if (!this.chart || this.isRendering) {
            console.warn('⚠️ Chart not available or rendering in progress, skipping update');
            return false;
        }

        try {
            const sorted = [...data].sort((a, b) => 
                new Date(a.date) - new Date(b.date)
            );

            const labels = sorted.map(d => d.date);
            const cddValues = sorted.map(d => parseFloat(d.value));

            // ⚡ SAFE: Check if chart still exists before updating
            if (!this.chart || !this.chart.data || !this.chart.data.datasets[0]) {
                console.warn('⚠️ Chart structure invalid, cannot update');
                return false;
            }

            // ⚡ Batch update for better performance
            this.chart.data.labels = labels;
            this.chart.data.datasets[0].data = cddValues;

            // ⚡ SAFE: Ultra-fast update with error handling
            this.chart.update('none');
            
            console.log('⚡ Chart updated safely:', cddValues.length, 'points');
            return true;
        } catch (error) {
            console.error('❌ Chart update error:', error);
            return false;
        }
    }

    /**
     * Full chart render with cleanup
     */
    renderChart(data, priceData = []) {
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
        
        // Clear canvas before rendering
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Prepare data
        const sorted = [...data].sort((a, b) => 
            new Date(a.date) - new Date(b.date)
        );

        const labels = sorted.map(d => d.date);
        const cddValues = sorted.map(d => parseFloat(d.value));
        
            // Render line chart with price overlay
            this.renderLineChart(sorted, labels, cddValues, priceData);
        } catch (error) {
            console.error('❌ Chart render error:', error);
            this.chart = null;
        } finally {
            this.isRendering = false; // ⚡ FIXED: Always reset flag
        }
    }

    /**
     * Render simple line chart with BTC price overlay (dual Y-axis)
     */
    renderLineChart(sorted, labels, cddValues, priceData = []) {
        const datasets = [
            {
                label: 'Exchange Inflow CDD',
                data: cddValues,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
                yAxisID: 'y',
                order: 2
            }
        ];

        // Add BTC price overlay if data is available
        if (priceData && priceData.length > 0) {
            // Create a map of prices by date for easy lookup
            const priceMap = {};
            priceData.forEach(p => {
                priceMap[p.date] = p.price;
            });

            // Map prices to match CDD dates
            const priceValues = labels.map(date => priceMap[date] || null);

            datasets.push({
                label: 'Bitcoin Price (USD)',
                data: priceValues,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.05)',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
                yAxisID: 'y1',
                order: 1
            });

            console.log('💰 Price overlay added:', priceValues.filter(p => p !== null).length, 'points');
        }

        console.log('📊 Line chart data prepared:', cddValues.length, 'CDD points');

        const chartOptions = this.getChartOptions(priceData && priceData.length > 0);
        
        // Update tooltip
        chartOptions.plugins.tooltip = {
            ...chartOptions.plugins.tooltip,
            callbacks: {
                ...chartOptions.plugins.tooltip.callbacks,
                title: (items) => {
                    const date = new Date(items[0].label);
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
                    if (context.dataset.yAxisID === 'y1') {
                        // Price
                        return `BTC: $${value ? value.toLocaleString() : 'N/A'}`;
                    } else {
                        // CDD
                        return `CDD: ${CDDUtils.formatCDD(value)}`;
                    }
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

        console.log('✅ Line chart rendered successfully' + (priceData && priceData.length > 0 ? ' with price overlay' : ''));
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
                    display: false
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
                    callbacks: {
                        title: (items) => {
                            const date = new Date(items[0].label);
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
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate());
                                const hours = String(date.getHours()).padStart(2, '0');
                                const minutes = String(date.getMinutes()).padStart(2, '0');
                                return `${year}-${month}-${day} ${hours}:${minutes}`;
                            } else {
                                return date.toLocaleDateString('en-US', {
                                    weekday: 'short',
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric'
                                });
                            }
                        },
                        label: (context) => {
                            const value = context.parsed.y;
                            return [`  CDD: ${CDDUtils.formatCDD(value)}`];
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
                        color: 'rgba(148, 163, 184, 0.15)',
                        drawBorder: false
                    }
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Exchange Inflow CDD',
                        color: '#3b82f6',
                        font: { size: 11, weight: '500' }
                    },
                    ticks: {
                        color: '#3b82f6',
                        font: { size: 11 },
                        callback: (value) => CDDUtils.formatCDD(value)
                    },
                    grid: {
                        color: (context) => {
                            if (context.tick.value === 0) {
                                return 'rgba(148, 163, 184, 0.3)';
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
                },
                ...(hasPriceOverlay ? {
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Bitcoin Price (USD)',
                            color: '#f59e0b',
                            font: { size: 11, weight: '500' }
                        },
                        ticks: {
                            color: '#f59e0b',
                            font: { size: 11 },
                            callback: (value) => '$' + value.toLocaleString()
                        },
                        grid: {
                            display: false, // Hide grid for secondary axis
                            drawBorder: false
                        }
                    }
                } : {})
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

    /**
     * Render Z-Score Distribution Bar Chart
     */
    renderZScoreChart(data, zScore) {
        const canvas = document.getElementById('zscore-distribution-chart');
        if (!canvas) {
            console.warn('⚠️ Z-Score chart canvas not found');
            return null;
        }

        // Destroy existing chart if exists
        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        // Calculate Z-Scores for all data points
        const values = data.map(d => parseFloat(d.value));
        const avg = values.reduce((a, b) => a + b, 0) / values.length;
        const variance = values.reduce((sum, val) => sum + Math.pow(val - avg, 2), 0) / values.length;
        const stdDev = Math.sqrt(variance);

        // Count events in different Z-Score ranges
        const zScores = values.map(val => (val - avg) / stdDev);
        
        const bins = {
            extreme_low: zScores.filter(z => z < -3).length,
            high_low: zScores.filter(z => z >= -3 && z < -2).length,
            normal_low: zScores.filter(z => z >= -2 && z < -1).length,
            normal: zScores.filter(z => z >= -1 && z <= 1).length,
            normal_high: zScores.filter(z => z > 1 && z <= 2).length,
            high: zScores.filter(z => z > 2 && z <= 3).length,
            extreme: zScores.filter(z => z > 3).length
        };

        const chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: ['< -3σ', '-3σ to -2σ', '-2σ to -1σ', '-1σ to 1σ', '1σ to 2σ', '2σ to 3σ', '> 3σ'],
                datasets: [{
                    label: 'Frekuensi Event',
                    data: [
                        bins.extreme_low,
                        bins.high_low,
                        bins.normal_low,
                        bins.normal,
                        bins.normal_high,
                        bins.high,
                        bins.extreme
                    ],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',   // extreme low (red)
                        'rgba(251, 146, 60, 0.8)',  // high low (orange)
                        'rgba(250, 204, 21, 0.8)',  // normal low (yellow)
                        'rgba(34, 197, 94, 0.8)',   // normal (green)
                        'rgba(250, 204, 21, 0.8)',  // normal high (yellow)
                        'rgba(251, 146, 60, 0.8)',  // high (orange)
                        'rgba(239, 68, 68, 0.8)'    // extreme (red)
                    ],
                    borderColor: [
                        'rgb(239, 68, 68)',
                        'rgb(251, 146, 60)',
                        'rgb(250, 204, 21)',
                        'rgb(34, 197, 94)',
                        'rgb(250, 204, 21)',
                        'rgb(251, 146, 60)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y} event (${((context.parsed.y / data.length) * 100).toFixed(1)}%)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Event'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Z-Score Range'
                        }
                    }
                }
            }
        });

        console.log('📊 Z-Score distribution chart rendered');
        return chart;
    }

    /**
     * Render Moving Average Trend Line Chart
     */
    renderMAChart(data, ma7Data, ma30Data) {
        const canvas = document.getElementById('ma-trend-chart');
        if (!canvas) {
            console.warn('⚠️ MA chart canvas not found');
            return null;
        }

        // Destroy existing chart if exists
        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        // Sort data by date
        const sorted = [...data].sort((a, b) => new Date(a.date) - new Date(b.date));
        const labels = sorted.map(d => d.date);
        const cddValues = sorted.map(d => parseFloat(d.value));

        // Calculate MA7 and MA30 for each point
        const ma7Values = [];
        const ma30Values = [];

        for (let i = 0; i < cddValues.length; i++) {
            // MA7
            if (i >= 6) {
                const slice = cddValues.slice(i - 6, i + 1);
                ma7Values.push(slice.reduce((a, b) => a + b, 0) / 7);
            } else {
                ma7Values.push(null);
            }

            // MA30
            if (i >= 29) {
                const slice = cddValues.slice(i - 29, i + 1);
                ma30Values.push(slice.reduce((a, b) => a + b, 0) / 30);
            } else {
                ma30Values.push(null);
            }
        }

        const chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'MA 7 Hari',
                        data: ma7Values,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                        pointRadius: 0,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'MA 30 Hari',
                        data: ma30Values,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                        pointRadius: 0,
                        pointHoverRadius: 5
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
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = CDDUtils.formatCDD(context.parsed.y);
                                return `${label}: ${value}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return CDDUtils.formatCDD(value);
                            }
                        },
                        title: {
                            display: true,
                            text: 'CDD Value'
                        }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: 8,
                            autoSkip: true
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });

        console.log('📈 MA trend chart rendered');
        return chart;
    }
}

