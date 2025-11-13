export class OpenInterestAPIService {
    constructor(baseUrl = '') {
        const meta = document.querySelector('meta[name="api-base-url"]');
        const resolved = (meta?.content || baseUrl || '').replace(/\/+$/, '');
        this.baseUrl = resolved || window.location.origin;
        // Use per-request AbortControllers to allow concurrent prefetches

        // Stale-while-revalidate cache
        this.cache = new Map();
        this.CACHE_TTL = 30000; // ⚡ Reduced from 60s to 30s for fresher data
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
        console.log('💾 Caching data for key:', key);
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
        console.log('💾 Cache size now:', this.cache.size);
    }

    // Build URLs used as cache keys (must match fetch methods)
    buildAggregatedUrl({ symbol, interval, start_time, end_time, unit }) {
        const url = new URL(`/api/coinglass/open-interest/history`, window.location.origin);
        const qs = new URLSearchParams({
            ...(symbol ? { symbol } : {}),
            ...(interval ? { interval } : {}),
            ...(start_time ? { start_time: String(start_time) } : {}),
            ...(end_time ? { end_time: String(end_time) } : {}),
            ...(unit ? { unit } : {}), // ⚡ FIXED: Include unit in cache key
        });
        url.search = qs.toString();
        return url;
    }

    buildExchangeUrl({ symbol, exchange, interval, start_time, end_time }) {
        const url = new URL(`/api/coinglass/open-interest/exchange-history`, window.location.origin);
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

    // Return mapped cached points immediately if available (for instant render)
    getCachedHistoryPoints(params = {}) {
        const { symbol, exchange, interval, start_time, end_time, unit } = params; // ⚡ FIXED: Include unit
        if (exchange) {
            const url = this.buildExchangeUrl({ symbol, exchange, interval, start_time, end_time });
            const cached = this.getCachedData(this.getCacheKey(url));
            const res = cached?.data;
            if (res && res.success && Array.isArray(res.data)) {
                const points = res.data;
                return points.map(p => ({
                    date: new Date(p.ts).toISOString(),
                    value: p.oi_value,
                    price: null,
                    exchange: p.exchange || exchange,
                    symbol: p.symbol || symbol
                }));
            }
            return null;
        }
        const url = this.buildAggregatedUrl({ symbol, interval, start_time, end_time, unit }); // ⚡ FIXED: Include unit
        const cacheKey = this.getCacheKey(url);
        console.log('🔍 Cache lookup - URL:', url.toString());
        console.log('🔍 Cache lookup - Key:', cacheKey);

        const cached = this.getCachedData(cacheKey);
        console.log('🔍 Cache lookup - Result:', cached ? 'Found' : 'Not found');

        const res = cached?.data;
        if (res && res.success && Array.isArray(res.data)) {
            const points = res.data;
            return points.map(p => ({
                date: new Date(p.ts).toISOString(),
                value: p.oi_total,
                price: null,
                symbol
            }));
        }
        return null;
    }

    // Compute a default date range (ms epoch) from interval when start/end not provided
    computeDateRange(interval) {
        const now = Date.now();
        const dayMs = 24 * 60 * 60 * 1000;
        const windowDaysByInterval = {
            '1m': 1,
            '5m': 2,
            '15m': 7,
            '1h': 30,
            '4h': 90,
            '8h': 180,
            '1w': 365
        };
        const days = windowDaysByInterval[interval] || 30;
        return { start_time: now - days * dayMs, end_time: now };
    }

    async fetchAggregatedOI(params, { preferFresh = false } = {}) {
        const { symbol, interval, limit, start_time, end_time, unit } = params;
        const controller = new AbortController();

        // Prefer date range over limit
        const range = (!start_time && !end_time) ? this.computeDateRange(interval) : { start_time, end_time };
        const url = new URL(`/api/coinglass/open-interest/history`, window.location.origin);
        const qs = new URLSearchParams({
            ...(symbol ? { symbol } : {}),
            ...(interval ? { interval } : {}),
            ...(range.start_time ? { start_time: String(range.start_time) } : {}),
            ...(range.end_time ? { end_time: String(range.end_time) } : {}),
            ...(unit ? { unit } : {}),
        });
        url.search = qs.toString();

        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Return strategy depends on preferFresh flag
        if (cached && cached.isStale) {
            if (preferFresh) {
                // Fetch fresh now for user-initiated changes
                return await this.fetchFreshData(url, controller, cacheKey);
            }
            // Auto-refresh path: return stale now, refresh in background
            this.fetchFreshData(url, controller, cacheKey).catch(() => { });
            return cached.data;
        } else if (cached && !cached.isStale) {
            // Return fresh cached data
            return cached.data;
        }

        // No cache - fetch synchronously
        return await this.fetchFreshData(url, controller, cacheKey);
    }

    async fetchFreshData(url, controller, cacheKey) {
        const timeoutMs = 8000; // ⚡ Reduced from 15s to 8s for faster failure
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
        try {
            const res = await fetch(url.toString(), { signal: controller.signal, headers: { 'Accept': 'application/json' } });
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

    async fetchExchangeOI(params, { preferFresh = false } = {}) {
        const { symbol, exchange, interval, limit, start_time, end_time } = params;
        const controller = new AbortController();

        const range = (!start_time && !end_time) ? this.computeDateRange(interval) : { start_time, end_time };
        const url = new URL(`/api/coinglass/open-interest/exchange-history`, window.location.origin);
        const qs = new URLSearchParams({
            ...(symbol ? { symbol } : {}),
            ...(exchange ? { exchange } : {}),
            ...(interval ? { interval } : {}),
            ...(range.start_time ? { start_time: String(range.start_time) } : {}),
            ...(range.end_time ? { end_time: String(range.end_time) } : {}),
        });
        url.search = qs.toString();

        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Stale-while-revalidate / preferFresh behavior
        if (cached && cached.isStale) {
            if (preferFresh) {
                return await this.fetchFreshData(url, controller, cacheKey);
            }
            this.fetchFreshData(url, controller, cacheKey).catch(() => { });
            return cached.data;
        } else if (cached && !cached.isStale) {
            return cached.data;
        }

        return await this.fetchFreshData(url, controller, cacheKey);
    }

    async fetchExchangeList() {
        const controller = new AbortController();

        const url = new URL(`/api/coinglass/open-interest/exchanges`, window.location.origin);
        const timeoutMs = 10000;
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
        try {
            const res = await fetch(url.toString(), { signal: controller.signal, headers: { 'Accept': 'application/json' } });
            clearTimeout(timeoutId);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            clearTimeout(timeoutId);
            return { success: false, error: { message: e.message } };
        }
    }

    // Backward-compatible method used by controller.js
    // Signature: fetchHistory(params, noTimeout = false, useTimeout = true)
    async fetchHistory(params /*, noTimeout, useTimeout */) {
        const { symbol, exchange, interval, limit, start_time, end_time, unit, preferFresh } = params || {};
        if (exchange) {
            const res = await this.fetchExchangeOI({ symbol, exchange, interval, start_time, end_time }, { preferFresh: !!preferFresh });
            if (!res || res.success === false) return [];
            const points = res.data || [];
            // Map to controller-expected shape
            return points.map(p => ({
                date: new Date(p.ts).toISOString(),
                value: p.oi_value,
                price: null,
                exchange: p.exchange || exchange,
                symbol: p.symbol || symbol
            }));
        } else {
            const res = await this.fetchAggregatedOI({ symbol, interval, start_time, end_time, unit }, { preferFresh: !!preferFresh });
            if (!res || res.success === false) return [];
            const points = res.data || [];
            return points.map(p => ({
                date: new Date(p.ts).toISOString(),
                value: p.oi_total,
                price: null,
                symbol
            }));
        }
    }

    cancelRequest() { /* no-op: per-request controllers */ }
}
