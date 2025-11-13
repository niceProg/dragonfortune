/**
 * Open Interest Main Controller
 * Coordinates data fetching, chart rendering, and metrics calculation
 */

import { OpenInterestAPIService } from './api-service.js';
import { ChartManager } from './chart-manager.js';
import { OpenInterestUtils } from './utils.js';

export function createOpenInterestController() {
    return {
        // Initialization flag
        initialized: false,

        // Services
        apiService: null,
        chartManager: null,

        // Global state
        isLoading: false, // Flag to prevent multiple simultaneous loads
        selectedSymbol: 'BTCUSDT',
        selectedExchange: 'Binance',
        scaleType: 'linear',

        // In-memory cache for all interval combinations (for instant switching)
        intervalCache: new Map(), // key: 'interval_limit', value: { data, analytics, timestamp }

        // Chart intervals
        chartIntervals: [
            { label: '1m', value: '1m' },
            { label: '5m', value: '5m' },
            { label: '15m', value: '15m' },
            { label: '1H', value: '1h' },
            { label: '4H', value: '4h' },
            { label: '8H', value: '8h' },
            { label: '1W', value: '1w' }
        ],
        selectedInterval: '8h',

        // Date range selector (Coinglass uses start_time/end_time, no limit)
        timeRanges: [
            { label: '24H', value: '1d', days: 1 },
            { label: '7D', value: '7d', days: 7 },
            { label: '1M', value: '1m', days: 30 },
            { label: '3M', value: '3m', days: 90 },
            { label: '6M', value: '6m', days: 180 },
            { label: '1Y', value: '1y', days: 365 }
        ],
        selectedTimeRange: '1m', // Default 1 month

        // Auto-refresh state
        refreshInterval: null,
        errorCount: 0,
        maxErrors: 3,
        lastUpdateTime: null,

        // Data
        rawData: [],
        priceData: [],
        dataLoaded: false,
        summaryDataLoaded: false,

        // Summary metrics (Open Interest) - Only 4 cards: Current OI, Min OI, Max OI, Trend
        currentOI: null,
        oiChange: null,
        minOI: null,  // Set from analytics API
        maxOI: null,  // Set from analytics API

        // Trend (from analytics API)
        trend: null, // will be set by analytics (no dummy default)
        analyticsData: null,
        analyticsLoading: false,
        lastAnalyticsAt: 0,

        // Price metrics
        currentPrice: null,
        priceChange: null,
        priceDataAvailable: false,

        // Chart state
        chartType: 'line', // 'line' or 'candlestick'
        distributionChart: null,
        maChart: null,


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

            // STEP 1: Load current interval data (from cache or API)
            const cacheLoaded = this.loadFromCache();
            if (cacheLoaded) {
                console.log('✅ Cache data loaded instantly - showing cached data');
                // Render chart immediately with cached data
                if (this.chartManager && this.rawData.length > 0) {
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.chartManager.renderChart(this.rawData, this.priceData, this.chartType);
                        }, 10);
                    });
                }

                // Fetch fresh data in background (silent update)
                this.loadData(true).catch(err => {
                    console.warn('⚠️ Background fetch failed:', err);
                });
            } else {
                // No cache - load current interval first
                console.log('⚠️ No cache available - loading current interval');
                await this.loadData(false).catch(err => {
                    console.warn('⚠️ Initial load failed:', err);
                });
            }

            // STEP 2: Start auto-refresh (5 seconds, uses CURRENT state)
            this.startAutoRefresh();

            // STEP 3: Prefetch ALL intervals in background for smooth switching
            this.prefetchAllIntervals();

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
         * Start auto-refresh with safety checks (5 seconds - READS CURRENT STATE)
         */
        startAutoRefresh() {
            this.stopAutoRefresh(); // Clear any existing interval

            const intervalMs = 5000; // 5 seconds

            this.refreshInterval = setInterval(() => {
                // Safety checks
                if (document.hidden) return; // Don't refresh hidden tabs
                if (this.isLoading) return; // Skip if already loading (prevent race condition)
                if (this.errorCount >= this.maxErrors) {
                    console.error('❌ Too many errors, stopping auto refresh');
                    this.stopAutoRefresh();
                    return;
                }

                console.log('[AR:TICK] state', {
                    interval: this.selectedInterval,
                    limit: this.selectedLimit
                });

                // IMPORTANT: loadData() reads this.selectedInterval and this.selectedLimit
                // Pass isAutoRefresh=true to prevent loading skeleton during auto-refresh
                this.loadData(true).catch(err => {
                    // Handle errors gracefully (AbortError expected during rapid refreshes)
                    if (err.name !== 'AbortError') {
                        console.warn('⚠️ Auto-refresh error:', err);
                    }
                }); // Silent update - no skeleton shown

            }, intervalMs);

            console.log('✅ Auto-refresh started (5 second interval)');
        },

        /**
         * Stop auto-refresh
         */
        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },

        /**
         * Set limit for data fetching - INSTANT SWITCH with in-memory cache
         */
        setLimit(limit) {
            let limitValue;
            if (limit === null || limit === 'null' || limit === undefined || limit === 'undefined') {
                limitValue = 'all'; // ALL (no limit)
            } else if (typeof limit === 'string') {
                limitValue = limit === 'all' ? 'all' : String(parseInt(limit, 10));
            } else {
                limitValue = String(limit);
            }

            if (this.selectedLimit === limitValue) return;
            console.log('🔄 Setting limit to:', limitValue === 'all' ? 'ALL' : limitValue);
            this.selectedLimit = limitValue;

            // Don't stop auto-refresh - let it continue with new state
            // Don't cancel requests - let them finish for caching
            // Don't set isLoading flag - allow instant switching

            // Load data immediately (will check in-memory cache first)
            this.loadData(false).catch(err => {
                console.error('Error loading data:', err);
            });
        },

        /**
         * Get cache key for current filter state
         */
        getCacheKey() {
            const exchange = OpenInterestUtils.capitalizeExchange(this.selectedExchange);
            return `oi_dashboard_v2_${this.selectedSymbol}_${exchange}_${this.selectedInterval}_${this.selectedLimit}`;
        },

        /**
         * Load data from cache
         */
        loadFromCache() {
            try {
                const cacheKey = this.getCacheKey();
                const cached = localStorage.getItem(cacheKey);
                if (cached) {
                    const data = JSON.parse(cached);
                    const cacheAge = Date.now() - data.timestamp;
                    const maxAge = 5 * 60 * 1000; // 5 minutes

                    if (cacheAge < maxAge && data.rawData && data.rawData.length > 0) {
                        this.rawData = data.rawData;
                        this.priceData = data.priceData || [];
                        this.currentOI = data.currentOI;
                        this.minOI = data.minOI;
                        this.maxOI = data.maxOI;
                        this.trend = (data.trend !== undefined) ? data.trend : null;
                        this.currentPrice = data.currentPrice;
                        this.dataLoaded = true;
                        this.summaryDataLoaded = true;

                        // IMPORTANT: Hide loading skeletons immediately after cache loaded
                        // This matches Open Interest optimization pattern
                        this.globalLoading = false;
                        this.analyticsLoading = false;

                        console.log('✅ Cache loaded:', {
                            records: this.rawData.length,
                            age: Math.round(cacheAge / 1000) + 's'
                        });
                        return true;
                    } else {
                        localStorage.removeItem(cacheKey);
                    }
                }
            } catch (error) {
                console.warn('⚠️ Cache load error:', error);
            }
            return false;
        },

        /**
         * Save data to cache
         */
        saveToCache() {
            try {
                const cacheKey = this.getCacheKey();
                const data = {
                    timestamp: Date.now(),
                    rawData: this.rawData,
                    priceData: this.priceData,
                    currentOI: this.currentOI,
                    minOI: this.minOI,
                    maxOI: this.maxOI,
                    trend: this.trend,
                    currentPrice: this.currentPrice
                };
                localStorage.setItem(cacheKey, JSON.stringify(data));
                console.log('💾 Data saved to cache:', cacheKey);
            } catch (error) {
                console.warn('⚠️ Cache save error:', error);
            }
        },

        /**
         * Load data from API (OPTIMIZED: Progressive Loading)
         */
        async loadData(isAutoRefresh = false) {
            // REMOVED isLoading check for instant switching
            // In-memory cache handles instant switching, so we allow concurrent loads
            console.log(isAutoRefresh ? '🔄 Auto-refresh - silent update (no skeleton)' : '📊 Loading data...');

            // Performance monitoring
            const loadStartTime = Date.now();
            console.log('⏱️ loadData() started at:', new Date().toISOString());

            const exchange = OpenInterestUtils.capitalizeExchange(this.selectedExchange);

            // Check in-memory cache first for INSTANT switching
            const cacheKey = `${this.selectedInterval}_${this.selectedLimit}`;
            const cachedData = this.intervalCache.get(cacheKey);

            if (cachedData && !isAutoRefresh) {
                const cacheAge = Date.now() - cachedData.timestamp;
                if (cacheAge < 60000) { // 1 minute cache
                    console.log(`[CACHE:HIT] key=${cacheKey} age=${Math.round(cacheAge / 1000)}s records=${cachedData.data.length}`);
                    this.rawData = cachedData.data;
                    this.priceData = cachedData.priceData || [];
                    this.currentOI = cachedData.analytics?.currentOI || null;
                    this.minOI = cachedData.analytics?.minOI || null;
                    this.maxOI = cachedData.analytics?.maxOI || null;
                    this.trend = cachedData.analytics?.trend || 'stable';

                    // Render chart immediately
                    if (this.chartManager && this.rawData.length > 0) {
                        this.chartManager.renderChart(this.rawData, this.priceData, this.chartType);
                    }

                    // Background refresh in parallel (don't wait)
                    this.loadData(true).catch(() => { });
                    return;
                }
            }
            if (!cachedData) {
                console.log(`[CACHE:MISS] key=${cacheKey}`);
            }

            try {
                console.log('📡 Loading Open Interest data...');

                // Use limit-based approach (no dateRange)
                // Parse selectedLimit: 'all' means no limit, otherwise use numeric value
                let limitValue;
                if (this.selectedLimit === 'all' || this.selectedLimit === null || this.selectedLimit === undefined) {
                    limitValue = null; // No limit - API will return all available
                } else {
                    limitValue = parseInt(this.selectedLimit, 10);
                }

                // Use selected limit or a small default for speed
                const limit = limitValue || 100;

                console.log('[HIST:START] (controller) ', {
                    symbol: this.selectedSymbol,
                    exchange: exchange,
                    interval: this.selectedInterval,
                    limit: limit,
                    selectedLimit: this.selectedLimit
                });

                // Fetch data from internal API with limit-based approach
                let data = null;
                try {
                    data = await this.apiService.fetchHistory({
                        symbol: this.selectedSymbol,
                        exchange: exchange,
                        interval: this.selectedInterval,
                        limit: limit,
                        with_price: false // Set to false to reduce payload size (price is always "0E-8")
                    }, false, !isAutoRefresh); // noTimeout for main; keep timeout for auto-refresh

                    // Normalize undefined to null
                    if (data === undefined) {
                        data = null;
                    }

                    if (data === null) {
                        // If null due to cancel, just exit gracefully
                    } else if (Array.isArray(data)) {
                        this.rawData = data;
                    }
                } catch (err) {
                    if (err && (err.code === 'TIMEOUT' || err.message === 'RequestTimeout')) {
                        console.warn('[HIST:TIMEOUT] fallback sequence');
                        const fallbackLimits = [50, 20, 10];
                        let data = null;
                        for (const fl of fallbackLimits) {
                            try {
                                data = await this.apiService.fetchHistory({
                                    symbol: this.selectedSymbol,
                                    exchange: exchange,
                                    interval: this.selectedInterval,
                                    limit: fl,
                                    with_price: false
                                }, false, true);
                                if (data && data.length > 0) {
                                    console.log(`[HIST:FALLBACK:OK] records=${data.length} limit=${fl}`);
                                    break;
                                }
                            } catch (_) {
                                // ignore and continue trying smaller limit
                            }
                        }
                        if (!data || data.length === 0) {
                            throw err; // rethrow if all fallbacks failed
                        }
                        // proceed with loaded fallback data
                        this.rawData = data;
                    } else {
                        throw err;
                    }
                }
                // continue after catch only when fallback succeeded
                if (!this.rawData || this.rawData.length === 0) {
                    return;
                }

                this.errorCount = 0; // Reset on success
                this.lastUpdateTime = new Date();

                console.log(`[HIST:OK] (controller) records=${this.rawData.length}`);

                // Extract price data from Open Interest data (optimized - direct map, filter later if needed)
                this.priceData = this.rawData
                    .filter(d => d.price !== null && d.price !== undefined)
                    .map(d => ({ date: d.date, price: d.price }));

                console.log(`[RENDER:PRICE:EXTRACT] points=${this.priceData.length}`);

                // SKIP calculateMetrics - Don't compute in frontend
                // Summary cards will ONLY show data from API analytics (no dummy data)
                // calculateMetrics() is commented out to prevent dummy -- values
                // if (this.rawData.length > 0) {
                //     this.calculateMetrics();
                //     console.log('✅ Metrics calculated from rawData (instant, no delay)');
                //     this.updateCurrentValues();
                // }

                this.dataLoaded = true;

                // Render chart IMMEDIATELY (before analytics fetch for faster perceived performance)
                // Chart is the most important visual element - show it ASAP
                // Don't wait for Chart.js - it will render when ready (non-blocking)
                const chartRenderStart = Date.now();
                const renderChart = () => {
                    try {
                        if (this.chartManager && this.rawData.length > 0) {
                            // Use renderChart directly (same as Open Interest)
                            this.chartManager.renderChart(this.rawData, this.priceData, this.chartType);
                            const chartRenderTime = Date.now() - chartRenderStart;
                            console.log(`[RENDER:CHART] points=${this.rawData.length} render_ms=${chartRenderTime} source=${isAutoRefresh ? 'fetch' : 'fetch'}`);
                        }
                    } catch (error) {
                        console.error('❌ Error rendering chart:', error);
                        setTimeout(() => {
                            if (this.chartManager && this.rawData.length > 0) {
                                this.chartManager.renderChart(this.rawData, this.priceData, this.chartType);
                            }
                        }, 50);
                    }
                };

                // Try immediate render (Chart.js might already be loaded)
                if (typeof Chart !== 'undefined') {
                    renderChart();
                } else {
                    // Chart.js not ready yet - wait for it (non-blocking)
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        renderChart();
                    }).catch(() => {
                        console.warn('⚠️ Chart.js not available, will retry later');
                        setTimeout(renderChart, 100);
                    });
                }


                // Fetch analytics data IMMEDIATELY (BLOCKING for initial load)
                // This ensures summary cards are populated from API (no dummy data)
                // For auto-refresh, this runs in background (non-blocking)
                if (!isAutoRefresh) {
                    // Initial load - wait for analytics to complete
                    try {
                        await this.fetchAnalyticsData(isAutoRefresh);
                        console.log('✅ Analytics data loaded - summary cards populated from API');
                    } catch (err) {
                        console.warn('⚠️ Analytics fetch failed:', err);
                    }
                } else {
                    // Auto-refresh - run analytics in background (non-blocking)
                    this.fetchAnalyticsData(isAutoRefresh).catch(err => {
                        console.warn('⚠️ Analytics fetch failed:', err);
                    });
                }

                // Save to in-memory cache for instant switching
                this.intervalCache.set(cacheKey, {
                    data: this.rawData,
                    priceData: this.priceData,
                    analytics: {
                        currentOI: this.currentOI,
                        minOI: this.minOI,
                        maxOI: this.maxOI,
                        trend: this.trend
                    },
                    timestamp: Date.now()
                });
                console.log(`[CACHE:SAVE] key=${cacheKey} records=${this.rawData.length}`);

                // Log total load time and save cache
                const totalLoadTime = Date.now() - loadStartTime;
                console.log(`[HIST:TOTAL] (controller) ms=${totalLoadTime}`);
                this.saveToCache();

            } catch (error) {
                // Handle AbortError gracefully (don't log as error)
                if (error.name === 'AbortError') {
                    console.log('⏭️ Request was cancelled (expected during auto-refresh)');
                    return; // Exit early, don't increment error count
                }

                console.error('❌ Error loading data:', error);
                // Don't penalize TIMEOUT errors for auto-refresh health
                if (!(error && (error.code === 'TIMEOUT' || error.message === 'RequestTimeout'))) {
                    this.errorCount++;
                }

                if (this.errorCount >= this.maxErrors) {
                    this.stopAutoRefresh();
                    this.showError('Auto-refresh disabled due to repeated errors');
                }
            } finally {
                // Always reset loading flags
                this.isLoading = false;
            }
        },

        /**
         * Calculate all metrics from rawData (instant fallback)
         * Analytics API will update these values later if available
         */
        calculateMetrics() {
            if (this.rawData.length === 0) {
                console.warn('⚠️ No data for metrics calculation');
                return;
            }

            const sorted = [...this.rawData].sort((a, b) =>
                new Date(a.date) - new Date(b.date)
            );

            const oiValues = sorted.map(d => parseFloat(d.value));

            // Current metrics
            this.currentOI = oiValues[oiValues.length - 1] || 0;
            const previousOI = oiValues[oiValues.length - 2] || this.currentOI;
            this.oiChange = ((this.currentOI - previousOI) / previousOI) * 100; // Percentage change

            // Statistical metrics (fallback if analytics API not available)
            // Analytics API will update these later if available (non-blocking enhancement)
            if (this.minOI === null || this.minOI === undefined) {
                this.minOI = Math.min(...oiValues);
            }
            if (this.maxOI === null || this.maxOI === undefined) {
                this.maxOI = Math.max(...oiValues);
            }

            // Price metrics
            if (this.priceData.length > 0) {
                this.currentPrice = this.priceData[this.priceData.length - 1].price;
                const previousPrice = this.priceData[this.priceData.length - 2]?.price || this.currentPrice;
                this.priceChange = ((this.currentPrice - previousPrice) / previousPrice) * 100;
                this.priceDataAvailable = true;
            } else {
                this.currentPrice = null;
                this.priceChange = null;
                this.priceDataAvailable = false;
            }

            console.log('✅ Metrics calculated from rawData:', {
                currentOI: this.currentOI,
                oiChange: this.oiChange,
                minOI: this.minOI,
                maxOI: this.maxOI
            });
        },

        /**
         * Update current values for summary cards display
         */
        updateCurrentValues() {
            // This function is called after calculateMetrics to ensure summary cards are updated
            // Values are already set in calculateMetrics, this is just for logging/debugging
            console.log('✅ Current values updated:', {
                currentOI: this.currentOI,
                oiChange: this.oiChange,
                currentPrice: this.currentPrice,
                priceChange: this.priceChange
            });
        },

        /**
         * Map analytics API response to UI state
         * Uses direct values from Open Interest analytics API
         */
        mapAnalyticsToState(analyticsData) {
            console.log('🔄 Mapping analytics data:', analyticsData);

            if (!analyticsData) {
                console.warn('⚠️ No analytics data provided, keeping defaults from calculateMetrics');
                return;
            }

            // Map trend from API (stable/increasing/decreasing)
            if (analyticsData.trend) {
                this.trend = analyticsData.trend;
                console.log('✅ Trend set from API:', this.trend);
            }

            // Map insights from API
            const insights = analyticsData.insights || {};

            // Update summary stats from API (only 4 cards: Current OI, Min OI, Max OI, Trend)
            if (insights.min_oi !== null && insights.min_oi !== undefined) {
                this.minOI = parseFloat(insights.min_oi);
            }
            if (insights.max_oi !== null && insights.max_oi !== undefined) {
                this.maxOI = parseFloat(insights.max_oi);
            }

            // Update current OI from API if available
            if (analyticsData.currentOI !== null && analyticsData.currentOI !== undefined) {
                this.currentOI = analyticsData.currentOI;
            }

            console.log('✅ Analytics mapped to state:', {
                trend: this.trend,
                currentOI: this.currentOI,
                minOI: this.minOI,
                maxOI: this.maxOI
            });
        },

        /**
         * Fetch analytics data from API (includes trend + insights)
         */
        async fetchAnalyticsData(isAutoRefresh = false) {
            if (this.analyticsLoading) {
                console.log('⏭️ Skip analytics fetch (already loading)');
                return;
            }

            // Throttle analytics on auto-refresh (20s)
            if (isAutoRefresh) {
                const now = Date.now();
                if (now - this.lastAnalyticsAt < 20000) {
                    console.log('⏭️ Skip analytics (throttled)');
                    return;
                }
            }

            // Logic to prevent analyticsLoading = true if isAutoRefresh is true
            // Auto-refresh should be silent (no skeleton)
            if (isAutoRefresh) {
                this.analyticsLoading = false; // Don't show skeleton during auto-refresh
            } else if (this.rawData.length === 0) {
                this.analyticsLoading = true; // Only for initial load without data
            } else {
                this.analyticsLoading = false; // Data already exists, no skeleton needed
            }

            try {
                const exchange = OpenInterestUtils.capitalizeExchange(this.selectedExchange);
                // Use same limit as history request for consistency
                const limit = this.selectedLimit === 'all' ? null : parseInt(this.selectedLimit, 10) || 1000;
                console.log(`[AN:START] interval=${this.selectedInterval} limit=${limit}`);

                const analyticsData = await this.apiService.fetchAnalytics(
                    this.selectedSymbol,
                    exchange,
                    this.selectedInterval,
                    limit,
                    false,
                    true
                );

                // Handle cancelled requests
                if (analyticsData === null) {
                    console.warn('[AN:CANCEL]');
                    return;
                }

                if (!analyticsData) {
                    console.warn('[AN:ERROR] null-response');
                    return;
                }

                this.analyticsData = analyticsData;

                // Map analytics data to UI state (includes trend + insights)
                this.mapAnalyticsToState(analyticsData);

                console.log('[AN:OK]', {
                    trend: this.trend,
                    currentOI: this.currentOI,
                    minOI: this.minOI,
                    maxOI: this.maxOI
                });

                // Save to cache after analytics loaded (if not auto-refresh)
                if (!isAutoRefresh) {
                    this.saveToCache();
                }
                this.lastAnalyticsAt = Date.now();

            } catch (error) {
                console.error('❌ Error loading analytics data:', error);
                // Don't update errorCount or stop auto-refresh for analytics errors
                // Metrics from calculateMetrics() will be used as fallback
            } finally {
                this.analyticsLoading = false;
            }
        },


        /**
         * Set chart interval - INSTANT SWITCH with in-memory cache
         */
        setChartInterval(interval) {
            if (this.selectedInterval === interval) return;
            console.log('🔄 Setting chart interval to:', interval);
            this.selectedInterval = interval;

            // Don't stop auto-refresh - let it continue with new state
            // Don't cancel requests - let them finish for caching
            // Don't set isLoading flag - allow instant switching

            // Load data immediately (will check in-memory cache first)
            this.loadData(false).catch(err => {
                console.error('Error loading data:', err);
            });
        },

        /**
         * Toggle chart type between line and candlestick
         */
        toggleChartType(type) {
            if (this.chartType === type) return;
            console.log('🔄 Toggle chart type to:', type);
            this.chartType = type;

            // Re-render chart with new type
            if (this.rawData && this.rawData.length > 0) {
                setTimeout(() => {
                    try {
                        this.chartManager.updateChart(this.rawData, this.priceData, this.chartType);
                    } catch (error) {
                        console.error('❌ Error updating chart type:', error);
                    }
                }, 100);
            }
        },

        /**
         * Update symbol
         */
        updateSymbol() {
            // Enforce allowed symbol (BTCUSDT only)
            if (this.selectedSymbol !== 'BTCUSDT') {
                this.selectedSymbol = 'BTCUSDT';
            }
            console.log('🔄 Updating symbol to:', this.selectedSymbol);
            this.loadData();
        },

        /**
         * Update exchange
         */
        updateExchange() {
            // Normalize to supported exchanges list
            const allowed = new Set(['OKX', 'Binance', 'HTX', 'Bitmex', 'Bitfinex', 'Bybit', 'Deribit', 'Gate', 'Kraken', 'KuCoin', 'CME', 'Bitget', 'dYdX', 'CoinEx', 'BingX', 'Coinbase', 'Gemini', 'Crypto.com', 'Hyperliquid', 'Bitunix', 'MEXC', 'WhiteBIT', 'Aster', 'Lighter', 'EdgeX', 'Drift', 'Paradex', 'Extended', 'ApeX Omni']);
            if (!allowed.has(this.selectedExchange)) {
                this.selectedExchange = 'Binance';
            }
            console.log('🔄 Updating exchange to:', this.selectedExchange);
            this.loadData();
        },

        /**
         * Update interval
         */
        updateInterval() {
            // Normalize to supported intervals
            const allowed = new Set(['1m', '5m', '15m', '1h', '4h', '8h', '1w']);
            if (!allowed.has(this.selectedInterval)) {
                this.selectedInterval = '8h';
            }
            console.log('🔄 Updating interval to:', this.selectedInterval);
            this.loadData();
        },

        /**
         * Refresh all data
         */
        refreshAll() {
            this.loadData();
        },

        /**
         * Cleanup on destroy
         */
        cleanup() {
            console.log('🧹 Cleaning up...');
            this.stopAutoRefresh();

            if (this.chartManager) {
                this.chartManager.destroy();
            }


            if (this.apiService) {
                this.apiService.cancelRequest();
            }
        },

        /**
         * Format Open Interest value
         */
        formatOI(value) {
            return OpenInterestUtils.formatOI(value);
        },

        /**
         * Format price
         */
        formatPrice(value) {
            return OpenInterestUtils.formatPrice(value);
        },

        /**
         * Format price with USD label
         */
        formatPriceUSD(value) {
            return OpenInterestUtils.formatPrice(value);
        },

        /**
         * Format change (percentage)
         */
        formatChange(value) {
            return OpenInterestUtils.formatChange(value);
        },

        /**
         * Get trend class
         */
        getTrendClass(value) {
            if (value > 0) return 'text-success';
            if (value < 0) return 'text-danger';
            return 'text-secondary';
        },

        /**
         * Get price trend class
         */
        getPriceTrendClass(value) {
            if (value > 0) return 'text-success';
            if (value < 0) return 'text-danger';
            return 'text-secondary';
        },

        /**
         * Get trend badge class
         */
        getTrendBadgeClass() {
            const trendMap = {
                'increasing': 'text-bg-success',
                'decreasing': 'text-bg-danger',
                'stable': 'text-bg-secondary'
            };
            return trendMap[this.trend] || 'text-bg-secondary';
        },

        /**
         * Get trend color class
         */
        getTrendColorClass() {
            const colorMap = {
                'increasing': 'text-success',
                'decreasing': 'text-danger',
                'stable': 'text-secondary'
            };
            return colorMap[this.trend] || 'text-secondary';
        },

        /**
         * Show error message
         */
        showError(message) {
            console.error('Error:', message);
            // Could add toast notification here
        },

        /**
         * Prefetch ALL intervals in background for smooth switching
         */
        async prefetchAllIntervals() {
            console.log('🔄 Prefetching all intervals in background...');

            const exchange = OpenInterestUtils.capitalizeExchange(this.selectedExchange);
            const prefetchLimit = 10; // Smaller limit for quicker prefetch

            // Prefetch all intervals except current one
            const intervals = ['1m', '5m', '15m', '1h', '4h', '8h', '1w'];

            for (const interval of intervals) {
                if (interval === this.selectedInterval) continue; // Skip current

                // Use requestIdleCallback to not block UI
                const prefetchOne = async () => {
                    try {
                        const cacheKey = `${interval}_${this.selectedLimit}`;

                        // Skip if already cached
                        if (this.intervalCache.has(cacheKey)) {
                            console.log(`[PF:SKIP:CACHED] interval=${interval}`);
                            return;
                        }

                        console.log(`[PF:START] interval=${interval} limit=${prefetchLimit}`);

                        // Fetch history data (mark as prefetch to use separate abort controller)
                        const data = await this.apiService.fetchHistory({
                            symbol: this.selectedSymbol,
                            exchange: exchange,
                            interval: interval,
                            limit: prefetchLimit,
                            with_price: false
                        }, true); // isPrefetch = true

                        if (data && data.length > 0) {
                            // Fetch analytics
                            const analyticsData = await this.apiService.fetchAnalytics(
                                this.selectedSymbol,
                                exchange,
                                interval,
                                prefetchLimit,
                                true
                            );

                            // Save to in-memory cache
                            this.intervalCache.set(cacheKey, {
                                data: data,
                                priceData: data.filter(d => d.price).map(d => ({ date: d.date, price: d.price })),
                                analytics: {
                                    currentOI: analyticsData?.currentOI || null,
                                    minOI: analyticsData?.insights?.min_oi ? parseFloat(analyticsData.insights.min_oi) : null,
                                    maxOI: analyticsData?.insights?.max_oi ? parseFloat(analyticsData.insights.max_oi) : null,
                                    trend: analyticsData?.trend || 'stable'
                                },
                                timestamp: Date.now()
                            });

                            console.log(`[PF:OK] interval=${interval} records=${data.length}`);
                        }
                    } catch (error) {
                        if (error && (error.code === 'TIMEOUT' || error.message === 'RequestTimeout')) {
                            console.warn(`[PF:TIMEOUT] interval=${interval}`);
                        } else {
                            console.warn('[PF:ERROR]', interval, error);
                        }
                    }
                };

                // Schedule prefetch using idle callback
                if (window.requestIdleCallback) {
                    window.requestIdleCallback(() => prefetchOne(), { timeout: 5000 });
                } else {
                    setTimeout(() => prefetchOne(), 1000);
                }
            }
        }
    };
}

