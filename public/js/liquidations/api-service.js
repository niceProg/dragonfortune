/**
 * Liquidations API Service (Coinglass)
 * Blueprint: Open Interest API Service (proven stable)
 * 
 * Endpoint: /api/coinglass/liquidation/aggregated-heatmap/model3
 */

export class LiquidationsAPIService {
    constructor(baseUrl = '') {
        const meta = document.querySelector('meta[name="api-base-url"]');
        const resolved = (meta?.content || baseUrl || '').replace(/\/+$/, '');
        this.baseUrl = resolved || window.location.origin;

        // Stale-while-revalidate cache
        this.cache = new Map();
        this.CACHE_TTL = 30000; // 30 seconds for fresher data
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
     * Build heatmap URL for cache key
     */
    buildHeatmapUrl({ symbol, range }) {
        const url = new URL(`/api/coinglass/liquidation/aggregated-heatmap/model3`, window.location.origin);
        const qs = new URLSearchParams({
            ...(symbol ? { symbol } : {}),
            ...(range ? { range } : {}),
        });
        url.search = qs.toString();
        return url;
    }

    /**
     * Fetch heatmap data (Model 3)
     */
    async fetchHeatmap(params, { preferFresh = false } = {}) {
        const { symbol, range } = params;
        const controller = new AbortController();

        const url = this.buildHeatmapUrl({ symbol, range });
        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Stale-while-revalidate strategy
        if (cached && cached.isStale) {
            if (preferFresh) {
                // Fetch fresh now for user-initiated changes
                return await this.fetchFreshData(url, controller, cacheKey);
            }
            // Auto-refresh path: return stale now, refresh in background
            this.fetchFreshData(url, controller, cacheKey).catch(() => {});
            return cached.data;
        } else if (cached && !cached.isStale) {
            // Return fresh cached data
            return cached.data;
        }

        // No cache - fetch synchronously
        return await this.fetchFreshData(url, controller, cacheKey);
    }

    async fetchFreshData(url, controller, cacheKey) {
        const timeoutMs = 8000; // 8 seconds timeout
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
            return { success: false, error: { message: e.message } };
        }
    }

    /**
     * Get cached heatmap data immediately (for instant render)
     */
    getCachedHeatmap(params = {}) {
        const { symbol, range } = params;
        const url = this.buildHeatmapUrl({ symbol, range });
        const cached = this.getCachedData(this.getCacheKey(url));
        return cached?.data || null;
    }

    cancelRequest() { 
        /* no-op: per-request controllers */ 
    }
}
