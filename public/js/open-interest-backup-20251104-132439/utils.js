/**
 * Open Interest Utilities
 * Helper functions for formatting, calculations, and date handling
 */

export const OpenInterestUtils = {
    /**
     * Format Open Interest value (1.5B, 800M, etc)
     */
    formatOI(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        
        if (num >= 1e9) {
            return `$${(num / 1e9).toFixed(2)}B`;
        } else if (num >= 1e6) {
            return `$${(num / 1e6).toFixed(2)}M`;
        } else if (num >= 1e3) {
            return `$${(num / 1e3).toFixed(2)}K`;
        }
        return `$${num.toFixed(2)}`;
    },

    /**
     * Format price value in USD
     */
    formatPrice(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        
        if (num >= 1e6) {
            return `$${(num / 1e6).toFixed(2)}M`;
        } else if (num >= 1e3) {
            return `$${(num / 1e3).toFixed(2)}K`;
        }
        return `$${num.toFixed(2)}`;
    },

    /**
     * Format change percentage
     */
    formatChange(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        const sign = num >= 0 ? '+' : '';
        return `${sign}${num.toFixed(2)}%`;
    },

    /**
     * Format volume numbers
     */
    formatVolume(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        
        if (num >= 1e9) {
            return `${(num / 1e9).toFixed(2)}B`;
        } else if (num >= 1e6) {
            return `${(num / 1e6).toFixed(2)}M`;
        } else if (num >= 1e3) {
            return `${(num / 1e3).toFixed(2)}K`;
        }
        return num.toFixed(2);
    },

    /**
     * Get badge class for trend
     */
    getTrendBadgeClass(trend) {
        if (!trend) return 'text-bg-secondary';
        const trendLower = trend.toLowerCase();
        
        if (trendLower === 'increasing' || trendLower === 'bullish') {
            return 'text-bg-success';
        } else if (trendLower === 'decreasing' || trendLower === 'bearish') {
            return 'text-bg-danger';
        }
        return 'text-bg-secondary';
    },

    /**
     * Get color class for trend
     */
    getTrendColorClass(trend) {
        if (!trend) return 'text-secondary';
        const trendLower = trend.toLowerCase();
        
        if (trendLower === 'increasing' || trendLower === 'bullish') {
            return 'text-success';
        } else if (trendLower === 'decreasing' || trendLower === 'bearish') {
            return 'text-danger';
        }
        return 'text-secondary';
    },

    /**
     * Get badge class for volatility level
     */
    getVolatilityBadgeClass(level) {
        if (!level) return 'text-bg-secondary';
        const levelLower = level.toLowerCase();
        
        if (levelLower === 'high') {
            return 'text-bg-danger';
        } else if (levelLower === 'moderate') {
            return 'text-bg-warning';
        } else if (levelLower === 'low') {
            return 'text-bg-success';
        }
        return 'text-bg-secondary';
    },

    /**
     * Calculate API limit based on date range and interval
     */
    calculateLimit(days, interval) {
        const pointsPerDayMap = {
            '1m': 1440,
            '5m': 288,
            '15m': 96,
            '1h': 24,
            '4h': 6,
            '8h': 3,
            '1w': 1 / 7 // approx one point per 7 days
        };
        const perDay = pointsPerDayMap[interval] || 288; // default to 5m density
        const rawNeeded = Math.ceil(perDay * days);
        const headroom = Math.ceil(rawNeeded * 1.25); // 25% headroom for gaps
        const maxLimit = 20000;
        return Math.max(500, Math.min(headroom, maxLimit));
    },

    /**
     * Get date range object (startDate, endDate) from period
     */
    getDateRange(period, timeRanges) {
        const now = new Date();
        let startDate = new Date();
        let endDate = new Date();

        if (period === 'all') {
            // All time: 2 years back to end of today
            startDate = new Date(now.getTime() - (730 * 24 * 60 * 60 * 1000));
        } else {
            const range = timeRanges.find(r => r.value === period);
            const days = range ? range.days : 1;
            startDate = new Date(now.getTime() - (days * 24 * 60 * 60 * 1000));
        }
        // Normalize end to end-of-day for inclusive range
        endDate.setHours(23, 59, 59, 999);

        return { startDate, endDate };
    },

    /**
     * Get time range in milliseconds
     */
    getTimeRange(period, timeRanges) {
        const range = timeRanges.find(r => r.value === period);
        if (!range) return 24 * 60 * 60 * 1000; // Default 1 day
        
        if (period === 'all') {
            return 730 * 24 * 60 * 60 * 1000; // 2 years
        }
        
        return range.days * 24 * 60 * 60 * 1000;
    }
};

