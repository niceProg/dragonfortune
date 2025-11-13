/**
 * Long Short Ratio Main Controller
 * Coordinates data fetching, chart rendering, and metrics calculation
 */

import { LongShortRatioAPIService } from './api-service.js';
import { ChartManager } from './chart-manager.js';
import { LongShortRatioUtils } from './utils.js';

export function createLongShortRatioController() {
    return {
        // Initialization flag
        initialized: false,

        // Services
        apiService: null,
        mainChartManager: null,
        comparisonChartManager: null,

        // Global state
        globalPeriod: '1d',
        globalLoading: false, // Start false - optimistic UI (no skeleton)
        isLoading: false, // Flag to prevent multiple simultaneous loads
        selectedExchange: 'Binance',
        selectedSymbol: 'BTCUSDT',
        selectedInterval: '1h',
        scaleType: 'linear',
        chartType: 'line',
        
        // Time ranges
        timeRanges: [],
        
        // Chart intervals
        chartIntervals: [
            { label: '1M', value: '1m' },
            { label: '5M', value: '5m' },
            { label: '15M', value: '15m' },
            { label: '1H', value: '1h' },
            { label: '4H', value: '4h' },
            { label: '8H', value: '8h' },
            { label: '1W', value: '1w' }
        ],
        
        // Auto-refresh state
        refreshInterval: null,
        errorCount: 0,
        maxErrors: 3,

        // Data containers (from Internal API)
        topAccountData: [],      // Top trader accounts ratio (/api/long-short-ratio/top-accounts)
        topPositionData: [],     // Top trader positions ratio (/api/long-short-ratio/top-positions)

        // Analytics data (from internal API)
        overviewData: null,
        analyticsData: null,
        analyticsLoading: false,

        // Current metrics
        currentTopAccountRatio: null,
        currentTopPositionRatio: null,
        topAccountRatioChange: 0,
        topPositionRatioChange: 0,

        // Market sentiment
        marketSentiment: 'Balanced',
        sentimentStrength: 'Normal',
        sentimentDescription: 'Loading...',
        crowdingLevel: 'Balanced',

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
            console.log('🚀 Long Short Ratio Dashboard initialized');

            // Initialize services IMMEDIATELY (non-blocking)
            this.apiService = new LongShortRatioAPIService();
            this.mainChartManager = new ChartManager('longShortRatioMainChart');
            this.positionsChartManager = new ChartManager('longShortRatioPositionsChart');
            this.comparisonChartManager = new ChartManager('longShortRatioComparisonChart');

            // Set globalLoading = false initially (optimistic UI, no skeleton)
            this.globalLoading = false;
            this.analyticsLoading = false;

            // Initialize time ranges (simplified: 1D, 7D, 1M, ALL)
            this.timeRanges = [
                { label: '1D', value: '1d', days: 1 },
                { label: '7D', value: '7d', days: 7 },
                { label: '1M', value: '1m', days: 30 },
                { label: 'ALL', value: 'all', days: 730 }
                // Note: YTD and 1Y commented out for future use
                // { label: 'YTD', value: 'ytd', days: LongShortRatioUtils.getYTDDays() },
                // { label: '1Y', value: '1y', days: 365 }
            ];

            // STEP 1: Load cache data INSTANT (no loading skeleton)
            const cacheLoaded = this.loadFromCache();
            if (cacheLoaded) {
                console.log('✅ Cache data loaded instantly - showing cached data');
                // Render charts immediately with cached data (don't wait Chart.js)
                if (this.mainChartManager && this.topAccountData.length > 0) {
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.mainChartManager.renderMainChart(this.topAccountData, this.chartType);
                        }, 10);
                    });
                }
                if (this.positionsChartManager && this.topPositionData.length > 0) {
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.positionsChartManager.renderPositionsChart(this.topPositionData, this.chartType);
                        }, 10);
                    });
                }
                if (this.comparisonChartManager) {
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.comparisonChartManager.renderComparisonChart([], this.topAccountData, this.topPositionData);
                        }, 10);
                    });
                }
                
                // STEP 2: Fetch fresh data from endpoints (background, no skeleton)
                this.loadAllData(true).catch(err => {
                    console.warn('⚠️ Background fetch failed:', err);
                });
            } else {
                // No cache available - optimistic UI (no skeleton, show placeholder values)
                console.log('⚠️ No cache available - loading data with optimistic UI (no skeleton)');
                // IMPORTANT: Start fetch IMMEDIATELY (don't wait for Chart.js)
                await this.loadAllData(false).catch(err => {
                    console.warn('⚠️ Initial load failed:', err);
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
         * Start auto-refresh
         */
        startAutoRefresh() {
            this.stopAutoRefresh();

            const intervalMs = 5000; // 5 seconds

            this.refreshInterval = setInterval(() => {
                if (document.hidden) return;
                if (this.globalLoading) return; // Skip if showing skeleton
                if (this.isLoading) return; // Skip if already loading (prevent race condition)
                if (this.errorCount >= this.maxErrors) {
                    console.error('❌ Too many errors, stopping auto refresh');
                    this.stopAutoRefresh();
                    return;
                }

                console.log('🔄 Auto-refresh triggered');
                this.loadAllData(true).catch(err => {
                    // Handle errors gracefully (AbortError expected during rapid refreshes)
                    if (err.name !== 'AbortError') {
                        console.warn('⚠️ Auto-refresh error:', err);
                    }
                });
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
         * Get cache key for current filter state
         */
        getCacheKey() {
            return `lsr_dashboard_${this.selectedSymbol}_${this.selectedExchange}_${this.selectedInterval}_${this.globalPeriod}`;
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
                    
                    if (cacheAge < maxAge && data.topAccountData && data.topAccountData.length > 0) {
                        this.topAccountData = data.topAccountData;
                        this.topPositionData = data.topPositionData || [];
                        this.currentTopAccountRatio = data.currentTopAccountRatio;
                        this.currentTopPositionRatio = data.currentTopPositionRatio;
                        this.topAccountRatioChange = data.topAccountRatioChange || 0;
                        this.topPositionRatioChange = data.topPositionRatioChange || 0;
                        this.marketSentiment = data.marketSentiment || 'Balanced';
                        this.sentimentStrength = data.sentimentStrength || 'Normal';
                        this.analyticsData = data.analyticsData;
                        
                        console.log('✅ Cache loaded:', {
                            topAccountRecords: this.topAccountData.length,
                            topPositionRecords: this.topPositionData.length,
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
                    topAccountData: this.topAccountData,
                    topPositionData: this.topPositionData,
                    currentTopAccountRatio: this.currentTopAccountRatio,
                    currentTopPositionRatio: this.currentTopPositionRatio,
                    topAccountRatioChange: this.topAccountRatioChange,
                    topPositionRatioChange: this.topPositionRatioChange,
                    marketSentiment: this.marketSentiment,
                    sentimentStrength: this.sentimentStrength,
                    analyticsData: this.analyticsData
                };
                localStorage.setItem(cacheKey, JSON.stringify(data));
                console.log('💾 Data saved to cache:', cacheKey);
            } catch (error) {
                console.warn('⚠️ Cache save error:', error);
            }
        },
        
        /**
         * Load all data (FASE 1: Prioritize Internal API)
         * Optimized: Progressive Loading + Race Condition Prevention
         */
        async loadAllData(isAutoRefresh = false) {
            // Guard: Skip if already loading (prevent race condition)
            if (this.isLoading) {
                console.log('⏭️ Skip load (already loading)');
                return;
            }

            // Set loading flag to prevent multiple simultaneous loads
            this.isLoading = true;

            // Only show loading skeleton on initial load (hard refresh)
            // Auto-refresh should be silent (no skeleton) since data already exists
            const isInitialLoad = this.topAccountData.length === 0;
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
            console.log('⏱️ loadAllData() started at:', new Date().toISOString());

            this.errorCount = 0;

            try {
                const timeRange = LongShortRatioUtils.getTimeRange(this.globalPeriod, this.timeRanges);
                const calculatedLimit = LongShortRatioUtils.calculateLimit(
                    this.timeRanges.find(r => r.value === this.globalPeriod)?.days || 1,
                    this.selectedInterval
                );
                
                // For initial load, use smaller limit (100) for faster response
                // Then load full data in background after first render
                // This provides instant feedback to user - chart appears in <500ms
                const limit = isInitialLoad ? Math.min(100, calculatedLimit) : calculatedLimit;

                console.log('📡 Loading Long Short Ratio data (FASE 1: Internal API Priority)...', {
                    period: this.globalPeriod,
                    exchange: this.selectedExchange,
                    symbol: this.selectedSymbol,
                    interval: this.selectedInterval,
                    limit: limit,
                    supportedExchanges: ['Binance', 'Bybit', 'CoinEx'],
                    supportedIntervals: ['1m', '5m', '15m', '1h', '4h', '8h', '1w']
                });

                // Calculate date range for filtering
                const dateRange = this.getDateRange();
                console.log('📅 Date Range Filter:', {
                    period: this.globalPeriod,
                    from: dateRange.startDate.toLocaleString(),
                    to: dateRange.endDate.toLocaleString(),
                    days: this.getDateRangeDays()
                });

                // ✅ OPTIMIZATION: Fetch critical data FIRST (for instant chart render)
                // Don't wait for analytics - fetch it in background after chart renders
                // This is similar to Open Interest optimization
                const topAccountsPromise = this.apiService.fetchTopAccounts({
                    symbol: this.selectedSymbol,
                    exchange: this.selectedExchange,
                    interval: this.selectedInterval,
                    limit: limit,
                    dateRange: dateRange
                });
                
                const topPositionsPromise = this.apiService.fetchTopPositions({
                    symbol: this.selectedSymbol,
                    exchange: this.selectedExchange,
                    interval: this.selectedInterval,
                    limit: limit,
                    dateRange: dateRange
                });

                // Wait for critical data only (for chart)
                const [topAccountsResult, topPositionsResult] = await Promise.allSettled([
                    topAccountsPromise,
                    topPositionsPromise
                ]);

                // Process critical data
                if (topAccountsResult.status === 'fulfilled' && topAccountsResult.value) {
                    this.topAccountData = topAccountsResult.value;
                    console.log('✅ Top Accounts data loaded:', this.topAccountData.length, 'records');
                } else if (topAccountsResult.status === 'rejected') {
                    console.error('❌ Error loading top accounts:', topAccountsResult.reason);
                }
                
                if (topPositionsResult.status === 'fulfilled' && topPositionsResult.value) {
                    this.topPositionData = topPositionsResult.value;
                    console.log('✅ Top Positions data loaded:', this.topPositionData.length, 'records');
                } else if (topPositionsResult.status === 'rejected') {
                    console.error('❌ Error loading top positions:', topPositionsResult.reason);
                }

                // Update current values from critical data (for summary cards)
                this.updateCurrentValues();

                // Hide skeleton immediately after critical data is loaded
                // Don't wait for analytics - chart will render with this data
                if (shouldShowLoading) {
                    this.globalLoading = false;
                    console.log('⚡ Critical data ready, hiding skeleton');
                }

                // Taker Buy/Sell data removed (Exchange Rankings section is hidden)

                // Render charts IMMEDIATELY (before analytics completes for faster perceived performance)
                // Chart is the most important visual element - show it ASAP
                // Don't wait for Chart.js - it will render when ready (non-blocking)
                const renderCharts = () => {
                    try {
                        // CRITICAL: Clone data BEFORE passing to ChartManager to break Alpine.js Proxy
                        // This prevents Chart.js "Maximum call stack size exceeded" error
                        const clonedTopAccounts = this.topAccountData.length > 0 
                            ? JSON.parse(JSON.stringify(this.topAccountData)) 
                            : [];
                        const clonedTopPositions = this.topPositionData.length > 0 
                            ? JSON.parse(JSON.stringify(this.topPositionData)) 
                            : [];

                        // Render main chart (Top Account Ratio & Distribution)
                        if (this.mainChartManager && clonedTopAccounts.length > 0) {
                            if (this.mainChartManager.chart) {
                                this.mainChartManager.updateRatioDistributionData(clonedTopAccounts, false);
                            } else {
                                this.mainChartManager.renderMainChart(
                                    clonedTopAccounts,
                                    this.chartType
                                );
                            }
                        }

                        // Render positions chart (Top Positions Ratio & Distribution)
                        if (this.positionsChartManager && clonedTopPositions.length > 0) {
                            if (this.positionsChartManager.chart) {
                                this.positionsChartManager.updateRatioDistributionData(clonedTopPositions, true);
                            } else {
                                this.positionsChartManager.renderPositionsChart(
                                    clonedTopPositions,
                                    this.chartType
                                );
                            }
                        }

                        // Render comparison chart (Top Account vs Top Position ratio lines only)
                        if (this.comparisonChartManager && (clonedTopAccounts.length > 0 || clonedTopPositions.length > 0)) {
                            if (this.comparisonChartManager.chart) {
                                this.comparisonChartManager.updateComparisonChartData(
                                    [], // No global account data (redundant with top accounts)
                                    clonedTopAccounts,
                                    clonedTopPositions
                                );
                            } else {
                                this.comparisonChartManager.renderComparisonChart(
                                    [], // No global account data (redundant with top accounts)
                                    clonedTopAccounts,
                                    clonedTopPositions
                                );
                            }
                        }
                    } catch (error) {
                        console.error('❌ Error rendering charts:', error);
                        setTimeout(() => {
                            // Retry chart rendering (always use full render on retry, not update)
                            if (this.mainChartManager && this.topAccountData.length > 0) {
                                this.mainChartManager.renderMainChart(this.topAccountData, this.chartType);
                            }
                            if (this.positionsChartManager && this.topPositionData.length > 0) {
                                this.positionsChartManager.renderPositionsChart(this.topPositionData, this.chartType);
                            }
                            if (this.comparisonChartManager && (this.topAccountData.length > 0 || this.topPositionData.length > 0)) {
                                this.comparisonChartManager.renderComparisonChart([], this.topAccountData, this.topPositionData);
                            }
                        }, 50);
                    }
                };
                
                // Try immediate render (Chart.js might already be loaded)
                if (typeof Chart !== 'undefined') {
                    renderCharts();
                } else {
                    // Chart.js not ready yet - wait for it (non-blocking)
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        renderCharts();
                    }).catch(() => {
                        console.warn('⚠️ Chart.js not available, will retry later');
                        setTimeout(renderCharts, 100);
                    });
                }

                // Fetch analytics and overview data AFTER chart render (non-blocking, fire-and-forget)
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

                // Log total load time
                const totalLoadTime = Date.now() - loadStartTime;
                console.log('⏱️ Total loadAllData() time:', totalLoadTime + 'ms');
                console.log('✅ Critical data loaded successfully (FASE 1: Internal API Priority)');

                // If this was initial load with reduced limit, load full data in background
                if (isInitialLoad && limit < calculatedLimit && this.topAccountData.length > 0) {
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
                            const fullTopAccounts = await this.apiService.fetchTopAccounts({
                                symbol: this.selectedSymbol,
                                exchange: this.selectedExchange,
                                interval: this.selectedInterval,
                                limit: 5000,
                                dateRange: capturedDateRange
                            });
                            const fullTopPositions = await this.apiService.fetchTopPositions({
                                symbol: this.selectedSymbol,
                                exchange: this.selectedExchange,
                                interval: this.selectedInterval,
                                limit: 5000,
                                dateRange: capturedDateRange
                            });

                            if (fullTopAccounts && fullTopAccounts.length > 0) {
                                this.topAccountData = fullTopAccounts;
                                this.updateCurrentValues();
                                if (this.mainChartManager) {
                                    // Clone before passing to ChartManager
                                    const clonedData = JSON.parse(JSON.stringify(this.topAccountData));
                                    if (this.mainChartManager.chart) {
                                        this.mainChartManager.updateRatioDistributionData(clonedData, false);
                                    } else {
                                        this.mainChartManager.renderMainChart(clonedData, this.chartType);
                                    }
                                }
                            }
                            if (fullTopPositions && fullTopPositions.length > 0) {
                                this.topPositionData = fullTopPositions;
                                this.updateCurrentValues();
                                if (this.positionsChartManager) {
                                    // Clone before passing to ChartManager
                                    const clonedData = JSON.parse(JSON.stringify(this.topPositionData));
                                    if (this.positionsChartManager.chart) {
                                        this.positionsChartManager.updateRatioDistributionData(clonedData, true);
                                    } else {
                                        this.positionsChartManager.renderPositionsChart(clonedData, this.chartType);
                                    }
                                }
                            }

                            this.saveToCache();
                            console.log('✅ Full dataset loaded and charts updated');
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
         * Fetch analytics and overview data (non-blocking, after chart render)
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
            } else if (this.topAccountData.length === 0) {
                this.analyticsLoading = true; // Only for initial load without data
            } else {
                this.analyticsLoading = false; // Data already exists, no skeleton needed
            }

            try {
                console.log('📡 Fetching analytics and overview data...');
                
                const limit = LongShortRatioUtils.calculateLimit(
                    this.timeRanges.find(r => r.value === this.globalPeriod)?.days || 1,
                    this.selectedInterval
                );

                // Fetch analytics and overview in parallel
                const [analyticsResult, overviewResult] = await Promise.allSettled([
                    this.apiService.fetchAnalytics({
                        symbol: this.selectedSymbol,
                        exchange: this.selectedExchange,
                        interval: this.selectedInterval,
                        ratio_type: 'accounts',
                        limit: limit
                    }),
                    this.apiService.fetchOverview({
                        symbol: this.selectedSymbol,
                        interval: this.selectedInterval,
                        limit: limit
                    })
                ]);

                if (analyticsResult.status === 'fulfilled' && analyticsResult.value) {
                    // Normalize analytics payload: extract all fields from response
                    const raw = analyticsResult.value;
                    const normalized = Array.isArray(raw) ? (raw[0] || null) : raw;
                    this.analyticsData = normalized ? {
                        // Core analytics fields
                        positioning: normalized.positioning ?? null,
                        trend: normalized.trend ?? null,
                        data_points: normalized.data_points ?? null,
                        exchange: normalized.exchange ?? null,
                        symbol: normalized.symbol ?? null,
                        // Ratio statistics (for summary cards)
                        ratio_stats: normalized.ratio_stats ?? null
                    } : null;
                    console.log('✅ Analytics data loaded from Internal API', this.analyticsData);
                    // Map analytics to state (for summary cards)
                    this.mapAnalyticsToState();
                } else if (analyticsResult.status === 'rejected') {
                    console.warn('⚠️ Analytics fetch failed:', analyticsResult.reason);
                }
                
                if (overviewResult.status === 'fulfilled' && overviewResult.value) {
                    this.overviewData = overviewResult.value;
                    console.log('✅ Overview data loaded from Internal API');
                } else if (overviewResult.status === 'rejected') {
                    console.warn('⚠️ Overview fetch failed:', overviewResult.reason);
                }

                // Save to cache after analytics loaded (if not auto-refresh)
                if (!isAutoRefresh) {
                    this.saveToCache();
                }

            } catch (error) {
                // Handle AbortError gracefully
                if (error.name === 'AbortError') {
                    console.log('⏭️ Analytics request was cancelled');
                    return;
                }
                console.error('❌ Error loading analytics data:', error);
            } finally {
                // Reset analytics loading flag
                this.analyticsLoading = false;
            }
        },

        /**
         * Map analytics data to state (use direct values from /analytics API only)
         */
        mapAnalyticsToState() {
            // Use analytics data directly from /analytics endpoint
            if (!this.analyticsData) return;

            // Use positioning directly from API (format for display only)
            if (this.analyticsData.positioning) {
                // Format: "extreme_bullish" -> "Extreme Bullish"
                const positioning = this.analyticsData.positioning;
                this.marketSentiment = this.formatPositioning(positioning);
            }

            // Use trend directly from API
            if (this.analyticsData.trend) {
                // Format: "stable" -> "Trend stabil - sentimen tidak berubah signifikan"
                const trend = this.analyticsData.trend;
                this.sentimentDescription = this.formatTrendDescription(trend);
            }
        },

        /**
         * Format positioning value for display (from analytics API)
         * Example: "extreme_bullish" -> "Extreme Bullish"
         */
        formatPositioning(positioning) {
            if (!positioning) return 'Unknown';
            
            // Replace underscores with spaces and capitalize each word
            return positioning
                .split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        /**
         * Format trend description for display (from analytics API)
         * Example: "stable" -> "Trend stabil - sentimen tidak berubah signifikan"
         */
        formatTrendDescription(trend) {
            if (!trend) return 'No trend data';
            
            const trendMap = {
                'increasing': 'Trend meningkat - sentimen bullish menguat',
                'decreasing': 'Trend menurun - sentimen bearish menguat',
                'stable': 'Trend stabil - sentimen tidak berubah signifikan'
            };
            
            return trendMap[trend.toLowerCase()] || `Trend: ${trend.charAt(0).toUpperCase() + trend.slice(1)}`;
        },

        /**
         * Update current values from data arrays (FASE 1: Use Internal API)
         */
        updateCurrentValues() {
            // FASE 1: Update from top accounts data (Internal API)
            if (this.topAccountData.length > 0) {
                // Get latest by timestamp (not just array index) to ensure truly latest value
                const sorted = [...this.topAccountData].sort((a, b) => (a.ts || a.time || 0) - (b.ts || b.time || 0));
                const latest = sorted[sorted.length - 1];
                
                // Use internal API field names - handle string "2.06000000" correctly
                const ratioValue = latest.ls_ratio_accounts;
                this.currentTopAccountRatio = ratioValue ? parseFloat(ratioValue) : 0;
                
                // Calculate 24h change
                if (sorted.length > 24) {
                    const previous = sorted[sorted.length - 25];
                    const prevRatio = parseFloat(previous.ls_ratio_accounts || 0);
                    this.topAccountRatioChange = prevRatio > 0 
                        ? ((this.currentTopAccountRatio - prevRatio) / prevRatio) * 100 
                        : 0;
                }
                
                console.log('✅ Top Account Ratio:', {
                    raw: ratioValue,
                    parsed: this.currentTopAccountRatio,
                    formatted: LongShortRatioUtils.formatRatio(this.currentTopAccountRatio),
                    change24h: this.topAccountRatioChange,
                    long: latest.long_accounts,
                    short: latest.short_accounts,
                    timestamp: latest.ts || latest.time
                });
            }

            // FASE 1: Update from top positions data (Internal API)
            if (this.topPositionData.length > 0) {
                // Get latest by timestamp (not just array index) to ensure truly latest value
                const sorted = [...this.topPositionData].sort((a, b) => (a.ts || a.time || 0) - (b.ts || b.time || 0));
                const latest = sorted[sorted.length - 1];
                
                // Use internal API field names - handle string "1.92000000" correctly
                const ratioValue = latest.ls_ratio_positions;
                this.currentTopPositionRatio = ratioValue ? parseFloat(ratioValue) : 0;
                
                // Calculate 24h change
                if (sorted.length > 24) {
                    const previous = sorted[sorted.length - 25];
                    const prevRatio = parseFloat(previous.ls_ratio_positions || 0);
                    this.topPositionRatioChange = prevRatio > 0 
                        ? ((this.currentTopPositionRatio - prevRatio) / prevRatio) * 100 
                        : 0;
                }
                
                console.log('✅ Top Position Ratio:', {
                    raw: ratioValue,
                    parsed: this.currentTopPositionRatio,
                    formatted: LongShortRatioUtils.formatRatio(this.currentTopPositionRatio),
                    change24h: this.topPositionRatioChange,
                    long: latest.long_positions_percent,
                    short: latest.short_positions_percent,
                    timestamp: latest.ts || latest.time
                });
            }

            // Market sentiment will be updated from analytics API via mapAnalyticsToState()
            // No fallback calculation needed - use analytics data only
        },


        /**
         * Filter handlers
         */
        setTimeRange(range) {
            if (this.globalPeriod === range) return;
            console.log('🔄 Setting time range to:', range);
            this.globalPeriod = range;
            this.loadAllData();
        },

        setChartInterval(interval) {
            if (this.selectedInterval === interval) return;
            console.log('🔄 Setting chart interval to:', interval);
            this.selectedInterval = interval;
            this.loadAllData();
        },

        /**
         * Get date range days from globalPeriod
         */
        getDateRangeDays() {
            const periodMap = {
                '1d': 1, 
                '7d': 7, 
                '1m': 30,
                'all': 730  // 2 years
            };
            return periodMap[this.globalPeriod] || 1;
        },


        /**
         * Get date range for filtering (similar to Funding Rate)
         * @returns {{startDate: Date, endDate: Date}}
         */
        getDateRange() {
            const now = new Date();
            const days = this.getDateRangeDays();
            
            let startDate;
            let endDate = new Date(now); // End date is always "now"
            
            if (this.globalPeriod === 'all') {
                startDate = new Date(now.getFullYear() - 2, 0, 1); // 2 years ago
            } else {
                startDate = new Date(now);
                startDate.setDate(startDate.getDate() - days);
            }
            
            // Set end of day for endDate
            endDate.setHours(23, 59, 59, 999);
            
            return { startDate, endDate };
        },

        updateExchange() {
            console.log('🔄 Updating exchange to:', this.selectedExchange);
            this.loadAllData();
        },

        updateSymbol() {
            console.log('🔄 Updating symbol to:', this.selectedSymbol);
            this.loadAllData();
        },

        updateInterval() {
            console.log('🔄 Updating interval to:', this.selectedInterval);
            this.loadAllData();
        },

        toggleChartType(type) {
            if (this.chartType === type) return;
            console.log('🔄 Toggling chart type to:', type);
            this.chartType = type;
            // Re-render charts with new type if data exists
            if (this.topAccountData.length > 0 && this.mainChartManager) {
                this.mainChartManager.renderMainChart(this.topAccountData, this.chartType);
            }
            if (this.topPositionData.length > 0 && this.positionsChartManager) {
                this.positionsChartManager.renderPositionsChart(this.topPositionData, this.chartType);
            }
        },

        toggleScale(type) {
            if (this.scaleType === type) return;
            console.log('🔄 Toggling scale to:', type);
            this.scaleType = type;
            // Note: Scale toggle needs chart options update - implement if needed
        },

        refreshAll() {
            this.globalLoading = true;
            this.loadAllData().finally(() => {
                this.globalLoading = false;
            });
        },


        /**
         * Format functions (delegate to utils)
         */
        formatRatio(value) {
            return LongShortRatioUtils.formatRatio(value);
        },

        formatChange(value) {
            return LongShortRatioUtils.formatChange(value);
        },

        formatPriceUSD(value) {
            return LongShortRatioUtils.formatPriceUSD(value);
        },

        formatVolume(value) {
            return LongShortRatioUtils.formatVolume(value);
        },

        formatNetBias(value) {
            return LongShortRatioUtils.formatNetBias(value);
        },

        getRatioTrendClass(value) {
            return LongShortRatioUtils.getRatioTrendClass(value);
        },

        getSentimentBadgeClass() {
            return LongShortRatioUtils.getSentimentBadgeClass(this.sentimentStrength);
        },

        getSentimentColorClass() {
            return LongShortRatioUtils.getSentimentColorClass(this.marketSentiment);
        },

        getPriceTrendClass(value) {
            return LongShortRatioUtils.getRatioTrendClass(value); // Reuse same logic
        },

        getExchangeColor(exchangeName) {
            return LongShortRatioUtils.getExchangeColor(exchangeName);
        },

        getBiasClass(value) {
            return LongShortRatioUtils.getBiasClass(value);
        },

        getBuyRatioClass(value) {
            return LongShortRatioUtils.getBuyRatioClass(value);
        },

        getSellRatioClass(value) {
            return LongShortRatioUtils.getSellRatioClass(value);
        },

        /**
         * Cleanup
         */
        cleanup() {
            this.stopAutoRefresh();
            if (this.mainChartManager) this.mainChartManager.destroy();
            if (this.positionsChartManager) this.positionsChartManager.destroy();
            if (this.comparisonChartManager) this.comparisonChartManager.destroy();
        }
    };
}

