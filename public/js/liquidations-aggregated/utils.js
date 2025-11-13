/**
 * Liquidations Aggregated Utilities
 * Helper functions for formatting and calculations
 */

export class LiquidationsAggregatedUtils {
    /**
     * Format large numbers with K/M/B suffixes
     */
    static formatValue(value) {
        if (value === null || value === undefined) return 'N/A';
        
        const num = parseFloat(value);
        if (isNaN(num)) return 'N/A';

        if (Math.abs(num) >= 1e9) {
            return (num / 1e9).toFixed(2) + 'B';
        } else if (Math.abs(num) >= 1e6) {
            return (num / 1e6).toFixed(2) + 'M';
        } else if (Math.abs(num) >= 1e3) {
            return (num / 1e3).toFixed(2) + 'K';
        } else {
            return num.toFixed(2);
        }
    }

    /**
     * Format percentage change
     */
    static formatChange(value) {
        if (value === null || value === undefined) return 'N/A';
        
        const num = parseFloat(value);
        if (isNaN(num)) return 'N/A';

        const sign = num >= 0 ? '+' : '';
        return sign + num.toFixed(2) + '%';
    }

    /**
     * Format percentage
     */
    static formatPercentage(value) {
        if (value === null || value === undefined) return 'N/A';
        
        const num = parseFloat(value);
        if (isNaN(num)) return 'N/A';

        return num.toFixed(2) + '%';
    }

    /**
     * Format timestamp to readable date
     */
    static formatDate(timestamp) {
        if (!timestamp) return 'N/A';
        
        const date = new Date(timestamp);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Calculate statistics from liquidation data
     */
    static calculateStats(data) {
        if (!data || data.length === 0) {
            return {
                totalLong: 0,
                totalShort: 0,
                total: 0,
                avgLong: 0,
                avgShort: 0,
                maxLong: 0,
                maxShort: 0,
                longShortRatio: 0,
                count: 0
            };
        }

        let totalLong = 0;
        let totalShort = 0;
        let maxLong = 0;
        let maxShort = 0;

        data.forEach(item => {
            const longLiq = item.long_liquidation_usd || 0;
            const shortLiq = item.short_liquidation_usd || 0;

            totalLong += longLiq;
            totalShort += shortLiq;

            if (longLiq > maxLong) maxLong = longLiq;
            if (shortLiq > maxShort) maxShort = shortLiq;
        });

        const count = data.length;
        const total = totalLong + totalShort;
        const avgLong = count > 0 ? totalLong / count : 0;
        const avgShort = count > 0 ? totalShort / count : 0;
        const longShortRatio = totalShort > 0 ? totalLong / totalShort : 0;

        return {
            totalLong,
            totalShort,
            total,
            avgLong,
            avgShort,
            maxLong,
            maxShort,
            longShortRatio,
            count
        };
    }
}
