/**
 * Sentiment & Flow Controller
 * Alpine.js controller with auto-refresh and race condition protection
 * Pattern: Copied from Open Interest (proven working)
 */

import { SentimentFlowAPIService } from './api-service.js';
import { SentimentFlowChartManager } from './chart-manager.js';
import { SentimentFlowUtils } from './utils.js';

// Immediately expose controller function for Alpine.js (no waiting!)
window.sentimentFlowController = function() {
    return {
        // State
        initialized: false,
        isLoading: false,
        errorMessage: null,
        errorCount: 0,
        maxErrors: 3,

        // Services
        apiService: null,
        chartManager: null,

        // Fear & Greed Data
        fearGreedValue: null,
        fearGreedSentiment: null,
        fearGreedHistory: [],
        fearGreedTimeRanges: [
            { value: '1d', label: '1D', days: 1 },
            { value: '1w', label: '1W', days: 7 },
            { value: '1m', label: '1M', days: 30 },
            { value: '3m', label: '3M', days: 90 },
            { value: 'all', label: 'ALL', days: null }
        ],
        selectedFearGreedRange: '1m',

        // Funding Dominance Data
        fundingExchanges: [],
        fundingAggregate: null,
        selectedSymbol: 'BTC',

        // Whale Alerts Data
        whaleAlerts: [],
        whaleAlertsFiltered: [],
        whaleAggregate: null,
        selectedWhaleSymbol: 'ALL',
        availableWhaleSymbols: ['ALL'],

        // Auto-refresh
        refreshEnabled: true,
        refreshInterval: null,

        /**
         * Initialize controller
         */
        async init() {
            if (this.initialized) return;
            this.initialized = true;

            console.log('🚀 Sentiment & Flow Analysis initialized');

            // Wait for Chart.js to be ready
            if (typeof window.chartJsReady !== 'undefined') {
                await window.chartJsReady;
                console.log('✅ Chart.js ready, creating chart manager');
            }

            this.apiService = new SentimentFlowAPIService();
            this.chartManager = new SentimentFlowChartManager('fearGreedChart');

            // Load all three sections in parallel
            await Promise.all([
                this.loadFearGreed(),
                this.loadFundingDominance(),
                this.loadWhaleAlerts()
            ]);

            // Start auto-refresh
            this.startAutoRefresh();
        },

        /**
         * Load Fear & Greed Index
         */
        async loadFearGreed(silent = false) {
            if (!silent) {
                this.isLoading = true;
                this.errorMessage = null;
            }

            try {
                const response = await this.apiService.fetchFearGreed({
                    preferFresh: !silent
                });

                if (response.success && response.data) {
                    this.fearGreedValue = response.data.current_value;
                    this.fearGreedSentiment = response.data.sentiment;
                    
                    // Filter history based on selected range
                    const allHistory = response.data.history || [];
                    const rangeConfig = this.fearGreedTimeRanges.find(r => r.value === this.selectedFearGreedRange);
                    
                    if (rangeConfig && rangeConfig.days !== null) {
                        // Get last N days
                        this.fearGreedHistory = allHistory.slice(-rangeConfig.days);
                    } else {
                        // ALL - show everything
                        this.fearGreedHistory = allHistory;
                    }

                    // Render chart
                    if (this.chartManager) {
                        this.chartManager.renderFearGreedChart(
                            this.fearGreedValue,
                            this.fearGreedHistory
                        );
                    }

                    if (!silent) {
                        console.log('✅ Fear & Greed data loaded:', this.fearGreedValue, '(' + this.fearGreedHistory.length + ' points)');
                    }
                    this.errorCount = 0;
                } else {
                    throw new Error(response.error?.message || 'Failed to load Fear & Greed data');
                }
            } catch (error) {
                console.error('[SentimentFlow:FearGreed:ERROR]', error);
                if (!silent) {
                    this.errorMessage = error.message;
                }
                this.errorCount++;
            } finally {
                if (!silent) {
                    this.isLoading = false;
                }
            }
        },

        /**
         * Update Fear & Greed time range
         */
        async updateFearGreedRange(range) {
            console.log('🎯 Updating Fear & Greed range to:', range);
            this.selectedFearGreedRange = range;
            await this.loadFearGreed();
        },

        /**
         * Load Funding Dominance
         */
        async loadFundingDominance(silent = false) {
            try {
                const response = await this.apiService.fetchFundingDominance({
                    symbol: this.selectedSymbol,
                    preferFresh: !silent
                });

                if (response.success && response.data) {
                    this.fundingExchanges = response.data.exchanges || [];
                    this.fundingAggregate = response.data.aggregate;

                    if (!silent) {
                        console.log('✅ Funding dominance loaded:', this.fundingExchanges.length, 'exchanges');
                    }
                } else {
                    throw new Error(response.error?.message || 'Failed to load funding data');
                }
            } catch (error) {
                console.error('[SentimentFlow:Funding:ERROR]', error);
            }
        },

        /**
         * Load Whale Alerts
         */
        async loadWhaleAlerts(silent = false) {
            try {
                const response = await this.apiService.fetchWhaleAlerts({
                    preferFresh: !silent
                });

                if (response.success && response.data) {
                    this.whaleAlerts = response.data.alerts || [];
                    this.whaleAggregate = response.data.aggregate;

                    // Extract unique symbols
                    const uniqueSymbols = [...new Set(this.whaleAlerts.map(a => a.symbol))].sort();
                    this.availableWhaleSymbols = ['ALL', ...uniqueSymbols];

                    // Apply filter
                    this.filterWhaleAlerts();

                    if (!silent) {
                        console.log('✅ Whale alerts loaded:', this.whaleAlerts.length, 'alerts');
                    }
                } else {
                    throw new Error(response.error?.message || 'Failed to load whale alerts');
                }
            } catch (error) {
                console.error('[SentimentFlow:Whale:ERROR]', error);
            }
        },

        /**
         * Filter whale alerts by symbol
         */
        filterWhaleAlerts() {
            if (this.selectedWhaleSymbol === 'ALL') {
                this.whaleAlertsFiltered = this.whaleAlerts;
            } else {
                this.whaleAlertsFiltered = this.whaleAlerts.filter(
                    alert => alert.symbol === this.selectedWhaleSymbol
                );
            }
        },

        /**
         * Update whale symbol filter
         */
        updateWhaleSymbol(symbol) {
            console.log('🐋 Filtering whales by symbol:', symbol);
            this.selectedWhaleSymbol = symbol;
            this.filterWhaleAlerts();
        },

        // Whale transfers logic removed (handled in On-Chain Metrics dashboard)

        /**
         * Start auto-refresh (5 seconds interval)
         */
        startAutoRefresh() {
            this.stopAutoRefresh();

            if (!this.refreshEnabled) return;

            // 5 second interval
            this.refreshInterval = setInterval(() => {
                if (document.hidden) return;
                if (this.isLoading) return;
                if (this.errorCount >= this.maxErrors) {
                    console.warn('🚨 Auto-refresh disabled due to errors');
                    this.stopAutoRefresh();
                    return;
                }

                console.log('🔄 Auto-refresh: Silent update (5s) - ALL sections');

                // Refresh ALL 4 sections in parallel
                Promise.all([
                    this.loadFearGreed(true),
                    this.loadFundingDominance(true),
                    this.loadWhaleAlerts(true)
                ]).catch(error => {
                    console.error('Auto-refresh error:', error);
                    this.errorCount++;
                });

            }, 5000); // 5 seconds

            // Refresh when tab becomes visible
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.refreshEnabled) {
                    console.log('👁️ Page visible: Triggering refresh');
                    if (!this.isLoading) {
                        Promise.all([
                            this.loadFearGreed(true),
                            this.loadFundingDominance(true),
                            this.loadWhaleAlerts(true)
                        ]);
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
         * Manual refresh all
         */
        async refreshAll() {
            console.log('🔄 Manual refresh triggered');
            this.apiService.clearCache();
            await Promise.all([
                this.loadFearGreed(),
                this.loadFundingDominance(),
                this.loadWhaleAlerts()
            ]);
        },

        /**
         * Utility formatters (exposed to Alpine template)
         */
        formatPercent(value) {
            return SentimentFlowUtils.formatPercent(value);
        },

        formatFundingRate(value) {
            return SentimentFlowUtils.formatFundingRate(value);
        },

        formatAnnualizedRate(value) {
            return SentimentFlowUtils.formatAnnualizedRate(value);
        },

        formatUSD(value) {
            return SentimentFlowUtils.formatUSD(value);
        },

        formatPrice(value) {
            return SentimentFlowUtils.formatPrice(value);
        },

        formatNumber(value) {
            return SentimentFlowUtils.formatNumber(value);
        },

        formatDate(timestamp) {
            return SentimentFlowUtils.formatDate(timestamp);
        },

        getFearGreedColor(value) {
            return SentimentFlowUtils.getFearGreedColor(value);
        },

        getFundingColor(rate) {
            return SentimentFlowUtils.getFundingColor(rate);
        },

        getFundingTrend(rate) {
            return SentimentFlowUtils.getFundingTrend(rate);
        },

        getPositionBadgeClass(type) {
            return SentimentFlowUtils.getPositionBadgeClass(type);
        },

        getActionBadgeClass(action) {
            return SentimentFlowUtils.getActionBadgeClass(action);
        },

        truncateAddress(address) {
            return SentimentFlowUtils.truncateAddress(address);
        },

        formatLargeUsd(value) {
            return SentimentFlowUtils.formatLargeUsd(value);
        },

        formatBlockchain(blockchain) {
            return SentimentFlowUtils.formatBlockchain(blockchain);
        },

        truncateTxHash(hash) {
            return SentimentFlowUtils.truncateTxHash(hash);
        },

        getDirectionBadgeClass(from, to) {
            return SentimentFlowUtils.getDirectionBadgeClass(from, to);
        },

        getDirectionLabel(from, to) {
            return SentimentFlowUtils.getDirectionLabel(from, to);
        }
    };
};

console.log('✅ Sentiment & Flow controller registered');

