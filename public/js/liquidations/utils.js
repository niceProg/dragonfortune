/**
 * Liquidations Utilities
 * Blueprint: Open Interest Utils (proven stable)
 */

export const LiquidationsUtils = {
    /**
     * Format liquidation value with appropriate units
     */
    formatValue(value) {
        if (value === null || value === undefined) return 'N/A';
        
        const num = parseFloat(value);
        if (isNaN(num)) return 'N/A';

        // Format based on magnitude
        if (Math.abs(num) >= 1e9) {
            return `$${(num / 1e9).toFixed(2)}B`;
        } else if (Math.abs(num) >= 1e6) {
            return `$${(num / 1e6).toFixed(2)}M`;
        } else if (Math.abs(num) >= 1e3) {
            return `$${(num / 1e3).toFixed(2)}K`;
        }
        return `$${num.toFixed(2)}`;
    },

    /**
     * Format percentage change
     */
    formatChange(value) {
        if (value === null || value === undefined) return 'N/A';
        
        const num = parseFloat(value);
        if (isNaN(num)) return 'N/A';

        const sign = num >= 0 ? '+' : '';
        return `${sign}${num.toFixed(2)}%`;
    },

    /**
     * Format percentage
     */
    formatPercentage(value) {
        if (value === null || value === undefined) return 'N/A';
        
        const num = parseFloat(value);
        if (isNaN(num)) return 'N/A';

        return `${num.toFixed(2)}%`;
    },

    /**
     * Get liquidation type badge class
     */
    getLiquidationBadge(type) {
        if (!type) return 'text-bg-secondary';
        
        const t = type.toLowerCase();
        if (t === 'long' || t.includes('long')) {
            return 'text-bg-danger'; // Longs liquidated = bearish
        } else if (t === 'short' || t.includes('short')) {
            return 'text-bg-success'; // Shorts liquidated = bullish
        }
        return 'text-bg-secondary';
    },

    /**
     * Get liquidation type label
     */
    getLiquidationLabel(type) {
        if (!type) return 'N/A';
        
        const t = type.toLowerCase();
        if (t === 'long' || t.includes('long')) {
            return 'Long Liquidations';
        } else if (t === 'short' || t.includes('short')) {
            return 'Short Liquidations';
        }
        return type;
    },

    /**
     * Parse heatmap data from API response
     */
    parseHeatmapData(apiResponse) {
        if (!apiResponse || !apiResponse.success || !apiResponse.data) {
            return null;
        }

        const data = apiResponse.data;
        
        return {
            yAxis: data.y_axis || [],
            heatmapData: data.liquidation_leverage_data || [],
            priceData: data.price_candlesticks || [],
            timestamp: Date.now()
        };
    },

    /**
     * Calculate statistics from heatmap data
     */
    calculateStats(heatmapData) {
        if (!heatmapData || !Array.isArray(heatmapData) || heatmapData.length === 0) {
            return {
                totalLiquidations: 0,
                maxLiquidation: 0,
                avgLiquidation: 0,
                count: 0
            };
        }

        // Each point is [x, y, value]
        const values = heatmapData.map(point => point[2] || 0);
        
        // Calculate stats without spread operator (avoid stack overflow for large arrays)
        let total = 0;
        let max = 0;
        
        for (let i = 0; i < values.length; i++) {
            const val = values[i];
            total += val;
            if (val > max) max = val;
        }
        
        const avg = values.length > 0 ? total / values.length : 0;

        return {
            totalLiquidations: total,
            maxLiquidation: max,
            avgLiquidation: avg,
            count: values.length
        };
    }
};
