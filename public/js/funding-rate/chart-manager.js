/**
 * Funding Rate Chart Manager
 * Handles Chart.js operations - creation, updates, cleanup
 */

import { FundingRateUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false;
    }

    /**
     * Create or update chart smoothly
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
            const fundingValues = sorted.map(d => parseFloat(d.value));

            // Check if chart still exists before updating
            if (!this.chart || !this.chart.data || !this.chart.data.datasets[0]) {
                console.warn('⚠️ Chart structure invalid, cannot update');
                return false;
            }

            // Batch update for better performance
            this.chart.data.labels = labels;
            this.chart.data.datasets[0].data = fundingValues;

            // Update chart
            this.chart.update('none'); // No animation for performance
            console.log('✅ Chart data updated successfully');
            return true;

        } catch (error) {
            console.error('❌ Error updating chart data:', error);
            return false;
        }
    }

    /**
     * Render chart with data - COPIED FROM OPEN INTEREST FOR ROBUSTNESS
     */
    renderChart(data, priceData = [], chartType = 'line') {
        // ⚡ FIXED: Prevent concurrent renders (SAME AS OPEN INTEREST)
        if (this.isRendering) {
            console.warn('⚠️ Chart rendering already in progress, skipping');
            return;
        }

        this.isRendering = true;

        try {
            // Always destroy existing chart first
            this.destroy();

            // ⚡ FIXED: Verify Chart.js loaded (SAME AS OPEN INTEREST)
            if (typeof Chart === 'undefined') {
                console.warn('⚠️ Chart.js not loaded, aborting render');
                this.isRendering = false;
                return;
            }

            // ⚡ FIXED: Enhanced canvas validation (SAME AS OPEN INTEREST)
            const canvas = document.getElementById(this.canvasId);
            if (!canvas || !canvas.isConnected) {
                console.warn('⚠️ Canvas not available or not connected to DOM');
                this.isRendering = false;
                return;
            }

            // ⚡ FIXED: Validate context (SAME AS OPEN INTEREST)
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('⚠️ Cannot get 2D context');
                this.isRendering = false;
                return;
            }

            // ⚡ FIXED: Clear canvas before rendering (SAME AS OPEN INTEREST)
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Prepare data
            const sorted = [...data].sort((a, b) => 
                new Date(a.date) - new Date(b.date)
            );

            const labels = sorted.map(d => new Date(d.date));
            const fundingValues = sorted.map(d => parseFloat(d.value));

            console.log('📊 Funding rate chart data prepared:', fundingValues.length, 'points');

            // Create chart
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Funding Rate',
                        data: fundingValues,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.1,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // ⚡ CRITICAL: Disable all animations (SAME AS OPEN INTEREST)
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed.y;
                                    return `Funding Rate: ${value.toFixed(4)}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                displayFormats: {
                                    minute: 'HH:mm',
                                    hour: 'MMM dd HH:mm',
                                    day: 'MMM dd',
                                    week: 'MMM dd',
                                    month: 'MMM yyyy'
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(4) + '%';
                                }
                            }
                        }
                    }
                }
            });

            console.log('✅ Funding rate chart rendered successfully');

        } catch (error) {
            console.error('❌ Error rendering funding rate chart:', error);
            this.chart = null;
        } finally {
            this.isRendering = false; // ⚡ FIXED: Always reset flag (SAME AS OPEN INTEREST)
        }
    }

    /**
     * Destroy chart instance - COPIED FROM OPEN INTEREST FOR ROBUSTNESS
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
}