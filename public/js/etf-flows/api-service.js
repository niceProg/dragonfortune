export class EtfFlowsAPIService {
    constructor(baseUrl = '') {
        const meta = document.querySelector('meta[name="api-base-url"]');
        const resolved = (meta?.content || baseUrl || '').replace(/\/+$/, '');
        this.baseUrl = resolved || window.location.origin;
        
        // Stale-while-revalidate cache
        this.cache = new Map();
        this.pendingRequests = new Map();
        this.CACHE_TTL = 1800000; // 30 minutes for ETF data (daily updates)
    }

    getCachedData(key) {
        const cached = this.cache.get(key);
        if (!cached) return null;

        return {
            data: cached.data,
            timestamp: cached.timestamp
        };
    }

    setCachedData(key, data) {
        console.log('💾 Caching ETF flow data');
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    /**
     * Fetch ETF flow history data (Daily aggregated flows)
     */
    async fetchFlowHistory({ preferFresh = false } = {}) {
        const cacheKey = 'etf_flows_history';
        return await this._fetchWithCache(cacheKey, '/api/coinglass/etf-flows/history', preferFresh);
    }

    /**
     * Fetch ETF List (Real-time comparison data)
     */
    async fetchEtfList({ preferFresh = false } = {}) {
        const cacheKey = 'etf_list';
        return await this._fetchWithCache(cacheKey, '/api/coinglass/etf-flows/list', preferFresh, 300000); // 5 min cache
    }

    /**
     * Fetch Premium/Discount History (Per ETF)
     */
    async fetchPremiumDiscount(ticker = 'GBTC', { preferFresh = false } = {}) {
        const cacheKey = `premium_discount_${ticker}`;
        return await this._fetchWithCache(cacheKey, `/api/coinglass/etf-flows/premium-discount?ticker=${ticker}`, preferFresh, 900000); // 15 min cache
    }

    /**
     * Fetch Flow Breakdown (Per ETF aggregated)
     */
    async fetchFlowBreakdown({ preferFresh = false } = {}) {
        const cacheKey = 'etf_flow_breakdown';
        return await this._fetchWithCache(cacheKey, '/api/coinglass/etf-flows/breakdown', preferFresh);
    }

    /**
     * Generic fetch with cache (reusable)
     */
    async _fetchWithCache(cacheKey, url, preferFresh = false, customTTL = null) {
        const ttl = customTTL || this.CACHE_TTL;
        const cached = this.getCachedData(cacheKey);
        
        // Stale-while-revalidate strategy
        if (cached) {
            const age = Date.now() - cached.timestamp;
            const isStale = age > ttl;
            
            if (isStale) {
            if (preferFresh) {
                // User-initiated: fetch fresh now
                    return await this._fetchFreshData(cacheKey, url);
            }
            // Auto-refresh: return stale, refresh in background
                this._fetchFreshData(cacheKey, url).catch(() => {});
            return cached.data;
            } else {
            // Return fresh cached data
            return cached.data;
            }
        }

        // No cache - fetch synchronously
        return await this._fetchFreshData(cacheKey, url);
    }

    /**
     * Fetch fresh data (generic)
     */
    async _fetchFreshData(cacheKey, url) {
        // Check if request is already pending
        if (this.pendingRequests.has(cacheKey)) {
            return this.pendingRequests.get(cacheKey);
        }

        const requestPromise = this._makeRequest(url);
        this.pendingRequests.set(cacheKey, requestPromise);

        try {
            const data = await requestPromise;
            this.setCachedData(cacheKey, data);
            return data;
        } finally {
            this.pendingRequests.delete(cacheKey);
        }
    }

    /**
     * Legacy method for backward compatibility
     */
    async fetchFreshFlowData(cacheKey) {
        return await this._fetchFreshData(cacheKey, '/api/coinglass/etf-flows/history');
    }

    /**
     * Make HTTP request with error handling
     */
    async _makeRequest(url) {
        try {
            console.log('🌐 Making API request to:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            console.log('📡 Response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('📊 API Response data:', data);

            if (!data.success) {
                throw new Error(data.error || 'API request failed');
            }

            return data;

        } catch (error) {
            console.error('ETF Flows API Error:', error);
            throw error;
        }
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
        console.log('ETF Flows API cache cleared');
    }
}
