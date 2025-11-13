/**
 * Liquidations Heatmap Chart Manager (Model 3)
 * Blueprint: Open Interest Chart Manager (proven stable)
 * 
 * Renders Coinglass-style liquidation heatmap
 */

import { LiquidationsUtils } from './utils.js';

export class ChartManager {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.chart = null;
        this.isRendering = false; // Prevent concurrent renders
        
        // Interactive state (default 0.2 for colorful display)
        this.threshold = 0.2;
        this.zoomLevel = 1;
        this.panX = 0;
        this.panY = 0;
        
        // Cached data for re-rendering
        this.cachedYAxis = null;
        this.cachedHeatmapData = null;
        this.cachedPriceData = null;
        this.cachedMaxLiq = null;
    }

    /**
     * Create or update heatmap chart
     */
    updateChart(data) {
        this.renderChart(data);
    }

    /**
     * Full chart render with cleanup
     */
    renderChart(data) {
        // Prevent concurrent renders
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
                this.isRendering = false;
                return;
            }

            // Enhanced canvas validation
            const canvas = document.getElementById(this.canvasId);
            if (!canvas || !canvas.isConnected) {
                console.warn('⚠️ Canvas not available or not connected to DOM');
                this.isRendering = false;
                return;
            }

            // Validate context
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('⚠️ Cannot get 2D context');
                this.isRendering = false;
                return;
            }

            // Clear canvas before rendering
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Parse heatmap data
            const parsed = LiquidationsUtils.parseHeatmapData(data);
            if (!parsed) {
                console.warn('⚠️ Invalid heatmap data');
                this.isRendering = false;
                return;
            }

            console.log('📊 Heatmap data prepared:', parsed.heatmapData.length, 'points');

            // Render heatmap
            this.renderHeatmap(ctx, parsed);

        } catch (error) {
            console.error('❌ Chart render error:', error);
            this.chart = null;
        } finally {
            this.isRendering = false;
        }
    }

    /**
     * Render heatmap using custom canvas rendering (pixel-perfect like Coinglass)
     */
    renderHeatmap(ctx, parsed) {
        const { yAxis, heatmapData, priceData } = parsed;

        // Use custom canvas rendering for pixel-perfect heatmap
        this.renderCustomHeatmap(ctx, yAxis, heatmapData, priceData);

        console.log('✅ Heatmap rendered successfully');
    }

    /**
     * Custom canvas rendering for pixel-perfect heatmap
     */
    renderCustomHeatmap(ctx, yAxis, heatmapData, priceData) {
        // Cache data for re-rendering
        this.cachedYAxis = yAxis;
        this.cachedHeatmapData = heatmapData;
        this.cachedPriceData = priceData;
        
        // Find max without spread operator (avoid stack overflow)
        let maxLiq = 0;
        for (let i = 0; i < heatmapData.length; i++) {
            const val = heatmapData[i][2];
            if (val > maxLiq) maxLiq = val;
        }
        this.cachedMaxLiq = maxLiq;

        this.drawHeatmap(ctx);
    }

    /**
     * Draw heatmap with current zoom/pan/threshold settings
     */
    drawHeatmap(ctx) {
        const canvas = ctx.canvas;
        const width = canvas.width;
        const height = canvas.height;

        // Clear canvas
        ctx.clearRect(0, 0, width, height);

        if (!this.cachedHeatmapData || !this.cachedYAxis) return;

        const yAxis = this.cachedYAxis;
        const heatmapData = this.cachedHeatmapData;
        const priceData = this.cachedPriceData;
        const maxLiq = this.cachedMaxLiq;

        // Calculate dimensions with zoom (avoid spread operator for large arrays)
        let maxX = 0;
        for (let i = 0; i < heatmapData.length; i++) {
            const x = heatmapData[i][0];
            if (x > maxX) maxX = x;
        }
        const maxY = yAxis.length - 1;
        
        const cellWidth = (width / (maxX + 1)) * this.zoomLevel;
        const cellHeight = (height / (maxY + 1)) * this.zoomLevel;

        // Apply threshold filter
        const thresholdValue = maxLiq * this.threshold;
        const filteredData = heatmapData.filter(([x, y, value]) => value >= thresholdValue);

        // Draw heatmap cells with pan offset
        ctx.save();
        ctx.translate(this.panX, this.panY);

        filteredData.forEach(([x, y, value]) => {
            const color = this.getHeatmapColor(value, maxLiq);
            ctx.fillStyle = color;
            ctx.fillRect(
                x * cellWidth,
                (maxY - y) * cellHeight, // Invert Y axis
                cellWidth,
                cellHeight
            );
        });

        // Overlay price line if available
        if (priceData && priceData.length > 0) {
            this.drawPriceLine(ctx, priceData, yAxis, width, height, cellWidth, cellHeight, maxY);
        }

        ctx.restore();

        // Add interaction
        this.addInteraction(canvas, yAxis, heatmapData);
    }

    /**
     * Update threshold and re-render (optimized with RAF)
     */
    updateThreshold(threshold) {
        this.threshold = threshold;
        const canvas = document.getElementById(this.canvasId);
        if (canvas) {
            // Use requestAnimationFrame for smooth 60fps rendering
            requestAnimationFrame(() => {
                const ctx = canvas.getContext('2d');
                this.drawHeatmap(ctx);
            });
        }
    }

    /**
     * Update zoom/pan and re-render
     */
    updateZoom(zoomLevel, panX, panY) {
        this.zoomLevel = zoomLevel;
        this.panX = panX;
        this.panY = panY;
        const canvas = document.getElementById(this.canvasId);
        if (canvas) {
            const ctx = canvas.getContext('2d');
            this.drawHeatmap(ctx);
        }
    }

    /**
     * Draw price line overlay
     */
    drawPriceLine(ctx, priceData, yAxis, width, height, cellWidth, cellHeight, maxY) {
        ctx.strokeStyle = '#ef4444'; // Red line
        ctx.lineWidth = 2;
        ctx.beginPath();

        priceData.forEach((candle, index) => {
            const closePrice = parseFloat(candle[4]); // Close price
            
            // Find closest Y index for this price
            const yIndex = this.findClosestYIndex(closePrice, yAxis);
            
            const x = index * cellWidth + cellWidth / 2;
            const y = (maxY - yIndex) * cellHeight + cellHeight / 2;

            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });

        ctx.stroke();
    }

    /**
     * Find closest Y axis index for a given price
     */
    findClosestYIndex(price, yAxis) {
        let closestIndex = 0;
        let minDiff = Math.abs(yAxis[0] - price);

        for (let i = 1; i < yAxis.length; i++) {
            const diff = Math.abs(yAxis[i] - price);
            if (diff < minDiff) {
                minDiff = diff;
                closestIndex = i;
            }
        }

        return closestIndex;
    }

    /**
     * Get heatmap color based on value (Coinglass style - exact gradient)
     */
    getHeatmapColor(value, maxValue) {
        if (value === 0 || value === null) {
            return 'rgba(75, 0, 130, 0.3)'; // Dark purple for no liquidations
        }
        
        // Normalize value to 0-1 range
        const normalized = Math.min(value / maxValue, 1);

        // Coinglass color gradient (from reference image):
        // Dark purple -> Purple -> Cyan -> Green -> Yellow -> Orange
        
        if (normalized < 0.1) {
            // Dark purple to purple
            const r = Math.floor(75 + normalized * 10 * 55);
            const g = Math.floor(0 + normalized * 10 * 20);
            const b = Math.floor(130);
            return `rgba(${r}, ${g}, ${b}, 0.8)`;
        } else if (normalized < 0.3) {
            // Purple to cyan
            const t = (normalized - 0.1) / 0.2;
            const r = Math.floor(130 - t * 80);
            const g = Math.floor(20 + t * 180);
            const b = Math.floor(130 + t * 70);
            return `rgba(${r}, ${g}, ${b}, 0.9)`;
        } else if (normalized < 0.5) {
            // Cyan to green
            const t = (normalized - 0.3) / 0.2;
            const r = Math.floor(50 - t * 50);
            const g = Math.floor(200 + t * 55);
            const b = Math.floor(200 - t * 100);
            return `rgba(${r}, ${g}, ${b}, 0.95)`;
        } else if (normalized < 0.7) {
            // Green to yellow
            const t = (normalized - 0.5) / 0.2;
            const r = Math.floor(0 + t * 255);
            const g = Math.floor(255);
            const b = Math.floor(100 - t * 100);
            return `rgba(${r}, ${g}, ${b}, 1)`;
        } else {
            // Yellow to orange/red
            const t = (normalized - 0.7) / 0.3;
            const r = Math.floor(255);
            const g = Math.floor(255 - t * 155);
            const b = Math.floor(0);
            return `rgba(${r}, ${g}, ${b}, 1)`;
        }
    }

    /**
     * Add mouse interaction for tooltips
     */
    addInteraction(canvas, yAxis, heatmapData) {
        const tooltip = document.createElement('div');
        tooltip.style.cssText = `
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            display: none;
            z-index: 1000;
        `;
        canvas.parentElement.appendChild(tooltip);

        canvas.addEventListener('mousemove', (e) => {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Calculate maxX without spread operator
            let maxX = 0;
            for (let i = 0; i < heatmapData.length; i++) {
                const xVal = heatmapData[i][0];
                if (xVal > maxX) maxX = xVal;
            }
            
            const cellWidth = canvas.width / (maxX + 1);
            const cellHeight = canvas.height / yAxis.length;

            const xIndex = Math.floor(x / cellWidth);
            const yIndex = Math.floor(y / cellHeight);

            // Find data point
            const point = heatmapData.find(d => d[0] === xIndex && d[1] === (yAxis.length - 1 - yIndex));

            if (point) {
                const price = yAxis[point[1]];
                const value = point[2];
                tooltip.innerHTML = `
                    Price: $${price.toLocaleString()}<br>
                    Liquidations: ${LiquidationsUtils.formatValue(value)}
                `;
                tooltip.style.display = 'block';
                tooltip.style.left = (e.clientX + 10) + 'px';
                tooltip.style.top = (e.clientY + 10) + 'px';
            } else {
                tooltip.style.display = 'none';
            }
        });

        canvas.addEventListener('mouseleave', () => {
            tooltip.style.display = 'none';
        });
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
