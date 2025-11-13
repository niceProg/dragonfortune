/**
 * Sentiment & Flow Utilities
 * Helper functions for formatting and calculations
 */

export const SentimentFlowUtils = {
    
    /**
     * Format percentage (funding rate)
     */
    formatPercent(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const percent = value * 100;
        const sign = percent >= 0 ? '+' : '';
        return `${sign}${percent.toFixed(4)}%`;
    },

    /**
     * Format funding rate in decimal format (real value, not percentage)
     * Example: 0.01 (not 1.0%)
     */
    formatFundingRate(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(6)}`;
    },

    /**
     * Format annualized rate
     */
    formatAnnualizedRate(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(2)}%`;
    },

    /**
     * Format USD value with abbreviation
     */
    formatUSD(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        
        if (num >= 1e9) {
            return '$' + (num / 1e9).toFixed(2) + 'B';
        } else if (num >= 1e6) {
            return '$' + (num / 1e6).toFixed(2) + 'M';
        } else if (num >= 1e3) {
            return '$' + (num / 1e3).toFixed(2) + 'K';
        }
        
        return '$' + num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    /**
     * Format price
     */
    formatPrice(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        return '$' + num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    /**
     * Format number
     */
    formatNumber(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        return num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    /**
     * Format timestamp to readable date
     */
    formatDate(timestamp) {
        if (!timestamp) return 'N/A';
        const date = new Date(timestamp);
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Get fear & greed color
     */
    getFearGreedColor(value) {
        if (value >= 80) return '#f43f5e'; // Extreme Greed - red
        if (value >= 60) return '#fb923c'; // Greed - orange
        if (value >= 40) return '#fbbf24'; // Neutral - yellow
        if (value >= 20) return '#a3e635'; // Fear - lime
        return '#22c55e'; // Extreme Fear - green
    },

    /**
     * Get fear & greed label
     */
    getFearGreedLabel(value) {
        if (value >= 80) return 'Extreme Greed';
        if (value >= 60) return 'Greed';
        if (value >= 40) return 'Neutral';
        if (value >= 20) return 'Fear';
        return 'Extreme Fear';
    },

    /**
     * Get funding rate sentiment color
     * Threshold: 0.1% (0.001) for extreme positioning
     */
    getFundingColor(rate) {
        if (rate > 0.001) return '#22c55e'; // Bullish - green (>0.1%)
        if (rate < -0.001) return '#ef4444'; // Bearish - red (<-0.1%)
        return '#94a3b8'; // Neutral - gray (-0.1% to 0.1%)
    },

    /**
     * Get funding rate trend icon
     * Threshold: 0.1% (0.001) for extreme positioning
     */
    getFundingTrend(rate) {
        if (rate > 0.001) return '📈 Bullish';
        if (rate < -0.001) return '📉 Bearish';
        return '➡️ Neutral';
    },

    /**
     * Get position type badge class
     */
    getPositionBadgeClass(type) {
        return type === 'Long' ? 'text-bg-success' : 'text-bg-danger';
    },

    /**
     * Get action badge class
     */
    getActionBadgeClass(action) {
        return action === 'Open' ? 'text-bg-primary' : 'text-bg-secondary';
    },

    /**
     * Truncate address for display
     */
    truncateAddress(address) {
        if (!address || address.length < 10) return address;
        return address.slice(0, 6) + '...' + address.slice(-4);
    },

    /**
     * Format large USD amount with M/B suffix
     */
    formatLargeUsd(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        
        if (num >= 1000000000) {
            return `$${(num / 1000000000).toFixed(2)}B`;
        } else if (num >= 1000000) {
            return `$${(num / 1000000).toFixed(2)}M`;
        } else {
            return `$${num.toFixed(2)}`;
        }
    },

    /**
     * Format blockchain name
     */
    formatBlockchain(blockchain) {
        if (!blockchain) return 'N/A';
        return blockchain.charAt(0).toUpperCase() + blockchain.slice(1);
    },

    /**
     * Truncate transaction hash
     */
    truncateTxHash(hash) {
        if (!hash || hash.length < 12) return hash;
        return hash.slice(0, 8) + '...' + hash.slice(-6);
    },

    /**
     * Get direction badge class (to/from exchange)
     */
    getDirectionBadgeClass(from, to) {
        const exchanges = ['binance', 'coinbase', 'kraken', 'okx', 'bitfinex', 'huobi', 'bybit', 'gate', 'kucoin'];
        const fromLower = (from || '').toLowerCase();
        const toLower = (to || '').toLowerCase();
        
        const isFromExchange = exchanges.some(ex => fromLower.includes(ex));
        const isToExchange = exchanges.some(ex => toLower.includes(ex));
        
        if (isToExchange && !isFromExchange) return 'text-bg-danger'; // To exchange (bearish?)
        if (isFromExchange && !isToExchange) return 'text-bg-success'; // From exchange (bullish?)
        return 'text-bg-secondary'; // Unknown/Wallet to wallet
    },

    /**
     * Get direction label (to/from exchange)
     */
    getDirectionLabel(from, to) {
        const exchanges = ['binance', 'coinbase', 'kraken', 'okx', 'bitfinex', 'huobi', 'bybit', 'gate', 'kucoin'];
        const fromLower = (from || '').toLowerCase();
        const toLower = (to || '').toLowerCase();
        
        const isFromExchange = exchanges.some(ex => fromLower.includes(ex));
        const isToExchange = exchanges.some(ex => toLower.includes(ex));
        
        if (isToExchange && !isFromExchange) return '📥 To Exchange';
        if (isFromExchange && !isToExchange) return '📤 From Exchange';
        return '🔄 Wallet Transfer';
    }
};

