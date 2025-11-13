/**
 * Sentiment & Flow Chart Manager
 * Handles chart rendering with race condition protection
 * Pattern: Copied from Open Interest (proven working)
 */

export class SentimentFlowChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false;
    }

    /**
     * Render Fear & Greed gauge chart
     */
    renderFearGreedChart(value, history = []) {
        // Race condition protection
        if (this.isRendering) {
            console.warn('⚠️ Chart rendering already in progress, skipping');
            return;
        }

        this.isRendering = true;

        try {
            const canvas = document.getElementById(this.canvasId);
            
            // Validate canvas
            if (!canvas) {
                console.error('Canvas not found:', this.canvasId);
                return;
            }

            if (!canvas.isConnected) {
                console.error('Canvas not connected to DOM');
                return;
            }

            // Get 2D context
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Could not get 2D context');
                return;
            }

            // Destroy existing chart
            if (this.chart) {
                this.destroy();
            }

            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Prepare data - use timestamp for reliable date parsing
            const labels = history.length > 0 
                ? history.map(item => item.timestamp || item.date || new Date(item.date).getTime())
                : [Date.now()];
            
            const data = history.length > 0 
                ? history.map(item => item.value || item)
                : [value];

            // Create new chart
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Fear & Greed Index',
                        data: data,
                        borderColor: this.getFearGreedColor(value),
                        backgroundColor: this.getFearGreedColor(value) + '33',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Disable for race condition prevention
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.98)',
                            titleColor: '#1e293b',
                            bodyColor: '#334155',
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            boxWidth: 8,
                            boxHeight: 8,
                            callbacks: {
                                title: function(context) {
                                    // Format date properly for tooltip
                                    if (!context || !context[0]) return 'Date unavailable';
                                    
                                    const label = context[0].label;
                                    
                                    // Try to parse as date
                                    const date = new Date(label);
                                    
                                    // Check if valid date
                                    if (!isNaN(date.getTime())) {
                                        return date.toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric'
                                        });
                                    }
                                    
                                    // Fallback to original label
                                    return label;
                                },
                                label: function(context) {
                                    const value = context.parsed.y;
                                    let sentiment = '';
                                    if (value >= 80) sentiment = 'Extreme Greed';
                                    else if (value >= 60) sentiment = 'Greed';
                                    else if (value >= 40) sentiment = 'Neutral';
                                    else if (value >= 20) sentiment = 'Fear';
                                    else sentiment = 'Extreme Fear';
                                    
                                    return `Fear & Greed: ${value} (${sentiment})`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value;
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                autoSkip: true,
                                maxTicksLimit: 10,
                                color: '#64748b',
                                font: { size: 10 },
                                callback: function(value, index) {
                                    // Get label for this tick
                                    const label = this.getLabelForValue(value);
                                    
                                    // Try to parse as date
                                    const date = new Date(label);
                                    
                                    // Check if valid date
                                    if (!isNaN(date.getTime())) {
                                        return date.toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric'
                                        });
                                    }
                                    
                                    // Fallback to original label
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            console.log('✅ Fear & Greed chart rendered successfully');
        } catch (error) {
            console.error('Chart render error:', error);
        } finally {
            this.isRendering = false;
        }
    }

    /**
     * Destroy chart
     */
    destroy() {
        if (this.chart) {
            try {
                // Stop animations and updates
                if (this.chart.options) {
                    this.chart.options.animation = false;
                }
                this.chart.stop();
                this.chart.destroy();
                this.chart = null;
                console.log('🗑️ Chart destroyed');
            } catch (error) {
                console.error('Error destroying chart:', error);
                this.chart = null;
            }
        }
    }

    /**
     * Get color based on fear & greed value
     */
    getFearGreedColor(value) {
        if (value >= 80) return '#f43f5e'; // Extreme Greed - red
        if (value >= 60) return '#fb923c'; // Greed - orange
        if (value >= 40) return '#fbbf24'; // Neutral - yellow
        if (value >= 20) return '#a3e635'; // Fear - lime
        return '#22c55e'; // Extreme Fear - green
    }
}

