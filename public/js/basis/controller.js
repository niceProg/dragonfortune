/**
 * Basis & Term Structure Main Controller
 * Coordinates data fetching, chart rendering, and metrics calculation
 */

import { BasisAPIService } from './api-service.js';
import { ChartManager } from './chart-manager.js';
import { BasisUtils } from './utils.js';

export function createBasisController() {
    return {
        // Initialization flag
        initialized: false,

        // Services
        apiService: null,
        chartManager: null,
        termStructureChartManager: null,

        // Global state
        globalPeriod: '1d', // Default: 1D (1 day) - better for analytics API
        globalLoading: false, // Start false - optimistic UI (no skeleton)
        isLoading: false, // Flag to prevent multiple simultaneous loads
        selectedExchange: 'Binance',
        selectedSpotPair: 'BTC/USDT',
        selectedFuturesSymbol: 'BTCUSDT', // Default futures symbol
        selectedInterval: '1h',
        termStructureSymbol: 'BTC', // Symbol for term structure API (BTC, ETH)

        // Chart intervals (supported by API: 5m, 15m, 1h, 4h)
        chartIntervals: [
            { label: '5M', value: '5m' },
            { label: '15M', value: '15m' },
            { label: '1H', value: '1h' },
            { label: '4H', value: '4h' }
        ],

        // Time ranges (same pattern as funding-rate and perp-quarterly)
        timeRanges: [
            { label: '1D', value: '1d', days: 1 },
            { label: '7D', value: '7d', days: 7 },
            { label: '1M', value: '1m', days: 30 },
            { label: 'ALL', value: 'all', days: null } // null means use 2 years ago
        ],

        // Auto-refresh state
        refreshInterval: null,
        errorCount: 0,
        maxErrors: 3,

        // Data
        rawData: [],
        dataLoaded: false,

        // Summary metrics (from analytics API)
        currentBasis: null, // From history API (latest data point)
        avgBasis: null, // From analytics API
        basisAnnualized: null, // From analytics API
        basisVolatility: null, // From analytics API
        marketStructure: null, // From analytics API
        trend: null, // From analytics API

        // Analytics data
        analyticsData: null,
        analyticsLoading: false,

        // Term structure data
        termStructureData: null,
        termStructureLoading: false,

        // Chart state
        chartType: 'line', // 'line' or 'bar'

        /**
         * Initialize controller
         */
        async init() {
            // Prevent double initialization
            if (this.initialized) {
                console.warn('⚠️ Dashboard already initialized, skipping...');
                return;
            }

            this.initialized = true;
            console.log('🚀 Basis & Term Structure Dashboard initialized');

            // Initialize services IMMEDIATELY (non-blocking)
            this.apiService = new BasisAPIService();
            this.chartManager = new ChartManager('basisMainChart');
            this.termStructureChartManager = new ChartManager('basisTermStructureChart');

            // Set globalLoading = false initially (optimistic UI, no skeleton)
            this.globalLoading = false;
            this.analyticsLoading = false;
            this.termStructureLoading = false;

            // STEP 1: Load cache data INSTANT (no loading skeleton)
            const cacheLoaded = this.loadFromCache();
            if (cacheLoaded) {
                console.log('✅ Cache data loaded instantly - showing cached data');
                // Render charts immediately with cached data (don't wait Chart.js)
                if (this.chartManager && this.rawData.length > 0) {
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.chartManager.renderHistoryChart(this.rawData, this.rawData, this.rawData);
                        }, 10);
                    });
                }
                if (this.termStructureChartManager && this.termStructureData) {
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.termStructureChartManager.renderTermStructureChart(this.termStructureData);
                        }, 10);
                    });
                }
                
                // STEP 2: Fetch fresh data from endpoints (background, no skeleton)
                this.loadData(true).catch(err => {
                    console.warn('⚠️ Background fetch failed:', err);
                });
                this.loadTermStructure(true).catch(err => {
                    console.warn('⚠️ Background term structure fetch failed:', err);
                });
            } else {
                // No cache available - optimistic UI (no skeleton, show placeholder values)
                console.log('⚠️ No cache available - loading data with optimistic UI (no skeleton)');
                // IMPORTANT: Start fetch IMMEDIATELY (don't wait for Chart.js)
                await this.loadData(false).catch(err => {
                    console.warn('⚠️ Initial load failed:', err);
                });
                await this.loadTermStructure(false).catch(err => {
                    console.warn('⚠️ Initial term structure load failed:', err);
                });
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
         * Get cache key for current filter state
         */
        getCacheKey() {
            return `basis_dashboard_${this.selectedExchange}_${this.selectedSpotPair}_${this.selectedFuturesSymbol}_${this.selectedInterval}_${this.globalPeriod}`;
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
                        this.currentBasis = data.currentBasis;
                        this.avgBasis = data.avgBasis;
                        this.basisAnnualized = data.basisAnnualized;
                        this.basisVolatility = data.basisVolatility;
                        this.marketStructure = data.marketStructure;
                        this.trend = data.trend;
                        this.analyticsData = data.analyticsData;
                        this.termStructureData = data.termStructureData || null;
                        this.dataLoaded = true;
                        
                        console.log('✅ Cache loaded:', {
                            records: this.rawData.length,
                            termStructure: !!this.termStructureData,
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
                    currentBasis: this.currentBasis,
                    avgBasis: this.avgBasis,
                    basisAnnualized: this.basisAnnualized,
                    basisVolatility: this.basisVolatility,
                    marketStructure: this.marketStructure,
                    trend: this.trend,
                    analyticsData: this.analyticsData,
                    termStructureData: this.termStructureData
                };
                localStorage.setItem(cacheKey, JSON.stringify(data));
                console.log('💾 Data saved to cache:', cacheKey);
            } catch (error) {
                console.warn('⚠️ Cache save error:', error);
            }
        },
        
        /**
         * Load all data (analytics and history in parallel)
         * Optimized: Progressive Loading + Race Condition Prevention
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
            const isInitialLoad = this.rawData.length === 0;
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

            // Performance monitoring
            const loadStartTime = Date.now();
            console.log('⏱️ loadData() started at:', new Date().toISOString());

            this.errorCount = 0;

            try {
                const dateRange = this.getDateRange();
                
                // For initial load, use smaller limit (100) for faster response
                // Then load full data (5000) in background after first render
                // This provides instant feedback to user - chart appears in <500ms
                const calculatedLimit = 5000;
                const limit = isInitialLoad ? Math.min(100, calculatedLimit) : calculatedLimit;

                console.log('📅 Date Range Request:', {
                    period: this.globalPeriod,
                    startDate: dateRange.startDate.toISOString(),
                    endDate: dateRange.endDate.toISOString(),
                    days: Math.ceil((dateRange.endDate - dateRange.startDate) / (1000 * 60 * 60 * 24))
                });

                // ✅ OPTIMIZATION: Fetch critical data FIRST (for instant chart render)
                // Don't wait for analytics - fetch it in background after chart renders
                // This is similar to Open Interest optimization
                const historyData = await this.apiService.fetchHistory({
                    exchange: this.selectedExchange,
                    spotPair: this.selectedSpotPair,
                    futuresSymbol: this.selectedFuturesSymbol,
                    interval: this.selectedInterval,
                    limit: limit,
                    dateRange: dateRange
                });

                // Handle cancelled requests
                if (historyData === null) {
                    console.log('🚫 Request was cancelled');
                    return;
                }

                this.rawData = historyData;

                // Calculate current basis from latest history data
                if (this.rawData.length > 0) {
                    this.currentBasis = this.rawData[this.rawData.length - 1].basisAbs;
                }

                // Hide skeleton immediately after critical data is loaded
                // Don't wait for analytics - chart will render with this data
                if (shouldShowLoading) {
                    this.globalLoading = false;
                    console.log('⚡ Critical data ready, hiding skeleton');
                }

                // Render chart IMMEDIATELY (before analytics completes for faster perceived performance)
                // Chart is the most important visual element - show it ASAP
                // Don't wait for Chart.js - it will render when ready (non-blocking)
                const chartRenderStart = Date.now();
                const renderChart = () => {
                    try {
                        if (this.chartManager && this.rawData.length > 0) {
                            // Extract spot price and futures price from rawData
                            const spotPriceData = this.rawData.map(d => ({ ts: d.ts, price: d.spotPrice }));
                            const futuresPriceData = this.rawData.map(d => ({ ts: d.ts, price: d.futuresPrice }));
                            this.chartManager.renderHistoryChart(
                                this.rawData, // basis data
                                spotPriceData, // spot price
                                futuresPriceData // futures price
                            );
                            const chartRenderTime = Date.now() - chartRenderStart;
                            console.log('⏱️ Chart render time:', chartRenderTime + 'ms');
                        }
                    } catch (error) {
                        console.error('❌ Error rendering chart:', error);
                        setTimeout(() => {
                            if (this.chartManager && this.rawData.length > 0) {
                                const spotPriceData = this.rawData.map(d => ({ ts: d.ts, price: d.spotPrice }));
                                const futuresPriceData = this.rawData.map(d => ({ ts: d.ts, price: d.futuresPrice }));
                                this.chartManager.renderHistoryChart(this.rawData, spotPriceData, futuresPriceData);
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

                // Fetch analytics AFTER chart render (non-blocking, fire-and-forget)
                // This allows chart to appear instantly, analytics updates summary cards later
                // Following Open Interest optimization pattern
                if (!isInitialLoad || !isAutoRefresh) {
                    this.fetchAnalyticsData().catch(err => {
                        console.warn('⚠️ Analytics fetch failed:', err);
                    });
                } else {
                    // Initial load: load analytics in background after chart render
                    setTimeout(() => {
                        this.fetchAnalyticsData(true).catch(err => {
                            console.warn('⚠️ Background analytics fetch failed:', err);
                        });
                    }, 100);
                }

                this.dataLoaded = true;

                // Log total load time
                const totalLoadTime = Date.now() - loadStartTime;
                console.log('⏱️ Total loadData() time:', totalLoadTime + 'ms');
                console.log('✅ Critical data loaded:', {
                    history: historyData.length
                });

                // If this was initial load with reduced limit, load full data in background
                if (isInitialLoad && limit < calculatedLimit && this.rawData.length > 0) {
                    console.log('🔄 Initial load complete, loading full dataset in background...', {
                        currentLimit: limit,
                        fullLimit: calculatedLimit
                    });
                    
                    const capturedDateRange = dateRange;
                    
                    // Load full data in background IMMEDIATELY (no delay)
                    this.isLoading = false; // Reset flag so we can load again
                    
                    const scheduleFullDataLoad = (callback) => {
                        if (window.requestIdleCallback) {
                            window.requestIdleCallback(callback, { timeout: 50 });
                        } else {
                            setTimeout(callback, 0);
                        }
                    };

                    scheduleFullDataLoad(async () => {
                        try {
                            const fullHistoryData = await this.apiService.fetchHistory({
                                exchange: this.selectedExchange,
                                spotPair: this.selectedSpotPair,
                                futuresSymbol: this.selectedFuturesSymbol,
                                interval: this.selectedInterval,
                                limit: calculatedLimit,
                                dateRange: capturedDateRange
                            });

                            if (fullHistoryData && fullHistoryData.length > 0) {
                                this.rawData = fullHistoryData;
                                
                                // Calculate current basis from latest history data
                                if (this.rawData.length > 0) {
                                    this.currentBasis = this.rawData[this.rawData.length - 1].basisAbs;
                                }

                                // Update chart with full data
                                if (this.chartManager) {
                                    const spotPriceData = this.rawData.map(d => ({ ts: d.ts, price: d.spotPrice }));
                                    const futuresPriceData = this.rawData.map(d => ({ ts: d.ts, price: d.futuresPrice }));
                                    this.chartManager.renderHistoryChart(this.rawData, spotPriceData, futuresPriceData);
                                }

                                // Save updated cache
                                this.saveToCache();

                                console.log('✅ Full dataset loaded and chart updated:', {
                                    records: this.rawData.length,
                                    previousRecords: limit
                                });
                            }
                        } catch (err) {
                            console.warn('⚠️ Background full data load failed (using initial data):', err);
                        }
                    });
                } else {
                    // Normal load complete, reset isLoading flag and save cache
                    this.isLoading = false;
                    this.saveToCache();
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
                    this.stopAutoRefresh();
                    console.error('❌ Max errors reached, stopping auto-refresh');
                }
            } finally {
                // Always reset loading flags
                this.isLoading = false;
                
                // Hide skeleton only if it was shown (initial load)
                if (shouldShowLoading) {
                    this.globalLoading = false;
                    console.log('✅ Initial load complete - skeleton hidden');
                }
            }
        },

        /**
         * Fetch analytics data (non-blocking, after chart render)
         * Following Open Interest optimization pattern
         */
        async fetchAnalyticsData(isAutoRefresh = false) {
            if (this.analyticsLoading) {
                console.log('⏭️ Skip analytics fetch (already loading)');
                return;
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

            // Cancel any previous request before starting new one
            if (this.apiService && this.apiService.analyticsAbortController) {
                this.apiService.analyticsAbortController.abort();
            }

            try {
                const dateRange = this.getDateRange();
                const days = dateRange.startDate && dateRange.endDate
                    ? Math.ceil((dateRange.endDate - dateRange.startDate) / (1000 * 60 * 60 * 24))
                    : 7;
                
                // Use fixed limit 5000 for analytics
                const limit = 5000;

                console.log('📡 Fetching analytics with:', { 
                    days, 
                    interval: this.selectedInterval, 
                    limit,
                    exchange: this.selectedExchange,
                    spotPair: this.selectedSpotPair,
                    futuresSymbol: this.selectedFuturesSymbol
                });

                const data = await this.apiService.fetchAnalytics({
                    exchange: this.selectedExchange,
                    spotPair: this.selectedSpotPair,
                    futuresSymbol: this.selectedFuturesSymbol,
                    interval: this.selectedInterval,
                    limit: limit
                });

                if (data) {
                    this.analyticsData = data;
                    // Immediately update state when data is received
                    this.mapAnalyticsToState(data);
                    console.log('✅ Analytics data stored and state updated');

                    // Save to cache after analytics loaded (if not auto-refresh)
                    if (!isAutoRefresh) {
                        this.saveToCache();
                    }
                } else {
                    console.warn('⚠️ Analytics API returned null');
                    // Don't clear existing state if API returns null (might be temporary error)
                    // Only clear if explicitly needed
                }

                return data;

            } catch (error) {
                // Handle AbortError gracefully
                if (error.name === 'AbortError') {
                    console.log('⏭️ Analytics request was cancelled');
                    return null;
                }
                console.error('❌ Error fetching analytics:', error);
                // Don't throw - analytics is optional, chart can still work without it
                return null;
            } finally {
                // Reset analytics loading flag
                this.analyticsLoading = false;
            }
        },

        /**
         * Map analytics API response to UI state
         */
        mapAnalyticsToState(analyticsData) {
            if (!analyticsData) {
                console.warn('⚠️ Analytics data is null or empty - preserving existing state');
                // Don't clear existing state - preserve what we have
                // Only clear if this is the first load and we explicitly know there's no data
                if (!this.analyticsData && !this.dataLoaded) {
                    // First load, no cached data - set to null to show placeholders
                    this.marketStructure = null;
                    this.trend = null;
                    this.avgBasis = null;
                    this.basisAnnualized = null;
                    this.basisVolatility = null;
                }
                // Otherwise, keep existing state
                return;
            }

            console.log('📊 Mapping analytics data:', {
                market_structure: analyticsData.market_structure,
                trend: analyticsData.trend,
                basis_abs: analyticsData.basis_abs,
                basis_annualized: analyticsData.basis_annualized,
                basis_volatility: analyticsData.basis_volatility
            });

            // Map all analytics fields - always update when valid data is received
            this.avgBasis = analyticsData.basis_abs !== undefined && analyticsData.basis_abs !== null 
                ? parseFloat(analyticsData.basis_abs) 
                : null;
            this.basisAnnualized = analyticsData.basis_annualized !== undefined && analyticsData.basis_annualized !== null
                ? parseFloat(analyticsData.basis_annualized)
                : null;
            this.basisVolatility = analyticsData.basis_volatility !== undefined && analyticsData.basis_volatility !== null
                ? parseFloat(analyticsData.basis_volatility)
                : null;
            this.marketStructure = analyticsData.market_structure || null;
            this.trend = analyticsData.trend || null;

            console.log('✅ State updated:', {
                marketStructure: this.marketStructure,
                trend: this.trend,
                avgBasis: this.avgBasis,
                basisAnnualized: this.basisAnnualized,
                basisVolatility: this.basisVolatility
            });
        },

        /**
         * Load term structure data
         */
        async loadTermStructure(isAutoRefresh = false) {
            // Logic to prevent termStructureLoading = true if isAutoRefresh is true
            // Auto-refresh should be silent (no skeleton)
            if (isAutoRefresh) {
                this.termStructureLoading = false; // Don't show skeleton during auto-refresh
            } else if (!this.termStructureData) {
                this.termStructureLoading = true; // Only for initial load without data
            } else {
                this.termStructureLoading = false; // Data already exists, no skeleton needed
            }

            try {
                const data = await this.apiService.fetchTermStructure({
                    symbol: this.termStructureSymbol, // Use termStructureSymbol (BTC or ETH)
                    exchange: this.selectedExchange,
                    limit: 1000
                });

                // Handle cancelled requests
                if (data === null) {
                    console.log('🚫 Term structure request was cancelled');
                    return;
                }

                this.termStructureData = data;

                // Save to cache after term structure loaded (if not auto-refresh)
                if (!isAutoRefresh) {
                    this.saveToCache();
                }

                // Render term structure chart IMMEDIATELY (non-blocking)
                const renderTermStructureChart = () => {
                    try {
                        if (data && data.basis_curve && this.termStructureChartManager) {
                            this.termStructureChartManager.renderTermStructureChart(data);
                        }
                    } catch (error) {
                        console.error('❌ Error rendering term structure chart:', error);
                        setTimeout(() => {
                            if (data && data.basis_curve && this.termStructureChartManager) {
                                this.termStructureChartManager.renderTermStructureChart(data);
                            }
                        }, 50);
                    }
                };

                // Try immediate render (Chart.js might already be loaded)
                if (typeof Chart !== 'undefined') {
                    renderTermStructureChart();
                } else {
                    // Chart.js not ready yet - wait for it (non-blocking)
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        renderTermStructureChart();
                    }).catch(() => {
                        console.warn('⚠️ Chart.js not available, will retry later');
                        setTimeout(renderTermStructureChart, 100);
                    });
                }

                console.log('✅ Term structure loaded');

            } catch (error) {
                // Handle AbortError gracefully
                if (error.name === 'AbortError') {
                    console.log('⏭️ Term structure request was cancelled');
                    return;
                }
                console.error('❌ Error loading term structure:', error);
            } finally {
                // Reset term structure loading flag
                this.termStructureLoading = false;
            }
        },

        /**
         * Get date range from selected period
         */
        getDateRange() {
            const now = new Date();
            const range = this.timeRanges.find(r => r.value === this.globalPeriod);
            const days = range ? range.days : 7;

            let startDate;
            let endDate = new Date(now);

            if (this.globalPeriod === 'all') {
                startDate = new Date(now.getFullYear() - 2, 0, 1); // 2 years ago
            } else {
                startDate = new Date(now);
                startDate.setDate(startDate.getDate() - days);
            }

            endDate.setHours(23, 59, 59, 999);

            return { startDate, endDate };
        },

        /**
         * Format basis value
         */
        formatBasis(value) {
            return BasisUtils.formatBasis(value);
        },

        /**
         * Format basis annualized
         */
        formatBasisAnnualized(value) {
            return BasisUtils.formatBasisAnnualized(value);
        },

        /**
         * Format market structure
         */
        formatMarketStructure(value) {
            return BasisUtils.formatMarketStructure(value);
        },

        /**
         * Format trend
         */
        formatTrend(value) {
            return BasisUtils.formatTrend(value);
        },

        /**
         * Get market structure badge class
         */
        getMarketStructureBadgeClass() {
            if (!this.marketStructure) return 'text-bg-secondary';
            
            const structure = this.marketStructure.toLowerCase();
            if (structure.includes('contango')) {
                return 'text-bg-success'; // Green for contango
            } else if (structure.includes('backwardation')) {
                return 'text-bg-danger'; // Red for backwardation
            }
            return 'text-bg-info';
        },

        /**
         * Get trend badge class
         */
        getTrendBadgeClass() {
            if (!this.trend) return 'text-bg-secondary';
            
            const trend = this.trend.toLowerCase();
            if (trend === 'increasing') {
                return 'text-bg-success';
            } else if (trend === 'decreasing') {
                return 'text-bg-danger';
            }
            return 'text-bg-secondary';
        },

        /**
         * Get trend color class
         */
        getTrendColorClass() {
            if (!this.trend) return '';
            
            const trend = this.trend.toLowerCase();
            if (trend === 'increasing') {
                return 'text-success';
            } else if (trend === 'decreasing') {
                return 'text-danger';
            }
            return '';
        },

        /**
         * Refresh all data
         */
        refreshAll() {
            this.loadData();
            this.loadTermStructure();
        },

        /**
         * Set time range
         */
        setTimeRange(range) {
            this.globalPeriod = range;
            this.loadData();
        },

        /**
         * Update exchange
         */
        updateExchange() {
            this.loadData();
            this.loadTermStructure(); // Term structure also depends on exchange
        },

        /**
         * Get available futures symbols based on selected spot pair
         */
        getAvailableFuturesSymbols() {
            if (this.selectedSpotPair === 'BTC/USDT') {
                return [
                    'BTCUSDT',
                    'BTC-USDT-SWAP',
                    'BTC_PERP',
                    'BTC-PERP',
                    'BTCUSDT-PERP',
                    'BTC-PERPETUAL',
                    'BTCPERP',
                    'BTCUSDT_UMCBL',
                    'BTC_USDT',
                    'BTC-31OCT25',
                    'BTCUSDT_251226',
                    'XBTUSD',
                    'tBTCF0:USTF0'
                ];
            } else if (this.selectedSpotPair === 'ETH/USDT') {
                return [
                    'ETHUSDT',
                    'ETH-USDT-SWAP',
                    'ETH_PERP',
                    'ETH-PERP',
                    'ETHUSD-PERP',
                    'ETH-PERPETUAL',
                    'ETHPERP',
                    'ETH_USDT',
                    'ETH-31OCT25',
                    'ETHUSDT_251226'
                ];
            }
            return [];
        },

        /**
         * Update spot pair
         */
        updateSpotPair() {
            // Update futures symbol to first available symbol when spot pair changes
            const availableSymbols = this.getAvailableFuturesSymbols();
            if (availableSymbols.length > 0) {
                this.selectedFuturesSymbol = availableSymbols[0];
            }
            this.loadData();
        },

        /**
         * Update futures symbol
         */
        updateFuturesSymbol() {
            this.loadData();
        },

        /**
         * Update interval
         */
        updateInterval() {
            this.loadData();
        },

        /**
         * Set chart interval
         */
        setChartInterval(interval) {
            this.selectedInterval = interval;
            this.loadData();
        },

        /**
         * Toggle chart type
         */
        toggleChartType(type) {
            this.chartType = type;
            if (this.rawData && this.rawData.length > 0) {
                this.chartManager.updateChart(this.rawData, type);
            }
        },

        /**
         * Start auto-refresh
         */
        startAutoRefresh() {
            this.stopAutoRefresh(); // Clear any existing interval
            
            this.refreshInterval = setInterval(() => {
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
                
                this.loadTermStructure(true).catch(err => {
                    // Handle errors gracefully
                    if (err.name !== 'AbortError') {
                        console.warn('⚠️ Auto-refresh term structure error:', err);
                    }
                }); // Silent update - no skeleton shown
            }, 5000); // 5 seconds interval (same as funding-rate)
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
         * Cleanup on component destroy
         */
        cleanup() {
            this.stopAutoRefresh();
            if (this.apiService) {
                this.apiService.cancelAllRequests();
            }
            if (this.chartManager) {
                this.chartManager.destroy();
            }
            if (this.termStructureChartManager) {
                this.termStructureChartManager.destroy();
            }
        }
    };
}

