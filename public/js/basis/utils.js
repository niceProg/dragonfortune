/**
 * Basis & Term Structure Utilities
 * Helper functions for formatting and calculations
 * 
 * Blueprint: Open Interest Utils (proven stable)
 */

export const BasisUtils = {

    /**
     * Format Basis value for display (percentage)
     * Example: 0.0374 -> "3.74%"
     */
    formatBasis(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        return (num * 100).toFixed(2) + '%';
    },

    /**
     * Format Change value (percentage)
     * Example: 41.5 -> "41.50%"
     */
    formatChange(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        const sign = num >= 0 ? '+' : '';
        return `${sign}${num.toFixed(2)}%`;
    },

    /**
     * Get market structure from basis
     * Positive basis = Contango (normal)
     * Negative basis = Backwardation (bullish signal)
     */
    getMarketStructure(basis) {
        if (basis === null || basis === undefined || isNaN(basis)) return 'Unknown';
        const num = parseFloat(basis);
        
        if (num > 0.001) return 'Contango';
        if (num < -0.001) return 'Backwardation';
        return 'Flat';
    },

    /**
     * Get market structure color class
     */
    getStructureColor(basis) {
        if (basis === null || basis === undefined || isNaN(basis)) return 'text-secondary';
        const num = parseFloat(basis);
        
        if (num > 0.001) return 'text-primary'; // Contango (normal)
        if (num < -0.001) return 'text-success'; // Backwardation (bullish)
        return 'text-warning'; // Flat
    },

    /**
     * Get market structure badge class
     */
    getStructureBadge(basis) {
        if (basis === null || basis === undefined || isNaN(basis)) return 'text-bg-secondary';
        const num = parseFloat(basis);
        
        if (num > 0.001) return 'text-bg-primary'; // Contango
        if (num < -0.001) return 'text-bg-success'; // Backwardation
        return 'text-bg-warning'; // Flat
    },

    /**
     * Capitalize exchange name (binance -> Binance)
     */
    capitalizeExchange(exchange) {
        if (!exchange) return exchange;
        return exchange.charAt(0).toUpperCase() + exchange.slice(1);
    }

};
