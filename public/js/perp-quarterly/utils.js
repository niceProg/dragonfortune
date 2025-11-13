/**
 * Perp-Quarterly Spread Utilities
 * Helper functions for date handling, formatting, calculations
 */

export const PerpQuarterlyUtils = {
    /**
     * Calculate optimal limit based on date range and interval
     * 
     * @param {number} days - Date range in days
     * @param {string} interval - Interval (5m, 15m, 1h, 4h)
     * @returns {number} - Limit for API request
     */
    calculateLimit(days, interval) {
        const intervalHours = {
            '5m': 5 / 60,   // 5 minutes = 5/60 hours
            '15m': 15 / 60, // 15 minutes = 15/60 hours
            '1h': 1,
            '4h': 4
        };
        
        const hours = intervalHours[interval] || 1;
        
        // Calculate exact records needed
        const exactRecordsNeeded = Math.ceil((days * 24) / hours);
        
        // Add 50% buffer to ensure we get enough data
        const bufferMultiplier = 1.5;
        let calculatedLimit = Math.ceil(exactRecordsNeeded * bufferMultiplier);
        
        // Enforce max limit (API limit)
        const maxLimit = 5000;
        return Math.min(calculatedLimit, maxLimit);
    },

    /**
     * Capitalize exchange name for display
     */
    capitalizeExchange(exchange) {
        if (!exchange) return '';
        return exchange.charAt(0).toUpperCase() + exchange.slice(1).toLowerCase();
    },

    /**
     * Format spread value (USD)
     */
    formatSpread(value) {
        if (value === null || value === undefined || isNaN(value)) return '--';
        return parseFloat(value).toFixed(2);
    },

    /**
     * Format spread as basis points (BPS)
     */
    formatSpreadBPS(value) {
        if (value === null || value === undefined || isNaN(value)) return '--';
        return parseFloat(value).toFixed(2) + ' bps';
    },

    /**
     * Format price (USD)
     */
    formatPrice(value) {
        if (value === null || value === undefined || isNaN(value)) return '--';
        return '$' + parseFloat(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    /**
     * Format change percentage
     */
    formatChange(value) {
        if (value === null || value === undefined || isNaN(value)) return '--';
        const sign = value >= 0 ? '+' : '';
        return sign + parseFloat(value).toFixed(2) + '%';
    },

    /**
     * Calculate median from array of numbers
     */
    calculateMedian(values) {
        if (!values || values.length === 0) return 0;
        const sorted = [...values].sort((a, b) => a - b);
        const mid = Math.floor(sorted.length / 2);
        return sorted.length % 2 === 0
            ? (sorted[mid - 1] + sorted[mid]) / 2
            : sorted[mid];
    },

    /**
     * Calculate standard deviation
     */
    calculateStdDev(values) {
        if (!values || values.length === 0) return 0;
        const mean = values.reduce((a, b) => a + b, 0) / values.length;
        const squaredDiffs = values.map(v => Math.pow(v - mean, 2));
        const avgSquaredDiff = squaredDiffs.reduce((a, b) => a + b, 0) / values.length;
        return Math.sqrt(avgSquaredDiff);
    },

    /**
     * Calculate moving average array
     */
    calculateMA(values, period) {
        if (!values || values.length < period) return [];
        const ma = [];
        for (let i = period - 1; i < values.length; i++) {
            const sum = values.slice(i - period + 1, i + 1).reduce((a, b) => a + b, 0);
            ma.push(sum / period);
        }
        return ma;
    },

    /**
     * Create histogram bins for distribution analysis
     */
    createHistogramBins(values, binCount = 20) {
        if (!values || values.length === 0) return [];
        const min = Math.min(...values);
        const max = Math.max(...values);
        const binWidth = (max - min) / binCount;
        
        const bins = Array(binCount).fill(0);
        values.forEach(value => {
            const binIndex = Math.min(
                Math.floor((value - min) / binWidth),
                binCount - 1
            );
            bins[binIndex]++;
        });
        
        return bins.map((count, index) => ({
            bin: min + (index * binWidth),
            count: count
        }));
    }
};

