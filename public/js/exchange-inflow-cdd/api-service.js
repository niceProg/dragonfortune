export class ExchangeInflowCDDAPIService {
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
        console.log('💾 Caching CDD data for key:', key);
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    // Compute date range for CDD (default 30 days)
    computeDateRange(days = 30) {
        const now = new Date();
        const end_date = now.toISOString().split('T')[0];
        const start = new Date(now);
        start.setDate(start.getDate() - days);
        const start_date = start.toISOString().split('T')[0];
        return { start_date, end_date };
    }

    async fetchFreshData(url, controller, cacheKey) {
        const timeoutMs = 8000;
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

    async fetchCDDHistory(params, { preferFresh = false } = {}) {
        const { exchange, interval, start_date, end_date } = params;
        const controller = new AbortController();

        const url = new URL(`/api/cryptoquant/exchange-inflow-cdd`, window.location.origin);
        const qs = new URLSearchParams({
            ...(exchange ? { exchange } : {}),
            ...(interval ? { interval } : {}),
            ...(start_date ? { start_date } : {}),
            ...(end_date ? { end_date } : {}),
        });
        url.search = qs.toString();

        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Stale-while-revalidate
        if (cached && cached.isStale) {
            if (preferFresh) {
                return await this.fetchFreshData(url, controller, cacheKey);
            }
            this.fetchFreshData(url, controller, cacheKey).catch(() => {});
            return cached.data;
        } else if (cached && !cached.isStale) {
            return cached.data;
        }

        return await this.fetchFreshData(url, controller, cacheKey);
    }

    // Main method
    async fetchHistory(params) {
        const { exchange, interval, start_date, end_date, preferFresh } = params || {};
        
        const res = await this.fetchCDDHistory(
            { exchange, interval, start_date, end_date }, 
            { preferFresh: !!preferFresh }
        );
        
        if (!res || res.success === false) {
            return { data: [], metrics: null };
        }
        
        const points = res.data || [];
        const mappedData = points.map(p => ({
            date: p.date,
            value: parseFloat(p.value),
            exchange: exchange
        }));
        
        // Return full response with data and metrics (if available from backend)
        return {
            data: mappedData,
            metrics: res.metrics || null // Include backend-calculated metrics
        };
    }

    cancelRequest() { /* no-op: per-request controllers */ }
}

