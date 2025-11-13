/**
 * OnChain Chart Optimizer
 * Provides performance optimizations for Chart.js rendering
 */

window.OnChainChartOptimizer = {
    // Default optimization settings
    defaultSettings: {
        // Data decimation for large datasets
        maxDataPoints: 100,
        decimationAlgorithm: 'lttb', // Largest-Triangle-Three-Buckets
        
        // Animation settings
        enableAnimations: true,
        animationDuration: 300,
        
        // Responsive settings
        maintainAspectRatio: false,
        responsive: true,
        
        // Performance settings
        skipNull: true,
        normalized: true,
        parsing: false
    },
    
    // Decimate data using Largest-Triangle-Three-Buckets algorithm
    decimateData(data, maxPoints) {
        if (!data || data.length <= maxPoints) {
            return data;
        }
        
        const bucketSize = (data.length - 2) / (maxPoints - 2);
        const decimated = [data[0]]; // Always keep first point
        
        for (let i = 1; i < maxPoints - 1; i++) {
            const bucketStart = Math.floor(i * bucketSize) + 1;
            const bucketEnd = Math.floor((i + 1) * bucketSize) + 1;
            
            let maxArea = -1;
            let maxAreaPoint = null;
            
            for (let j = bucketStart; j < bucketEnd; j++) {
                if (j >= data.length) break;
                
                // Calculate area of triangle
                const area = Math.abs(
                    (decimated[decimated.length - 1].x - data[j + 1]?.x || 0) * 
                    (data[j].y - decimated[decimated.length - 1].y) -
                    (decimated[decimated.length - 1].x - data[j].x) * 
                    ((data[j + 1]?.y || 0) - decimated[decimated.length - 1].y)
                );
                
                if (area > maxArea) {
                    maxArea = area;
                    maxAreaPoint = data[j];
                }
            }
            
            if (maxAreaPoint) {
                decimated.push(maxAreaPoint);
            }
        }
        
        decimated.push(data[data.length - 1]); // Always keep last point
        return decimated;
    },
    
    // Optimize chart configuration for performance
    optimizeChartConfig(config, dataLength = 0) {
        const optimized = JSON.parse(JSON.stringify(config)); // Deep clone
        
        // Apply performance optimizations based on data size
        if (dataLength > this.defaultSettings.maxDataPoints) {
            // Disable animations for large datasets
            optimized.options = optimized.options || {};
            optimized.options.animation = {
                duration: 0
            };
            optimized.options.hover = {
                animationDuration: 0
            };
            optimized.options.responsiveAnimationDuration = 0;
            
            // Enable data decimation
            optimized.options.plugins = optimized.options.plugins || {};
            optimized.options.plugins.decimation = {
                enabled: true,
                algorithm: this.defaultSettings.decimationAlgorithm,
                samples: this.defaultSettings.maxDataPoints
            };
        }
        
        // Optimize scales for performance
        if (optimized.options.scales) {
            Object.keys(optimized.options.scales).forEach(scaleKey => {
                const scale = optimized.options.scales[scaleKey];
                
                // Optimize tick generation
                scale.ticks = scale.ticks || {};
                scale.ticks.maxTicksLimit = scale.ticks.maxTicksLimit || 10;
                
                // Skip drawing grid lines for better performance
                if (dataLength > 500) {
                    scale.grid = scale.grid || {};
                    scale.grid.display = false;
                }
            });
        }
        
        // Optimize interaction settings
        optimized.options.interaction = optimized.options.interaction || {};
        optimized.options.interaction.intersect = false;
        optimized.options.interaction.mode = 'nearest';
        
        return optimized;
    },
    
    // Create responsive chart with optimizations
    createOptimizedChart(canvas, config, data) {
        if (!canvas || !config) {
            console.warn('Invalid canvas or config for chart creation');
            return null;
        }
        
        const ctx = canvas.getContext('2d');
        const dataLength = this.getDataLength(data);
        
        // Optimize configuration
        const optimizedConfig = this.optimizeChartConfig(config, dataLength);
        
        // Apply responsive settings
        this.applyResponsiveSettings(canvas, optimizedConfig);
        
        // Create chart with optimizations
        const chart = new Chart(ctx, optimizedConfig);
        
        // Add resize observer for better responsiveness
        this.addResizeObserver(canvas, chart);
        
        console.log(`📊 Optimized chart created with ${dataLength} data points`);
        return chart;
    },
    
    // Get total data length from chart data
    getDataLength(data) {
        if (!data || !data.datasets) return 0;
        
        return data.datasets.reduce((total, dataset) => {
            return total + (dataset.data ? dataset.data.length : 0);
        }, 0);
    },
    
    // Apply responsive settings to chart
    applyResponsiveSettings(canvas, config) {
        // Set canvas responsive attributes
        canvas.style.maxWidth = '100%';
        canvas.style.height = 'auto';
        
        // Ensure responsive options are set
        config.options = config.options || {};
        config.options.responsive = true;
        config.options.maintainAspectRatio = false;
        
        // Add responsive breakpoints
        config.options.onResize = (chart, size) => {
            this.handleChartResize(chart, size);
        };
    },
    
    // Handle chart resize events
    handleChartResize(chart, size) {
        const { width } = size;
        
        // Adjust settings based on screen size
        if (width < 768) {
            // Mobile optimizations
            chart.options.plugins.legend.display = false;
            chart.options.scales.x.ticks.maxTicksLimit = 5;
            chart.options.scales.y.ticks.maxTicksLimit = 5;
        } else {
            // Desktop settings
            chart.options.plugins.legend.display = true;
            chart.options.scales.x.ticks.maxTicksLimit = 10;
            chart.options.scales.y.ticks.maxTicksLimit = 8;
        }
        
        chart.update('none');
    },
    
    // Add resize observer for better responsiveness
    addResizeObserver(canvas, chart) {
        if (!window.ResizeObserver) return;
        
        const resizeObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
                const { width, height } = entry.contentRect;
                
                // Debounce resize updates
                clearTimeout(chart._resizeTimeout);
                chart._resizeTimeout = setTimeout(() => {
                    if (chart && !chart.destroyed) {
                        chart.resize(width, height);
                    }
                }, 100);
            }
        });
        
        resizeObserver.observe(canvas.parentElement);
        
        // Store observer for cleanup
        chart._resizeObserver = resizeObserver;
    },
    
    // Update chart data with optimizations
    updateChartData(chart, newData, options = {}) {
        if (!chart || chart.destroyed) return;
        
        const {
            useAnimation = true,
            decimateData = true
        } = options;
        
        // Decimate data if needed
        if (decimateData && newData.datasets) {
            newData.datasets.forEach(dataset => {
                if (dataset.data && dataset.data.length > this.defaultSettings.maxDataPoints) {
                    dataset.data = this.decimateData(dataset.data, this.defaultSettings.maxDataPoints);
                }
            });
        }
        
        // Update chart data
        chart.data = newData;
        
        // Update with or without animation
        chart.update(useAnimation ? 'active' : 'none');
    },
    
    // Destroy chart with cleanup
    destroyChart(chart) {
        if (!chart) return;
        
        // Clean up resize observer
        if (chart._resizeObserver) {
            chart._resizeObserver.disconnect();
            delete chart._resizeObserver;
        }
        
        // Clear resize timeout
        if (chart._resizeTimeout) {
            clearTimeout(chart._resizeTimeout);
            delete chart._resizeTimeout;
        }
        
        // Destroy chart
        chart.destroy();
    },
    
    // Get performance metrics
    getPerformanceMetrics() {
        const charts = Chart.instances;
        const metrics = {
            totalCharts: Object.keys(charts).length,
            memoryUsage: this.estimateMemoryUsage(charts),
            averageDataPoints: 0
        };
        
        let totalDataPoints = 0;
        Object.values(charts).forEach(chart => {
            if (chart.data && chart.data.datasets) {
                chart.data.datasets.forEach(dataset => {
                    totalDataPoints += dataset.data ? dataset.data.length : 0;
                });
            }
        });
        
        metrics.averageDataPoints = metrics.totalCharts > 0 
            ? Math.round(totalDataPoints / metrics.totalCharts) 
            : 0;
        
        return metrics;
    },
    
    // Estimate memory usage (rough calculation)
    estimateMemoryUsage(charts) {
        let totalPoints = 0;
        Object.values(charts).forEach(chart => {
            if (chart.data && chart.data.datasets) {
                chart.data.datasets.forEach(dataset => {
                    totalPoints += dataset.data ? dataset.data.length : 0;
                });
            }
        });
        
        // Rough estimate: ~100 bytes per data point
        return Math.round(totalPoints * 100 / 1024); // KB
    },
    
    // Initialize optimizer
    init() {
        // Set Chart.js global defaults for performance
        if (window.Chart) {
            Chart.defaults.animation.duration = this.defaultSettings.animationDuration;
            Chart.defaults.responsive = this.defaultSettings.responsive;
            Chart.defaults.maintainAspectRatio = this.defaultSettings.maintainAspectRatio;
            
            // Enable data decimation by default
            Chart.defaults.plugins.decimation = {
                enabled: false, // Enable per chart as needed
                algorithm: this.defaultSettings.decimationAlgorithm
            };
        }
        
        console.log('⚡ OnChain Chart Optimizer initialized');
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    OnChainChartOptimizer.init();
});