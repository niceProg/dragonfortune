/**
 * Basis & Term Structure Chart Manager
 * Handles Chart.js operations - creation, updates, cleanup
 * 
 * Blueprint: Open Interest Chart Manager (proven stable)
 * COPIED EXACT IMPLEMENTATION for race condition prevention
 */

import { BasisUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false; // ⚡ Prevent concurrent renders
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
            const openBasisValues = sorted.map(d => parseFloat(d.open_basis || 0));
            const closeBasisValues = sorted.map(d => parseFloat(d.close_basis || 0));

            // ⚡ SAFE: Check if chart still exists before updating
            if (!this.chart || !this.chart.data || !this.chart.data.datasets[0]) {
                console.warn('⚠️ Chart structure invalid, cannot update');
                return false;
            }

            // ⚡ Batch update for better performance
            this.chart.data.labels = labels;
            this.chart.data.datasets[0].data = openBasisValues;
            this.chart.data.datasets[1].data = closeBasisValues;

            // ⚡ SAFE: Ultra-fast update with error handling
            this.chart.update('none');
            
            console.log('⚡ Chart updated safely:', closeBasisValues.length, 'points');
            return true;
        } catch (error) {
            console.error('❌ Chart update error:', error);
            return false;
        }
    }

    /**
     * Full chart render with cleanup - COPIED FROM OPEN INTEREST
     */
    renderChart(data) {
        // ⚡ FIXED: Prevent concurrent renders (SAME AS OPEN INTEREST)
        if (this.isRendering) {
            console.warn('⚠️ Chart render already in progress, skipping');
            return;
        }
        
        this.isRendering = true;
        
        try {
            // Cleanup old chart
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
            const openBasisValues = sorted.map(d => parseFloat(d.open_basis || 0));
            const closeBasisValues = sorted.map(d => parseFloat(d.close_basis || 0));

            console.log('📊 Basis chart data prepared:', closeBasisValues.length, 'points');

            // Render line chart with 2 lines
            this.renderLineChart(ctx, labels, openBasisValues, closeBasisValues);

        } catch (error) {
            console.error('❌ Chart render error:', error);
            this.chart = null;
        } finally {
            this.isRendering = false; // ⚡ FIXED: Always reset flag (SAME AS OPEN INTEREST)
        }
    }

    /**
     * Render line chart with Open & Close Basis
     */
    renderLineChart(ctx, labels, openBasisValues, closeBasisValues) {
        const datasets = [
            {
                label: 'Open Basis',
                data: openBasisValues,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
                yAxisID: 'y'
            },
            {
                label: 'Close Basis',
                data: closeBasisValues,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
                yAxisID: 'y'
            }
        ];

        console.log('📊 Line chart data prepared:', closeBasisValues.length, 'points');

        const chartOptions = this.getChartOptions();

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
     * Get chart configuration options - COPIED FROM OPEN INTEREST
     */
    getChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: false, // ⚡ CRITICAL: Disable all animations (SAME AS OPEN INTEREST)
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15
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
                    boxWidth: 8,
                    boxHeight: 8,
                    callbacks: {
                        title: (items) => {
                            // ⚡ FIXED: Use parsed.x (timestamp) instead of label
                            const timestamp = items[0].parsed.x;
                            const date = new Date(timestamp);
                            
                            // Validate date
                            if (isNaN(date.getTime())) {
                                return 'Invalid Date';
                            }
                            
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
                            const label = context.dataset.label;
                            const structure = BasisUtils.getMarketStructure(value);
                            return [
                                `${label}: ${BasisUtils.formatBasis(value)}`,
                                `Structure: ${structure}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        displayFormats: {
                            hour: 'MMM dd HH:mm',
                            day: 'MMM dd',
                            week: 'MMM dd',
                            month: 'MMM yyyy'
                        }
                    },
                    ticks: {
                        color: '#64748b',
                        font: { size: 10 },
                        maxRotation: 45,
                        minRotation: 45
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
                        text: 'Basis (%)',
                        color: '#475569',
                        font: { size: 11, weight: '500' }
                    },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11 },
                        callback: (value) => BasisUtils.formatBasis(value)
                    },
                    grid: {
                        color: (context) => {
                            // Highlight zero line (contango/backwardation boundary)
                            if (Math.abs(context.tick.value) < 0.0001) {
                                return 'rgba(148, 163, 184, 0.5)';
                            }
                            return 'rgba(148, 163, 184, 0.15)';
                        },
                        lineWidth: (context) => {
                            // Thicker line at zero
                            if (Math.abs(context.tick.value) < 0.0001) {
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
     * Destroy chart and cleanup - COPIED FROM OPEN INTEREST
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
