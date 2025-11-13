/**
 * Perp-Quarterly Spread Main Controller
 * Coordinates data fetching, chart rendering, and metrics calculation
 */

import { PerpQuarterlyAPIService } from './api-service.js';
import { ChartManager } from './chart-manager.js';
import { PerpQuarterlyUtils } from './utils.js';

export function createPerpQuarterlyController() {
    return {
        // Services
        apiService: null,
        chartManager: null,
        
        // Initialization flag
        initialized: false,

        // Global state
        globalPeriod: 'all', // Start with 'all' to show all available data
        globalLoading: false, // Start false - optimistic UI (no skeleton)
        isLoading: false, // Flag to prevent multiple simultaneous loads
        selectedSymbol: 'BTC',
        selectedExchange: 'Bybit',
        scaleType: 'linear',
        
        // Chart intervals
        chartIntervals: [
            { label: '5M', value: '5m' },
            { label: '15M', value: '15m' },
            { label: '1H', value: '1h' },
            { label: '4H', value: '4h' }
        ],
        selectedInterval: '1h',
        
        // Time ranges (same pattern as funding-rate)
        timeRanges: [
            { label: '1D', value: '1d', days: 1 },
            { label: '7D', value: '7d', days: 7 },
            { label: '1M', value: '1m', days: 30 },
            { label: 'ALL', value: 'all', days: null } // null means use 2 years ago in getDateRange()
        ],
        
        // Auto-refresh state
        refreshInterval: null,
        errorCount: 0,
        maxErrors: 3,
        
        // Data
        rawData: [],
        dataLoaded: false,
        
        // Summary metrics
        currentSpread: null, // From history API (latest data point)
        avgSpread: null, // From analytics API
        maxSpread: null, // From analytics API
        minSpread: null, // From analytics API
        spreadVolatility: null, // From analytics API
        avgSpreadBps: null, // From analytics API (avg_spread_bps)
        spreadTrend: 'neutral', // From analytics API (widening, narrowing, neutral)
        peakDate: '--',
        
        // Market signal
        marketSignal: 'Neutral',
        signalStrength: 'Normal',
        signalDescription: 'Loading...',
        analyticsData: null,
        analyticsLoading: false,
        
        // Chart state
        chartType: 'bar', // 'line' or 'bar' (default to bar for better visibility of positive/negative spreads)
        
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
            console.log('🚀 Perp-Quarterly Spread Dashboard initialized');

            // Initialize services IMMEDIATELY (non-blocking)
            this.apiService = new PerpQuarterlyAPIService();
            this.chartManager = new ChartManager('perpQuarterlyMainChart');

            // Set globalLoading = false initially (optimistic UI, no skeleton)
            this.globalLoading = false;
            this.analyticsLoading = false;

            // STEP 1: Load cache data INSTANT (no loading skeleton)
            const cacheLoaded = this.loadFromCache();
            if (cacheLoaded) {
                console.log('✅ Cache data loaded instantly - showing cached data');
                // Render chart immediately with cached data (don't wait Chart.js)
                if (this.chartManager && this.rawData.length > 0) {
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.chartManager.renderChart(this.rawData);
                        }, 10);
                    });
                }
                
                // STEP 2: Fetch fresh data from endpoints (background, no skeleton)
                this.loadData(true).catch(err => {
                    console.warn('⚠️ Background fetch failed:', err);
                });
            } else {
                // No cache available - optimistic UI (no skeleton, show placeholder values)
                console.log('⚠️ No cache available - loading data with optimistic UI (no skeleton)');
                // IMPORTANT: Start fetch IMMEDIATELY (don't wait for Chart.js)
                await this.loadData(false).catch(err => {
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
         * Get cache key for current filter state
         */
        getCacheKey() {
            return `perp_quarterly_dashboard_${this.selectedSymbol}_${this.selectedExchange}_${this.selectedInterval}_${this.globalPeriod}`;
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
                        this.currentSpread = data.currentSpread;
                        this.avgSpread = data.avgSpread;
                        this.maxSpread = data.maxSpread;
                        this.minSpread = data.minSpread;
                        this.spreadVolatility = data.spreadVolatility;
                        this.avgSpreadBps = data.avgSpreadBps;
                        this.spreadTrend = data.spreadTrend;
                        this.marketSignal = data.marketSignal;
                        this.signalStrength = data.signalStrength;
                        this.signalDescription = data.signalDescription;
                        this.analyticsData = data.analyticsData;
                        this.dataLoaded = true;
                        
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
                    currentSpread: this.currentSpread,
                    avgSpread: this.avgSpread,
                    maxSpread: this.maxSpread,
                    minSpread: this.minSpread,
                    spreadVolatility: this.spreadVolatility,
                    avgSpreadBps: this.avgSpreadBps,
                    spreadTrend: this.spreadTrend,
                    marketSignal: this.marketSignal,
                    signalStrength: this.signalStrength,
                    signalDescription: this.signalDescription,
                    analyticsData: this.analyticsData
                };
                localStorage.setItem(cacheKey, JSON.stringify(data));
                console.log('💾 Data saved to cache:', cacheKey);
            } catch (error) {
                console.warn('⚠️ Cache save error:', error);
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
                this.apiService.cancelRequest();
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
                // Get date range
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
                    symbol: this.selectedSymbol,
                    exchange: this.selectedExchange,
                    interval: this.selectedInterval,
                    limit: limit,
                    dateRange: dateRange
                });

                // Handle cancelled requests
                if (historyData === null) {
                    console.log('🚫 Request was cancelled');
                    return;
                }
                
                if (!historyData) {
                    console.warn('⚠️ No history data received');
                    return;
                }
                
                this.rawData = historyData;
                
                // Calculate current spread from latest history data
                if (this.rawData.length > 0) {
                    this.currentSpread = this.rawData[this.rawData.length - 1].spread;
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
                            this.chartManager.updateChart(this.rawData, this.chartType);
                            const chartRenderTime = Date.now() - chartRenderStart;
                            console.log('⏱️ Chart render time:', chartRenderTime + 'ms');
                        }
                    } catch (error) {
                        console.error('❌ Error rendering chart:', error);
                        setTimeout(() => {
                            if (this.chartManager && this.rawData.length > 0) {
                                this.chartManager.updateChart(this.rawData, this.chartType);
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
                                symbol: this.selectedSymbol,
                                exchange: this.selectedExchange,
                                interval: this.selectedInterval,
                                limit: calculatedLimit,
                                dateRange: capturedDateRange
                            });

                            if (fullHistoryData && fullHistoryData.length > 0) {
                                this.rawData = fullHistoryData;
                                
                                // Calculate current spread from latest history data
                                if (this.rawData.length > 0) {
                                    this.currentSpread = this.rawData[this.rawData.length - 1].spread;
                                }

                                // Update chart with full data
                                if (this.chartManager) {
                                    this.chartManager.updateChart(this.rawData, this.chartType);
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

            try {
                const dateRange = this.getDateRange();
                
                // Calculate days from date range (same pattern as funding-rate)
                const days = dateRange.startDate && dateRange.endDate
                    ? Math.ceil((dateRange.endDate - dateRange.startDate) / (1000 * 60 * 60 * 24))
                    : 7; // Default to 7 days if date range not available
                
                // Use fixed limit 5000 for analytics (same as history)
                // Analytics API can handle this internally
                const limit = 5000;
                
                console.log('📡 Fetching analytics with:', { days, interval: this.selectedInterval, limit });
                
                const data = await this.apiService.fetchAnalytics(
                    this.selectedSymbol,
                    this.selectedExchange,
                    this.selectedInterval,
                    limit
                );

                // Handle cancelled requests
                if (data === null) {
                    console.log('🚫 Analytics request was cancelled');
                    return;
                }
                
                this.analyticsData = data;
                this.mapAnalyticsToState(data);

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
                console.error('❌ Error fetching analytics:', error);
                // Don't throw - analytics is optional, chart can still work without it
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
                this.marketSignal = 'Neutral';
                this.signalStrength = 'Normal';
                this.signalDescription = 'No analytics data available';
                return;
            }
            
            // Map spread_analysis (all data from analytics API)
            if (analyticsData.spread_analysis) {
                const analysis = analyticsData.spread_analysis;
                this.avgSpread = analysis.avg_spread || null;
                this.maxSpread = analysis.max_spread || null;
                this.minSpread = analysis.min_spread || null;
                this.spreadVolatility = analysis.spread_volatility || null;
                this.avgSpreadBps = analysis.avg_spread_bps || null; // Added: Avg spread in basis points
            }
            
            // Map trend (use API value directly, format for display)
            if (analyticsData.trend) {
                this.spreadTrend = analyticsData.trend; // widening, narrowing, stable
                
                // Map trend to market signal (use actual API values)
                const trend = analyticsData.trend.toLowerCase();
                if (trend === 'widening') {
                    this.marketSignal = 'Widening';
                    this.signalDescription = 'Spread widening - increased divergence';
                } else if (trend === 'narrowing') {
                    this.marketSignal = 'Narrowing';
                    this.signalDescription = 'Spread narrowing - convergence';
                } else if (trend === 'stable') {
                    this.marketSignal = 'Stable';
                    this.signalDescription = 'Spread stable';
                } else {
                    // Use API value as-is, capitalize first letter
                    this.marketSignal = trend.charAt(0).toUpperCase() + trend.slice(1);
                    this.signalDescription = `Spread ${trend}`;
                }
            }
            
            // Signal strength from spread_level (use API value directly, format for display)
            // API returns: "tight_spread", "moderate", "wide_spread", etc.
            if (analyticsData.spread_analysis?.spread_level) {
                const level = analyticsData.spread_analysis.spread_level;
                // Format: "tight_spread" → "Tight Spread", "wide_spread" → "Wide Spread"
                this.signalStrength = level
                    .split('_')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
            }
        },
        
        /**
         * Calculate metrics from raw data
         */
        calculateMetrics() {
            if (!this.rawData || this.rawData.length === 0) return;
            
            const spreads = this.rawData.map(d => parseFloat(d.spread || 0));
            
            // Current spread from latest data point (always update from history)
            if (spreads.length > 0) {
                this.currentSpread = spreads[spreads.length - 1];
            }
            
            // Fallback calculations if analytics not available
            if (this.avgSpread === null || this.avgSpread === undefined) {
                this.avgSpread = spreads.reduce((a, b) => a + b, 0) / spreads.length;
            }
            
            if (this.maxSpread === null || this.maxSpread === undefined) {
                this.maxSpread = Math.max(...spreads);
            }
            
            if (this.minSpread === null || this.minSpread === undefined) {
                this.minSpread = Math.min(...spreads);
            }
            
            if (this.spreadVolatility === null || this.spreadVolatility === undefined) {
                this.spreadVolatility = PerpQuarterlyUtils.calculateStdDev(spreads);
            }
            
            // Find peak date
            const maxIndex = spreads.indexOf(this.maxSpread);
            if (maxIndex >= 0 && this.rawData[maxIndex]) {
                const peakTs = this.rawData[maxIndex].ts || this.rawData[maxIndex].date;
                this.peakDate = new Date(peakTs).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            }
        },
        
        /**
         * Get date range from globalPeriod
         * Same pattern as funding-rate controller.js
         */
        getDateRange() {
            const now = new Date();
            const range = this.timeRanges.find(r => r.value === this.globalPeriod);
            const days = range ? range.days : 7;
            
            let startDate;
            let endDate = new Date(now); // End date is always "now" (latest available)
            
            if (this.globalPeriod === 'all') {
                // All data: from a very old date (e.g., 2 years ago) to now
                startDate = new Date(now.getFullYear() - 2, 0, 1);
            } else if (days === null) {
                // Fallback: if days is null, use 2 years ago
                startDate = new Date(now.getFullYear() - 2, 0, 1);
            } else {
                // Standard periods: days ago from now
                startDate = new Date(now);
                startDate.setDate(startDate.getDate() - days);
            }
            
            // Set end date to end of today (23:59:59) for inclusive range
            endDate.setHours(23, 59, 59, 999);
            
            return { startDate, endDate };
        },
        
        /**
         * Format spread for display
         */
        formatSpread(value) {
            return PerpQuarterlyUtils.formatSpread(value);
        },
        
        /**
         * Format spread BPS
         */
        formatSpreadBPS(value) {
            return PerpQuarterlyUtils.formatSpreadBPS(value);
        },
        
        /**
         * Refresh all data
         */
        refreshAll() {
            this.errorCount = 0;
            this.loadData();
        },
        
        /**
         * Set time range
         */
        setTimeRange(range) {
            if (this.globalPeriod === range) return;
            this.globalPeriod = range;
            this.loadData();
        },
        
        /**
         * Update exchange
         */
        updateExchange() {
            this.loadData();
        },
        
        /**
         * Update interval (from header selector)
         */
        updateInterval() {
            this.loadData();
        },
        
        /**
         * Set chart interval (from chart header dropdown)
         */
        setChartInterval(interval) {
            if (this.selectedInterval === interval) return;
            this.selectedInterval = interval;
            this.loadData();
        },
        
        /**
         * Update symbol
         */
        updateSymbol() {
            this.loadData();
        },
        
        /**
         * Toggle chart type
         */
        toggleChartType(type) {
            if (this.chartType === type) return;
            this.chartType = type;
            
            if (this.rawData && this.rawData.length > 0) {
                setTimeout(() => {
                    this.chartManager.updateChart(this.rawData, this.chartType);
                }, 100);
            }
        },
        
        /**
         * Get signal badge class
         */
        getSignalBadgeClass() {
            const strengthMap = {
                'High': 'text-bg-danger',
                'Moderate': 'text-bg-warning',
                'Low': 'text-bg-info',
                'Normal': 'text-bg-secondary'
            };
            return strengthMap[this.signalStrength] || 'text-bg-secondary';
        },
        
        /**
         * Get signal color class
         */
        getSignalColorClass() {
            const colorMap = {
                'Widening': 'text-warning',
                'Narrowing': 'text-success',
                'Neutral': 'text-secondary'
            };
            return colorMap[this.marketSignal] || 'text-secondary';
        },
        
        /**
         * Cleanup
         */
        cleanup() {
            this.stopAutoRefresh();
            this.apiService.cancelRequest();
            this.chartManager.destroy();
        }
    };
}

