/**
 * Macro Overlay Utility Functions
 * Formatting and helper functions for macro data
 */

export class MacroUtils {
    /**
     * Format number with appropriate precision
     */
    static formatNumber(value, decimals = 2) {
        if (value === null || value === undefined) return 'N/A';
        return Number(value).toFixed(decimals);
    }

    /**
     * Format large numbers with K/M/B/T suffixes
     */
    static formatLargeNumber(value) {
        if (value === null || value === undefined) return 'N/A';
        
        const num = Math.abs(value);
        const sign = value < 0 ? '-' : '';
        
        if (num >= 1e12) {
            return sign + (num / 1e12).toFixed(2) + 'T';
        } else if (num >= 1e9) {
            return sign + (num / 1e9).toFixed(2) + 'B';
        } else if (num >= 1e6) {
            return sign + (num / 1e6).toFixed(2) + 'M';
        } else if (num >= 1e3) {
            return sign + (num / 1e3).toFixed(2) + 'K';
        }
        
        return sign + num.toFixed(2);
    }

    /**
     * Format percentage
     */
    static formatPercent(value, decimals = 2) {
        if (value === null || value === undefined) return 'N/A';
        return Number(value).toFixed(decimals) + '%';
    }

    /**
     * Format basis points
     */
    static formatBps(value) {
        if (value === null || value === undefined) return 'N/A';
        return Number(value).toFixed(0) + ' bps';
    }

    /**
     * Format date
     */
    static formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        } catch (e) {
            return dateString;
        }
    }

    /**
     * Format timestamp
     */
    static formatTimestamp(timestamp) {
        if (!timestamp) return 'N/A';
        
        try {
            const date = new Date(timestamp);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        } catch (e) {
            return 'Invalid Date';
        }
    }

    /**
     * Get color based on value change
     */
    static getChangeColor(current, previous) {
        if (current === previous || current === null || previous === null) {
            return 'text-muted';
        }
        return current > previous ? 'text-success' : 'text-danger';
    }

    /**
     * Get trend arrow
     */
    static getTrendArrow(current, previous) {
        if (current === previous || current === null || previous === null) {
            return '→';
        }
        return current > previous ? '↑' : '↓';
    }

    /**
     * Get series label from series ID
     */
    static getSeriesLabel(seriesId) {
        const labels = {
            'DTWEXBGS': 'DXY (Dollar Index)',
            'DGS10': '10Y Treasury Yield',
            'DGS2': '2Y Treasury Yield',
            'DFF': 'Fed Funds Rate',
            'CPIAUCSL': 'CPI (Inflation)',
            'PAYEMS': 'Nonfarm Payrolls',
            'M2SL': 'M2 Money Supply',
            'RRPONTSYD': 'Reverse Repo',
            'WTREGEN': 'Treasury General Account'
        };
        
        return labels[seriesId] || seriesId;
    }

    /**
     * Get series unit/format
     */
    static getSeriesUnit(seriesId) {
        const units = {
            'DTWEXBGS': 'Index',
            'DGS10': '%',
            'DGS2': '%',
            'DFF': '%',
            'CPIAUCSL': 'Index',
            'PAYEMS': 'K',
            'M2SL': '$B',
            'RRPONTSYD': '$B',
            'WTREGEN': '$B'
        };
        
        return units[seriesId] || '';
    }

    /**
     * Format value based on series type
     */
    static formatSeriesValue(seriesId, value) {
        if (value === null || value === undefined) return 'N/A';
        
        const unit = this.getSeriesUnit(seriesId);
        
        switch (unit) {
            case '%':
                return this.formatPercent(value, 2);
            case 'Index':
                return this.formatNumber(value, 2);
            case 'K':
                return this.formatLargeNumber(value * 1000);
            case '$B':
                return '$' + this.formatLargeNumber(value * 1e9);
            default:
                return this.formatNumber(value, 2);
        }
    }

    /**
     * Calculate change between two values
     */
    static calculateChange(current, previous) {
        if (current === null || previous === null || previous === 0) {
            return null;
        }
        
        return ((current - previous) / previous) * 100;
    }

    /**
     * Get badge class for percentage change
     */
    static getChangeBadgeClass(change) {
        if (change === null || change === 0) {
            return 'text-bg-secondary';
        }
        return change > 0 ? 'text-bg-success' : 'text-bg-danger';
    }

    /**
     * Format change with sign
     */
    static formatChangeWithSign(change) {
        if (change === null) return 'N/A';
        
        const sign = change >= 0 ? '+' : '';
        return sign + this.formatPercent(change, 2);
    }
}

