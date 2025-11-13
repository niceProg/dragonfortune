/**
 * ETF Rankings Table Component
 * Sortable, filterable comparison table for Bitcoin ETFs
 */

export class EtfTableManager {
    constructor() {
        this.etfData = [];
        this.sortField = 'aum_usd';
        this.sortDirection = 'desc';
        this.filterText = '';
    }

    /**
     * Update table data
     */
    setData(etfList) {
        this.etfData = etfList || [];
        console.log(`📊 ETF Table: Loaded ${this.etfData.length} ETFs`);
    }

    /**
     * Get filtered and sorted data
     */
    getDisplayData() {
        let data = [...this.etfData];

        // Apply filter
        if (this.filterText) {
            const search = this.filterText.toLowerCase();
            data = data.filter(etf => 
                etf.ticker.toLowerCase().includes(search) ||
                etf.fund_name.toLowerCase().includes(search)
            );
        }

        // Apply sort
        data.sort((a, b) => {
            let aVal = a[this.sortField];
            let bVal = b[this.sortField];

            // Handle nulls
            if (aVal === null) return 1;
            if (bVal === null) return -1;

            // Sort numbers
            if (typeof aVal === 'number' && typeof bVal === 'number') {
                return this.sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
            }

            // Sort strings
            aVal = String(aVal).toLowerCase();
            bVal = String(bVal).toLowerCase();
            if (this.sortDirection === 'asc') {
                return aVal.localeCompare(bVal);
            } else {
                return bVal.localeCompare(aVal);
            }
        });

        return data;
    }

    /**
     * Set sort field and direction
     */
    setSorting(field, direction = null) {
        if (this.sortField === field && direction === null) {
            // Toggle direction
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortField = field;
            this.sortDirection = direction || 'desc';
        }
        console.log(`🔄 Sort by: ${field} (${this.sortDirection})`);
    }

    /**
     * Set filter text
     */
    setFilter(text) {
        this.filterText = text;
        console.log(`🔍 Filter: "${text}"`);
    }

    /**
     * Format currency
     */
    formatCurrency(value) {
        if (value === null || value === undefined) return 'N/A';
        
        const absValue = Math.abs(value);
        
        if (absValue >= 1e9) {
            return `$${(value / 1e9).toFixed(2)}B`;
        } else if (absValue >= 1e6) {
            return `$${(value / 1e6).toFixed(2)}M`;
        } else if (absValue >= 1e3) {
            return `$${(value / 1e3).toFixed(2)}K`;
        } else {
            return `$${value.toFixed(2)}`;
        }
    }

    /**
     * Format BTC quantity
     */
    formatBTC(value) {
        if (value === null || value === undefined) return 'N/A';
        return `${value.toLocaleString('en-US', { maximumFractionDigits: 0 })} BTC`;
    }

    /**
     * Format percentage
     */
    formatPercent(value) {
        if (value === null || value === undefined) return 'N/A';
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(2)}%`;
    }

    /**
     * Format BPS (basis points)
     */
    formatBPS(value) {
        if (value === null || value === undefined) return 'N/A';
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(0)} bps`;
    }

    /**
     * Get premium/discount class (for coloring)
     */
    getPDClass(value) {
        if (value > 0) return 'text-danger'; // Premium (red)
        if (value < 0) return 'text-success'; // Discount (green)
        return 'text-muted'; // Neutral
    }

    /**
     * Get change class (for coloring)
     */
    getChangeClass(value) {
        if (value > 0) return 'text-success';
        if (value < 0) return 'text-danger';
        return 'text-muted';
    }

    /**
     * Get rank badge
     */
    getRankBadge(index) {
        if (index === 0) return '🥇';
        if (index === 1) return '🥈';
        if (index === 2) return '🥉';
        return `#${index + 1}`;
    }

    /**
     * Get sort icon for column
     */
    getSortIcon(field) {
        if (this.sortField !== field) {
            return '<i class="fas fa-sort text-muted"></i>';
        }
        if (this.sortDirection === 'asc') {
            return '<i class="fas fa-sort-up text-primary"></i>';
        } else {
            return '<i class="fas fa-sort-down text-primary"></i>';
        }
    }

    /**
     * Clear all filters and sorting
     */
    reset() {
        this.sortField = 'aum_usd';
        this.sortDirection = 'desc';
        this.filterText = '';
        console.log('🔄 ETF Table reset');
    }
}

/**
 * Utility functions for ETF data
 */
export const EtfUtils = {
    /**
     * Get top N ETFs by AUM
     */
    getTopByAUM(etfList, n = 10) {
        return [...etfList]
            .sort((a, b) => b.aum_usd - a.aum_usd)
            .slice(0, n);
    },

    /**
     * Get ETFs with biggest discount (buying opportunity)
     */
    getBiggestDiscounts(etfList, n = 5) {
        return [...etfList]
            .filter(etf => etf.premium_discount_bps < 0)
            .sort((a, b) => a.premium_discount_bps - b.premium_discount_bps)
            .slice(0, n);
    },

    /**
     * Get ETFs with biggest premium
     */
    getBiggestPremiums(etfList, n = 5) {
        return [...etfList]
            .filter(etf => etf.premium_discount_bps > 0)
            .sort((a, b) => b.premium_discount_bps - a.premium_discount_bps)
            .slice(0, n);
    },

    /**
     * Get total AUM of all ETFs
     */
    getTotalAUM(etfList) {
        return etfList.reduce((sum, etf) => sum + (etf.aum_usd || 0), 0);
    },

    /**
     * Get total BTC holdings
     */
    getTotalHoldings(etfList) {
        return etfList.reduce((sum, etf) => sum + (etf.holding_quantity || 0), 0);
    },

    /**
     * Calculate average fee
     */
    getAverageFee(etfList) {
        const fees = etfList.map(etf => etf.management_fee_percent).filter(f => f > 0);
        if (fees.length === 0) return 0;
        return fees.reduce((sum, f) => sum + f, 0) / fees.length;
    },

    /**
     * Get ETFs by region
     */
    getByRegion(etfList, region) {
        return etfList.filter(etf => 
            etf.region && etf.region.toLowerCase() === region.toLowerCase()
        );
    },

    /**
     * Get market leaders (positive 24h change + high AUM)
     */
    getMarketLeaders(etfList, n = 5) {
        return [...etfList]
            .filter(etf => etf.change_percent_24h > 0 && etf.aum_usd > 1e9)
            .sort((a, b) => {
                // Score = change% * log(AUM)
                const scoreA = etf.change_percent_24h * Math.log10(etf.aum_usd);
                const scoreB = etf.change_percent_24h * Math.log10(etf.aum_usd);
                return scoreB - scoreA;
            })
            .slice(0, n);
    }
};

