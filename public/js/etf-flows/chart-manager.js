/**
 * ETF Flows Chart Manager
 * Manages Chart.js operations for ETF flow visualization
 */

import { EtfFlowsUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false;
    }

    /**
     * Render chart with flow data
     */
    renderChart(data) {
        if (this.isRendering) {
            console.warn('⚠️ Chart render already in progress, skipping');
            return;
        }
        
        this.isRendering = true;
        
        try {
            // Cleanup old chart
            this.destroy();

            // ⚡ FIXED: Verify Chart.js loaded
            if (typeof Chart === 'undefined') {
                console.warn('⚠️ Chart.js not loaded, aborting render');
                return;
            }

            // ⚡ FIXED: Enhanced canvas validation
            const canvas = document.getElementById(this.canvasId);
            if (!canvas || !canvas.isConnected) {
                console.warn('⚠️ Canvas not available or not connected to DOM');
                return;
            }

            // ⚡ FIXED: Validate context before rendering
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('⚠️ Cannot get 2D context');
                return;
            }

            // ⚡ FIXED: Clear canvas before rendering
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Prepare data
            const sortedData = [...data].sort((a, b) => a.ts - b.ts);
            
            // Debug: Log sample data to check timestamp format
            if (sortedData.length > 0) {
                console.log('📊 ETF Chart Debug:');
                console.log('- Sample data point:', sortedData[0]);
                console.log('- Timestamp:', sortedData[0].ts);
                console.log('- Date object:', new Date(sortedData[0].ts));
                console.log('- Date string:', new Date(sortedData[0].ts).toLocaleDateString('en-US'));
                console.log('- Total data points:', sortedData.length);
            }
            
            const labels = sortedData.map(d => {
                const timestamp = d.ts;
                const date = new Date(timestamp);
                if (isNaN(date.getTime())) {
                    console.warn('Invalid timestamp:', timestamp);
                    return new Date(); // fallback to current date
                }
                return date;
            });
            const flows = sortedData.map(d => d.flow_usd || 0);

            // Store reference for tooltip callbacks
            this.currentData = sortedData;

            // Create chart with validated context
            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'ETF Flow',
                        data: flows,
                        backgroundColor: flows.map(flow => 
                            flow >= 0 
                                ? 'rgba(34, 197, 94, 0.8)'  // Green for inflow
                                : 'rgba(239, 68, 68, 0.8)'   // Red for outflow
                        ),
                        borderColor: flows.map(flow => 
                            flow >= 0 
                                ? '#22c55e'  // Green border for inflow
                                : '#ef4444'   // Red border for outflow
                        ),
                        borderWidth: 1,
                        borderRadius: 4,
                        hoverBackgroundColor: flows.map(flow => 
                            flow >= 0 
                                ? 'rgba(34, 197, 94, 1)'  // Solid green on hover
                                : 'rgba(239, 68, 68, 1)'   // Solid red on hover
                        ),
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // ⚡ CRITICAL: Disable animations to prevent race conditions

                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.98)',
                            titleColor: '#1e293b',
                            bodyColor: '#334155',
                            borderColor: 'rgba(59, 130, 246, 0.3)',
                            borderWidth: 1,
                            padding: 16,
                            displayColors: true,
                            boxWidth: 8,
                            boxHeight: 8,
                            usePointStyle: true,
                            callbacks: {
                                title: (items) => {
                                    const dataIndex = items[0].dataIndex;
                                    
                                    // Use the original data
                                    if (this.currentData && this.currentData[dataIndex]) {
                                        const originalData = this.currentData[dataIndex];
                                        
                                        // Use the pre-formatted date string (YYYY-MM-DD format)
                                        if (originalData.date) {
                                            const dateStr = originalData.date; // e.g., "2024-01-11"
                                            const date = new Date(dateStr + 'T00:00:00'); // Add time to avoid timezone issues
                                            
                                            if (!isNaN(date.getTime())) {
                                                return date.toLocaleDateString('en-US', {
                                                    weekday: 'short',
                                                    year: 'numeric',
                                                    month: 'short',
                                                    day: 'numeric'
                                                });
                                            }
                                        }
                                        
                                        // Fallback: just return the date string as-is
                                        if (originalData.date) {
                                            return originalData.date;
                                        }
                                    }
                                    
                                    return 'Date unavailable';
                                },
                                label: (context) => {
                                    const value = context.parsed.y;
                                    const absValue = Math.abs(value);
                                    const direction = value >= 0 ? '📈 Inflow' : '📉 Outflow';
                                    return `${direction}: ${EtfFlowsUtils.formatFlow(absValue)}`;
                                },
                                labelColor: (context) => {
                                    const value = context.parsed.y;
                                    return {
                                        borderColor: value >= 0 ? '#22c55e' : '#ef4444',
                                        backgroundColor: value >= 0 ? '#22c55e' : '#ef4444',
                                        borderWidth: 2,
                                        borderRadius: 2,
                                    };
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
                                font: { size: 10, weight: '500' },
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
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Daily Flow (USD)',
                                color: '#475569',
                                font: { size: 12, weight: '600' }
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11 },
                                callback: (value) => EtfFlowsUtils.formatFlow(value)
                            },
                            grid: {
                                color: (context) => {
                                    if (context.tick.value === 0) {
                                        return 'rgba(100, 116, 139, 0.4)'; // Darker line at zero
                                    }
                                    return 'rgba(148, 163, 184, 0.15)';
                                },
                                lineWidth: (context) => {
                                    if (context.tick.value === 0) {
                                        return 2; // Thicker line at zero baseline
                                    }
                                    return 1;
                                },
                                drawBorder: false
                            }
                        }
                    }
                }
            });

            console.log('✅ ETF Flows chart rendered successfully');

        } catch (error) {
            console.error('❌ Chart render error:', error);
            this.chart = null;
        } finally {
            this.isRendering = false;
        }
    }

    /**
     * Destroy chart and cleanup
     * Enhanced destruction to prevent race conditions (copied from Open Interest/Funding Rate)
     */
    destroy() {
        if (this.chart) {
            try {
                // ⚡ FIXED: Stop all animations before destroying
                if (this.chart.options && this.chart.options.animation) {
                    this.chart.options.animation = false;
                }
                
                // ⚡ FIXED: Stop chart updates
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