/**
 * Liquidations Aggregated API Service
 * Pattern copied from Open Interest (uses Laravel proxy)
 */

export class LiquidationsAggregatedAPIService {
    constructor(baseUrl = '') {
        const meta = document.querySelector('meta[name="api-base-url"]');
        const resolved = (meta?.content || baseUrl || '').replace(/\/+$/, '');
        this.baseUrl = resolved || window.location.origin;
        
        // Stale-while-revalidate cache
        this.cache = new Map();
        this.CACHE_TTL = 30000; // 30 seconds
    }

    getCacheKey(url) {
        return url.toString();
    }

    getCachedData(key) {
        const cached = this.cache.get(key);
        if (!cached) return null;

        const now = Date.now();
        const age = now - cached.timestamp;

        return {
            data: cached.data,
            isStale: age > this.CACHE_TTL
        };
    }

    setCachedData(key, data) {
        console.log('💾 Caching liquidations data for key:', key);
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    /**
     * Fetch aggregated liquidation history (via Laravel proxy)
     */
    async fetchAggregatedHistory(params) {
        const { exchange_list, symbol, interval, start_time, end_time } = params;
        const controller = new AbortController();
        
        const url = new URL(`/api/coinglass/liquidation/aggregated-history`, window.location.origin);
        const qs = new URLSearchParams({
            ...(exchange_list ? { exchange_list } : {}),
            ...(symbol ? { symbol } : {}),
            ...(interval ? { interval } : {}),
            ...(start_time ? { start_time: String(start_time) } : {}),
            ...(end_time ? { end_time: String(end_time) } : {}),
        });
        url.search = qs.toString();

        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Return cached if available and fresh
        if (cached && !cached.isStale) {
            console.log('💾 Using cached liquidations data');
            return cached.data;
        }

        // Fetch fresh data
        return await this.fetchFreshData(url, controller, cacheKey);
    }

    async fetchFreshData(url, controller, cacheKey) {
        const timeoutMs = 8000;
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
        
        try {
            const res = await fetch(url.toString(), { 
                signal: controller.signal, 
                headers: { 'Accept': 'application/json' } 
            });
            
            clearTimeout(timeoutId);
            
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const data = await res.json();
            this.setCachedData(cacheKey, data);
            
            return data;
        } catch (e) {
            clearTimeout(timeoutId);
            return { 
                success: false, 
                error: { message: e.message } 
            };
        }
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
        console.log('🗑️ Cache cleared');
    }
}
