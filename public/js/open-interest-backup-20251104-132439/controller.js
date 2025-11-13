/**
 * Open Interest Controller
 * Main Alpine.js controller for Open Interest dashboard
 */

import { OpenInterestUtils } from './utils.js';
import { OpenInterestAPIService } from './api-service.js';
import { ChartManager } from './chart-manager.js';

export function createOpenInterestController() {
    return {
        // Initialization flag
        initialized: false,

        // Loading states
        globalLoading: false, // Start false - will show skeleton only if no cache
        analyticsLoading: false,
        isLoading: false, // Flag to prevent multiple simultaneous loads
        errorCount: 0,
        maxErrors: 3,

        // Auto-refresh
        refreshInterval: null,

        // Data containers
        historyData: [],
        analyticsData: null,
        priceData: [],

        // Current metrics
        currentOI: null,
        oiChange: 0,
        currentPrice: null,
        priceChange: 0,

        // Analytics fields
        trend: 'stable',
        volatilityLevel: 'moderate',
        minOI: null,
        maxOI: null,
        dataPoints: 0,

        // Filters
        selectedSymbol: 'BTCUSDT',
        selectedExchange: 'Binance',
        selectedInterval: '8h', // Default: 8h interval
        selectedLimit: '100', // Default: 100 records (string for Alpine)
        // globalPeriod: '7d', // Default: 7D range (kept for compatibility, commented out in usage)
        chartType: 'line',

        // Available options
        symbols: ['BTCUSDT'],
        exchanges: ['OKX','Binance','HTX','Bitmex','Bitfinex','Bybit','Deribit','Gate','Kraken','KuCoin','CME','Bitget','dYdX','CoinEx','BingX','Coinbase','Gemini','Crypto.com','Hyperliquid','Bitunix','MEXC','WhiteBIT','Aster','Lighter','EdgeX','Drift','Paradex','Extended','ApeX Omni'],
        intervals: [
            { label: '1 Minute', value: '1m' },
            { label: '5 Minutes', value: '5m' },
            { label: '15 Minutes', value: '15m' },
            { label: '1 Hour', value: '1h' },
            { label: '4 Hours', value: '4h' },
            { label: '8 Hours', value: '8h' },
            { label: '1 Week', value: '1w' }
        ],
        timeRanges: [
            { label: '1D', value: '1d', days: 1 },
            { label: '7D', value: '7d', days: 7 },
            { label: '1M', value: '1m', days: 30 },
            { label: 'ALL', value: 'all', days: 730 }
        ],
        limitOptions: [
            { label: '100 Records', value: '100' },
            { label: '500 Records', value: '500' },
            { label: '1,000 Records', value: '1000' },
            { label: '2,000 Records', value: '2000' },
            { label: '5,000 Records', value: '5000' },
            { label: 'ALL (No Limit)', value: 'all' } // 'all' means no limit
        ],

        // Services
        apiService: null,
        chartManager: null,

        /**
         * Initialize controller
         */
        async init() {
            // Prevent double initialization
            if (this.initialized) {
                console.log('⏭️ Controller already initialized');
                return;
            }

            this.initialized = true;
            console.log('🚀 Open Interest Dashboard initialized');

            // Initialize services IMMEDIATELY (non-blocking)
            this.apiService = new OpenInterestAPIService();
            this.chartManager = new ChartManager('openInterestMainChart');

            // Set globalLoading = false initially (will show skeleton only if no cache)
            this.globalLoading = false;
            this.analyticsLoading = false;

            // STEP 1: Load cache data INSTANT (no loading skeleton)
            const cacheLoaded = this.loadFromCache();
            if (cacheLoaded) {
                console.log('✅ Cache data loaded instantly - showing cached data');
                // Render chart immediately with cached data (don't wait Chart.js)
                // Chart will render when Chart.js is ready
                if (this.chartManager && this.historyData.length > 0) {
                    // Wait for Chart.js to be ready (but don't block other operations)
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.chartManager.renderChart(this.historyData, this.priceData, this.chartType);
                        }, 10);
                    });
                }
                // globalLoading already false from loadFromCache (no skeleton shown)
                
                // STEP 2: Fetch fresh data from endpoints (background, no skeleton)
                // Don't await - let it run in background while showing cache
                this.loadData(true).catch(err => {
                    console.warn('⚠️ Background fetch failed:', err);
                });
            } else {
                // No cache available - optimistic UI (no skeleton, show placeholder values)
                console.log('⚠️ No cache available - loading data with optimistic UI (no skeleton)');
                // Don't set globalLoading = true - show layout immediately with placeholder values
                // Data will appear seamlessly after fetch completes
                
                // IMPORTANT: Start fetch IMMEDIATELY (don't wait for Chart.js)
                // This makes hard refresh faster - API fetch starts ASAP
                const fetchPromise = this.loadData(false).catch(err => {
                    console.warn('⚠️ Initial load failed:', err);
                });
                
                // Wait for fetch to complete before starting auto-refresh
                // But don't block on Chart.js - chart will render when ready
                await fetchPromise;
            }

            // Start auto-refresh ONLY after initial load completes
            this.startAutoRefresh();

            // Setup cleanup listeners
            window.addEventListener('beforeunload', () => this.cleanup());
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAutoRefresh();
                } else {
                    this.startAutoRefresh();
                }
            });
        },

        /**
         * Get cache key based on current filters
         */
        getCacheKey() {
            const limitKey = this.selectedLimit === 'all' ? 'all' : this.selectedLimit;
            return `oi_dashboard_v2_${this.selectedSymbol}_${this.selectedExchange}_${this.selectedInterval}_${limitKey}`;
        },

        /**
         * Load data from cache (INSTANT display)
         */
        loadFromCache() {
            try {
                const cacheKey = this.getCacheKey();
                const cached = localStorage.getItem(cacheKey);
                
                if (cached) {
                    const data = JSON.parse(cached);
                    
                    // Check if cache is still valid (not older than 10 minutes)
                    const now = Date.now();
                    const cacheAge = now - (data.timestamp || 0);
                    const maxAge = 10 * 60 * 1000; // 10 minutes
                    
                    if (cacheAge < maxAge && data.historyData && data.historyData.length > 0) {
                        this.historyData = data.historyData;
                        this.priceData = data.priceData || [];
                        this.analyticsData = data.analyticsData || null;
                        
                        // Update state from cached analytics
                        if (this.analyticsData) {
                            this.mapAnalyticsToState();
                        }
                        
                        // Update current values
                        this.updateCurrentValues();
                        
                        // IMPORTANT: Hide loading skeletons immediately after cache loaded
                        this.globalLoading = false;
                        this.analyticsLoading = false;
                        
                        console.log('✅ Loaded from cache:', {
                            records: this.historyData.length,
                            age: Math.round(cacheAge / 1000) + 's'
                        });
                        
                        return true;
                    } else {
                        console.log('⚠️ Cache expired or invalid');
                        localStorage.removeItem(cacheKey);
                    }
                }
            } catch (error) {
                console.warn('⚠️ Error loading cache:', error);
            }
            
            return false;
        },

        /**
         * Save data to cache
         */
        saveToCache() {
            try {
                const cacheKey = this.getCacheKey();
                
                // Limit cache size to prevent QuotaExceededError
                // Only save essential data, limit historyData to last 1000 records
                const limitedHistoryData = this.historyData.slice(-1000);
                const limitedPriceData = this.priceData.slice(-1000);
                
                const cacheData = {
                    timestamp: Date.now(),
                    historyData: limitedHistoryData,
                    priceData: limitedPriceData,
                    analyticsData: this.analyticsData,
                    filters: {
                        symbol: this.selectedSymbol,
                        exchange: this.selectedExchange,
                        interval: this.selectedInterval,
                        period: this.globalPeriod
                    }
                };
                
                localStorage.setItem(cacheKey, JSON.stringify(cacheData));
                console.log('💾 Data saved to cache:', cacheKey);
            } catch (error) {
                if (error.name === 'QuotaExceededError') {
                    console.warn('⚠️ Cache quota exceeded, skipping cache save');
                    // Try to clear old cache entries
                    try {
                        const keys = Object.keys(localStorage);
                        const cacheKeys = keys.filter(k => k.startsWith('oi_dashboard_'));
                        // Remove oldest 5 cache entries
                        cacheKeys.sort().slice(0, 5).forEach(k => localStorage.removeItem(k));
                        console.log('🗑️ Cleared old cache entries');
                    } catch (clearErr) {
                        console.warn('⚠️ Could not clear old cache:', clearErr);
                    }
                } else {
                    console.warn('⚠️ Error saving cache:', error);
                }
            }
        },

        /**
         * Load all data (OPTIMISTIC LOADING: history first, analytics in background)
         * @param {boolean} isAutoRefresh - If true, don't show loading skeleton
         */
        async loadData(isAutoRefresh = false) {
            // Guard: Skip if already loading (prevent race condition)
            if (this.isLoading) {
                console.log('⏭️ Skip load (already loading)');
                return;
            }

            // Set loading flag to prevent multiple simultaneous loads
            this.isLoading = true;

            // Only show loading skeleton on initial load (hard refresh)
            // Auto-refresh should be silent (no skeleton) since data already exists
            const isInitialLoad = this.historyData.length === 0;
            const shouldShowLoading = isInitialLoad && !isAutoRefresh;

            // IMPORTANT: Don't cancel previous requests on initial load
            // Initial load needs to complete, and auto-refresh will skip if isLoading = true
            // Only cancel on subsequent loads (auto-refresh) to prevent stale data
            if (this.apiService && !isInitialLoad) {
                this.apiService.cancelAllRequests();
            }
            
            if (shouldShowLoading) {
                this.globalLoading = true; // Show skeleton only on first load
                console.log('🔄 Initial load - showing skeleton');
            } else {
                console.log('🔄 Auto-refresh - silent update (no skeleton)');
            }

            this.errorCount = 0;

            // Performance monitoring
            const loadStartTime = Date.now();
            console.log('⏱️ loadData() started at:', new Date().toISOString());

            try {
                // Use selectedLimit directly (simpler and more reliable)
                // Convert string to number
                let limit = parseInt(this.selectedLimit, 10) || 100;
                
                // If limit is 'all', use large number
                if (this.selectedLimit === 'all') {
                    limit = 50000; // Large enough for "all" data
                }
                
                // For initial load, use smaller limit (100) for faster response
                // Then load full data in background after first render
                if (isInitialLoad && limit > 100) {
                    limit = 100; // Start with 100 for instant feedback
                }
                
                // COMMENTED OUT: Date range approach (kept for reference)
                // const periodDays = this.globalPeriod === 'all' 
                //     ? 730 
                //     : (this.timeRanges.find(r => r.value === this.globalPeriod)?.days || 7);
                // const calculatedLimit = OpenInterestUtils.calculateLimit(periodDays, this.selectedInterval);
                
                // Per new API spec, always include price overlay
                const withPrice = true;

                console.log('📡 Loading Open Interest data...', {
                    symbol: this.selectedSymbol,
                    exchange: this.selectedExchange,
                    interval: this.selectedInterval,
                    limit: limit,
                    selectedLimit: this.selectedLimit,
                    withPrice: withPrice,
                    isInitialLoad: isInitialLoad
                });

                // OPTIMISTIC LOADING: Fetch history first (main data)
                // Use limit-based approach (simpler and more reliable)
                const historyData = await this.apiService.fetchHistory({
                    symbol: this.selectedSymbol,
                    exchange: this.selectedExchange,
                    interval: this.selectedInterval,
                    limit: limit,
                    with_price: withPrice
                    // COMMENTED OUT: dateRange approach (kept for reference)
                    // dateRange: dateRange
                });

                // Handle cancelled requests
                if (historyData === null) {
                    console.log('🚫 Request was cancelled');
                    return;
                }

                // Data is already limited by API (no client-side filtering needed)
                // This approach is simpler and more reliable than date range filtering
                let filteredData = historyData;

                // Transform price data (optimized)
                const transformStartTime = Date.now();
                this.historyData = filteredData;
                this.priceData = filteredData.map(d => ({ ts: d.ts, price: d.price }));
                const transformTime = Date.now() - transformStartTime;
                console.log('⏱️ Data transform time:', transformTime + 'ms');

                this.errorCount = 0; // Reset on success

                console.log('✅ History data loaded:', this.historyData.length, 'records');

                // CRITICAL: Update current values IMMEDIATELY from rawData (like Funding Rate)
                // This ensures summary cards are populated INSTANTLY
                this.updateCurrentValues();

                // CRITICAL: Render chart IMMEDIATELY (before analytics fetch for instant feedback)
                // Chart is the most important visual element - show it ASAP
                // Following Funding Rate pattern for instant rendering
                const chartRenderStart = Date.now();
                const renderChart = () => {
                    try {
                        if (this.chartManager && this.historyData.length > 0) {
                            // Use renderChart directly (same as Funding Rate)
                            this.chartManager.renderChart(this.historyData, this.priceData, this.chartType);
                            const chartRenderTime = Date.now() - chartRenderStart;
                            console.log('⏱️ Chart render time:', chartRenderTime + 'ms');
                        }
                    } catch (error) {
                        console.error('❌ Error rendering chart:', error);
                        // Fallback: try with small delay if immediate render fails
                        setTimeout(() => {
                            if (this.chartManager && this.historyData.length > 0) {
                                this.chartManager.renderChart(this.historyData, this.priceData, this.chartType);
                            }
                        }, 50);
                    }
                };

                // Try immediate render (Chart.js might already be loaded)
                // This is CRITICAL for instant chart updates when limit/interval changes
                if (typeof Chart !== 'undefined') {
                    renderChart(); // Instant render - no delay
                } else {
                    // Chart.js not ready yet - wait for it (non-blocking)
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        renderChart();
                    }).catch(() => {
                        // Fallback if Chart.js fails to load
                        console.warn('⚠️ Chart.js not available, will retry later');
                        setTimeout(renderChart, 100);
                    });
                }

                // CRITICAL: Fetch analytics IMMEDIATELY (not in background)
                // Summary cards MUST show data from analytics API
                // Don't skip on auto-refresh - always fetch for accuracy
                this.fetchAnalyticsData(isAutoRefresh).then(() => {
                    // Save to cache after analytics loaded
                    this.saveToCache();
                }).catch(err => {
                    console.warn('⚠️ Analytics fetch failed (will use defaults):', err);
                    // Set defaults if analytics fails
                    this.trend = 'stable';
                    this.volatilityLevel = 'moderate';
                    // Save cache even if analytics failed
                    this.saveToCache();
                });

                // Log total load time
                const totalLoadTime = Date.now() - loadStartTime;
                console.log('⏱️ Total loadData() time:', totalLoadTime + 'ms');

                // Load full data in background (same as Funding Rate)
                // Reset isLoading flag first so auto-refresh can work
                this.isLoading = false;
                
                // Background load only on initial load (not on filter changes)
                // Only if selectedLimit is larger than 100
                const fullLimit = this.selectedLimit === 'all' ? 50000 : (parseInt(this.selectedLimit, 10) || 100);
                if (isInitialLoad && limit < fullLimit) {
                    // Use requestIdleCallback for better performance (falls back to setTimeout)
                    const scheduleFullDataLoad = (callback) => {
                        if (window.requestIdleCallback) {
                            window.requestIdleCallback(callback, { timeout: 50 });
                        } else {
                            setTimeout(callback, 0); // No delay for instant update
                        }
                    };

                    scheduleFullDataLoad(async () => {
                        try {
                            // Use limit-based approach for background load
                            const fullHistoryData = await this.apiService.fetchHistory({
                                symbol: this.selectedSymbol,
                                exchange: this.selectedExchange,
                                interval: this.selectedInterval,
                                limit: fullLimit,
                                with_price: true
                                // COMMENTED OUT: dateRange approach
                                // dateRange: backgroundDateRange
                            });

                            if (fullHistoryData && fullHistoryData.length > 0) {
                                // Data is already limited by API (no filtering needed)
                                // Update with full dataset
                                this.historyData = fullHistoryData;
                                this.priceData = fullHistoryData.map(d => ({ ts: d.ts, price: d.price }));
                                this.updateCurrentValues();

                                // Update chart with full data (smooth update)
                                if (this.chartManager) {
                                    this.chartManager.renderChart(this.historyData, this.priceData, this.chartType);
                                }

                                // Save updated cache (catch quota errors silently)
                                try {
                                    this.saveToCache();
                                } catch (cacheErr) {
                                    if (cacheErr.name === 'QuotaExceededError') {
                                        console.warn('⚠️ Cache quota exceeded, skipping cache save');
                                    }
                                }

                                console.log('✅ Full dataset loaded and chart updated:', {
                                    records: this.historyData.length,
                                    previousRecords: limit
                                });
                            }
                        } catch (err) {
                            console.warn('⚠️ Background full data load failed (using initial data):', err);
                        }
                    });
                }

            } catch (error) {
                // Handle AbortError gracefully (don't log as error)
                if (error.name === 'AbortError') {
                    console.log('⏭️ Request was cancelled (expected during auto-refresh)');
                    return; // Exit early, don't increment error count
                }

                console.error('❌ Error loading data:', error);
                this.errorCount++;

                if (this.errorCount >= this.maxErrors) {
                    console.error('❌ Max errors reached, stopping auto-refresh');
                    this.stopAutoRefresh();
                }
            } finally {
                // Always reset loading flag
                this.isLoading = false;

                // Hide skeleton only if it was shown (initial load)
                // Auto-refresh doesn't show skeleton, so don't set it here
                if (shouldShowLoading) {
                    this.globalLoading = false;
                    console.log('✅ Initial load complete - skeleton hidden');
                }
            }
        },

        /**
         * Fetch analytics data (CRITICAL: for summary cards)
         * @param {boolean} isAutoRefresh - If true, don't show loading skeleton
         */
        async fetchAnalyticsData(isAutoRefresh = false) {
            // Never show analytics loading skeleton during auto-refresh
            // Auto-refresh should be silent (data already visible)
            this.analyticsLoading = false; // Always silent for now

            try {
                // Use larger limit for analytics to get comprehensive stats
                // Analytics needs more data points for accurate calculations
                const limit = 1000; // Fixed limit for analytics

                console.log('📡 Fetching analytics data for summary cards...');

                const analyticsData = await this.apiService.fetchAnalytics({
                    symbol: this.selectedSymbol,
                    exchange: this.selectedExchange,
                    interval: this.selectedInterval,
                    limit: limit
                });

                if (analyticsData) {
                    // Handle array response (API returns array with single object)
                    const data = Array.isArray(analyticsData) ? analyticsData[0] : analyticsData;
                    
                    this.analyticsData = data;
                    this.mapAnalyticsToState();
                    console.log('✅ Analytics data loaded for summary cards:', data);
                } else {
                    console.warn('⚠️ Analytics data is null');
                }

            } catch (error) {
                // Handle AbortError gracefully
                if (error.name === 'AbortError') {
                    console.log('⏭️ Analytics request cancelled (expected during filter change)');
                    return;
                }
                console.warn('⚠️ Analytics fetch error:', error);
                // Don't throw - let main flow continue
            } finally {
                this.analyticsLoading = false;
            }
        },

        /**
         * Map analytics data to state (from API response)
         */
        mapAnalyticsToState() {
            if (!this.analyticsData) {
                console.warn('⚠️ Analytics data is null or empty');
                return;
            }

            // Analytics API response structure:
            // { current_price, exchange, insights: { data_points, max_oi, min_oi, volatility_level }, open_interest, trend }
            
            // Extract trend
            this.trend = this.analyticsData.trend || 'stable';
            
            // Extract insights object
            const insights = this.analyticsData.insights || {};
            this.volatilityLevel = insights.volatility_level || 'moderate';
            this.minOI = insights.min_oi ? parseFloat(insights.min_oi) : null;
            this.maxOI = insights.max_oi ? parseFloat(insights.max_oi) : null;
            this.dataPoints = insights.data_points || 0;

            console.log('✅ Analytics mapped to state from API:', {
                trend: this.trend,
                volatilityLevel: this.volatilityLevel,
                minOI: this.minOI,
                maxOI: this.maxOI,
                dataPoints: this.dataPoints
            });
        },

        /**
         * Update current values from history data
         */
        updateCurrentValues() {
            if (this.historyData.length === 0) return;

            // Sort by timestamp to get truly latest value
            const sorted = [...this.historyData].sort((a, b) => a.ts - b.ts);
            const latest = sorted[sorted.length - 1];

            // Update current OI
            this.currentOI = latest.oi_usd ? parseFloat(latest.oi_usd) : null;

            // Update current price
            this.currentPrice = latest.price ? parseFloat(latest.price) : null;

            // Calculate 24h change (compare with data from 24h ago)
            const oneDayAgo = latest.ts - (24 * 60 * 60 * 1000);
            const previous = sorted.find(d => d.ts <= oneDayAgo) || sorted[0];

            if (previous && previous.oi_usd) {
                const prevOI = parseFloat(previous.oi_usd);
                this.oiChange = prevOI > 0 
                    ? ((this.currentOI - prevOI) / prevOI) * 100 
                    : 0;
            }

            if (previous && previous.price) {
                const prevPrice = parseFloat(previous.price);
                this.priceChange = prevPrice > 0 
                    ? ((this.currentPrice - prevPrice) / prevPrice) * 100 
                    : 0;
            }

            console.log('✅ Current values updated:', {
                currentOI: this.currentOI,
                oiChange: this.oiChange,
                currentPrice: this.currentPrice,
                priceChange: this.priceChange
            });
        },

        /**
         * Start auto-refresh (5 seconds)
         */
        startAutoRefresh() {
            this.stopAutoRefresh();

            const intervalMs = 5000; // 5 seconds

            this.refreshInterval = setInterval(() => {
                // Safety checks
                if (document.hidden) return; // Don't refresh hidden tabs
                if (this.globalLoading) return; // Skip if showing skeleton
                if (this.isLoading) return; // Skip if already loading (prevent race condition)
                if (this.errorCount >= this.maxErrors) {
                    console.error('❌ Too many errors, stopping auto refresh');
                    this.stopAutoRefresh();
                    return;
                }

                console.log('🔄 Auto-refresh triggered');
                // Pass isAutoRefresh=true to prevent loading skeleton during auto-refresh
                this.loadData(true).catch(err => {
                    // Handle errors gracefully (AbortError expected during rapid refreshes)
                    if (err.name !== 'AbortError') {
                        console.warn('⚠️ Auto-refresh error:', err);
                    }
                }); // Silent update - no skeleton shown

                // Also refresh analytics independently (non-blocking)
                // Pass isAutoRefresh=true to prevent analytics skeleton during auto-refresh
                if (!this.analyticsLoading) {
                    this.fetchAnalyticsData(true).catch(err => {
                        console.warn('⚠️ Analytics refresh failed:', err);
                    });
                }
            }, intervalMs);

            console.log('✅ Auto-refresh started (5s interval)');
        },

        /**
         * Stop auto-refresh
         */
        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
                console.log('⏸️ Auto-refresh stopped');
            }
        },

        /**
         * Cleanup
         */
        cleanup() {
            this.stopAutoRefresh();
            if (this.chartManager) this.chartManager.destroy();
            if (this.apiService) this.apiService.cancelAllRequests();
        },

        /**
         * Handle filter change with cache support (instant update)
         */
        async handleFilterChange() {
            // CRITICAL: Cancel any pending requests first for instant update
            if (this.apiService) {
                this.apiService.cancelAllRequests();
            }
            
            // Reset isLoading flag to allow immediate reload
            this.isLoading = false;
            
            // Set loading states to false initially (will show skeleton only if no cache)
            this.globalLoading = false;
            this.analyticsLoading = false;
            
            // Try to load cache for new filter combination
            const cacheLoaded = this.loadFromCache();
            
            if (cacheLoaded) {
                console.log('✅ Cache loaded for new filter - showing cached data instantly');
                // Render cached data immediately (no filtering needed - limit-based approach)
                // Instant render like Funding Rate
                if (this.chartManager && this.historyData.length > 0) {
                    // Try immediate render (Chart.js might already be loaded)
                    if (typeof Chart !== 'undefined') {
                        const cachedPrice = this.historyData.map(d => ({ ts: d.ts, price: d.price }));
                        this.chartManager.renderChart(this.historyData, cachedPrice, this.chartType);
                    } else {
                        // Wait for Chart.js if needed
                        (window.chartJsReady || Promise.resolve()).then(() => {
                            setTimeout(() => {
                                const cachedPrice = this.historyData.map(d => ({ ts: d.ts, price: d.price }));
                                this.chartManager.renderChart(this.historyData, cachedPrice, this.chartType);
                            }, 10);
                        });
                    }
                }
                // Fetch fresh data in background (no skeleton)
                this.loadData(true).catch(err => {
                    console.warn('⚠️ Background fetch failed:', err);
                });
            } else {
                // No cache - load data normally (will show skeleton if needed)
                await this.loadData();
            }
        },

        /**
         * Filter handlers
         */
        setTimeRange(range) {
            if (this.globalPeriod === range) return;
            console.log('🔄 Setting time range to:', range);
            this.globalPeriod = range;
            // CRITICAL: Cancel any pending requests for instant update
            if (this.apiService) {
                this.apiService.cancelAllRequests();
            }
            // Reset isLoading flag to allow immediate reload
            this.isLoading = false;
            // Load data immediately (no delay)
            this.loadData();
        },

        updateSymbol(symbol) {
            const allowed = new Set(['BTCUSDT']);
            const finalSymbol = allowed.has(symbol) ? symbol : 'BTCUSDT';
            if (this.selectedSymbol === finalSymbol) return;
            this.selectedSymbol = finalSymbol;
            console.log('💱 Symbol changed:', symbol);
            this.handleFilterChange();
        },

        updateExchange(exchange) {
            const allowed = new Set(['OKX','Binance','HTX','Bitmex','Bitfinex','Bybit','Deribit','Gate','Kraken','KuCoin','CME','Bitget','dYdX','CoinEx','BingX','Coinbase','Gemini','Crypto.com','Hyperliquid','Bitunix','MEXC','WhiteBIT','Aster','Lighter','EdgeX','Drift','Paradex','Extended','ApeX Omni']);
            const finalExchange = allowed.has(exchange) ? exchange : 'Binance';
            if (this.selectedExchange === finalExchange) return;
            this.selectedExchange = finalExchange;
            console.log('🏦 Exchange changed:', exchange);
            this.handleFilterChange();
        },

        /**
         * Set chart interval (instant update like Funding Rate)
         */
        setChartInterval(interval) {
            if (this.selectedInterval === interval) return;
            console.log('🔄 Setting chart interval to:', interval);
            this.selectedInterval = interval;
            
            // CRITICAL: Stop auto-refresh temporarily to avoid conflicts
            this.stopAutoRefresh();
            
            // CRITICAL: Cancel any pending requests for instant update
            if (this.apiService) {
                this.apiService.cancelAllRequests();
            }
            
            // Reset isLoading flag to allow immediate reload
            this.isLoading = false;
            
            // Load data immediately (no delay)
            this.loadData().then(() => {
                // Restart auto-refresh after data loaded
                this.startAutoRefresh();
            }).catch(err => {
                console.error('Error loading data:', err);
                // Restart auto-refresh even if error
                this.startAutoRefresh();
            });
        },
        
        /**
         * Set limit (instant update like Funding Rate)
         */
        setLimit(limit) {
            // Keep as string for consistency with select element
            const limitValue = limit === 'null' || limit === null || limit === undefined ? 'all' : String(limit);
            
            if (this.selectedLimit === limitValue) return;
            console.log('🔄 Setting limit to:', limitValue === 'all' ? 'ALL' : limitValue);
            this.selectedLimit = limitValue;
            
            // CRITICAL: Stop auto-refresh temporarily to avoid conflicts
            this.stopAutoRefresh();
            
            // CRITICAL: Cancel any pending requests for instant update
            if (this.apiService) {
                this.apiService.cancelAllRequests();
            }
            
            // Reset isLoading flag to allow immediate reload
            this.isLoading = false;
            
            // Load data immediately (no delay) - instant update like Funding Rate
            this.loadData().then(() => {
                // Restart auto-refresh after data loaded
                this.startAutoRefresh();
            }).catch(err => {
                console.error('Error loading data:', err);
                // Restart auto-refresh even if error
                this.startAutoRefresh();
            });
        },

        /**
         * Update interval (for form select) - instant update
         */
        updateInterval(interval) {
            const allowed = new Set(['1m','5m','15m','1h','4h','8h','1w']);
            const finalInterval = allowed.has(interval) ? interval : '8h';
            if (this.selectedInterval === finalInterval) return;
            this.selectedInterval = finalInterval;
            console.log('🔄 Updating interval to:', finalInterval);
            
            // CRITICAL: Stop auto-refresh temporarily to avoid conflicts
            this.stopAutoRefresh();
            
            // CRITICAL: Cancel any pending requests for instant update
            if (this.apiService) {
                this.apiService.cancelAllRequests();
            }
            
            // Reset isLoading flag to allow immediate reload
            this.isLoading = false;
            
            // Load data immediately (no delay)
            this.loadData().then(() => {
                // Restart auto-refresh after data loaded
                this.startAutoRefresh();
            }).catch(err => {
                console.error('Error loading data:', err);
                // Restart auto-refresh even if error
                this.startAutoRefresh();
            });
        },

        toggleChartType() {
            this.chartType = this.chartType === 'line' ? 'bar' : 'line';
            console.log('📊 Chart type toggled:', this.chartType);
            // Render chart with new type
            if (this.chartManager && this.historyData.length > 0) {
                this.chartManager.renderChart(this.historyData, this.priceData, this.chartType);
            }
        },

        /**
         * Helper methods
         */
        // COMMENTED OUT: Date range approach (using limit instead)
        // getDateRange() {
        //     return OpenInterestUtils.getDateRange(this.globalPeriod, this.timeRanges);
        // },

        formatOI(value) {
            return OpenInterestUtils.formatOI(value);
        },

        formatPrice(value) {
            return OpenInterestUtils.formatPrice(value);
        },

        formatChange(value) {
            return OpenInterestUtils.formatChange(value);
        },

        getTrendBadgeClass(trend) {
            return OpenInterestUtils.getTrendBadgeClass(trend);
        },

        getTrendColorClass(trend) {
            return OpenInterestUtils.getTrendColorClass(trend);
        },

        getVolatilityBadgeClass(level) {
            return OpenInterestUtils.getVolatilityBadgeClass(level);
        }
    };
}

