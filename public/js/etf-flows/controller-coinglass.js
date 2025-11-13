/**
 * ETF Flows Controller (Coinglass)
 * Bitcoin ETF Flow History Dashboard
 */

import { EtfFlowsAPIService } from './api-service.js';
import { ChartManager } from './chart-manager.js';
import { EtfFlowsUtils } from './utils.js';

// Alpine.js controller function
function etfFlowsController() {
    return {
        initialized: false,
        apiService: null,
        chartManager: null,

        // State - ETF Flows is Bitcoin only, no symbol selection needed
        selectedTimeRange: '1m', // Default 1 month

        // Time ranges for filtering display (ETF data is daily)
        timeRanges: [
            { label: '1W', value: '1w', days: 7 },
            { label: '1M', value: '1m', days: 30 },
            { label: '3M', value: '3m', days: 90 },
            { label: '6M', value: '6m', days: 180 },
            { label: '1Y', value: '1y', days: 365 },
            { label: 'ALL', value: 'all', days: 1095 }
        ],

        // Loading state
        isLoading: false,

        // Auto-refresh
        refreshInterval: null,
        refreshEnabled: true,
        errorCount: 0,
        maxErrors: 3,

        // Data (Flow-based)
        rawData: [],
        currentFlow: null,    // Latest flow value
        totalInflow: null,    // Total positive flows
        totalOutflow: null,   // Total negative flows
        netFlow: null,        // Net flow (inflow - outflow)
        avgDailyFlow: null,   // Average daily flow
        flowTrend: null,      // Recent trend (positive/negative)

        // CME Open Interest State
        cmeData: [],
        cmeLoading: false,
        cmeOiLatest: 0,
        cmeOiChange: 0,
        cmeOiChangePercent: 0,
        cmeTimeRanges: [
            { label: '1W', value: 7 },
            { label: '1M', value: 30 },
            { label: '3M', value: 90 },
            { label: '6M', value: 180 }
        ],
        selectedCmeTimeRange: 30, // Default 1 month
        cmeChartInstance: null,

        async init() {
            if (this.initialized) return;
            this.initialized = true;

            console.log('🚀 ETF Flows (Coinglass) initialized');

            this.apiService = new EtfFlowsAPIService();
            this.chartManager = new ChartManager('etfFlowsMainChart');

            await this.loadData();
            
            // Load CME Open Interest
            await this.loadCmeOpenInterest();

            // Start auto-refresh for real-time updates
            this.startAutoRefresh();
        },



        async loadData(isAutoRefresh = false) {
            if (this.isLoading && !isAutoRefresh) {
                console.warn('⚠️ Load already in progress, skipping');
                return;
            }

            const startTime = performance.now();

            // ⚡ FIXED: Always set loading to prevent concurrent calls
            this.isLoading = true;

            try {
                const { start_time, end_time } = this.getDateRange();

                console.log('[ETF:LOAD]', {
                    range: this.selectedTimeRange
                });

                const fetchStart = performance.now();

                // ETF Flow History (Bitcoin only)
                const data = await this.apiService.fetchFlowHistory({
                    preferFresh: !isAutoRefresh
                });

                const fetchEnd = performance.now();
                const fetchTime = Math.round(fetchEnd - fetchStart);

                console.log('[ETF:DEBUG] Raw API response:', data);
                
                if (data && data.success && data.data && data.data.length > 0) {
                    // Filter data based on selected time range
                    this.rawData = this.filterDataByTimeRange(data.data);
                    this.calculateMetrics();

                    // Update UI
                    this.renderChart();
                    this.updateStatsCards();
                    this.updateLastRefreshTime();

                    // Reset error count on successful load
                    this.errorCount = 0;

                    const totalTime = Math.round(performance.now() - startTime);
                    console.log(`[ETF:OK] ${this.rawData.length} points (fetch: ${fetchTime}ms, total: ${totalTime}ms)`);
                } else {
                    console.warn('[ETF:EMPTY] No data received:', data);
                }
            } catch (error) {
                console.error('[ETF:ERROR]', error);

                // Circuit breaker: Prevent infinite error loops
                this.errorCount++;
                if (this.errorCount >= this.maxErrors) {
                    console.error('🚨 Circuit breaker tripped! Too many errors, stopping auto-refresh');
                    this.stopAutoRefresh();

                    // Reset after 5 minutes
                    setTimeout(() => {
                        console.log('🔄 Circuit breaker reset, resuming auto-refresh');
                        this.errorCount = 0;
                        this.startAutoRefresh();
                    }, 300000); // 5 minutes
                }
            } finally {
                this.isLoading = false;
            }
        },

        filterDataByTimeRange(data) {
            if (this.selectedTimeRange === 'all') return data;
            
            const range = this.timeRanges.find(r => r.value === this.selectedTimeRange);
            if (!range) return data;
            
            const cutoffDate = Date.now() - (range.days * 24 * 60 * 60 * 1000);
            return data.filter(item => item.ts >= cutoffDate);
        },

        getDateRange() {
            // ⚡ SIMPLIFIED: Use current time for fresh data
            const now = Date.now();
            const range = this.timeRanges.find(r => r.value === this.selectedTimeRange);
            const days = range ? range.days : 30;
            const start_time = now - (days * 24 * 60 * 60 * 1000);
            return { start_time, end_time: now };
        },

        getEffectiveInterval() {
            if (!this.useAdaptiveInterval) return this.selectedInterval;
            const range = this.timeRanges.find(r => r.value === this.selectedTimeRange);
            const days = range ? range.days : 30;
            if (days <= 2) return '1m';
            if (days <= 7) return '5m';
            if (days <= 30) return '15m';
            if (days <= 90) return '1h';
            if (days <= 180) return '4h';
            if (days <= 365) return '8h';
            return '1d';
        },

        calculateMetrics() {
            if (this.rawData.length === 0) return;

            const metrics = this.computeFlowMetrics(this.rawData);

            // Update properties
            this.currentFlow = metrics.currentFlow;
            this.totalInflow = metrics.totalInflow;
            this.totalOutflow = metrics.totalOutflow;
            this.netFlow = metrics.netFlow;
            this.avgDailyFlow = metrics.avgDailyFlow;
            this.flowTrend = metrics.flowTrend;
        },

        computeFlowMetrics(rawData) {
            if (rawData.length === 0) return {};

            const flows = rawData.map(d => parseFloat(d.flow_usd || 0));
            
            // Current flow (latest)
            const currentFlow = flows[flows.length - 1];
            
            // Separate inflows and outflows
            const inflows = flows.filter(f => f > 0);
            const outflows = flows.filter(f => f < 0);
            
            // Calculate totals
            const totalInflow = inflows.reduce((a, b) => a + b, 0);
            const totalOutflow = Math.abs(outflows.reduce((a, b) => a + b, 0));
            const netFlow = totalInflow - totalOutflow;
            const avgDailyFlow = flows.reduce((a, b) => a + b, 0) / flows.length;
            
            // Calculate trend (last 7 days vs previous 7 days)
            let flowTrend = 0;
            if (flows.length >= 14) {
                const recent7 = flows.slice(-7).reduce((a, b) => a + b, 0) / 7;
                const previous7 = flows.slice(-14, -7).reduce((a, b) => a + b, 0) / 7;
                if (previous7 !== 0) {
                    flowTrend = ((recent7 - previous7) / Math.abs(previous7)) * 100;
                }
            }

            return {
                currentFlow,
                totalInflow,
                totalOutflow,
                netFlow,
                avgDailyFlow,
                flowTrend
            };
        },

        renderChart() {
            if (!this.chartManager || this.rawData.length === 0) return;
            this.chartManager.renderChart(this.rawData);
        },



        // Direct load for user interactions
        instantLoadData() {
            console.log('⚡ Instant load triggered');

            // Force load even if currently loading (user interaction priority)
            if (this.isLoading) {
                console.log('⚡ Force loading for user interaction (overriding current load)');
                this.isLoading = false; // Reset flag to allow new load
            }

            this.loadData(); // Load immediately
        },

        setTimeRange(value) {
            console.log('🎯 setTimeRange called with:', value, 'current:', this.selectedTimeRange);
            if (this.selectedTimeRange === value) {
                console.log('⚠️ Same time range, skipping');
                return;
            }
            console.log('🎯 Time range changed to:', value);
            this.selectedTimeRange = value;

            // ⚡ FIXED: Always trigger load for filter changes
            console.log('🚀 Filter changed, triggering instant load');
            this.instantLoadData();
        },

        setChartInterval(value) {
            console.log('🎯 setChartInterval called with:', value, 'current:', this.selectedInterval);
            if (this.selectedInterval === value) {
                console.log('⚠️ Same interval, skipping');
                return;
            }
            console.log('🎯 Interval changed to:', value);
            this.selectedInterval = value;

            // ⚡ FIXED: Always trigger load for filter changes
            console.log('🚀 Filter changed, triggering instant load');
            this.instantLoadData();
        },

        // Alpine expects these names from the blade template
        updateInterval(value) {
            console.log('🎯 updateInterval called with:', value);
            this.setChartInterval(value);
        },

        updateSymbol(value) {
            console.log('🎯 updateSymbol called with:', value);
            if (value && value !== this.selectedSymbol) {
                console.log('🎯 Symbol changed to:', value);
                this.selectedSymbol = value;

                // ⚡ FIXED: Always trigger load for filter changes
                console.log('🚀 Filter changed, triggering instant load');
                this.instantLoadData();
            }
        },

        updateUnit(value) {
            console.log('🎯 updateUnit called with:', value);
            if (value && value !== this.selectedUnit) {
                console.log('🎯 Unit changed to:', value);
                this.selectedUnit = value;

                // ⚡ FIXED: Always trigger load for filter changes
                console.log('🚀 Filter changed, triggering instant load');
                this.instantLoadData();
            }
        },

        // ⚡ ADDED: Method for time range updates (might be missing)
        updateTimeRange(value) {
            console.log('🎯 updateTimeRange called with:', value);
            this.setTimeRange(value);
        },



        formatOI(value) {
            return OpenInterestUtils.formatOI(value);
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

        // Auto-refresh functionality
        startAutoRefresh() {
            this.stopAutoRefresh(); // Clear any existing interval

            if (!this.refreshEnabled) return;

            // 30 minute interval for ETF data updates
            this.refreshInterval = setInterval(() => {
                // Skip if page is hidden (tab not active)
                if (document.hidden) return;

                // Skip if currently loading to prevent race conditions
                if (this.isLoading) return;

                // Skip if too many errors
                if (this.errorCount >= this.maxErrors) {
                    console.warn('🚨 Auto-refresh disabled due to errors');
                    this.stopAutoRefresh();
                    return;
                }

                console.log('🔄 Auto-refresh: Silent update (30min)');
                this.loadData(true); // Silent background update

            }, 1800000); // 30 minutes

            // Handle page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.refreshEnabled) {
                    // Page became visible - trigger immediate update
                    console.log('👁️ Page visible: Triggering refresh');
                    if (!this.isLoading) {
                        this.loadData(true);
                    }
                }
            });

            console.log('✅ Auto-refresh started (30 minute interval)');
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
        },

        // Event handlers
        onTimeRangeChange(range) {
            if (this.selectedTimeRange !== range) {
                this.selectedTimeRange = range;
                this.loadData(false);
            }
        },

        // Update UI with calculated metrics
        updateStatsCards() {
            // Update current flow
            const currentFlowElement = document.getElementById('currentFlow');
            if (currentFlowElement && this.currentFlow !== null) {
                const color = EtfFlowsUtils.getFlowColor(this.currentFlow);
                currentFlowElement.style.color = color;
                currentFlowElement.textContent = EtfFlowsUtils.formatFlow(this.currentFlow);
            }

            // Update total inflow
            const totalInflowElement = document.getElementById('totalInflow');
            if (totalInflowElement && this.totalInflow !== null) {
                totalInflowElement.textContent = EtfFlowsUtils.formatFlow(this.totalInflow);
            }

            // Update total outflow
            const totalOutflowElement = document.getElementById('totalOutflow');
            if (totalOutflowElement && this.totalOutflow !== null) {
                totalOutflowElement.textContent = EtfFlowsUtils.formatFlow(this.totalOutflow);
            }

            // Update net flow
            const netFlowElement = document.getElementById('netFlow');
            if (netFlowElement && this.netFlow !== null) {
                const color = EtfFlowsUtils.getFlowColor(this.netFlow);
                netFlowElement.style.color = color;
                netFlowElement.textContent = EtfFlowsUtils.formatFlow(this.netFlow);
            }
        },

        updateLastRefreshTime() {
            const lastRefreshElement = document.getElementById('lastRefresh');
            if (lastRefreshElement) {
                const now = new Date();
                lastRefreshElement.textContent = now.toLocaleTimeString('en-US');
            }
        },

        // Format methods for Alpine.js templates
        formatFlow(value) {
            return EtfFlowsUtils.formatFlow(value);
        },

        formatPercentage(value) {
            return EtfFlowsUtils.formatPercentage(value);
        },

        // Manual refresh method (for Alpine.js compatibility)
        refresh() {
            console.log('🔄 Manual refresh triggered');
            this.apiService.clearCache();
            this.loadData(false); // preferFresh = true
        },

        // =================================================================
        // SECTION 4: CME FUTURES OPEN INTEREST
        // =================================================================

        async loadCmeOpenInterest() {
            if (this.cmeLoading) return;
            
            this.cmeLoading = true;
            console.log('🏛️ Loading CME Open Interest...');

            try {
                const response = await fetch(`/api/coinglass/etf-flows/cme-oi?symbol=BTC&interval=1d&limit=${this.selectedCmeTimeRange}`);
                const result = await response.json();

                if (result.success && result.data) {
                    this.cmeData = result.data;
                    
                    // Update summary metrics
                    if (result.summary) {
                        this.cmeOiLatest = result.summary.latest_oi;
                        this.cmeOiChange = result.summary.change;
                        this.cmeOiChangePercent = result.summary.change_percent;
                    }

                    // Render chart
                    this.renderCmeChart();
                    console.log('✅ CME OI loaded:', this.cmeData.length, 'points');
                } else {
                    console.error('❌ CME OI API error:', result.error);
                }
            } catch (error) {
                console.error('❌ Failed to load CME OI:', error);
            } finally {
                this.cmeLoading = false;
            }
        },

        updateCmeTimeRange(days) {
            if (this.selectedCmeTimeRange === days) return;
            
            console.log('📊 Updating CME time range to:', days, 'days');
            this.selectedCmeTimeRange = days;
            this.loadCmeOpenInterest();
        },

        renderCmeChart() {
            const canvas = document.getElementById('cmeOiChart');
            if (!canvas) {
                console.warn('⚠️ CME chart canvas not found');
                return;
            }

            // Destroy existing chart
            if (this.cmeChartInstance) {
                this.cmeChartInstance.destroy();
            }

            const ctx = canvas.getContext('2d');
            
            // Prepare data
            const labels = this.cmeData.map(d => new Date(d.ts));
            const values = this.cmeData.map(d => d.close);

            // Create chart
            this.cmeChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'CME Open Interest',
                        data: values,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#f59e0b',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 13,
                                weight: '600'
                            },
                            bodyFont: {
                                size: 13
                            },
                            displayColors: false,
                            callbacks: {
                                title: (tooltipItems) => {
                                    const date = new Date(tooltipItems[0].parsed.x);
                                    return date.toLocaleDateString('en-US', {
                                        month: 'short',
                                        day: 'numeric',
                                        year: 'numeric'
                                    });
                                },
                                label: (context) => {
                                    const value = context.parsed.y;
                                    return `OI: ${this.formatCurrency(value)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: this.selectedCmeTimeRange <= 30 ? 'day' : 'week',
                                tooltipFormat: 'MMM d, yyyy',
                                displayFormats: {
                                    day: 'MMM d',
                                    week: 'MMM d'
                                }
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                callback: (value) => {
                                    return this.formatCurrencyShort(value);
                                }
                            }
                        }
                    }
                }
            });

            console.log('📊 CME chart rendered successfully');
        },

        formatCurrency(value) {
            if (value === null || value === undefined) return '$0';
            return '$' + (value / 1_000_000_000).toFixed(2) + 'B';
        },

        formatCurrencyShort(value) {
            if (value === null || value === undefined) return '$0';
            const billions = value / 1_000_000_000;
            return '$' + billions.toFixed(1) + 'B';
        }
    };
}

// Make controller available globally for Alpine.js
window.etfFlowsController = etfFlowsController;

// Ensure controller is registered before Alpine initializes
document.addEventListener('DOMContentLoaded', () => {
    console.log('✅ ETF Flows controller registered');
});