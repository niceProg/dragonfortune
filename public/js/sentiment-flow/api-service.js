/**
 * Sentiment & Flow API Service
 * Handles API calls with caching and stale-while-revalidate pattern
 * Pattern: Copied from Open Interest (proven working)
 */

export class SentimentFlowAPIService {
    constructor(baseUrl = '') {
        const meta = document.querySelector('meta[name="api-base-url"]');
        const resolved = (meta?.content || baseUrl || '').replace(/\/+$/, '');
        this.baseUrl = resolved || window.location.origin;

        // Stale-while-revalidate cache
        this.cache = new Map();
        this.CACHE_TTL = 1000; // 1 second for sentiment data (real-time)
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
        console.log('💾 Caching sentiment data for key:', key);
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    /**
     * Fetch Fear & Greed Index
     */
    async fetchFearGreed({ preferFresh = false } = {}) {
        const url = new URL('/api/coinglass/sentiment/fear-greed', window.location.origin);
        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        // Stale-while-revalidate pattern
        if (cached && cached.isStale) {
            if (preferFresh) {
                return await this.fetchFreshData(url, cacheKey);
            }
            this.fetchFreshData(url, cacheKey).catch(() => {});
            return cached.data;
        } else if (cached && !cached.isStale) {
            return cached.data;
        }

        return await this.fetchFreshData(url, cacheKey);
    }

    /**
     * Fetch Funding Dominance (Funding Rate Exchange List)
     */
    async fetchFundingDominance({ symbol = 'BTC', preferFresh = false } = {}) {
        const url = new URL('/api/coinglass/sentiment/funding-dominance', window.location.origin);
        url.searchParams.set('symbol', symbol);
        
        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        if (cached && cached.isStale) {
            if (preferFresh) {
                return await this.fetchFreshData(url, cacheKey);
            }
            this.fetchFreshData(url, cacheKey).catch(() => {});
            return cached.data;
        } else if (cached && !cached.isStale) {
            return cached.data;
        }

        return await this.fetchFreshData(url, cacheKey);
    }

    /**
     * Fetch Whale Alerts
     */
    async fetchWhaleAlerts({ preferFresh = false } = {}) {
        const url = new URL('/api/coinglass/sentiment/whale-alerts', window.location.origin);
        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        if (cached && cached.isStale) {
            if (preferFresh) {
                return await this.fetchFreshData(url, cacheKey);
            }
            this.fetchFreshData(url, cacheKey).catch(() => {});
            return cached.data;
        } else if (cached && !cached.isStale) {
            return cached.data;
        }

        return await this.fetchFreshData(url, cacheKey);
    }

    /**
     * Fetch fresh data from API
     */
    async fetchFreshData(url, cacheKey) {
        const controller = new AbortController();
        const timeoutMs = 8000;
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

        try {
            const res = await fetch(url.toString(), {
                signal: controller.signal,
                headers: { 'Accept': 'application/json' }
            });
            
            clearTimeout(timeoutId);
            
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            
            const data = await res.json();
            this.setCachedData(cacheKey, data);
            return data;
        } catch (e) {
            clearTimeout(timeoutId);
            console.error('API Error:', e);
            return {
                success: false,
                error: { message: e.message }
            };
        }
    }

    /**
     * Fetch Whale Transfers (On-Chain)
     * @param {Object} options - Filters: { symbol, startTime, endTime, preferFresh }
     */
    async fetchWhaleTransfers({ symbol = 'BTC', startTime = null, endTime = null, preferFresh = false } = {}) {
        const url = new URL('/api/onchain/whale-transfers', window.location.origin);

        if (symbol) {
            url.searchParams.append('symbol', symbol);
        }
        if (startTime) {
            url.searchParams.append('start_time', startTime);
        }
        if (endTime) {
            url.searchParams.append('end_time', endTime);
        }

        const cacheKey = this.getCacheKey(url);
        const cached = this.getCachedData(cacheKey);

        console.log('🌐 Making API request to:', url.toString());

        if (cached && cached.isStale) {
            if (preferFresh) {
                return await this.fetchFreshData(url, cacheKey);
            }
            this.fetchFreshData(url, cacheKey).catch(() => {});
            return cached.data;
        } else if (cached && !cached.isStale) {
            return cached.data;
        }

        return await this.fetchFreshData(url, cacheKey);
    }

    /**
     * Clear all cached data
     */
    clearCache() {
        this.cache.clear();
        console.log('🗑️ Sentiment cache cleared');
    }
}

