/**
 * Macro Overlay API Service
 * Handles all API calls with caching and stale-while-revalidate pattern
 */

export class MacroAPIService {
    constructor() {
        this.cache = new Map();
        this.cacheTTL = 5 * 60 * 1000; // 5 minutes default
    }

    /**
     * Generic cache management
     */
    getCacheKey(endpoint, params = {}) {
        const paramStr = Object.keys(params)
            .sort()
            .map(k => `${k}=${params[k]}`)
            .join('&');
        return `${endpoint}?${paramStr}`;
    }

    getCachedData(key) {
        const cached = this.cache.get(key);
        if (!cached) return null;
        
        const age = Date.now() - cached.timestamp;
        if (age > this.cacheTTL) {
            this.cache.delete(key);
            return null;
        }
        
        return cached.data;
    }

    setCachedData(key, data, ttl = this.cacheTTL) {
        this.cache.set(key, {
            data,
            timestamp: Date.now(),
            ttl
        });
    }

    clearCache() {
        this.cache.clear();
        console.log('🗑️ Cache cleared');
    }

    /**
     * Generic fetch with cache
     */
    async fetchWithCache(endpoint, params = {}, options = {}) {
        const { preferFresh = false, ttl = this.cacheTTL } = options;
        const cacheKey = this.getCacheKey(endpoint, params);
        
        // Return cached data if available and not requesting fresh
        if (!preferFresh) {
            const cached = this.getCachedData(cacheKey);
            if (cached) {
                console.log('📦 Using cached data for:', endpoint);
                return cached;
            }
        }
        
        // Fetch fresh data
        const url = new URL(endpoint, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== undefined && params[key] !== null) {
                url.searchParams.append(key, params[key]);
            }
        });
        
        console.log('🌐 Making API request to:', url.toString());
        
        const fetchStart = Date.now();
        const response = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const fetchTime = Date.now() - fetchStart;
        console.log('📡 Response status:', response.status, `(${fetchTime}ms)`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('📊 API Response data:', data);
        
        // Cache the result
        this.setCachedData(cacheKey, data, ttl);
        console.log('💾 Caching macro data');
        
        return data;
    }

    /**
     * Fetch multiple FRED series
     */
    async fetchFredMultiSeries(options = {}) {
        const {
            seriesIds = 'DTWEXBGS,DGS10,DGS2,DFF',
            limit = 100,
            sortOrder = 'desc',
            preferFresh = false
        } = options;
        
        return this.fetchWithCache('/api/coinglass/macro-overlay/fred', {
            series_ids: seriesIds,
            limit,
            sort_order: sortOrder
        }, { preferFresh, ttl: 15 * 60 * 1000 }); // 15 min cache
    }

    /**
     * Fetch single FRED series
     */
    async fetchFredSingleSeries(seriesId, options = {}) {
        const {
            limit = 100,
            sortOrder = 'desc',
            observationStart = null,
            observationEnd = null,
            preferFresh = false
        } = options;
        
        const params = {
            limit,
            sort_order: sortOrder
        };
        
        if (observationStart) params.observation_start = observationStart;
        if (observationEnd) params.observation_end = observationEnd;
        
        return this.fetchWithCache(`/api/coinglass/macro-overlay/fred/${seriesId}`, params, {
            preferFresh,
            ttl: 15 * 60 * 1000
        });
    }

    /**
     * Fetch latest values for all major indicators
     */
    async fetchFredLatest(options = {}) {
        const { preferFresh = false } = options;
        
        return this.fetchWithCache('/api/coinglass/macro-overlay/fred-latest', {}, {
            preferFresh,
            ttl: 5 * 60 * 1000 // 5 min cache for latest data
        });
    }

    /**
     * Fetch Bitcoin vs M2 data
     * Note: Endpoint does not require any query parameters
     */
    async fetchBitcoinM2(options = {}) {
        const {
            preferFresh = false
        } = options;
        
        // No parameters needed - endpoint returns all data
        return this.fetchWithCache('/api/coinglass/macro-overlay/bitcoin-m2', {}, {
            preferFresh,
            ttl: 60 * 60 * 1000 // 1 hour cache for historical data
        });
    }
}

