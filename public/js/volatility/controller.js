/**
 * Volatility & Regime Analysis Controller
 * Alpine.js controller for the Volatility dashboard
 * Pattern: Open Interest blueprint (NO WAIT - immediate registration)
 */

import { VolatilityAPIService } from './api-service.js';
import { VolatilityChartManager } from './chart-manager.js';
import { VolatilityUtils } from './utils.js';

// Immediately expose controller function for Alpine.js (no waiting!)
window.volatilityController = function() {
        return {
            // State
            initialized: false,
            isLoading: false,
            eodLoading: false,
            errorMessage: null,
            errorCount: 0,
            maxErrors: 3,

            // Services
            apiService: null,
            chartManager: null,

            // Data
            priceData: [],
            eodData: [],
            
            // Current metrics
            currentPrice: null,
            priceChange: null,
            priceChangePercent: null,
            high24h: null,
            low24h: null,
            volume24h: null,
            
            // Volatility metrics
            atr: null,
            hv: null,
            rv: null,
            regime: null,

            // Filters
            selectedExchange: 'Binance',
            selectedSymbol: 'BTCUSDT',
            selectedInterval: '1h',
            intervals: [
                { value: '1m', label: '1 Minute' },
                { value: '3m', label: '3 Minutes' },
                { value: '5m', label: '5 Minutes' },
                { value: '15m', label: '15 Minutes' },
                { value: '30m', label: '30 Minutes' },
                { value: '1h', label: '1 Hour' },
                { value: '4h', label: '4 Hours' },
                { value: '12h', label: '12 Hours' },
                { value: '1d', label: '1 Day' },
                { value: '1w', label: '1 Week' }
            ],

            // Time Range for chart display
            selectedTimeRange: '7d',
            timeRanges: [
                { value: '1d', label: '1D' },
                { value: '7d', label: '7D' },
                { value: '30d', label: '30D' },
                { value: '90d', label: '90D' },
                { value: '180d', label: '6M' },
                { value: '1y', label: '1Y' },
                { value: 'all', label: 'All' }
            ],

            // Auto-refresh
            refreshEnabled: true,
            refreshInterval: null,

            /**
             * Initialize controller
             */
            async init() {
                if (this.initialized) return;
                this.initialized = true;

                console.log('🚀 Volatility & Regime Analysis initialized');

                // Wait for Chart.js to be ready before creating chart manager
                if (typeof window.chartJsReady !== 'undefined') {
                    await window.chartJsReady;
                    console.log('✅ Chart.js ready, creating chart manager');
                }

                this.apiService = new VolatilityAPIService();
                this.chartManager = new VolatilityChartManager('volatilityMainChart');

                // Load initial data (price data and EOD data in parallel)
                await Promise.all([
                    this.loadPriceData(),
                    this.loadEodData()
                ]);

                // Start auto-refresh
                this.startAutoRefresh();
            },

            /**
             * Load price data (OHLC)
             */
            async loadPriceData(silent = false) {
                if (!silent) {
                    this.isLoading = true;
                    this.errorMessage = null;
                }

                const startTime = Date.now();
                console.log('[Volatility:LOAD]', {
                    exchange: this.selectedExchange,
                    symbol: this.selectedSymbol,
                    interval: this.selectedInterval,
                    timeRange: this.selectedTimeRange
                });

                try {
                    // Calculate time range based on selectedTimeRange
                    const timeRange = this.getTimeRangeForSelectedRange();

                    const response = await this.apiService.fetchPriceHistory({
                        exchange: this.selectedExchange,
                        symbol: this.selectedSymbol,
                        interval: this.selectedInterval,
                        start_time: timeRange.start_time,
                        end_time: timeRange.end_time,
                        preferFresh: !silent
                    });

                    console.log('[Volatility:DEBUG] Raw API response:', response);

                    if (!response || !response.data || !Array.isArray(response.data)) {
                        throw new Error('Invalid API response format');
                    }

                    this.priceData = response.data;
                    
                    // Update summary metrics
                    this.updateSummaryMetrics();

                    // Render chart
                    if (this.priceData.length > 0) {
                        this.chartManager.renderChart(this.priceData, {
                            symbol: this.selectedSymbol,
                            interval: this.selectedInterval
                        });
                    }

                    const elapsed = Date.now() - startTime;
                    console.log(`[Volatility:OK] ${this.priceData.length} candles (total: ${elapsed}ms)`);

                    this.errorCount = 0; // Reset error count on success

                } catch (error) {
                    console.error('[Volatility:ERROR]', error);
                    this.errorMessage = error.message || 'Failed to load price data';
                    this.errorCount++;
                } finally {
                    this.isLoading = false;
                }
            },

            /**
             * Get time range based on selected time range filter
             */
            getTimeRangeForSelectedRange() {
                const now = Date.now();
                let startTime = now;

                switch (this.selectedTimeRange) {
                    case '1d':
                        startTime = now - (24 * 60 * 60 * 1000);
                        break;
                    case '7d':
                        startTime = now - (7 * 24 * 60 * 60 * 1000);
                        break;
                    case '30d':
                        startTime = now - (30 * 24 * 60 * 60 * 1000);
                        break;
                    case '90d':
                        startTime = now - (90 * 24 * 60 * 60 * 1000);
                        break;
                    case '180d':
                        startTime = now - (180 * 24 * 60 * 60 * 1000);
                        break;
                    case '1y':
                        startTime = now - (365 * 24 * 60 * 60 * 1000);
                        break;
                    case 'all':
                        // Bitcoin genesis block date: 2009-01-03
                        startTime = new Date('2009-01-03').getTime();
                        break;
                    default:
                        startTime = now - (7 * 24 * 60 * 60 * 1000); // Default to 7 days
                }

                return {
                    start_time: startTime,
                    end_time: now
                };
            },

            /**
             * Load EOD data for volatility calculations
             */
            async loadEodData(silent = false) {
                // Always set loading state to prevent concurrent calls
                this.eodLoading = true;

                try {
                    const response = await this.apiService.fetchEodData({
                        exchange: this.selectedExchange,
                        symbol: this.selectedSymbol,
                        days: 30,
                        preferFresh: !silent
                    });

                    if (!response || !response.data || !Array.isArray(response.data)) {
                        throw new Error('Invalid EOD response format');
                    }

                    this.eodData = response.data;
                    
                    // Calculate metrics
                    this.calculateVolatilityMetrics();

                    if (!silent) {
                        console.log('✅ EOD data loaded and metrics calculated');
                    } else {
                        console.log('✅ EOD data refreshed (silent)');
                    }

                } catch (error) {
                    console.error('[Volatility:EOD:ERROR]', error);
                    if (!silent) {
                        alert('Failed to load EOD data: ' + error.message);
                    }
                } finally {
                    this.eodLoading = false;
                }
            },

            /**
             * Update summary metrics from price data
             */
            updateSummaryMetrics() {
                if (this.priceData.length === 0) return;

                const latestCandle = this.priceData[this.priceData.length - 1];
                const firstCandle = this.priceData[0];

                this.currentPrice = latestCandle.close;
                this.priceChange = latestCandle.close - firstCandle.open;
                this.priceChangePercent = firstCandle.open > 0 
                    ? (this.priceChange / firstCandle.open) * 100 
                    : 0;

                // Calculate 24h high/low and volume
                this.high24h = Math.max(...this.priceData.map(d => d.high));
                this.low24h = Math.min(...this.priceData.map(d => d.low));
                this.volume24h = this.priceData.reduce((sum, d) => sum + (d.volume_usd || 0), 0);
            },

            /**
             * Calculate volatility metrics from EOD data
             */
            calculateVolatilityMetrics() {
                if (this.eodData.length === 0) return;

                // Calculate ATR
                this.atr = VolatilityUtils.calculateATR(this.eodData, 14);

                // Calculate HV
                this.hv = VolatilityUtils.calculateHV(this.eodData, 20);

                // Determine regime
                this.regime = VolatilityUtils.getVolatilityRegime(this.hv);

                console.log('📊 Volatility Metrics:', {
                    atr: this.atr,
                    hv: this.hv,
                    regime: this.regime
                });
            },

            /**
             * Change interval
             */
            async changeInterval(newInterval) {
                if (newInterval === this.selectedInterval) return;
                
                console.log('🎯 Changing interval to:', newInterval);
                this.selectedInterval = newInterval;
                await this.loadPriceData();
            },

            /**
             * Change exchange
             */
            async changeExchange(newExchange) {
                if (newExchange === this.selectedExchange) return;
                
                console.log('🎯 Changing exchange to:', newExchange);
                this.selectedExchange = newExchange;
                await this.loadPriceData();
            },

            /**
             * Change symbol
             */
            async changeSymbol(newSymbol) {
                if (newSymbol === this.selectedSymbol) return;
                
                console.log('🎯 Changing symbol to:', newSymbol);
                this.selectedSymbol = newSymbol;
                await this.loadPriceData();
            },

            /**
             * Update time range
             */
            async updateTimeRange(range) {
                if (range === this.selectedTimeRange) return;
                
                console.log('🎯 Changing time range to:', range);
                this.selectedTimeRange = range;
                this.apiService.clearCache();
                await this.loadPriceData();
            },

            /**
             * Start auto-refresh
             */
            startAutoRefresh() {
                this.stopAutoRefresh();

                if (!this.refreshEnabled) return;

                // 5 second interval (like ETF Flows)
                this.refreshInterval = setInterval(() => {
                    if (document.hidden) return;
                    if (this.isLoading || this.eodLoading) return;
                    if (this.errorCount >= this.maxErrors) {
                        console.warn('🚨 Auto-refresh disabled due to errors');
                        this.stopAutoRefresh();
                        return;
                    }

                    console.log('🔄 Auto-refresh: Silent update (5s) - Price & Metrics');
                    // Refresh both price data and EOD data in parallel
                    Promise.all([
                        this.loadPriceData(true), // Silent update
                        this.loadEodData(true)   // Silent update
                    ]).catch(error => {
                        console.error('Auto-refresh error:', error);
                        this.errorCount++;
                    });

                }, 3000); // 5 seconds

                // Refresh when tab becomes visible
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden && this.refreshEnabled) {
                        console.log('👁️ Page visible: Triggering refresh');
                        if (!this.isLoading) {
                            this.loadPriceData(true);
                        }
                    }
                });

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
             * Toggle auto-refresh
             */
            toggleRefresh() {
                this.refreshEnabled = !this.refreshEnabled;
                
                if (this.refreshEnabled) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            },

            /**
             * Formatting helpers
             */
            formatPrice(value) {
                return VolatilityUtils.formatPrice(value);
            },

            formatVolume(value) {
                return VolatilityUtils.formatVolume(value);
            },

            formatPercent(value) {
                return VolatilityUtils.formatPercent(value);
            },

            formatChange(value) {
                return VolatilityUtils.formatChange(value);
            },

            getIntervalLabel(interval) {
                return VolatilityUtils.getIntervalLabel(interval);
            }
        };
};

console.log('✅ Volatility controller registered');

