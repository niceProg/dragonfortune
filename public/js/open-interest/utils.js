/**
 * Open Interest Utilities
 * Helper functions for date handling, formatting, calculations
 */

export const OpenInterestUtils = {

    /**
     * Capitalize exchange name (binance -> Binance)
     */
    capitalizeExchange(exchange) {
        if (exchange === 'all_exchange') return 'all_exchange';
        return exchange.charAt(0).toUpperCase() + exchange.slice(1);
    },

    /**
     * Format Open Interest value for display
     */
    formatOI(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        if (num >= 1e9) {
            return '$' + (num / 1e9).toFixed(2) + 'B';
        } else if (num >= 1e6) {
            return '$' + (num / 1e6).toFixed(2) + 'M';
        } else if (num >= 1e3) {
            return '$' + (num / 1e3).toFixed(2) + 'K';
        }
        return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    /**
     * Format price value
     */
    formatPrice(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        return '$' + num.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    },

    /**
     * Format change (percentage for Open Interest)
     */
    formatChange(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(2)}%`;
    }

};

