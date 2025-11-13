/**
 * Long-Short Ratio API Service
 * Handles API calls to Coinglass Long-Short Ratio endpoints
 * 
 * Blueprint: Open Interest API Service (proven stable)
 * 
 * Endpoints:
 * 1. Global Account Ratio - All traders sentiment
 * 2. Top Account Ratio - Smart money sentiment
 */

export class LongShortRatioAPIService {
    constructor(baseUrl = '') {
        const meta = document.querySelector('meta[name="api-base-url"]');
        const resolved = (meta?.content || baseUrl || '').replace(/\/+$/, '');
        this.baseUrl = resolved || window.location.origin;

        // Stale-while-revalidate cache (same as Open Interest)
        this.cache = new Map();
        this.CACHE_TTL = 30000; // 30 seconds for fresh data
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
        console.log('💾 Caching LSR data for key:', key);
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
        console.log('💾 Cache size now:', this.cache.size);
    }

    /**
     * Build URL for Global Account History
     */
    buildGlobalAccountUrl({ symbol, exchange, interval, start_time, end_time }) {
        const url = new URL(`/api/coinglass/long-short-ratio/global-account/history`, window.location.origin);
        const qs = new URLSearchParams({
            ...(symbol ? { symbol } : {}),
            ...(exchange ? { exchange } : {}),
            ...(interval ? { interval } : {}),
            ...(start_time ? { start_time: String(start_time) } : {}),
            ...(end_time ? { end_time: String(end_time) } : {}),
        });
        url.search = qs.toString();
        return url;
    }

    /**
     * Build URL for Top Account History
     */
    buildTopAccountUrl({ symbol, exchange, interval, start_time, end_time }) {
        const url = new URL(`/api/coinglass/long-short-ratio/top-account/history`, window.location.origin);
        const qs = new URLSearchParams({
            ...(symbol ? { symbol } : {}),
            ...(exchange ? { exchange } : {}),
            ...(interval ? { interval } : {}),
            ...(start_time ? { start_time: String(start_time) } : {}),
            ...(end_time ? { end_time: String(end_time) } : {}),
        });
        url.search = qs.toString();
        return url;
    }

    /**
     * Fetch fresh data with timeout
     */
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
            return {
                success: false,
                error: { message: e.message }
            };
        }
    }

    /**
     * Fetch Global Account History
     */
    async fetchGlobalAccountHistory(params, { preferFresh = false } = {}) {
        const { symbol, exchange, interval, start_time, end_time } = params;
        const controller = new AbortController();

        const url = this.buildGlobalAccountUrl({ symbol, exchange, interval, start_time, end_time });
        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Stale-while-revalidate strategy
        if (cached && cached.isStale) {
            if (preferFresh) {
                // User-initiated: fetch fresh now
                return await this.fetchFreshData(url, controller, cacheKey);
            }
            // Auto-refresh: return stale, refresh in background
            this.fetchFreshData(url, controller, cacheKey).catch(() => {});
            return cached.data;
        } else if (cached && !cached.isStale) {
            // Return fresh cached data
            return cached.data;
        }

        // No cache - fetch synchronously
        return await this.fetchFreshData(url, controller, cacheKey);
    }

    /**
     * Fetch Top Account History
     */
    async fetchTopAccountHistory(params, { preferFresh = false } = {}) {
        const { symbol, exchange, interval, start_time, end_time } = params;
        const controller = new AbortController();

        const url = this.buildTopAccountUrl({ symbol, exchange, interval, start_time, end_time });
        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Stale-while-revalidate strategy
        if (cached && cached.isStale) {
            if (preferFresh) {
                // User-initiated: fetch fresh now
                return await this.fetchFreshData(url, controller, cacheKey);
            }
            // Auto-refresh: return stale, refresh in background
            this.fetchFreshData(url, controller, cacheKey).catch(() => {});
            return cached.data;
        } else if (cached && !cached.isStale) {
            // Return fresh cached data
            return cached.data;
        }

        // No cache - fetch synchronously
        return await this.fetchFreshData(url, controller, cacheKey);
    }

    /**
     * Backward-compatible method for controller
     * Fetches both Global and Top Account data
     */
    async fetchHistory(params) {
        const { symbol, exchange, interval, start_time, end_time, preferFresh, type = 'global' } = params || {};

        if (type === 'top') {
            const res = await this.fetchTopAccountHistory(
                { symbol, exchange, interval, start_time, end_time },
                { preferFresh: !!preferFresh }
            );

            if (!res || res.success === false) return [];

            const points = res.data || [];
            return points.map(p => ({
                date: new Date(p.ts).toISOString(),
                ratio: p.ratio,
                long_percent: p.long_percent,
                short_percent: p.short_percent,
                symbol,
                exchange
            }));
        }

        // Default: Global Account
        const res = await this.fetchGlobalAccountHistory(
            { symbol, exchange, interval, start_time, end_time },
            { preferFresh: !!preferFresh }
        );

        if (!res || res.success === false) return [];

        const points = res.data || [];
        return points.map(p => ({
            date: new Date(p.ts).toISOString(),
            ratio: p.ratio,
            long_percent: p.long_percent,
            short_percent: p.short_percent,
            symbol,
            exchange
        }));
    }

    cancelRequest() {
        // No-op: per-request controllers
    }
}
