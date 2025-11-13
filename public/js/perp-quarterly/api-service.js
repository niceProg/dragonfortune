/**
 * Perp-Quarterly Spread API Service
 * Handles all data fetching from internal API
 */

export class PerpQuarterlyAPIService {
    constructor() {
        this.baseUrl = window.APP_CONFIG?.apiBaseUrl || 'https://test.dragonfortune.ai';
        this.abortController = null; // For history
        this.analyticsAbortController = null; // For analytics
        
        console.log('📡 Perp-Quarterly API Service initialized with base URL:', this.baseUrl);
    }

    /**
     * Fetch historical perp-quarterly spread data
     */
    async fetchHistory(params) {
        const { symbol, exchange, interval, limit, dateRange } = params;
        
        // Abort previous request if exists
        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();

        // Calculate limit: use dateRange if provided, otherwise use limit param
        let requestLimit = limit;
        if (dateRange && dateRange.startDate && dateRange.endDate) {
            // Request large limit to ensure we cover the entire date range
            requestLimit = 5000;
        }

        const url = `${this.baseUrl}/api/perp-quarterly/history?` +
            `symbol=${symbol}&` +
            `exchange=${exchange}&` +
            `interval=${interval}&` +
            `limit=${requestLimit}`;

        console.log('📡 Fetching perp-quarterly spread data:', url);
        
        const startTime = Date.now();
        if (dateRange) {
            console.log('📅 Date Range Filter:', {
                startDate: dateRange.startDate.toISOString(),
                endDate: dateRange.endDate.toISOString()
            });
        }

        let timeoutId = null;
        try {
            // Add timeout (30 seconds) to prevent hanging requests
            const timeoutDuration = 30000; // 30 seconds
            timeoutId = setTimeout(() => {
                if (this.abortController) {
                    console.warn('⏱️ History request timeout after', timeoutDuration / 1000, 'seconds');
                    this.abortController.abort();
                }
            }, timeoutDuration);

            const response = await fetch(url, {
                signal: this.abortController.signal,
                headers: {
                    'Accept': 'application/json'
                }
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
            console.log('✅ Perp-quarterly spread data received:', data.length, 'records from API', `(${fetchTime}ms)`);
            
            // Check if we have historical data
            if (data.length > 0) {
                // Get first and last timestamps from raw API
                const firstTs = data[0].ts;
                const lastTs = data[data.length - 1].ts;
                
                // Convert to readable dates (API returns seconds)
                const firstDate = new Date(firstTs * 1000);
                const lastDate = new Date(lastTs * 1000);
                
                console.log('📊 Raw API Data Range:', {
                    firstRecord: {
                        ts: firstTs,
                        tsType: typeof firstTs,
                        date: firstDate.toISOString(),
                        readable: firstDate.toLocaleString()
                    },
                    lastRecord: {
                        ts: lastTs,
                        date: lastDate.toISOString(),
                        readable: lastDate.toLocaleString()
                    },
                    timeSpan: {
                        seconds: lastTs - firstTs,
                        hours: (lastTs - firstTs) / 3600,
                        days: (lastTs - firstTs) / (3600 * 24)
                    }
                });
                
                console.log('📊 Raw API sample (first 3):', 
                    data.slice(0, 3).map(item => ({
                        ts: item.ts,
                        date: new Date(item.ts * 1000).toISOString(),
                        spread: item.spread,
                        perp_price: item.perp_price,
                        quarterly_price: item.quarterly_price
                    }))
                );
                
                if (data.length === 1) {
                    console.warn('⚠️ API returned only 1 record! Historical data may not be available.');
                    console.warn('⚠️ Check if API endpoint supports historical data for perp-quarterly spreads.');
                }
            } else {
                console.warn('⚠️ API returned 0 records! Check API endpoint and parameters.');
            }
            
            // Transform data first (same pattern as funding-rate)
            const transformed = this.transformHistoryData(data);
            
            // Aggregate data: Group by timestamp and calculate average spread
            // Multiple records per timestamp represent different quarterly contracts
            const aggregated = this.aggregateByTimestamp(transformed);
            console.log(`📊 Aggregated ${transformed.length} records → ${aggregated.length} timestamps`);
            
            // Check if all data has same timestamp (indicates API only has recent data)
            if (aggregated.length === 1 && transformed.length > 1) {
                console.warn('⚠️ WARNING: All records have the same timestamp after aggregation!');
                console.warn('⚠️ This indicates the API only returns recent data (not historical).');
                console.warn('⚠️ Sample timestamps from transformed data:', 
                    transformed.slice(0, 5).map(item => ({
                        ts: item.ts,
                        date: new Date(item.ts).toISOString(),
                        readable: new Date(item.ts).toLocaleString()
                    }))
                );
            }
            
            // Filter by date range if provided (same pattern as funding-rate)
            let filteredData = aggregated;
            if (dateRange && dateRange.startDate && dateRange.endDate) {
                const beforeDateFilter = filteredData.length;
                const startTs = dateRange.startDate.getTime();
                const endTs = dateRange.endDate.getTime();
                
                console.log('📅 Date Range Filter Details:', {
                    startDate: dateRange.startDate.toISOString(),
                    endDate: dateRange.endDate.toISOString(),
                    startTs: startTs,
                    endTs: endTs,
                    beforeFilter: beforeDateFilter
                });
                
                // Show sample timestamps from aggregated data
                if (filteredData.length > 0) {
                    console.log('📊 Sample timestamps (first 3):', 
                        filteredData.slice(0, 3).map(item => ({
                            ts: item.ts,
                            date: new Date(item.ts).toISOString(),
                            inRange: item.ts >= startTs && item.ts <= endTs
                        }))
                    );
                    if (filteredData.length > 3) {
                        console.log('📊 Sample timestamps (last 3):', 
                            filteredData.slice(-3).map(item => ({
                                ts: item.ts,
                                date: new Date(item.ts).toISOString(),
                                inRange: item.ts >= startTs && item.ts <= endTs
                            }))
                        );
                    }
                }
                
                filteredData = this.filterByDateRange(filteredData, dateRange.startDate, dateRange.endDate);
                
                console.log(`📅 Date Range Filter Result: ${beforeDateFilter} → ${filteredData.length} records`);
                
                if (filteredData.length === 0 && beforeDateFilter > 0) {
                    console.warn('⚠️ All data filtered out! Check timestamp conversion and date range.');
                }
            }
            
            // Sort filtered data by timestamp (oldest first) before return
            // Backend might return data in descending order, need ascending for chart
            const sortedFilteredData = [...filteredData].sort((a, b) => a.ts - b.ts);
            
            // Debug: Show transformation samples (same pattern as funding-rate)
            if (sortedFilteredData.length > 0) {
                console.log('📊 API Response Sample:', {
                    ts: data[0].ts,
                    spread: data[0].spread,
                    raw_timestamp: new Date(data[0].ts * 1000).toISOString() // Convert seconds to date
                });
                console.log('✨ Transformed Sample:', {
                    date: sortedFilteredData[0].date,
                    spread: sortedFilteredData[0].spread,
                    perpPrice: sortedFilteredData[0].perpPrice,
                    quarterlyPrice: sortedFilteredData[0].quarterlyPrice
                });
                
                // Show date range
                if (sortedFilteredData.length > 1) {
                    console.log('📅 Data Range:', {
                        from: new Date(sortedFilteredData[0].date).toLocaleString(),
                        to: new Date(sortedFilteredData[sortedFilteredData.length - 1].date).toLocaleString(),
                        count: sortedFilteredData.length
                    });
                }
            }
            
            return sortedFilteredData;
        } catch (error) {
            // Clear timeout in case of error
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
            
            if (error.name === 'AbortError') {
                console.log('🛑 History request aborted');
                return null;
            }
            console.error('❌ Error fetching perp-quarterly spread history:', error);
            throw error;
        }
    }

    /**
     * Fetch analytics data
     */
    async fetchAnalytics(symbol, exchange, interval, limit) {
        // Abort previous request if exists
        if (this.analyticsAbortController) {
            this.analyticsAbortController.abort();
        }
        this.analyticsAbortController = new AbortController();

        const url = `${this.baseUrl}/api/perp-quarterly/analytics?` +
            `symbol=${symbol}&` +
            `exchange=${exchange}&` +
            `interval=${interval}&` +
            `limit=${limit}`;

        console.log('📡 Fetching perp-quarterly analytics:', url);
        
        const startTime = Date.now();
        let timeoutId = null;

        try {
            // Add timeout (15 seconds) to prevent hanging requests
            const timeoutDuration = 15000; // 15 seconds
            timeoutId = setTimeout(() => {
                if (this.analyticsAbortController) {
                    console.warn('⏱️ Analytics request timeout after', timeoutDuration / 1000, 'seconds');
                    this.analyticsAbortController.abort();
                }
            }, timeoutDuration);

            const response = await fetch(url, {
                signal: this.analyticsAbortController.signal,
                headers: {
                    'Accept': 'application/json'
                }
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
            console.log('✅ Analytics data received:', data, `(${fetchTime}ms)`);
            
            // API returns array, get first item
            return data && data.length > 0 ? data[0] : null;
        } catch (error) {
            // Clear timeout in case of error
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
            
            if (error.name === 'AbortError') {
                console.log('🛑 Analytics request aborted');
                return null;
            }
            console.error('❌ Error fetching analytics:', error);
            throw error;
        }
    }

    /**
     * Transform history data from API format to chart format
     * Following the same pattern as funding-rate api-service.js
     * 
     * FROM (API Response):
     * [{
     *   "ts": 1762004469,  // Unix timestamp in SECONDS
     *   "perp_price": "109850.0000000000",
     *   "quarterly_price": "110158.0000000000",
     *   "spread": "-308.0000000000",
     *   "spread_bps": "-27.95983950325896",
     *   "exchange": "Bybit"
     * }]
     * 
     * TO (Controller format):
     * [{
     *   "ts": 1762004469000,  // Converted to milliseconds for JavaScript Date
     *   "date": "2025-11-01T13:41:09.000Z",  // ISO string for chart labels
     *   "perpPrice": 109850.0,
     *   "quarterlyPrice": 110158.0,
     *   "spread": -308.0,
     *   "spreadBps": -27.96,
     *   "value": -308.0  // For chart display compatibility
     * }]
     */
    transformHistoryData(data) {
        if (!Array.isArray(data)) {
            throw new Error('Invalid data format: expected array');
        }

        return data.map(item => {
            // Parse timestamp - API returns SECONDS, convert to MILLISECONDS
            const tsSeconds = parseInt(item.ts) || 0;
            const ts = tsSeconds * 1000; // Convert to milliseconds for JavaScript Date
            
            const perpPrice = parseFloat(item.perp_price) || 0;
            const quarterlyPrice = parseFloat(item.quarterly_price) || 0;
            const spread = parseFloat(item.spread) || 0;
            const spreadBps = parseFloat(item.spread_bps) || 0;

            return {
                ts: ts, // Store in milliseconds (for date filtering)
                date: new Date(ts).toISOString(), // ISO string for chart labels
                perpPrice: perpPrice,
                quarterlyPrice: quarterlyPrice,
                spread: spread,
                spreadBps: spreadBps,
                value: spread, // For chart display compatibility
                // For candlestick chart (using spread as OHLC if needed later)
                open: spread,
                high: spread,
                low: spread,
                close: spread
            };
        });
    }

    /**
     * Aggregate data by timestamp (multiple quarterly contracts per timestamp)
     * Calculate average spread, average prices per timestamp
     * 
     * @param {Array} data - Transformed data
     * @returns {Array} - Aggregated data with one record per timestamp
     */
    aggregateByTimestamp(data) {
        if (!Array.isArray(data) || data.length === 0) {
            return [];
        }
        
        // Group by timestamp
        const grouped = {};
        data.forEach(item => {
            const ts = item.ts;
            if (!grouped[ts]) {
                grouped[ts] = [];
            }
            grouped[ts].push(item);
        });
        
        // Calculate averages per timestamp
        return Object.keys(grouped).map(ts => {
            const group = grouped[ts];
            const tsNum = parseInt(ts);
            
            // Calculate averages
            const spreads = group.map(d => parseFloat(d.spread) || 0);
            const perpPrices = group.map(d => parseFloat(d.perpPrice) || 0);
            const quarterlyPrices = group.map(d => parseFloat(d.quarterlyPrice) || 0);
            const spreadBps = group.map(d => parseFloat(d.spreadBps) || 0);
            
            const avgSpread = spreads.reduce((a, b) => a + b, 0) / spreads.length;
            const avgPerpPrice = perpPrices.reduce((a, b) => a + b, 0) / perpPrices.length;
            const avgQuarterlyPrice = quarterlyPrices.reduce((a, b) => a + b, 0) / quarterlyPrices.length;
            const avgSpreadBps = spreadBps.reduce((a, b) => a + b, 0) / spreadBps.length;
            
            // Calculate range for reference
            const minSpread = Math.min(...spreads);
            const maxSpread = Math.max(...spreads);
            
            return {
                ts: tsNum,
                date: group[0].date, // ISO string from first item
                spread: avgSpread,
                spreadBps: avgSpreadBps,
                perpPrice: avgPerpPrice,
                quarterlyPrice: avgQuarterlyPrice,
                value: avgSpread, // For chart compatibility
                // Store range for future use
                spreadMin: minSpread,
                spreadMax: maxSpread,
                contractCount: group.length // How many contracts at this timestamp
            };
        });
    }

    /**
     * Filter data by date range (timestamp-based)
     * Same pattern as funding-rate api-service.js
     * 
     * @param {Array} data - Aggregated data (after aggregateByTimestamp)
     * @param {Date} startDate - Start date (inclusive)
     * @param {Date} endDate - End date (inclusive)
     * @returns {Array} - Filtered data within date range
     */
    filterByDateRange(data, startDate, endDate) {
        if (!Array.isArray(data) || !startDate || !endDate) {
            return data;
        }
        
        const startTs = startDate.getTime();
        const endTs = endDate.getTime();
        
        // Include records where timestamp is within range [startTs, endTs]
        const filtered = data.filter(item => {
            const itemTs = item.ts; // ts is already in milliseconds from transformHistoryData
            return itemTs >= startTs && itemTs <= endTs;
        });
        
        return filtered;
    }

    /**
     * Cancel all pending requests
     */
    cancelRequest() {
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
        if (this.analyticsAbortController) {
            this.analyticsAbortController.abort();
            this.analyticsAbortController = null;
        }
    }
}

