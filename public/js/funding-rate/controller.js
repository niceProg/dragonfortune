/**
 * Funding Rate Main Controller
 * Coordinates data fetching, chart rendering, and metrics calculation
 */

import { FundingRateAPIService } from './api-service.js';
import { ChartManager } from './chart-manager.js';
import { FundingRateUtils } from './utils.js';

export function createFundingRateController() {
    return {
        // Initialization flag
        initialized: false,

        // Services
        apiService: null,
        chartManager: null,
        
        // Global state
        globalPeriod: '1m',
        globalLoading: false, // Start false - optimistic UI (no skeleton)
        isLoading: false, // Flag to prevent multiple simultaneous loads
        selectedSymbol: 'BTCUSDT',
        selectedExchange: 'Binance',
        scaleType: 'linear',
        
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
        
        // Time ranges (initialized in init)
        timeRanges: [],
        
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
        
        // Summary metrics
        currentFundingRate: null,
        fundingChange: null,
        avgFundingRate: null,  // Set from analytics API
        medianFundingRate: null,
        maxFundingRate: null,  // Set from analytics API
        minFundingRate: null,  // Set from analytics API
        fundingVolatility: null,  // Set from analytics API
        peakDate: '--',
        
        // Price metrics
        currentPrice: null,
        priceChange: null,
        priceDataAvailable: false,
        
        // Analysis metrics
        ma7: 0,
        ma30: 0,
        highFundingEvents: 0,
        extremeFundingEvents: 0,
        currentZScore: 0,
        
        // Market signal (from analytics API)
        marketSignal: 'Neutral',
        signalStrength: 'Normal',
        signalDescription: '',
        analyticsData: null,
        analyticsLoading: false,
        
        // Chart state
        chartType: 'line', // 'line' or 'candlestick'
        distributionChart: null,
        maChart: null,
        
        // Exchange comparison data
        exchangesData: [],
        exchangesLoading: false,
        
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
            console.log('🚀 Funding Rate Dashboard initialized');
            
            // Initialize services IMMEDIATELY (non-blocking)
            this.apiService = new FundingRateAPIService();
            this.chartManager = new ChartManager('fundingRateMainChart');
            
            // Set globalLoading = false initially (optimistic UI, no skeleton)
            this.globalLoading = false;
            this.analyticsLoading = false;
            
            // Initialize time ranges
            this.timeRanges = [
                { label: '1D', value: '1d', days: 1 },
                { label: '7D', value: '7d', days: 7 },
                { label: '1M', value: '1m', days: 30 },
                { label: 'ALL', value: 'all', days: 365 }
                // Commented for future use:
                // { label: 'YTD', value: 'ytd', days: this.getYTDDays() },
                // { label: '1Y', value: '1y', days: 365 },
            ];
            
            // STEP 1: Load cache data INSTANT (no loading skeleton)
            const cacheLoaded = this.loadFromCache();
            if (cacheLoaded) {
                console.log('✅ Cache data loaded instantly - showing cached data');
                // Render chart immediately with cached data (don't wait Chart.js)
                // Chart will render when Chart.js is ready
                if (this.chartManager && this.rawData.length > 0) {
                    // Wait for Chart.js to be ready (but don't block other operations)
                    (window.chartJsReady || Promise.resolve()).then(() => {
                        setTimeout(() => {
                            this.chartManager.renderChart(this.rawData, this.priceData, this.chartType);
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
         * Start auto-refresh with safety checks
         */
        startAutoRefresh() {
            this.stopAutoRefresh(); // Clear any existing interval
            
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
                
                // Also refresh analytics data independently (handles its own errors)
                // Pass isAutoRefresh=true to prevent analytics skeleton during auto-refresh
                if (!this.analyticsLoading) {
                    this.fetchAnalyticsData(true).catch(err => {
                        console.warn('⚠️ Analytics refresh failed:', err);
                    });
                }

                // Also refresh exchanges data independently (silent)
                if (!this.exchangesLoading) {
                    this.fetchExchangesData(true).catch(err => {
                        console.warn('⚠️ Exchanges refresh failed:', err);
                    });
                }
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
         * Get Year-to-Date days
         */
        getYTDDays() {
            const now = new Date();
            const startOfYear = new Date(now.getFullYear(), 0, 1);
            const diffTime = Math.abs(now - startOfYear);
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        },
        
        /**
         * Get date range in days
         */
        getDateRangeDays() {
            const selectedRange = this.timeRanges.find(r => r.value === this.globalPeriod);
            return selectedRange ? selectedRange.days : 30;
        },
        
        /**
         * Calculate start and end date based on selected period
         * @returns {{startDate: Date, endDate: Date}}
         */
        getDateRange() {
            const now = new Date();
            const days = this.getDateRangeDays();
            
            let startDate;
            let endDate = new Date(now); // End date is always "now" (latest available)
            
            if (this.globalPeriod === 'ytd') {
                // Year to date: from start of year to now
                startDate = new Date(now.getFullYear(), 0, 1);
            } else if (this.globalPeriod === 'all') {
                // All data: from a very old date (e.g., 2 years ago) to now
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
         * Get cache key for current filter state
         */
        getCacheKey() {
            const exchange = FundingRateUtils.capitalizeExchange(this.selectedExchange);
            return `fr_dashboard_v2_${this.selectedSymbol}_${exchange}_${this.selectedInterval}_${this.globalPeriod}`;
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
                        this.currentFundingRate = data.currentFundingRate;
                        this.avgFundingRate = data.avgFundingRate;
                        this.maxFundingRate = data.maxFundingRate;
                        this.minFundingRate = data.minFundingRate;
                        this.fundingVolatility = data.fundingVolatility;
                        this.marketSignal = data.marketSignal;
                        this.signalStrength = data.signalStrength;
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
                    currentFundingRate: this.currentFundingRate,
                    avgFundingRate: this.avgFundingRate,
                    maxFundingRate: this.maxFundingRate,
                    minFundingRate: this.minFundingRate,
                    fundingVolatility: this.fundingVolatility,
                    marketSignal: this.marketSignal,
                    signalStrength: this.signalStrength,
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
            
            try {
                console.log('📡 Loading funding rate data...');
                
                const exchange = FundingRateUtils.capitalizeExchange(this.selectedExchange);
                
                // Calculate date range (startDate to endDate)
                const dateRange = this.getDateRange();
                
                // For initial load, use smaller limit (100) for faster response
                // Then load full data (5000) in background after first render
                // This provides instant feedback to user - chart appears in <500ms
                const calculatedLimit = 5000;
                const limit = isInitialLoad ? 100 : calculatedLimit;
                
                console.log('📡 Loading Funding Rate data...', {
                    symbol: this.selectedSymbol,
                    exchange: exchange,
                    interval: this.selectedInterval,
                    period: this.globalPeriod,
                    limit: limit,
                    isInitialLoad: isInitialLoad,
                    calculatedLimit: calculatedLimit
                });
                
                // Fetch data from internal API with progressive loading
                const data = await this.apiService.fetchHistory({
                    symbol: this.selectedSymbol,
                    exchange: exchange,
                    interval: this.selectedInterval,
                    dateRange: dateRange,
                    limit: limit // Pass limit for progressive loading
                });
                
                // Handle cancelled requests
                if (data === null) {
                    console.log('🚫 Request was cancelled');
                    return;
                }
                
                this.rawData = data;
                this.errorCount = 0; // Reset on success
                this.lastUpdateTime = new Date();
                
                console.log('✅ Data loaded:', this.rawData.length, 'records');
                
                // Extract price data from funding rate data (optimized - direct map, filter later if needed)
                this.priceData = data
                    .filter(d => d.price !== null && d.price !== undefined)
                    .map(d => ({ date: d.date, price: d.price }));
                
                console.log('💰 Price data extracted:', this.priceData.length, 'points');
                
                // CRITICAL: Calculate metrics IMMEDIATELY from rawData (like production)
                // This ensures summary cards are populated INSTANTLY without waiting for analytics API
                // Analytics API will update these values later if available (non-blocking)
                if (this.rawData.length > 0) {
                    this.calculateMetrics();
                    console.log('✅ Metrics calculated from rawData (instant, no delay)');
                }
                
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
                            console.log('⏱️ Chart render time:', chartRenderTime + 'ms');
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
                
                // Fetch analytics AFTER chart render (non-blocking, fire-and-forget)
                // This allows chart to appear instantly, analytics updates summary cards later
                // Following Open Interest optimization pattern
                if (!isInitialLoad || !isAutoRefresh) {
                    this.fetchAnalyticsData(isAutoRefresh).catch(err => {
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

                // Fetch exchanges comparison data in parallel (non-blocking)
                this.fetchExchangesData(isAutoRefresh).catch(err => {
                    console.warn('⚠️ Exchanges fetch failed:', err);
                });
                
                // Log total load time
                const totalLoadTime = Date.now() - loadStartTime;
                console.log('⏱️ Total loadData() time:', totalLoadTime + 'ms');
                
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
                            const fullData = await this.apiService.fetchHistory({
                                symbol: this.selectedSymbol,
                                exchange: exchange,
                                interval: this.selectedInterval,
                                dateRange: capturedDateRange,
                                limit: calculatedLimit
                            });

                            if (fullData && fullData.length > 0) {
                                // Update with full dataset
                                this.rawData = fullData;
                                this.priceData = fullData
                                    .filter(d => d.price !== null && d.price !== undefined)
                                    .map(d => ({ date: d.date, price: d.price }));
                                
                                // Recalculate metrics with full dataset
                                if (this.rawData.length > 0) {
                                    this.calculateMetrics();
                                    console.log('✅ Metrics recalculated with full dataset');
                                }

                                // Update chart with full data (use renderChart directly like Open Interest)
                                if (this.chartManager) {
                                    this.chartManager.renderChart(this.rawData, this.priceData, this.chartType);
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
                    this.showError('Auto-refresh disabled due to repeated errors');
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
         * Calculate all metrics
         */
        calculateMetrics() {
            if (this.rawData.length === 0) {
                console.warn('⚠️ No data for metrics calculation');
                return;
            }
            
            const sorted = [...this.rawData].sort((a, b) => 
                new Date(a.date) - new Date(b.date)
            );
            
            const fundingValues = sorted.map(d => parseFloat(d.value));
            
            // Current metrics
            this.currentFundingRate = fundingValues[fundingValues.length - 1] || 0;
            const previousFundingRate = fundingValues[fundingValues.length - 2] || this.currentFundingRate;
            this.fundingChange = (this.currentFundingRate - previousFundingRate) * 10000; // Basis points
            
            // Statistical metrics
            // CRITICAL: Always calculate from rawData FIRST (instant, no delay)
            // Analytics API will update these later if available (non-blocking enhancement)
            // This ensures summary cards are NEVER empty (no "--" or null values)
            this.avgFundingRate = fundingValues.reduce((a, b) => a + b, 0) / fundingValues.length;
            this.maxFundingRate = Math.max(...fundingValues);
            this.minFundingRate = Math.min(...fundingValues);
            
            // Median still calculated from raw data (not in analytics API)
            this.medianFundingRate = FundingRateUtils.calculateMedian(fundingValues);
            
            // Peak date
            const peakIndex = fundingValues.indexOf(this.maxFundingRate);
            this.peakDate = FundingRateUtils.formatDate(sorted[peakIndex]?.date || sorted[0].date);
            
            // Moving averages
            this.ma7 = FundingRateUtils.calculateMA(fundingValues, 7);
            this.ma30 = FundingRateUtils.calculateMA(fundingValues, 30);
            
            // Price metrics
            if (this.priceData.length > 0) {
                this.currentPrice = this.priceData[this.priceData.length - 1].price;
                const yesterdayPrice = this.priceData[this.priceData.length - 2]?.price || this.currentPrice;
                this.priceChange = ((this.currentPrice - yesterdayPrice) / yesterdayPrice) * 100;
                this.priceDataAvailable = true;
            } else {
                this.currentPrice = null;
                this.priceChange = null;
                this.priceDataAvailable = false;
            }
            
            // Outlier detection
            if (fundingValues.length >= 2) {
                const stdDev = FundingRateUtils.calculateStdDev(fundingValues);
                
                // Calculate volatility as fallback if API doesn't provide it
                // Only set if not already set by analytics API (to avoid overwriting API values)
                if (this.fundingVolatility === null || this.fundingVolatility === undefined) {
                    this.fundingVolatility = stdDev; // Use stdDev as volatility fallback
                }
                
                this.highFundingEvents = fundingValues.filter(v => {
                    const zScore = Math.abs((v - this.avgFundingRate) / stdDev);
                    return zScore > 2;
                }).length;
                
                this.extremeFundingEvents = fundingValues.filter(v => {
                    const zScore = Math.abs((v - this.avgFundingRate) / stdDev);
                    return zScore > 3;
                }).length;
                
                // Market signal is now fetched from /api/funding-rate/analytics endpoint
                // Removed calculateMarketSignal() call - see fetchAnalyticsData()
            }
            
            // Calculate Z-Score
            this.calculateCurrentZScore();
            
            console.log('📊 Metrics calculated:', {
                current: this.currentFundingRate,
                avg: this.avgFundingRate,
                max: this.maxFundingRate,
                signal: this.marketSignal
            });
        },
        
        /**
         * Map analytics API response to UI state
         * Uses direct values from API response without thresholds
         */
        mapAnalyticsToState(analyticsData) {
            console.log('🔄 Mapping analytics data:', analyticsData);
            
            if (!analyticsData) {
                console.warn('⚠️ No analytics data provided, setting defaults');
                this.marketSignal = 'Neutral';
                this.signalStrength = 'Normal';
                this.signalDescription = 'No analytics data available';
                return;
            }

            // Map bias direction to marketSignal (direct from API - use long/short terminology)
            // Analytics format: bias.direction = "long_pays_short" or "short_pays_long"
            console.log('🔍 Bias value:', analyticsData.bias, 'Type:', typeof analyticsData.bias);
            
            if (analyticsData.bias === 'long_pays_short') {
                this.marketSignal = 'Long';
                this.signalDescription = 'Long bias - longs paying shorts';
                console.log('✅ Mapped to Long signal');
            } else if (analyticsData.bias === 'short_pays_long') {
                this.marketSignal = 'Short';
                this.signalDescription = 'Short bias - shorts paying longs';
                console.log('✅ Mapped to Short signal');
            } else {
                console.warn('⚠️ Unknown bias value, setting to Neutral:', analyticsData.bias);
                this.marketSignal = 'Neutral';
                this.signalDescription = 'Neutral market conditions';
            }

            // Use strength value directly from API (format for display)
            if (analyticsData.biasStrength !== null && analyticsData.biasStrength !== undefined) {
                const strengthPercent = (analyticsData.biasStrength * 100).toFixed(2);
                this.signalStrength = `${strengthPercent}%`;
                console.log('✅ Signal strength set:', this.signalStrength);
            } else {
                console.warn('⚠️ No biasStrength, using default');
                this.signalStrength = 'Normal';
            }

            // Update summary stats from API (replace frontend calculations)
            if (analyticsData.average !== null && analyticsData.average !== undefined) {
                this.avgFundingRate = analyticsData.average;
            }
            if (analyticsData.max !== null && analyticsData.max !== undefined) {
                this.maxFundingRate = analyticsData.max;
                // Note: peakDate might not be available from analytics, keep existing logic if needed
            }
            if (analyticsData.min !== null && analyticsData.min !== undefined) {
                this.minFundingRate = analyticsData.min;
            }
            // Update volatility from API (will override fallback from calculateMetrics if available)
            if (analyticsData.volatility !== null && analyticsData.volatility !== undefined) {
                this.fundingVolatility = analyticsData.volatility;
                console.log('✅ Volatility set from API:', analyticsData.volatility);
            } else {
                console.warn('⚠️ Volatility not available from API, will use fallback from calculateMetrics');
            }
        },

        /**
         * Fetch analytics data from API (includes bias + summary stats)
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
                console.log('📡 Fetching analytics data...');

                const exchange = FundingRateUtils.capitalizeExchange(this.selectedExchange);
                const limit = 1000; // Large limit to ensure comprehensive stats

                const analyticsData = await this.apiService.fetchAnalytics(
                    this.selectedSymbol,
                    exchange,
                    this.selectedInterval,
                    limit
                );

                // Handle cancelled requests
                if (analyticsData === null) {
                    console.warn('🚫 Analytics request was cancelled - market signal will not update');
                    return;
                }

                if (!analyticsData) {
                    console.error('❌ Analytics data is null/undefined after fetch');
                    return;
                }

                this.analyticsData = analyticsData;
                
                console.log('📊 Analytics data before mapping:', {
                    hasBias: !!analyticsData.bias,
                    hasStrength: !!analyticsData.biasStrength,
                    hasVolatility: !!analyticsData.volatility,
                    biasValue: analyticsData.bias
                });
                
                // Map analytics data to UI state (includes bias + summary stats)
                this.mapAnalyticsToState(analyticsData);

                console.log('✅ Analytics data loaded:', {
                    bias: analyticsData.bias,
                    strength: analyticsData.biasStrength,
                    average: analyticsData.average,
                    max: analyticsData.max,
                    volatility: analyticsData.volatility,
                    signal: this.marketSignal,
                    signalStrength: this.signalStrength,
                    volatilitySet: this.fundingVolatility
                });

                // Save to cache after analytics loaded (if not auto-refresh)
                if (!isAutoRefresh) {
                    this.saveToCache();
                }

            } catch (error) {
                console.error('❌ Error loading analytics data:', error);
                console.error('❌ Error details:', {
                    symbol: this.selectedSymbol,
                    exchange: FundingRateUtils.capitalizeExchange(this.selectedExchange),
                    interval: this.selectedInterval,
                    errorMessage: error.message
                });
                // Don't update errorCount or stop auto-refresh for analytics errors
                // Only reset if values are still at default (to avoid overwriting existing good data)
                if (this.marketSignal === 'Neutral' && this.signalStrength === 'Normal') {
                    this.marketSignal = 'Neutral';
                    this.signalStrength = 'Normal';
                    this.signalDescription = 'Error loading analytics data';
                }
                // Don't reset volatility if it's already set from calculateMetrics fallback
            } finally {
                this.analyticsLoading = false;
            }
        },

        /**
         * Fetch exchanges comparison data
         */
        async fetchExchangesData(isAutoRefresh = false) {
            if (this.exchangesLoading) {
                console.log('⏭️ Skip exchanges fetch (already loading)');
                return;
            }

            // Silent update: only show loading if initial and no data
            if (!isAutoRefresh && this.exchangesData.length === 0) {
                this.exchangesLoading = true;
            } else {
                this.exchangesLoading = false;
            }

            try {
                console.log('📡 Fetching exchanges comparison data...');

                const data = await this.apiService.fetchExchanges(this.selectedSymbol, this.selectedInterval, 50);

                // Handle cancelled requests
                if (data === null) {
                    console.log('🚫 Exchanges request was cancelled');
                    return;
                }

                // Backend now supports interval param; keep defensive filter if mixed data
                const intervalMap = {
                    '1m': '1m',
                    '5m': '5m',
                    '15m': '15m',
                    '1h': '1h',
                    '4h': '4h',
                    '8h': '8h',
                    '1w': '1w'
                };
                const targetMarginType = intervalMap[this.selectedInterval] || this.selectedInterval;
                const filteredByInterval = Array.isArray(data)
                    ? data.filter(item => (item.margin_type || '').toLowerCase() === targetMarginType)
                    : [];

                // Group by exchange, take the one with latest (highest) next_funding_time
                const exchangeMap = new Map();
                
                filteredByInterval.forEach(item => {
                    const exchangeKey = item.exchange;
                    const existing = exchangeMap.get(exchangeKey);
                    
                    // Take the one with latest (highest) next_funding_time
                    if (!existing || item.next_funding_time > existing.next_funding_time) {
                        exchangeMap.set(exchangeKey, {
                            exchange: item.exchange,
                            funding_rate: parseFloat(item.funding_rate),
                            next_funding_time: item.next_funding_time,
                            margin_type: item.margin_type,
                            pair: item.pair
                        });
                    }
                });

                // Convert to array and sort by exchange name
                const filtered = Array.from(exchangeMap.values())
                    .sort((a, b) => a.exchange.localeCompare(b.exchange));

                this.exchangesData = filtered;

                console.log('✅ Exchanges data processed:', {
                    total: filtered.length,
                    exchanges: filtered.map(e => e.exchange)
                });

            } catch (error) {
                console.error('❌ Error loading exchanges data:', error);
                this.exchangesData = [];
            } finally {
                // Keep silent (don't flip loading on auto-refresh)
                if (!isAutoRefresh) {
                    this.exchangesLoading = false;
                }
            }
        },

        /**
         * Format next funding time for display
         */
        formatNextFundingTime(timestamp) {
            if (!timestamp) return '--';
            
            let date = new Date(timestamp);
            const now = new Date();
            
            // If timestamp is invalid, return '--'
            if (isNaN(date.getTime())) {
                return '--';
            }
            
            // If timestamp is in the past, calculate next funding time (8-hour intervals)
            // Funding times: 00:00, 08:00, 16:00 UTC
            if (date <= now) {
                // Calculate next funding time from now
                const hours = now.getUTCHours();
                let nextHour = 0;
                
                if (hours < 8) {
                    nextHour = 8;
                } else if (hours < 16) {
                    nextHour = 16;
                } else {
                    // After 16:00, next is 00:00 next day
                    nextHour = 0;
                    date = new Date(now);
                    date.setUTCDate(date.getUTCDate() + 1);
                    date.setUTCHours(nextHour, 0, 0, 0);
                }
                
                // Set to next funding hour
                if (nextHour !== 0) {
                    date = new Date(now);
                    date.setUTCHours(nextHour, 0, 0, 0);
                }
            }
            
            const diffMs = date - now;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);
            
            if (diffMins < 0) {
                // Should not happen, but fallback
                return '--';
            } else if (diffMins < 60) {
                return `${diffMins} menit lagi`;
            } else if (diffHours < 24) {
                const remainingMins = diffMins % 60;
                if (remainingMins > 0) {
                    return `${diffHours}j ${remainingMins}m`;
                }
                return `${diffHours} jam lagi`;
            } else if (diffDays === 1) {
                return date.toLocaleDateString('id-ID', { 
                    month: 'short', 
                    day: 'numeric', 
                    hour: '2-digit', 
                    minute: '2-digit',
                    timeZone: 'UTC'
                });
            } else {
                return date.toLocaleDateString('id-ID', { 
                    month: 'short', 
                    day: 'numeric', 
                    hour: '2-digit', 
                    minute: '2-digit',
                    timeZone: 'UTC'
                });
            }
        },

        /**
         * Calculate arbitrage opportunity (funding rate difference)
         */
        calculateArbitrage() {
            if (this.exchangesData.length < 2) return null;
            
            const rates = this.exchangesData.map(e => e.funding_rate);
            const maxRate = Math.max(...rates);
            const minRate = Math.min(...rates);
            const spread = maxRate - minRate;
            
            if (spread <= 0) return null;
            
            const maxExchange = this.exchangesData.find(e => e.funding_rate === maxRate);
            const minExchange = this.exchangesData.find(e => e.funding_rate === minRate);
            
            return {
                spread: spread,
                maxExchange: maxExchange.exchange,
                minExchange: minExchange.exchange,
                maxRate: maxRate,
                minRate: minRate
            };
        },
        
        /**
         * Calculate current Z-Score
         */
        calculateCurrentZScore() {
            if (this.rawData.length < 2) {
                this.currentZScore = 0;
                return;
            }
            
            const values = this.rawData.map(d => parseFloat(d.value));
            const mean = values.reduce((a, b) => a + b, 0) / values.length;
            const stdDev = FundingRateUtils.calculateStdDev(values);
            
            if (stdDev === 0) {
                this.currentZScore = 0;
                return;
            }
            
            this.currentZScore = (this.currentFundingRate - mean) / stdDev;
        },
        
        /**
         * Render distribution chart
         */
        renderDistributionChart() {
            const canvas = document.getElementById('fundingRateDistributionChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            
            // Proper cleanup
            if (this.distributionChart) {
                try {
                    this.distributionChart.stop();
                    this.distributionChart.destroy();
                } catch (e) {
                    console.warn('⚠️ Distribution chart cleanup error:', e);
                }
                this.distributionChart = null;
            }
            
            const values = this.rawData.map(d => parseFloat(d.value));
            let binCount = Math.min(20, Math.max(1, values.length));
            if (values.length === 1) binCount = 1;
            else if (values.length === 2) binCount = 2;
            
            const bins = FundingRateUtils.createHistogramBins(values, binCount);
            
            this.distributionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: bins.map(b => b.label),
                    datasets: [{
                        label: 'Frequency',
                        data: bins.map(b => b.count),
                        backgroundColor: 'rgba(139, 92, 246, 0.6)',
                        borderColor: '#8b5cf6',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Disable animation for stability during auto-refresh
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#94a3b8', maxRotation: 45 },
                            grid: { display: false }
                        },
                        y: {
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(148, 163, 184, 0.1)' }
                        }
                    }
                }
            });
        },
        
        /**
         * Render moving average chart
         */
        renderMAChart() {
            const canvas = document.getElementById('fundingRateMAChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            
            // Proper cleanup
            if (this.maChart) {
                try {
                    this.maChart.stop();
                    this.maChart.destroy();
                } catch (e) {
                    console.warn('⚠️ MA chart cleanup error:', e);
                }
                this.maChart = null;
            }
            
            const sorted = [...this.rawData].sort((a, b) => 
                new Date(a.date) - new Date(b.date)
            );
            
            const labels = sorted.map(d => d.date);
            const values = sorted.map(d => parseFloat(d.value));
            
            const ma7Data = FundingRateUtils.calculateMAArray(values, Math.min(7, values.length));
            const ma30Data = FundingRateUtils.calculateMAArray(values, Math.min(30, values.length));
            
            this.maChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Funding Rate',
                            data: values,
                            borderColor: '#94a3b8',
                            backgroundColor: 'transparent',
                            borderWidth: 1,
                            tension: 0.4
                        },
                        {
                            label: '7-Day MA',
                            data: ma7Data,
                            borderColor: '#22c55e',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            tension: 0.4
                        },
                        {
                            label: '30-Day MA',
                            data: ma30Data,
                            borderColor: '#ef4444',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Disable animation for stability during auto-refresh
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: { color: '#94a3b8', boxWidth: 20 }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                maxRotation: 45,
                                minRotation: 45,
                                callback: function (value, index) {
                                    const totalLabels = this.chart.data.labels.length;
                                    const showEvery = Math.ceil(totalLabels / 8);
                                    if (index % showEvery === 0) {
                                        const date = this.chart.data.labels[index];
                                        return new Date(date).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric'
                                        });
                                    }
                                    return '';
                                }
                            },
                            grid: { display: false }
                        },
                        y: {
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(148, 163, 184, 0.1)' }
                        }
                    }
                }
            });
        },
        
        /**
         * Set time range
         */
        setTimeRange(range) {
            if (this.globalPeriod === range) return;
            console.log('🔄 Setting time range to:', range);
            this.globalPeriod = range;
            this.loadData();
        },
        
        /**
         * Set chart interval
         */
        setChartInterval(interval) {
            if (this.selectedInterval === interval) return;
            console.log('🔄 Setting chart interval to:', interval);
            this.selectedInterval = interval;
            this.loadData();
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
            const allowed = new Set(['OKX','Binance','HTX','Bitmex','Bitfinex','Bybit','Deribit','Gate','Kraken','KuCoin','CME','Bitget','dYdX','CoinEx','BingX','Coinbase','Gemini','Crypto.com','Hyperliquid','Bitunix','MEXC','WhiteBIT','Aster','Lighter','EdgeX','Drift','Paradex','Extended','ApeX Omni']);
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
            const allowed = new Set(['1m','5m','15m','1h','4h','8h','1w']);
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
            
            if (this.distributionChart) {
                try {
                    this.distributionChart.destroy();
                } catch (e) {
                    console.warn('⚠️ Distribution chart cleanup error:', e);
                }
            }
            
            if (this.maChart) {
                try {
                    this.maChart.destroy();
                } catch (e) {
                    console.warn('⚠️ MA chart cleanup error:', e);
                }
            }
            
            if (this.apiService) {
                this.apiService.cancelRequest();
            }
        },
        
        /**
         * Format funding rate
         */
        formatFundingRate(value) {
            return FundingRateUtils.formatFundingRate(value);
        },
        
        /**
         * Format price
         */
        formatPrice(value) {
            return FundingRateUtils.formatPrice(value);
        },
        
        /**
         * Format price with USD label
         */
        formatPriceUSD(value) {
            return FundingRateUtils.formatPrice(value);
        },
        
        /**
         * Format change
         */
        formatChange(value) {
            return FundingRateUtils.formatChange(value);
        },
        
        /**
         * Format Z-Score
         */
        formatZScore(value) {
            return FundingRateUtils.formatZScore(value);
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
         * Get signal badge class
         */
        getSignalBadgeClass() {
            // signalStrength is now a percentage string (e.g., "51.75%")
            // For legacy compatibility, check if it's old format first
            const strengthMap = {
                'Strong': 'text-bg-danger',
                'Moderate': 'text-bg-warning',
                'Weak': 'text-bg-info',
                'Normal': 'text-bg-secondary'
            };
            
            // If it's old format, use the map
            if (strengthMap[this.signalStrength]) {
                return strengthMap[this.signalStrength];
            }
            
            // New format: percentage string - parse and assign color based on strength value AND market signal
            // Extract numeric value from percentage string (e.g., "51.75%" -> 51.75)
            const strengthMatch = this.signalStrength.match(/(\d+\.?\d*)%/);
            if (strengthMatch) {
                const strengthValue = parseFloat(strengthMatch[1]);
                
                // Color should reflect market signal direction:
                // - Long (bullish) + high strength = green (success)
                // - Short (bearish) + high strength = red (danger)
                // - Neutral or low strength = neutral colors
                
                if (this.marketSignal === 'Long') {
                    // Long signal: green for strong, yellow for moderate, blue for weak
                    if (strengthValue >= 50) return 'text-bg-success';    // Strong Long (green)
                    if (strengthValue >= 20) return 'text-bg-warning';  // Moderate (yellow)
                    if (strengthValue >= 5) return 'text-bg-info';       // Weak (blue)
                    return 'text-bg-secondary';                           // Normal (gray)
                } else if (this.marketSignal === 'Short') {
                    // Short signal: red for strong, yellow for moderate, blue for weak
                    if (strengthValue >= 50) return 'text-bg-danger';    // Strong Short (red)
                    if (strengthValue >= 20) return 'text-bg-warning';   // Moderate (yellow)
                    if (strengthValue >= 5) return 'text-bg-info';       // Weak (blue)
                    return 'text-bg-secondary';                           // Normal (gray)
                } else {
                    // Neutral: use neutral colors
                    if (strengthValue >= 50) return 'text-bg-secondary';   // Neutral (gray)
                    if (strengthValue >= 20) return 'text-bg-secondary'; // Moderate (gray)
                    if (strengthValue >= 5) return 'text-bg-secondary'; // Weak (gray)
                    return 'text-bg-secondary';                          // Normal (gray)
                }
            }
            
            return 'text-bg-secondary';
        },
        
        /**
         * Get signal color class
         */
        getSignalColorClass() {
            const colorMap = {
                'Long': 'text-success',
                'Short': 'text-danger',
                'Neutral': 'text-secondary'
            };
            return colorMap[this.marketSignal] || 'text-secondary';
        },
        
        /**
         * Get Z-Score badge class
         */
        getZScoreBadgeClass(value) {
            if (value === null || value === undefined || isNaN(value)) return 'text-bg-secondary';
            
            const absValue = Math.abs(value);
            if (absValue >= 3) return 'text-bg-danger';
            if (absValue >= 2) return 'text-bg-warning';
            if (absValue >= 1) return 'text-bg-info';
            return 'text-bg-success';
        },
        
        /**
         * Show error message
         */
        showError(message) {
            console.error('Error:', message);
            // Could add toast notification here
        }
    };
}

