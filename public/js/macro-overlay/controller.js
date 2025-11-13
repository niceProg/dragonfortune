/**
 * Macro Overlay Controller
 * Alpine.js controller for the Macro Overlay dashboard
 * Pattern: Volatility & ETF Flows blueprint (real-time silent updates)
 */

import { MacroAPIService } from './api-service.js';
import { MacroChartManager } from './chart-manager.js';
import { MacroUtils } from './utils.js';

// Immediately expose controller function for Alpine.js (no waiting!)
window.macroController = function() {
    return {
        // State
        initialized: false,
        isLoading: false,
        isLoadingCharts: false, // ⚡ Separate flag for chart loading
        errorMessage: null,
        errorCount: 0,
        maxErrors: 3,

        // Services
        apiService: null,
        chartManager: null,

        // Latest Macro Data
        latestData: {},
        
        // Historical Data for Charts
        dxyData: [],
        yield10yData: [],
        yield2yData: [],
        fedFundsData: [],
        cpiData: [],
        m2Data: [],
        rrpData: [],
        tgaData: [],
        bitcoinM2Data: [],

        // Filters
        dataLimit: 100,
        limitOptions: [
            { value: 30, label: '30 Days' },
            { value: 90, label: '90 Days' },
            { value: 180, label: '6 Months' },
            { value: 365, label: '1 Year' },
            { value: 730, label: '2 Years' },
            { value: 10000, label: 'ALL' } // ⚡ ALL option (FRED max)
        ],

        // Bitcoin M2 Data (no filters needed - endpoint returns all data)

        // Auto-refresh
        refreshEnabled: true,
        refreshInterval: null,

        /**
         * Initialize controller
         */
        async init() {
            if (this.initialized) return;
            this.initialized = true;

            console.log('🚀 Macro Overlay initialized');

            // Wait for Chart.js to be ready
            if (typeof window.chartJsReady !== 'undefined') {
                await window.chartJsReady;
                console.log('✅ Chart.js ready, creating chart manager');
            }

            this.apiService = new MacroAPIService();
            this.chartManager = new MacroChartManager();

            // Load initial data
            await this.loadAllData();

            // Start auto-refresh
            this.startAutoRefresh();
        },

        /**
         * Load all macro data
         */
        async loadAllData(silent = false) {
            if (!silent) {
                this.isLoading = true;
                this.errorMessage = null;
            }

            const startTime = Date.now();
            console.log('[Macro:LOAD] Loading all macro data...');

            try {
                // Load latest values and historical data in parallel
                await Promise.all([
                    this.loadLatestData(silent),
                    this.loadHistoricalCharts(silent),
                    this.loadBitcoinM2(silent)
                ]);

                const elapsed = Date.now() - startTime;
                console.log(`[Macro:OK] All data loaded (total: ${elapsed}ms)`);

                this.errorCount = 0; // Reset error count on success

            } catch (error) {
                console.error('[Macro:ERROR]', error);
                this.errorMessage = error.message || 'Failed to load macro data';
                this.errorCount++;
            } finally {
                if (!silent) {
                    this.isLoading = false;
                }
            }
        },

        /**
         * Load latest values for all indicators
         */
        async loadLatestData(silent = false) {
            try {
                const response = await this.apiService.fetchFredLatest({
                    preferFresh: !silent
                });

                if (response.success && response.data) {
                    this.latestData = response.data;
                    
                    if (!silent) {
                        console.log('✅ Latest macro data loaded:', this.latestData);
                    }
                }
            } catch (error) {
                console.error('[Macro:Latest:ERROR]', error);
                throw error;
            }
        },

        /**
         * Load historical data and render charts
         * Pattern: Open Interest blueprint (sequential rendering with delay)
         */
        async loadHistoricalCharts(silent = false) {
            // ⚡ FIXED: Prevent concurrent chart loads
            if (this.isLoadingCharts && !silent) {
                console.warn('⚠️ Chart load already in progress, skipping');
                return;
            }

            this.isLoadingCharts = true;

            try {
                // Load individual series
                const [dxy, yield10y, yield2y, fedFunds] = await Promise.all([
                    this.apiService.fetchFredSingleSeries('DTWEXBGS', {
                        limit: this.dataLimit,
                        preferFresh: !silent
                    }),
                    this.apiService.fetchFredSingleSeries('DGS10', {
                        limit: this.dataLimit,
                        preferFresh: !silent
                    }),
                    this.apiService.fetchFredSingleSeries('DGS2', {
                        limit: this.dataLimit,
                        preferFresh: !silent
                    }),
                    this.apiService.fetchFredSingleSeries('DFF', {
                        limit: this.dataLimit,
                        preferFresh: !silent
                    })
                ]);

                // ⚡ FIXED: Sequential rendering with micro-delays to prevent race conditions
                // Update data first
                if (dxy.success && dxy.data.length > 0) {
                    this.dxyData = dxy.data.reverse();
                }
                if (yield10y.success && yield10y.data.length > 0) {
                    this.yield10yData = yield10y.data.reverse();
                }
                if (yield2y.success && yield2y.data.length > 0) {
                    this.yield2yData = yield2y.data.reverse();
                }
                if (fedFunds.success && fedFunds.data.length > 0) {
                    this.fedFundsData = fedFunds.data.reverse();
                }

                // ⚡ FIXED: Render charts sequentially with delays
                // This prevents concurrent canvas access (increased delay for stability)
                if (this.dxyData.length > 0) {
                    this.renderChart('dxyChart', this.dxyData, {
                        seriesId: 'DTWEXBGS',
                        label: 'DXY (Dollar Index)',
                        color: '#3b82f6',
                        yAxisLabel: 'Index Value'
                    });
                    await new Promise(resolve => setTimeout(resolve, 50)); // 50ms delay for safety
                }

                if (this.yield10yData.length > 0) {
                    this.renderChart('yield10yChart', this.yield10yData, {
                        seriesId: 'DGS10',
                        label: '10Y Treasury Yield',
                        color: '#10b981',
                        yAxisLabel: 'Yield (%)'
                    });
                    await new Promise(resolve => setTimeout(resolve, 50));
                }

                if (this.yield2yData.length > 0) {
                    this.renderChart('yield2yChart', this.yield2yData, {
                        seriesId: 'DGS2',
                        label: '2Y Treasury Yield',
                        color: '#8b5cf6',
                        yAxisLabel: 'Yield (%)'
                    });
                    await new Promise(resolve => setTimeout(resolve, 50));
                }

                if (this.fedFundsData.length > 0) {
                    this.renderChart('fedFundsChart', this.fedFundsData, {
                        seriesId: 'DFF',
                        label: 'Fed Funds Rate',
                        color: '#ef4444',
                        yAxisLabel: 'Rate (%)'
                    });
                    await new Promise(resolve => setTimeout(resolve, 50));
                }

                if (!silent) {
                    console.log('✅ Historical charts rendered');
                }

            } catch (error) {
                console.error('[Macro:Charts:ERROR]', error);
                throw error;
            } finally {
                // ⚡ FIXED: Always reset loading flag
                this.isLoadingCharts = false;
            }
        },

        /**
         * Load Bitcoin vs M2 data
         * Pattern: Simple and robust - always render
         * Note: Endpoint does not require any parameters
         */
        async loadBitcoinM2(silent = false) {
            try {
                const response = await this.apiService.fetchBitcoinM2({
                    preferFresh: !silent
                });

                if (response.success && response.data && response.data.length > 0) {
                    this.bitcoinM2Data = response.data;
                    
                    // ⚡ FIXED: Always render chart (like Open Interest pattern)
                    this.chartManager.renderBitcoinM2Chart('bitcoinM2Chart', this.bitcoinM2Data);
                    
                    if (!silent) {
                        console.log(`✅ Bitcoin vs M2 data loaded: ${this.bitcoinM2Data.length} points`);
                    }
                }
            } catch (error) {
                console.error('[Macro:BitcoinM2:ERROR]', error);
                // Don't throw - this is optional data
            }
        },

        /**
         * Render a chart
         */
        renderChart(canvasId, data, options) {
            if (data.length > 0) {
                this.chartManager.renderFredChart(canvasId, data, options);
            }
        },

        /**
         * Change data limit
         * Pattern: Open Interest instantLoadData
         */
        async changeDataLimit(newLimit) {
            if (newLimit === this.dataLimit) return;
            
            console.log('🎯 Changing data limit to:', newLimit);
            this.dataLimit = newLimit;
            
            // ⚡ FIXED: Force load even if currently loading (user interaction priority)
            if (this.isLoadingCharts) {
                console.log('⚡ Force loading for user interaction (overriding current load)');
                this.isLoadingCharts = false; // Reset flag to allow new load
            }
            
            this.apiService.clearCache();
            await this.loadHistoricalCharts();
        },


        /**
         * Start auto-refresh
         */
        startAutoRefresh() {
            this.stopAutoRefresh();

            if (!this.refreshEnabled) return;

            // 5 second interval (like Volatility and ETF Flows)
            this.refreshInterval = setInterval(() => {
                if (document.hidden) return;
                if (this.isLoading) return;
                if (this.errorCount >= this.maxErrors) {
                    console.warn('🚨 Auto-refresh disabled due to errors');
                    this.stopAutoRefresh();
                    return;
                }

                console.log('🔄 Auto-refresh: Silent update (5s)');
                this.loadAllData(true).catch(error => {
                    console.error('Auto-refresh error:', error);
                    this.errorCount++;
                });

            }, 5000); // 5 seconds

            // Refresh when tab becomes visible
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.refreshEnabled) {
                    console.log('👁️ Page visible: Triggering refresh');
                    if (!this.isLoading) {
                        this.loadAllData(true);
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
         * Formatting helpers (expose utilities)
         */
        formatNumber: (value, decimals) => MacroUtils.formatNumber(value, decimals),
        formatLargeNumber: (value) => MacroUtils.formatLargeNumber(value),
        formatPercent: (value, decimals) => MacroUtils.formatPercent(value, decimals),
        formatDate: (dateString) => MacroUtils.formatDate(dateString),
        formatSeriesValue: (seriesId, value) => MacroUtils.formatSeriesValue(seriesId, value),
        getSeriesLabel: (seriesId) => MacroUtils.getSeriesLabel(seriesId),
        getChangeColor: (current, previous) => MacroUtils.getChangeColor(current, previous),
        getTrendArrow: (current, previous) => MacroUtils.getTrendArrow(current, previous)
    };
};

console.log('✅ Macro Overlay controller registered');

