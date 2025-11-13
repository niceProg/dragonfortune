/**
 * Long-Short Ratio Utilities
 * Helper functions for formatting and calculations
 * 
 * Blueprint: Open Interest Utils (proven stable)
 */

export const LongShortRatioUtils = {

    /**
     * Format Long-Short Ratio value for display
     * Example: 1.62 -> "1.62"
     */
    formatRatio(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        return num.toFixed(2);
    },

    /**
     * Format percentage value for display
     * Example: 61.87 -> "61.87%"
     */
    formatPercent(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        return num.toFixed(2) + '%';
    },

    /**
     * Format change (percentage)
     * Example: 5.23 -> "+5.23%", -3.45 -> "-3.45%"
     */
    formatChange(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(2)}%`;
    },

    /**
     * Get sentiment from ratio
     * Ratio > 1 = More longs (bullish)
     * Ratio < 1 = More shorts (bearish)
     * Ratio = 1 = Balanced
     */
    getSentiment(ratio) {
        if (ratio === null || ratio === undefined || isNaN(ratio)) return 'Unknown';
        const num = parseFloat(ratio);
        
        if (num > 1.5) return 'Strong Bullish';
        if (num > 1.2) return 'Bullish';
        if (num > 0.8) return 'Neutral';
        if (num > 0.5) return 'Bearish';
        return 'Strong Bearish';
    },

    /**
     * Get sentiment color class
     */
    getSentimentColor(ratio) {
        if (ratio === null || ratio === undefined || isNaN(ratio)) return 'text-secondary';
        const num = parseFloat(ratio);
        
        if (num > 1.2) return 'text-success';
        if (num > 0.8) return 'text-warning';
        return 'text-danger';
    },

    /**
     * Get sentiment badge class
     */
    getSentimentBadge(ratio) {
        if (ratio === null || ratio === undefined || isNaN(ratio)) return 'text-bg-secondary';
        const num = parseFloat(ratio);
        
        if (num > 1.2) return 'text-bg-success';
        if (num > 0.8) return 'text-bg-warning';
        return 'text-bg-danger';
    },

    /**
     * Capitalize exchange name (binance -> Binance)
     */
    capitalizeExchange(exchange) {
        if (!exchange) return exchange;
        return exchange.charAt(0).toUpperCase() + exchange.slice(1);
    }

};
