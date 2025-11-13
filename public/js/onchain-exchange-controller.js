/**
 * Exchange Reserves & Market Indicators Controller
 * Handles exchange reserves and market leverage data
 */

function onchainExchangeController() {
    return {
        // Global state
        loading: false,
        selectedAsset: 'BTC', // Fixed to BTC only
        selectedLimit: 200,
        
        // NEW: Auto-refresh State
        autoRefreshEnabled: true,
        autoRefreshTimer: null,
        autoRefreshInterval: 5000,   // 5 seconds
        lastUpdated: null,
        
        // NEW: Debouncing
        filterDebounceTimer: null,
        filterDebounceDelay: 300,

        // Component-specific state
        reservesData: [],
        reserveSummary: null,
        indicatorsData: [],
        exchangeList: [],
        currentLeverageRatio: 0,

        // Loading states
        loadingStates: {
            reserves: false,
            indicators: false
        },

        // Chart instances (stored in Alpine data like working controller)
        reservesChart: null,
        indicatorsChart: null,

        // Initialize controller
        init() {
            console.log('🚀 Initializing Exchange Metrics Controller');
            
            // Load shared state
            this.loadSharedState();
            
            // Subscribe to shared state changes
            this.subscribeToSharedState();
            
            this.loadAllData();

            // NEW: Start auto-refresh system
            this.startAutoRefresh();
            
            // NEW: Add visibility API integration
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    console.log('👁️ Tab hidden, pausing auto-refresh');
                    this.pauseAutoRefresh();
                } else {
                    console.log('👁️ Tab visible, resuming auto-refresh');
                    this.resumeAutoRefresh();
                }
            });
        },
        
        // Load shared state
        loadSharedState() {
            if (window.OnChainSharedState) {
                const sharedFilters = window.OnChainSharedState.getAllFilters();
                this.selectedAsset = 'BTC'; // Force BTC only
                this.selectedLimit = sharedFilters.selectedLimit || 200;
            }
        },
        
        // Subscribe to shared state changes
        subscribeToSharedState() {
            if (window.OnChainSharedState) {
                // Asset is fixed to BTC only, no subscription needed
                
                // Subscribe to limit changes
                window.OnChainSharedState.subscribe('selectedLimit', (value) => {
                    if (this.selectedLimit !== value) {
                        this.selectedLimit = value;
                        this.refreshAll();
                    }
                });
            }
        },
        
        // Update shared state when local state changes
        updateSharedState() {
            if (window.OnChainSharedState) {
                // Asset is fixed to BTC, only update limit
                window.OnChainSharedState.setFilter('selectedLimit', this.selectedLimit);
            }
        },

        // Render reserves chart
        renderReservesChart() {
            console.log('🎨 Rendering reserves chart...');
            const canvas = this.$refs.reservesChart;
            if (!canvas) {
                console.warn('❌ Reserves chart canvas not found');
                return;
            }
            
            // Destroy existing chart
            if (this.reservesChart) {
                this.reservesChart.destroy();
                this.reservesChart = null;
            }
            
            if (!this.reservesData.length) {
                console.warn('❌ No reserves data to render');
                return;
            }
            
            // FIXED: Sort data chronologically (oldest to newest)
            const sortedData = this.sortDataChronologically(this.reservesData);
            console.log('📊 Reserves chart data sorted:', sortedData.length, 'records');
            
            const labels = sortedData.map(item => {
                const date = new Date(item.timestamp);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const reserves = sortedData.map(item => item.reserve);
            const usdValues = sortedData.map(item => item.reserve_usd);
            
            const ctx = canvas.getContext('2d');
            this.reservesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Reserve Amount',
                                data: reserves,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                yAxisID: 'y',
                                tension: 0.4
                            },
                            {
                                label: 'USD Value',
                                data: usdValues,
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                yAxisID: 'y1',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 0  // CRITICAL: Prevents race conditions
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const label = context.dataset.label;
                                        const value = context.parsed.y;

                                        if (label.includes('USD')) {
                                            return `${label}: ${this.formatUSD(value)}`;
                                        } else {
                                            return `${label}: ${this.formatReserve(value, this.selectedAsset)}`;
                                        }
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                type: 'category',
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: `Reserve (${this.selectedAsset})`
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'USD Value'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
            });
            
            console.log('✅ Reserves chart created successfully');
        },

        // Render indicators chart
        renderIndicatorsChart() {
            console.log('🎨 Rendering indicators chart...');
            const canvas = this.$refs.indicatorsChart;
            if (!canvas) {
                console.warn('❌ Indicators chart canvas not found');
                return;
            }
            
            // Destroy existing chart
            if (this.indicatorsChart) {
                this.indicatorsChart.destroy();
                this.indicatorsChart = null;
            }
            
            if (!this.indicatorsData.length) {
                console.warn('❌ No indicators data to render');
                return;
            }
            
            // FIXED: Sort data chronologically (oldest to newest)
            const sortedData = this.sortDataChronologically(this.indicatorsData);
            console.log('📊 Indicators chart data sorted:', sortedData.length, 'records');
            
            const labels = sortedData.map(item => {
                const date = new Date(item.timestamp);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const leverageRatios = sortedData.map(item => item.estimated_leverage_ratio);
            
            const ctx = canvas.getContext('2d');
            this.indicatorsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Leverage Ratio',
                                data: leverageRatios,
                                borderColor: '#8b5cf6',
                                backgroundColor: 'rgba(139, 92, 246, 0.2)',
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 0  // CRITICAL: Prevents race conditions
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const value = context.parsed.y;
                                        return `Leverage Ratio: ${value.toFixed(4)}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                type: 'category',
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Leverage Ratio'
                                },
                                min: 0,
                                max: 1
                            }
                        }
                    }
            });
            
            console.log('✅ Indicators chart created successfully');
        },

        // Load all data
        async loadAllData() {
            this.loading = true;
            try {
                await Promise.all([
                    this.loadReservesData(),
                    this.loadIndicatorsData()
                ]);
                
                // NEW: Update timestamp on successful load
                this.updateLastUpdated();
            } catch (error) {
                console.error('❌ Error loading exchange data:', error);
            } finally {
                this.loading = false;
            }
        },

        // Load reserves data
        async loadReservesData() {
            this.loadingStates.reserves = true;
            try {
                const params = new URLSearchParams({
                    asset: this.selectedAsset,
                    window: 'day',  // Hardcoded to day only
                    limit: this.selectedLimit.toString()
                });

                console.log('🔍 Loading reserves with params:', {
                    asset: this.selectedAsset,
                    window: 'day',
                    limit: this.selectedLimit
                });

                const [reservesResponse, summaryResponse] = await Promise.all([
                    this.fetchAPI(`/api/onchain/exchange/reserves?${params}`),
                    this.fetchAPI(`/api/onchain/exchange/reserves/summary?${params}`)
                ]);

                this.reservesData = reservesResponse.data || [];
                this.reserveSummary = summaryResponse.data || null;
                this.exchangeList = this.reserveSummary?.exchanges || [];

                console.log('📊 Reserves data structure:', {
                    dataLength: this.reservesData.length,
                    sampleData: this.reservesData.slice(0, 2),
                    summary: this.reserveSummary
                });

                this.renderReservesChart();

                console.log('✅ Reserves data loaded:', this.reservesData.length, 'records');
            } catch (error) {
                console.error('❌ Error loading reserves data:', error);
                this.reservesData = [];
                this.reserveSummary = null;
            } finally {
                this.loadingStates.reserves = false;
            }
        },

        // Load market indicators data
        async loadIndicatorsData() {
            this.loadingStates.indicators = true;
            try {
                // Use XRP as default for market indicators as per API documentation
                const params = new URLSearchParams({
                    asset: 'XRP', // API default for market indicators
                    window: 'day',  // Hardcoded to day only
                    limit: this.selectedLimit.toString()
                });

                console.log('🔍 Loading indicators with params:', {
                    asset: 'XRP',
                    window: 'day',
                    limit: this.selectedLimit
                });

                const response = await this.fetchAPI(`/api/onchain/market/indicators?${params}`);

                this.indicatorsData = response.data || [];

                if (this.indicatorsData.length > 0) {
                    this.currentLeverageRatio = this.indicatorsData[0].estimated_leverage_ratio || 0;
                }

                console.log('📊 Indicators data structure:', {
                    dataLength: this.indicatorsData.length,
                    sampleData: this.indicatorsData.slice(0, 2),
                    currentLeverageRatio: this.currentLeverageRatio
                });

                this.renderIndicatorsChart();

                console.log('✅ Indicators data loaded:', this.indicatorsData.length, 'records');
            } catch (error) {
                console.error('❌ Error loading indicators data:', error);
                this.indicatorsData = [];
                this.currentLeverageRatio = 0;
            } finally {
                this.loadingStates.indicators = false;
            }
        },



        // Refresh all data
        async refreshAll() {
            this.updateSharedState();
            await this.loadAllData();
        },

        // Refresh reserves data only
        async refreshReservesData() {
            await this.loadReservesData();
        },

        // Refresh indicators data only
        async refreshIndicatorsData() {
            await this.loadIndicatorsData();
        },
        
        // ENHANCED: Debounced refresh for better performance
        handleLimitChange() {
            console.log('🔄 Filter changed');
            
            // Clear existing timer
            if (this.filterDebounceTimer) {
                clearTimeout(this.filterDebounceTimer);
            }
            
            // Set new timer with debouncing
            this.filterDebounceTimer = setTimeout(() => {
                console.log('⏰ Debounced filter change executing...');
                this.loadAllData();
            }, this.filterDebounceDelay);
        },
        
        // NEW: Auto-refresh system methods
        startAutoRefresh() {
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
            }
            
            console.log('🔄 Starting auto-refresh with', this.autoRefreshInterval, 'ms interval');
            this.autoRefreshTimer = setInterval(() => {
                if (this.autoRefreshEnabled && !document.hidden) {
                    console.log('⏰ Auto-refresh triggered');
                    this.loadAllData();
                }
            }, this.autoRefreshInterval);
        },
        
        pauseAutoRefresh() {
            console.log('⏸️ Pausing auto-refresh');
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
                this.autoRefreshTimer = null;
            }
        },
        
        resumeAutoRefresh() {
            if (this.autoRefreshEnabled) {
                console.log('▶️ Resuming auto-refresh');
                this.startAutoRefresh();
            }
        },
        
        toggleAutoRefresh() {
            this.autoRefreshEnabled = !this.autoRefreshEnabled;
            console.log('🔄 Auto-refresh toggled:', this.autoRefreshEnabled ? 'ON' : 'OFF');
            
            if (this.autoRefreshEnabled) {
                this.startAutoRefresh();
            } else {
                this.pauseAutoRefresh();
            }
        },
        
        // NEW: Update timestamp on successful data load
        updateLastUpdated() {
            this.lastUpdated = new Date().toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            console.log('🕒 Last updated:', this.lastUpdated);
        },
        
        // NEW: Sort data chronologically (oldest to newest) to fix chart ordering
        sortDataChronologically(data) {
            if (!Array.isArray(data)) return data;
            
            return data.sort((a, b) => {
                const dateA = new Date(a.timestamp);
                const dateB = new Date(b.timestamp);
                return dateA - dateB; // Ascending order (oldest first)
            });
        },

        // Fetch API helper
        async fetchAPI(endpoint) {
            try {
                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();

                let url;
                if (configuredBase) {
                    const base = configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase;
                    url = `${base}${endpoint}`;
                } else {
                    // Use relative URL as fallback
                    url = endpoint;
                }

                console.log(`🔗 Fetching: ${url}`);

                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log(`✅ Data received:`, data);
                return data;
            } catch (error) {
                console.error(`❌ API Error for ${endpoint}:`, error);
                throw error;
            }
        },

        // Formatting helpers
        formatReserve(value, asset) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            if (num >= 1000000) return `${(num / 1000000).toFixed(2)}M ${asset}`;
            if (num >= 1000) return `${(num / 1000).toFixed(2)}K ${asset}`;
            return `${num.toFixed(2)} ${asset}`;
        },

        formatReserveChange(value, asset) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            const sign = num >= 0 ? '+' : '';
            if (Math.abs(num) >= 1000000) return `${sign}${(num / 1000000).toFixed(2)}M ${asset}`;
            if (Math.abs(num) >= 1000) return `${sign}${(num / 1000).toFixed(2)}K ${asset}`;
            return `${sign}${num.toFixed(2)} ${asset}`;
        },

        formatUSD(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            if (Math.abs(num) >= 1e12) return `$${(num / 1e12).toFixed(2)}T`;
            if (Math.abs(num) >= 1e9) return `$${(num / 1e9).toFixed(2)}B`;
            if (Math.abs(num) >= 1e6) return `$${(num / 1e6).toFixed(2)}M`;
            if (Math.abs(num) >= 1e3) return `$${(num / 1e3).toFixed(2)}K`;
            return `$${num.toFixed(2)}`;
        },

        formatLeverage(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            return parseFloat(value).toFixed(4);
        },

        formatPercentage(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            return `${num >= 0 ? '+' : ''}${num.toFixed(2)}%`;
        },

        // Style helpers
        getReserveChangeClass() {
            if (!this.reserveSummary?.totals?.change) return 'text-secondary';
            const change = parseFloat(this.reserveSummary.totals.change);
            return change >= 0 ? 'text-success' : 'text-danger';
        },

        getFlowDirectionClass() {
            if (!this.reserveSummary?.totals?.change) return 'text-secondary';
            const change = parseFloat(this.reserveSummary.totals.change);
            if (change > 0) return 'text-danger'; // Inflow = bearish
            if (change < 0) return 'text-success'; // Outflow = bullish
            return 'text-secondary';
        },

        getFlowDirection() {
            if (!this.reserveSummary?.totals?.change) return 'Neutral';
            const change = parseFloat(this.reserveSummary.totals.change);
            if (change > 0) return 'Inflow';
            if (change < 0) return 'Outflow';
            return 'Neutral';
        },

        getLeverageRiskClass() {
            if (!this.currentLeverageRatio) return 'text-secondary';
            const ratio = parseFloat(this.currentLeverageRatio);
            if (ratio > 0.5) return 'text-danger';
            if (ratio > 0.3) return 'text-warning';
            return 'text-success';
        },

        getLeverageRiskLabel() {
            if (!this.currentLeverageRatio) return 'No data';
            const ratio = parseFloat(this.currentLeverageRatio);
            if (ratio > 0.5) return 'High Risk';
            if (ratio > 0.3) return 'Medium Risk';
            return 'Low Risk';
        },

        getRiskLevelClass() {
            return this.getLeverageRiskClass();
        },

        getRiskLevel() {
            return this.getLeverageRiskLabel();
        },

        getMarketHealthClass() {
            if (!this.currentLeverageRatio) return 'text-secondary';
            const ratio = parseFloat(this.currentLeverageRatio);
            if (ratio > 0.5) return 'text-danger';
            if (ratio > 0.3) return 'text-warning';
            return 'text-success';
        },

        getMarketHealth() {
            if (!this.currentLeverageRatio) return 'Unknown';
            const ratio = parseFloat(this.currentLeverageRatio);
            if (ratio > 0.5) return 'Unhealthy';
            if (ratio > 0.3) return 'Moderate';
            return 'Healthy';
        }
    };
}