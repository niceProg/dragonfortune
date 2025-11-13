/**
 * Funding Rate Utilities
 * Helper functions for formatting and calculations
 */

export const FundingRateUtils = {

    /**
     * Capitalize exchange name (binance -> Binance)
     */
    capitalizeExchange(exchange) {
        if (exchange === 'all_exchange') return 'all_exchange';
        return exchange.charAt(0).toUpperCase() + exchange.slice(1);
    },

    /**
     * Format Funding Rate value for display
     */
    formatFundingRate(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        return `${num.toFixed(4)}%`; // 4 decimal places for funding rate precision
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
     * Format change (percentage for Funding Rate)
     */
    formatChange(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(4)}%`;
    }

};