export class VolatilityAPIService {
    constructor(baseUrl = '') {
        const meta = document.querySelector('meta[name="api-base-url"]');
        const resolved = (meta?.content || baseUrl || '').replace(/\/+$/, '');
        this.baseUrl = resolved || window.location.origin;
        
        // Stale-while-revalidate cache
        this.cache = new Map();
        this.pendingRequests = new Map();
        this.CACHE_TTL = 30000; // 30 seconds default for volatility data (more frequent updates)
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
        console.log('💾 Caching volatility data');
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    /**
     * Fetch Spot Price History (OHLC)
     * 
     * @param {Object} params
     * @param {string} params.exchange - Exchange name (default: "Binance")
     * @param {string} params.symbol - Trading pair (default: "BTCUSDT")
     * @param {string} params.interval - Time interval (default: "1h")
     * @param {number} params.start_time - Start timestamp in ms (optional)
     * @param {number} params.end_time - End timestamp in ms (optional)
     * @param {boolean} params.preferFresh - Force fresh fetch
     */
    async fetchPriceHistory({
        exchange = 'Binance',
        symbol = 'BTCUSDT',
        interval = '1h',
        start_time = null,
        end_time = null,
        preferFresh = false
    } = {}) {
        const params = new URLSearchParams({
            exchange,
            symbol,
            interval
        });

        if (start_time) params.append('start_time', start_time);
        if (end_time) params.append('end_time', end_time);

        const url = `/api/coinglass/volatility/price-history?${params.toString()}`;
        const cacheKey = `price_history_${exchange}_${symbol}_${interval}_${start_time || 'auto'}_${end_time || 'auto'}`;
        
        // Dynamic TTL based on interval
        const ttl = this.getIntervalCacheTtl(interval);
        
        return await this._fetchWithCache(cacheKey, url, preferFresh, ttl);
    }

    /**
     * Fetch End-of-Day data (for ATR/HV/RV calculations)
     * 
     * @param {Object} params
     * @param {string} params.exchange - Exchange name (default: "Binance")
     * @param {string} params.symbol - Trading pair (default: "BTCUSDT")
     * @param {number} params.days - Number of days (default: 30)
     * @param {boolean} params.preferFresh - Force fresh fetch
     */
    async fetchEodData({
        exchange = 'Binance',
        symbol = 'BTCUSDT',
        days = 30,
        preferFresh = false
    } = {}) {
        const params = new URLSearchParams({
            exchange,
            symbol,
            days: days.toString()
        });

        const url = `/api/coinglass/volatility/eod?${params.toString()}`;
        const cacheKey = `eod_${exchange}_${symbol}_${days}`;
        
        return await this._fetchWithCache(cacheKey, url, preferFresh, 60000); // 1 minute cache for EOD
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
            console.error('Volatility API Error:', error);
            throw error;
        }
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
        console.log('Volatility API cache cleared');
    }

    /**
     * Get optimal cache TTL based on interval
     */
    getIntervalCacheTtl(interval) {
        const ttlMap = {
            '1m': 5000,      // 5 seconds
            '3m': 5000,      // 5 seconds
            '5m': 10000,     // 10 seconds
            '15m': 10000,    // 10 seconds
            '30m': 10000,    // 10 seconds
            '1h': 30000,     // 30 seconds
            '4h': 30000,     // 30 seconds
            '6h': 60000,     // 1 minute
            '8h': 60000,     // 1 minute
            '12h': 60000,    // 1 minute
            '1d': 300000,    // 5 minutes
            '1w': 900000,    // 15 minutes
        };

        return ttlMap[interval] || 30000; // Default 30 seconds
    }
}

