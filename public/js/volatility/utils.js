/**
 * Volatility Utils
 * Utility functions for volatility data formatting and calculations
 */

export class VolatilityUtils {
    /**
     * Format price for display
     */
    static formatPrice(value) {
        if (value === null || value === undefined || isNaN(value)) {
            return 'N/A';
        }
        
        if (value >= 1000) {
            return '$' + value.toLocaleString('en-US', { 
                minimumFractionDigits: 2,
                maximumFractionDigits: 2 
            });
        } else if (value >= 1) {
            return '$' + value.toFixed(2);
        } else {
            return '$' + value.toFixed(4);
        }
    }

    /**
     * Format volume for display
     */
    static formatVolume(value) {
        if (value === null || value === undefined || isNaN(value)) {
            return 'N/A';
        }
        
        if (value >= 1e9) {
            return '$' + (value / 1e9).toFixed(2) + 'B';
        } else if (value >= 1e6) {
            return '$' + (value / 1e6).toFixed(2) + 'M';
        } else if (value >= 1e3) {
            return '$' + (value / 1e3).toFixed(2) + 'K';
        }
        return '$' + value.toFixed(0);
    }

    /**
     * Format percentage
     */
    static formatPercent(value) {
        if (value === null || value === undefined || isNaN(value)) {
            return 'N/A';
        }
        
        const sign = value >= 0 ? '+' : '';
        return sign + value.toFixed(2) + '%';
    }

    /**
     * Format change amount
     */
    static formatChange(value) {
        if (value === null || value === undefined || isNaN(value)) {
            return 'N/A';
        }
        
        const sign = value >= 0 ? '+' : '';
        return sign + this.formatPrice(Math.abs(value));
    }

    /**
     * Get interval label
     */
    static getIntervalLabel(interval) {
        const labels = {
            '1m': '1 Minute',
            '3m': '3 Minutes',
            '5m': '5 Minutes',
            '15m': '15 Minutes',
            '30m': '30 Minutes',
            '1h': '1 Hour',
            '4h': '4 Hours',
            '6h': '6 Hours',
            '8h': '8 Hours',
            '12h': '12 Hours',
            '1d': '1 Day',
            '1w': '1 Week'
        };
        
        return labels[interval] || interval;
    }

    /**
     * Calculate time range based on interval
     * Returns { start_time, end_time } in milliseconds
     */
    static getTimeRangeForInterval(interval) {
        const now = Date.now();
        let daysBack = 1;
        
        switch (interval) {
            case '1m':
            case '3m':
            case '5m':
                daysBack = 1; // Last 1 day
                break;
            case '15m':
            case '30m':
                daysBack = 3; // Last 3 days
                break;
            case '1h':
                daysBack = 7; // Last 7 days
                break;
            case '4h':
                daysBack = 30; // Last 30 days
                break;
            case '6h':
            case '8h':
            case '12h':
                daysBack = 60; // Last 60 days
                break;
            case '1d':
                daysBack = 365; // Last 1 year
                break;
            case '1w':
                daysBack = 730; // Last 2 years
                break;
            default:
                daysBack = 7;
        }
        
        const startTime = now - (daysBack * 24 * 60 * 60 * 1000);
        
        return {
            start_time: startTime,
            end_time: now
        };
    }

    /**
     * Calculate ATR (Average True Range)
     * @param {Array} data - OHLC data with true_range field
     * @param {number} period - ATR period (default: 14)
     */
    static calculateATR(data, period = 14) {
        if (!data || data.length < period) {
            return null;
        }
        
        const trValues = data.map(d => d.true_range || 0);
        const sum = trValues.slice(-period).reduce((acc, val) => acc + val, 0);
        
        return sum / period;
    }

    /**
     * Calculate Historical Volatility (HV)
     * @param {Array} data - OHLC data with daily_return field
     * @param {number} period - HV period (default: 20)
     */
    static calculateHV(data, period = 20) {
        if (!data || data.length < period) {
            return null;
        }
        
        const returns = data.slice(-period).map(d => d.daily_return || 0);
        const mean = returns.reduce((acc, val) => acc + val, 0) / period;
        const variance = returns.reduce((acc, val) => acc + Math.pow(val - mean, 2), 0) / period;
        const stdDev = Math.sqrt(variance);
        
        // Annualize (multiply by sqrt of trading days)
        return stdDev * Math.sqrt(365);
    }

    /**
     * Calculate Realized Volatility (RV)
     * Similar to HV but uses intraday data
     */
    static calculateRV(data, period = 20) {
        return this.calculateHV(data, period);
    }

    /**
     * Determine volatility regime
     * @param {number} hv - Historical Volatility
     * @returns {string} - 'low', 'medium', 'high'
     */
    static getVolatilityRegime(hv) {
        if (!hv) return 'unknown';
        
        if (hv < 30) {
            return 'low';
        } else if (hv < 60) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Get regime color
     */
    static getRegimeColor(regime) {
        const colors = {
            'low': '#22c55e',      // Green
            'medium': '#f59e0b',   // Yellow/Orange
            'high': '#ef4444',     // Red
            'unknown': '#64748b'   // Gray
        };
        
        return colors[regime] || colors['unknown'];
    }
}

