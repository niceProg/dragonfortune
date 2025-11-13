/**
 * ETF Data Service
 * Handles all API calls and data transformation for ETF & Institutional dashboard
 * 
 * Following Volatility Dashboard pattern for consistency
 */

class ETFDataService {
    constructor(baseUrl = 'https://test.dragonfortune.ai') {
        this.baseUrl = baseUrl;
        this.cache = new Map();
        this.cacheTimeout = 5000; // 5 seconds cache
    }

    /**
     * Generic fetch with error handling and caching
     */
    async fetchWithCache(url, cacheKey) {
        // Check cache
        const cached = this.cache.get(cacheKey);
        if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
            console.log(`📦 ETF Cache hit: ${cacheKey}`);
            return cached.data;
        }

        try {
            console.log(`🌐 ETF Fetching: ${url}`);
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            // Cache the result
            this.cache.set(cacheKey, {
                data: data,
                timestamp: Date.now()
            });
            
            console.log(`✅ ETF Data received:`, data);
            return data;
        } catch (error) {
            console.error(`❌ ETF Fetch error for ${cacheKey}:`, error);
            throw error;
        }
    }

    /**
     * Calculate date range from period days
     * @param {number} periodDays - Number of days (30, 60, 90, 180)
     * @returns {Object} { start_date, end_date, limit }
     */
    calculateDateRange(periodDays) {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - periodDays);
        
        return {
            start_date: this.formatDate(startDate),
            end_date: this.formatDate(endDate),
            limit: periodDays
        };
    }

    /**
     * Format date to YYYY-MM-DD
     */
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Build query string from params object (omit null/undefined/'all' values)
     */
    buildQueryString(params) {
        const filtered = {};
        for (const [key, value] of Object.entries(params)) {
            if (value !== null && value !== undefined && value !== 'all' && value !== '') {
                filtered[key] = value;
            }
        }
        return new URLSearchParams(filtered).toString();
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
        console.log('🗑️ ETF Cache cleared');
    }

    /**
     * Clear specific cache entry
     */
    clearCacheEntry(key) {
        this.cache.delete(key);
        console.log(`🗑️ ETF Cache entry cleared: ${key}`);
    }

    /**
     * Fetch Spot ETF Flows
     * @param {Object} params - { issuer, ticker, start_date, end_date, limit }
     */
    async fetchSpotFlows(params) {
        const queryString = this.buildQueryString(params);
        const url = `${this.baseUrl}/api/etf-institutional/spot/daily-flows?${queryString}`;
        const cacheKey = `spot_flows_${queryString}`;
        
        try {
            return await this.fetchWithCache(url, cacheKey);
        } catch (error) {
            console.error('❌ Spot flows fetch error:', error);
            throw error;
        }
    }

    /**
     * Fetch Spot ETF Summary
     * @param {Object} params - { issuer, ticker, start_date, end_date, limit }
     */
    async fetchSpotSummary(params) {
        const queryString = this.buildQueryString(params);
        const url = `${this.baseUrl}/api/etf-institutional/spot/summary?${queryString}`;
        const cacheKey = `spot_summary_${queryString}`;
        
        try {
            return await this.fetchWithCache(url, cacheKey);
        } catch (error) {
            console.error('❌ Spot summary fetch error:', error);
            throw error;
        }
    }

    /**
     * Fetch Premium/Discount
     * @param {Object} params - { ticker, start_date, end_date, limit }
     */
    async fetchPremiumDiscount(params) {
        const queryString = this.buildQueryString(params);
        const url = `${this.baseUrl}/api/etf-institutional/spot/premium-discount?${queryString}`;
        const cacheKey = `premium_discount_${queryString}`;
        
        try {
            return await this.fetchWithCache(url, cacheKey);
        } catch (error) {
            console.error('❌ Premium/discount fetch error:', error);
            throw error;
        }
    }

    /**
     * Fetch Creations/Redemptions
     * @param {Object} params - { issuer, ticker, start_date, end_date, limit }
     */
    async fetchCreationsRedemptions(params) {
        const queryString = this.buildQueryString(params);
        const url = `${this.baseUrl}/api/etf-institutional/spot/creations-redemptions?${queryString}`;
        const cacheKey = `creations_redemptions_${queryString}`;
        
        try {
            return await this.fetchWithCache(url, cacheKey);
        } catch (error) {
            console.error('❌ Creations/redemptions fetch error:', error);
            throw error;
        }
    }

    /**
     * Fetch CME Open Interest
     * @param {Object} params - { symbol, start_date, end_date, limit }
     */
    async fetchCMEOI(params) {
        const queryString = this.buildQueryString(params);
        const url = `${this.baseUrl}/api/etf-institutional/cme/oi?${queryString}`;
        const cacheKey = `cme_oi_${queryString}`;
        
        try {
            return await this.fetchWithCache(url, cacheKey);
        } catch (error) {
            console.error('❌ CME OI fetch error:', error);
            throw error;
        }
    }

    /**
     * Fetch COT Data
     * @param {Object} params - { symbol, report_group, start_week, end_week, limit }
     */
    async fetchCOT(params) {
        const queryString = this.buildQueryString(params);
        const url = `${this.baseUrl}/api/etf-institutional/cme/cot?${queryString}`;
        const cacheKey = `cot_${queryString}`;
        
        try {
            return await this.fetchWithCache(url, cacheKey);
        } catch (error) {
            console.error('❌ COT fetch error:', error);
            throw error;
        }
    }

    /**
     * Fetch CME Summary
     * @param {Object} params - { symbol, start_date, end_date, limit }
     */
    async fetchCMESummary(params) {
        const queryString = this.buildQueryString(params);
        const url = `${this.baseUrl}/api/etf-institutional/cme/summary?${queryString}`;
        const cacheKey = `cme_summary_${queryString}`;
        
        try {
            return await this.fetchWithCache(url, cacheKey);
        } catch (error) {
            console.error('❌ CME summary fetch error:', error);
            throw error;
        }
    }

}

