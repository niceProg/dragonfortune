/**
 * Funding Rate API Service (Coinglass)
 * Based on Open Interest proven implementation
 */

export class FundingRateAPIService {
    constructor(baseUrl = '') {
        const meta = document.querySelector('meta[name="api-base-url"]');
        const resolved = (meta?.content || baseUrl || '').replace(/\/+$/, '');
        this.baseUrl = resolved || window.location.origin;
        
        // Stale-while-revalidate cache
        this.cache = new Map();
        this.CACHE_TTL = 30000; // 30s for fresher data
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
        console.log('💾 Caching funding rate data for key:', key);
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
        console.log('💾 Cache size now:', this.cache.size);
    }

    // Build URLs used as cache keys
    buildAggregatedUrl({ symbol, interval, start_time, end_time }) {
        const url = new URL(`/api/coinglass/funding-rate/history`, window.location.origin);
        const qs = new URLSearchParams({
            ...(symbol ? { symbol } : {}),
            ...(interval ? { interval } : {}),
            ...(start_time ? { start_time: String(start_time) } : {}),
            ...(end_time ? { end_time: String(end_time) } : {}),
        });
        url.search = qs.toString();
        return url;
    }

    // Return mapped cached points immediately if available
    getCachedHistoryPoints(params = {}) {
        const { symbol, interval, start_time, end_time } = params;
        const url = this.buildAggregatedUrl({ symbol, interval, start_time, end_time });
        const cacheKey = this.getCacheKey(url);
        console.log('🔍 Cache lookup - URL:', url.toString());

        const cached = this.getCachedData(cacheKey);
        console.log('🔍 Cache lookup - Result:', cached ? 'Found' : 'Not found');

        const res = cached?.data;
        if (res && res.success && Array.isArray(res.data)) {
            const points = res.data;
            return points.map(p => ({
                date: new Date(p.ts).toISOString(),
                value: p.funding_rate,
                price: null,
                symbol
            }));
        }
        return null;
    }

    // Compute default date range from interval
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

    async fetchAggregatedFundingRate(params, { preferFresh = false } = {}) {
        const { symbol, interval, start_time, end_time } = params;
        const controller = new AbortController();

        // Prefer date range over limit
        const range = (!start_time && !end_time) ? this.computeDateRange(interval) : { start_time, end_time };
        const url = new URL(`/api/coinglass/funding-rate/history`, window.location.origin);
        const qs = new URLSearchParams({
            ...(symbol ? { symbol } : {}),
            ...(interval ? { interval } : {}),
            ...(range.start_time ? { start_time: String(range.start_time) } : {}),
            ...(range.end_time ? { end_time: String(range.end_time) } : {}),
        });
        url.search = qs.toString();

        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Return strategy depends on preferFresh flag
        if (cached && cached.isStale) {
            if (preferFresh) {
                return await this.fetchFreshData(url, controller, cacheKey);
            }
            // Auto-refresh: return stale now, refresh in background
            this.fetchFreshData(url, controller, cacheKey).catch(() => { });
            return cached.data;
        } else if (cached && !cached.isStale) {
            return cached.data;
        }

        // No cache - fetch synchronously
        return await this.fetchFreshData(url, controller, cacheKey);
    }

    async fetchFreshData(url, controller, cacheKey) {
        const timeoutMs = 8000; // 8s timeout
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

    // Backward-compatible method used by controller
    async fetchHistory(params) {
        const { symbol, interval, start_time, end_time, preferFresh } = params || {};
        
        const res = await this.fetchAggregatedFundingRate(
            { symbol, interval, start_time, end_time }, 
            { preferFresh: !!preferFresh }
        );
        
        if (!res || res.success === false) return [];
        
        const points = res.data || [];
        return points.map(p => ({
            date: new Date(p.ts).toISOString(),
            value: p.funding_rate,
            price: null,
            symbol
        }));
    }

    cancelRequest() { /* no-op: per-request controllers */ }
}