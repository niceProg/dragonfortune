/**
 * Open Interest API Service
 * Handles all API requests for Open Interest data
 */

export class OpenInterestAPIService {
    constructor() {
        this.baseUrl = window.APP_CONFIG?.apiBaseUrl || '';
        
        // Separate AbortController for each request type
        this.historyAbortController = null;
        this.analyticsAbortController = null;
        this.exchangeAbortController = null;
    }

    /**
     * Fetch Open Interest history data
     */
    async fetchHistory(params) {
        const { symbol, exchange, interval, limit, with_price = true } = params;

        // Cancel previous request
        if (this.historyAbortController) {
            this.historyAbortController.abort();
        }
        this.historyAbortController = new AbortController();

        // Use limit directly (simpler and more reliable than date range filtering)
        // If limit is null or very large, use large number (API will handle it)
        const requestLimit = limit || 1000;

        // Build URL - if limit is very large (ALL), use large number
        const url = `${this.baseUrl}/api/open-interest/history?symbol=${symbol}&exchange=${exchange}&interval=${interval}&limit=${requestLimit}&with_price=${with_price}`;

        console.log('📡 Fetching OI history:', url);
        
        const startTime = Date.now();

        let timeoutId = null;
        try {
            // Add timeout (30 seconds for initial load, 10 seconds for auto-refresh)
            // API can be slow, so we need longer timeout
            const timeoutDuration = 30000; // 30 seconds
            timeoutId = setTimeout(() => {
                if (this.historyAbortController) {
                    console.warn('⏱️ Request timeout after', timeoutDuration / 1000, 'seconds');
                    this.historyAbortController.abort();
                }
            }, timeoutDuration);

            const response = await fetch(url, {
                signal: this.historyAbortController.signal
            });

            // Clear timeout if request succeeds
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            const fetchTime = Date.now() - startTime;
            console.log('✅ OI history data received:', data?.length || 0, 'records', `(${fetchTime}ms)`);

            // No date range filtering - use limit only (simpler and more reliable)
            // API already returns data in the correct order based on limit
            let filteredData = data;

            // Sort data by timestamp (oldest first) before transform
            // Backend might return data in descending order, need ascending for chart
            const sortedFilteredData = [...filteredData].sort((a, b) => {
                const tsA = a.ts || a.time || 0;
                const tsB = b.ts || b.time || 0;
                return tsA - tsB;
            });

            // Transform data efficiently
            const transformed = this.transformHistoryData(sortedFilteredData);
            const totalTime = Date.now() - startTime;
            console.log('⏱️ Total history fetch time:', totalTime + 'ms');

            return transformed;
        } catch (error) {
            // Clear timeout in case of error
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }

            if (error.name === 'AbortError') {
                console.log('⏭️ OI history request cancelled');
                return null;
            }
            console.error('❌ Error fetching OI history:', error);
            throw error;
        }
    }

    /**
     * Fetch Open Interest analytics data
     */
    async fetchAnalytics(params) {
        const { symbol, exchange, interval, limit } = params;

        // Cancel previous request
        if (this.analyticsAbortController) {
            this.analyticsAbortController.abort();
        }
        this.analyticsAbortController = new AbortController();

        const url = `${this.baseUrl}/api/open-interest/analytics?symbol=${symbol}&exchange=${exchange}&interval=${interval}&limit=${limit}`;

        console.log('📡 Fetching OI analytics:', url);
        
        const startTime = Date.now();

        let timeoutId = null;
        try {
            // Add timeout (15 seconds) to prevent hanging requests
            // Analytics endpoint might be slower than history
            const timeoutDuration = 15000; // 15 seconds
            timeoutId = setTimeout(() => {
                if (this.analyticsAbortController) {
                    console.warn('⏱️ Analytics request timeout after', timeoutDuration / 1000, 'seconds');
                    this.analyticsAbortController.abort();
                }
            }, timeoutDuration);

            const response = await fetch(url, {
                signal: this.analyticsAbortController.signal
            });

            // Clear timeout if request succeeds
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            const fetchTime = Date.now() - startTime;
            console.log('✅ OI analytics data received:', data, `(${fetchTime}ms)`);

            // Return first item if array, otherwise return as-is
            return Array.isArray(data) ? (data[0] || null) : data;
        } catch (error) {
            // Clear timeout in case of error
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }

            if (error.name === 'AbortError') {
                console.log('⏭️ OI analytics request cancelled');
                return null;
            }
            console.error('❌ Error fetching OI analytics:', error);
            return null; // Return null instead of throwing for analytics
        }
    }

    /**
     * Fetch Open Interest per exchange (for FASE 2)
     */
    async fetchExchange(params) {
        const { symbol, exchange, limit, pivot = false } = params;

        // Cancel previous request
        if (this.exchangeAbortController) {
            this.exchangeAbortController.abort();
        }
        this.exchangeAbortController = new AbortController();

        const url = `${this.baseUrl}/api/open-interest/exchange?symbol=${symbol}&exchange=${exchange}&limit=${limit}&pivot=${pivot}`;

        console.log('📡 Fetching OI exchange data:', url);

        try {
            const response = await fetch(url, {
                signal: this.exchangeAbortController.signal
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ OI exchange data received:', data?.length || 0, 'records');

            return data;
        } catch (error) {
            if (error.name === 'AbortError') {
                console.log('⏭️ OI exchange request cancelled');
                return null;
            }
            console.error('❌ Error fetching OI exchange:', error);
            return null;
        }
    }

    /**
     * Cancel all pending requests
     * Note: AbortError will be handled gracefully in fetch methods
     */
    cancelAllRequests() {
        try {
            if (this.historyAbortController) {
                this.historyAbortController.abort();
            }
            if (this.analyticsAbortController) {
                this.analyticsAbortController.abort();
            }
            if (this.exchangeAbortController) {
                this.exchangeAbortController.abort();
            }
        } catch (error) {
            // Ignore errors from abort (expected behavior)
            if (error.name !== 'AbortError') {
                console.warn('⚠️ Error canceling requests:', error);
            }
        }
    }

    /**
     * Transform history data from API format to chart format
     */
    transformHistoryData(data) {
        if (!Array.isArray(data)) {
            console.warn('⚠️ History data is not an array');
            return [];
        }

        // Transform data from API format
        // API returns: { ts: 1762208100000 (milliseconds), oi_usd: "8230487962.44420000", price: "0E-8", ... }
        const transformed = data.map(item => {
            const ts = item.ts || item.time || 0;
            // Timestamp from API is already in milliseconds (verified via curl test)
            // Only convert if it's in seconds (timestamp < 1e12 means seconds)
            const timestampMs = ts < 1e12 ? ts * 1000 : ts;
            
            // Parse price - handle "0E-8" as 0 (no price data)
            let priceValue = null;
            if (item.price && item.price !== "0E-8" && parseFloat(item.price) > 0) {
                priceValue = parseFloat(item.price);
            }
            
            return {
                ts: timestampMs,
                oi_usd: parseFloat(item.oi_usd || item.open_interest || 0),
                price: priceValue,
                exchange: item.exchange
            };
        });

        // Sort by timestamp ascending
        transformed.sort((a, b) => a.ts - b.ts);

        console.log('📊 Transformed history data:', {
            count: transformed.length,
            first: transformed[0],
            last: transformed[transformed.length - 1]
        });

        return transformed;
    }

    /**
     * COMMENTED OUT: Filter data by date range (client-side) - Not used anymore
     * Using limit-based approach instead (simpler and more reliable)
     * Kept for reference only
     */
    // filterByDateRange(data, startDate, endDate) {
    //     if (!Array.isArray(data) || data.length === 0) return data;
    //
    //     const startTs = startDate.getTime();
    //     const endTs = endDate.getTime();
    //
    //     // Optimized filter - single pass, early exit conditions
    //     const filtered = [];
    //     for (let i = 0; i < data.length; i++) {
    //         const item = data[i];
    //         const ts = item.ts || item.time || 0;
    //         
    //         // Early exit if we're past the end date (data should be sorted by timestamp)
    //         if (ts > endTs) break;
    //         
    //         if (ts >= startTs && ts <= endTs) {
    //             filtered.push(item);
    //         }
    //     }
    //
    //     console.log('📅 Date Range Filter:', {
    //         startDate: startDate.toISOString(),
    //         endDate: endDate.toISOString(),
    //         beforeFilter: data.length,
    //         afterFilter: filtered.length,
    //         filteredPercent: ((filtered.length / data.length) * 100).toFixed(1) + '%'
    //     });
    //
    //     return filtered;
    // }
}

