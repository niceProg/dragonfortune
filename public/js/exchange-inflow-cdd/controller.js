/**
 * Exchange Inflow CDD Controller
 * Copied from Open Interest - proven to work!
 */

import { ExchangeInflowCDDAPIService } from './api-service.js';
import { ChartManager } from './chart-manager.js';
import { CDDUtils } from './utils.js';

export function createCDDController() {
    return {
        initialized: false,
        apiService: null,
        chartManager: null,
        zScoreChart: null,
        maChart: null,

        // State
        selectedExchange: 'all_exchange',
        selectedInterval: '1d',
        selectedTimeRange: '1m', // Default 1 month

        // Exchange options (tested & verified working with CryptoQuant API)
        exchangeOptions: [
            // Aggregated Exchanges
            { value: 'all_exchange', label: 'All Exchanges' },
            { value: 'spot_exchange', label: 'Spot Exchanges' },
            { value: 'derivative_exchange', label: 'Derivative Exchanges' },
            // Major Exchanges (alphabetically)
            { value: 'binance', label: 'Binance' },
            { value: 'bitfinex', label: 'Bitfinex' },
            { value: 'bitflyer', label: 'bitFlyer' },
            { value: 'bithumb', label: 'Bithumb' },
            { value: 'bitmex', label: 'BitMEX' },
            { value: 'bitstamp', label: 'Bitstamp' },
            { value: 'bittrex', label: 'Bittrex' },
            { value: 'bybit', label: 'Bybit' },
            { value: 'coinone', label: 'CoinOne' },
            { value: 'deribit', label: 'Deribit' },
            { value: 'ftx', label: 'FTX' },
            { value: 'gate_io', label: 'Gate.io' },
            { value: 'gemini', label: 'Gemini' },
            { value: 'gopax', label: 'GOPAX' },
            { value: 'korbit', label: 'Korbit' },
            { value: 'kraken', label: 'Kraken' },
            { value: 'kucoin', label: 'KuCoin' },
            { value: 'mexc', label: 'MEXC' },
            { value: 'okx', label: 'OKX' },
            { value: 'poloniex', label: 'Poloniex' },
            { value: 'upbit', label: 'Upbit' }
        ],

        // Time ranges
        timeRanges: [
            { label: '1W', value: '1w', days: 7 },
            { label: '1M', value: '1m', days: 30 },
            { label: '3M', value: '3m', days: 90 },
            { label: '6M', value: '6m', days: 180 },
            { label: '1Y', value: '1y', days: 365 }
        ],

        // Chart intervals (ONLY DAILY available for this CryptoQuant plan)
        chartIntervals: [
            { label: '1D', value: '1d' } // Only daily data available
        ],

        // Loading state
        isLoading: false,

        // Auto-refresh
        refreshInterval: null,
        refreshEnabled: true,
        errorCount: 0,
        maxErrors: 3,

        // Data
        rawData: [],
        priceData: [], // BTC Price data for overlay
        currentCDD: null,
        minCDD: null,
        maxCDD: null,
        avgCDD: null,
        medianCDD: null, // Additional metrics for view
        ma7: null,
        ma30: null,
        peakDate: null,
        signalStrength: 'N/A',
        marketSignal: 'N/A',
        cddChange: null,
        cddVolatility: null,
        momentum: null,
        zScore: null, // Z-Score for anomaly detection
        maCrossSignal: 'neutral', // MA cross signal: 'warning', 'safe', 'neutral'
        
        // Z-Score Event Counts
        zScoreHighEvents: 0,  // > 2σ
        zScoreExtremeEvents: 0,  // > 3σ
        
        // BTC Price metrics
        currentPrice: null,
        priceChange: null,
        showPriceOverlay: true, // Toggle for price overlay

        async init() {
            if (this.initialized) return;
            this.initialized = true;

            console.log('🚀 Exchange Inflow CDD initialized');

            this.apiService = new ExchangeInflowCDDAPIService();
            this.chartManager = new ChartManager('cdd-chart');

            await this.loadData();

            // Start auto-refresh
            this.startAutoRefresh();
        },

        async loadData(isAutoRefresh = false) {
            if (this.isLoading && !isAutoRefresh) {
                console.warn('⚠️ Load already in progress, skipping');
                return;
            }

            const startTime = performance.now();
            this.isLoading = true;

            try {
                const { start_date, end_date } = this.getDateRange();

                console.log('[CDD:LOAD]', {
                    exchange: this.selectedExchange,
                    interval: this.selectedInterval,
                    range: this.selectedTimeRange,
                    start: start_date,
                    end: end_date
                });

                const fetchStart = performance.now();

                const response = await this.apiService.fetchHistory({
                    exchange: this.selectedExchange,
                    interval: this.selectedInterval,
                    start_date,
                    end_date,
                    preferFresh: !isAutoRefresh
                });

                const fetchEnd = performance.now();
                const fetchTime = Math.round(fetchEnd - fetchStart);

                // Handle response format: should be { data: [...], metrics: {...} }
                const data = response.data || [];
                const backendMetrics = response.metrics || null;

                if (data && data.length > 0) {
                    this.rawData = data;
                    this.calculateMetrics(backendMetrics);
                    
                    // Fetch BTC price data for overlay
                    await this.loadPriceData(start_date, end_date);
                    
                    this.renderChart();

                    // Reset error count on success
                    this.errorCount = 0;

                    const totalTime = Math.round(performance.now() - startTime);
                    console.log(`[CDD:OK] ${data.length} points (fetch: ${fetchTime}ms, total: ${totalTime}ms)`);
                } else {
                    console.warn('[CDD:EMPTY]');
                }
            } catch (error) {
                console.error('[CDD:ERROR]', error);

                // Circuit breaker
                this.errorCount++;
                if (this.errorCount >= this.maxErrors) {
                    console.error('🚨 Circuit breaker tripped!');
                    this.stopAutoRefresh();

                    setTimeout(() => {
                        console.log('🔄 Circuit breaker reset');
                        this.errorCount = 0;
                        this.startAutoRefresh();
                    }, 300000); // 5 minutes
                }
            } finally {
                this.isLoading = false;
            }
        },

        getDateRange() {
            const range = this.timeRanges.find(r => r.value === this.selectedTimeRange);
            const days = range ? range.days : 30;
            return this.apiService.computeDateRange(days);
        },

        async loadPriceData(startDate, endDate) {
            try {
                const url = `${window.location.origin}/api/cryptoquant/btc-market-price?start_date=${startDate}&end_date=${endDate}`;
                console.log('💰 Fetching Bitcoin price from:', url);
                
                const response = await fetch(url);
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        // Transform price data
                        this.priceData = data.data.map(item => ({
                            date: item.date,
                            price: parseFloat(item.close || item.value) // Use close price or value
                        }));
                        
                        // Calculate current price and change
                        const latest = this.priceData[this.priceData.length - 1];
                        const previous = this.priceData[this.priceData.length - 2];
                        
                        this.currentPrice = latest.price;
                        this.priceChange = previous ? ((latest.price - previous.price) / previous.price) * 100 : 0;
                        
                        console.log(`✅ Loaded ${this.priceData.length} BTC price points`);
                        console.log(`💰 Current BTC: $${this.currentPrice.toLocaleString()}, Change: ${this.priceChange.toFixed(2)}%`);
                    } else {
                        console.warn('⚠️ CryptoQuant returned empty price data');
                        this.priceData = [];
                    }
                } else {
                    console.warn('⚠️ Bitcoin price API failed:', response.status);
                    this.priceData = [];
                }
            } catch (error) {
                console.warn('⚠️ Failed to fetch Bitcoin price:', error);
                this.priceData = [];
            }
        },

        calculateMetrics(backendMetrics = null) {
            if (this.rawData.length === 0) return;

            // Use backend metrics if available (preferred for production)
            if (backendMetrics) {
                console.log('📊 Using backend-calculated metrics (PHP)');
                
                // Backend-calculated advanced metrics
                this.zScore = backendMetrics.zScore;
                this.ma7 = backendMetrics.ma7;
                this.ma30 = backendMetrics.ma30;
                this.maCrossSignal = backendMetrics.maCrossSignal;
                
                // Calculate basic metrics locally (for display)
                const basicMetrics = this.computeBasicMetrics(this.rawData);
                this.currentCDD = basicMetrics.currentCDD;
                this.minCDD = basicMetrics.minCDD;
                this.maxCDD = basicMetrics.maxCDD;
                this.avgCDD = basicMetrics.avgCDD;
                this.cddChange = basicMetrics.cddChange;
                this.cddVolatility = basicMetrics.cddVolatility;
                this.momentum = basicMetrics.momentum;
                
                // Calculate Z-Score event counts locally (needs full dataset)
                const fullMetrics = this.computeMetrics(this.rawData);
                this.zScoreHighEvents = fullMetrics.zScoreHighEvents || 0;
                this.zScoreExtremeEvents = fullMetrics.zScoreExtremeEvents || 0;
            } else {
                console.log('💻 Fallback to client-side calculation (JS)');
                
                // Fallback to full client-side calculation
                const metrics = this.computeMetrics(this.rawData);

                this.currentCDD = metrics.currentCDD;
                this.minCDD = metrics.minCDD;
                this.maxCDD = metrics.maxCDD;
                this.avgCDD = metrics.avgCDD;
                this.cddChange = metrics.cddChange;
                this.cddVolatility = metrics.cddVolatility;
                this.momentum = metrics.momentum;
                this.zScore = metrics.zScore;
                this.zScoreHighEvents = metrics.zScoreHighEvents || 0;
                this.zScoreExtremeEvents = metrics.zScoreExtremeEvents || 0;
                this.ma7 = metrics.ma7;
                this.ma30 = metrics.ma30;
                this.maCrossSignal = metrics.maCrossSignal;
            }
        },

        computeBasicMetrics(rawData) {
            if (rawData.length === 0) return {};

            const values = rawData.map(d => parseFloat(d.value || 0));

            const currentCDD = values[values.length - 1];
            const minCDD = Math.min(...values);
            const maxCDD = Math.max(...values);
            const avgCDD = values.reduce((a, b) => a + b, 0) / values.length;

            let cddChange = null;
            if (values.length > 1) {
                cddChange = ((currentCDD - values[0]) / values[0]) * 100;
            }

            let cddVolatility = 0;
            if (values.length > 1) {
                const variance = values.reduce((acc, val) => acc + Math.pow(val - avgCDD, 2), 0) / values.length;
                const stdDev = Math.sqrt(variance);
                cddVolatility = stdDev / avgCDD * 100;
            }

            let momentum = 0;
            if (values.length >= 10) {
                const recentAvg = values.slice(-5).reduce((a, b) => a + b, 0) / 5;
                momentum = ((recentAvg - avgCDD) / avgCDD) * 100;
            }

            return {
                currentCDD,
                minCDD,
                maxCDD,
                avgCDD,
                cddChange,
                cddVolatility,
                momentum
            };
        },

        computeMetrics(rawData) {
            if (rawData.length === 0) return {};

            const values = rawData.map(d => parseFloat(d.value || 0));

            const currentCDD = values[values.length - 1];
            const minCDD = Math.min(...values);
            const maxCDD = Math.max(...values);
            const avgCDD = values.reduce((a, b) => a + b, 0) / values.length;

            let cddChange = null;
            if (values.length > 1) {
                cddChange = ((currentCDD - values[0]) / values[0]) * 100;
            }

            let cddVolatility = 0;
            let stdDev = 0;
            if (values.length > 1) {
                const variance = values.reduce((acc, val) => acc + Math.pow(val - avgCDD, 2), 0) / values.length;
                stdDev = Math.sqrt(variance);
                cddVolatility = stdDev / avgCDD * 100;
            }

            let momentum = 0;
            if (values.length >= 10) {
                const recentAvg = values.slice(-5).reduce((a, b) => a + b, 0) / 5;
                momentum = ((recentAvg - avgCDD) / avgCDD) * 100;
            }

            // Z-Score calculation (anomaly detection)
            let zScore = null;
            let zScoreHighEvents = 0;  // > 2σ
            let zScoreExtremeEvents = 0;  // > 3σ
            
            if (stdDev > 0) {
                zScore = (currentCDD - avgCDD) / stdDev;
                
                // Count events by Z-Score threshold
                const zScores = values.map(val => (val - avgCDD) / stdDev);
                zScoreHighEvents = zScores.filter(z => Math.abs(z) > 2).length;
                zScoreExtremeEvents = zScores.filter(z => Math.abs(z) > 3).length;
            }

            // Moving Averages (7D and 30D)
            let ma7 = null;
            let ma30 = null;
            if (values.length >= 7) {
                ma7 = values.slice(-7).reduce((a, b) => a + b, 0) / 7;
            }
            if (values.length >= 30) {
                ma30 = values.slice(-30).reduce((a, b) => a + b, 0) / 30;
            }

            // MA Cross Signal
            // For CDD: MA7 > MA30 = pressure increasing (WARNING/bearish for price)
            // For CDD: MA7 < MA30 = pressure decreasing (SAFE/bullish for price)
            let maCrossSignal = 'neutral';
            if (ma7 !== null && ma30 !== null) {
                const crossPct = ((ma7 - ma30) / ma30) * 100;
                if (crossPct > 5) {
                    maCrossSignal = 'warning'; // MA7 significantly above MA30 = distribution increasing
                } else if (crossPct < -5) {
                    maCrossSignal = 'safe'; // MA7 significantly below MA30 = distribution decreasing
                }
            }

            return {
                currentCDD,
                minCDD,
                maxCDD,
                avgCDD,
                cddChange,
                cddVolatility,
                momentum,
                zScore,
                zScoreHighEvents,
                zScoreExtremeEvents,
                ma7,
                ma30,
                maCrossSignal
            };
        },

        renderChart() {
            if (!this.chartManager || this.rawData.length === 0) return;
            
            // Render main CDD chart with optional BTC price overlay
            const priceDataToShow = this.showPriceOverlay ? this.priceData : [];
            this.chartManager.renderChart(this.rawData, priceDataToShow);
            
            // Render Z-Score distribution chart
            if (this.zScore !== null) {
                this.zScoreChart = this.chartManager.renderZScoreChart(this.rawData, this.zScore);
            }
            
            // Render MA trend chart
            if (this.ma7 !== null && this.ma30 !== null) {
                this.maChart = this.chartManager.renderMAChart(this.rawData, this.ma7, this.ma30);
            }
        },
        
        togglePriceOverlay() {
            this.showPriceOverlay = !this.showPriceOverlay;
            console.log('💰 Price overlay toggled:', this.showPriceOverlay ? 'ON' : 'OFF');
            this.renderChart(); // Re-render chart with updated overlay state
        },

        // Direct load for user interactions
        instantLoadData() {
            console.log('⚡ Instant load triggered');

            if (this.isLoading) {
                console.log('⚡ Force loading for user interaction');
                this.isLoading = false;
            }

            this.loadData();
        },

        setChartInterval(value) {
            console.log('🎯 setChartInterval called with:', value, 'current:', this.selectedInterval);
            if (this.selectedInterval === value) {
                console.log('⚠️ Same interval, skipping');
                return;
            }
            console.log('🎯 Interval changed to:', value);
            this.selectedInterval = value;

            console.log('🚀 Filter changed, triggering instant load');
            this.instantLoadData();
        },

        // Alpine expects these names from the blade template
        updateInterval(value) {
            console.log('🎯 updateInterval called with:', value);
            this.setChartInterval(value);
        },

        updateExchange(value) {
            console.log('🎯 updateExchange called with:', value, 'current:', this.selectedExchange);
            if (value && value !== this.selectedExchange) {
                console.log('🎯 Exchange changed to:', value);
                this.selectedExchange = value;
                console.log('🚀 Filter changed, triggering instant load');
                this.instantLoadData();
            } else {
                console.log('⚠️ Same exchange, skipping');
            }
        },

        updateTimeRange(value) {
            console.log('🎯 updateTimeRange called with:', value, 'current:', this.selectedTimeRange);
            if (value && value !== this.selectedTimeRange) {
                console.log('🎯 Time range changed to:', value);
                this.selectedTimeRange = value;
                console.log('🚀 Filter changed, triggering instant load');
                this.instantLoadData();
            } else {
                console.log('⚠️ Same time range, skipping');
            }
        },

        formatCDD(value) {
            return CDDUtils.formatCDD(value);
        },

        formatChange(value) {
            if (value === null || value === undefined) return '';
            const sign = value >= 0 ? '+' : '';
            return `${sign}${value.toFixed(2)}%`;
        },

        formatPercentage(value) {
            if (value === null || value === undefined) return '';
            return `${value.toFixed(2)}%`;
        },

        // Stub methods for view compatibility
        getTrendClass(change) {
            if (change > 0) return 'text-danger'; // CDD increase = potential distribution
            if (change < 0) return 'text-success'; // CDD decrease = accumulation
            return 'text-secondary';
        },

        getSignalBadgeClass() {
            return 'text-bg-secondary'; // Default badge
        },

        getSignalColorClass() {
            return 'text-secondary'; // Default color
        },

        // Auto-refresh functionality
        startAutoRefresh() {
            this.stopAutoRefresh();

            if (!this.refreshEnabled) return;

            // 5 minute interval (optimized for daily CDD data + API rate limit)
            this.refreshInterval = setInterval(() => {
                if (document.hidden) return;
                if (this.isLoading) return;

                if (this.errorCount >= this.maxErrors) {
                    console.warn('🚨 Auto-refresh disabled due to errors');
                    this.stopAutoRefresh();
                    return;
                }

                console.log('🔄 Auto-refresh: Silent update (5min)');
                this.loadData(true);

            }, 300000); // 5 minutes (300 seconds)

            // Handle page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.refreshEnabled) {
                    console.log('👁️ Page visible: Triggering refresh');
                    if (!this.isLoading) {
                        this.loadData(true);
                    }
                }
            });

            console.log('✅ Auto-refresh started (5 min interval)');
        },

        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
                console.log('⏹️ Auto-refresh stopped');
            }
        },

        cleanup() {
            this.stopAutoRefresh();
            if (this.chartManager) this.chartManager.destroy();
            if (this.apiService) this.apiService.cancelRequest();
        }
    };
}

// Register globally
window.createCDDController = createCDDController;
console.log('✅ CDD controller registered');

