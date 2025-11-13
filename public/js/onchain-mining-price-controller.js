/**
 * Mining & Price Analytics Controller
 * Handles MPI data and comprehensive price analysis
 */

function onchainMiningPriceController() {
    return {
        // Global state
        loading: false,
        selectedAsset: 'BTC', // Fixed to BTC only
        selectedLimit: 200,
        chartType: 'line',
        
        // NEW: Auto-refresh State
        autoRefreshEnabled: true,
        autoRefreshTimer: null,
        autoRefreshInterval: 5000,   // 5 seconds
        lastUpdated: null,
        
        // NEW: Debouncing
        filterDebounceTimer: null,
        filterDebounceDelay: 300,

        // Component-specific state
        mpiData: [],
        mpiSummary: null,
        priceData: [],
        latestPriceData: null,
        currentPrice: 0,
        currentVolume: 0,
        priceCorrelation: 0,

        // Loading states
        loadingStates: {
            mpi: false,
            price: false
        },

        // Chart instances (stored in Alpine data like working controller)
        mpiChart: null,
        priceChart: null,

        // Initialize controller
        init() {
            console.log('🚀 Initializing Mining & Price Analytics Controller');
            
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

        // Render MPI chart
        renderMPIChart() {
            const canvas = this.$refs.mpiChart;
            if (!canvas) return;

            // Destroy existing chart
            if (this.mpiChart) {
                this.mpiChart.destroy();
                this.mpiChart = null;
            }

            if (!this.mpiData.length) return;

            // FIXED: Sort data chronologically (oldest to newest)
            const sortedData = this.sortDataChronologically(this.mpiData);
            console.log('📊 MPI chart data sorted:', sortedData.length, 'records');

            const labels = sortedData.map(item => {
                const date = new Date(item.timestamp);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const mpiValues = sortedData.map(item => item.mpi);

            const ctx = canvas.getContext('2d');
            this.mpiChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'MPI',
                            data: mpiValues,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
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
                                    return `MPI: ${value.toFixed(4)}`;
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
                                text: 'MPI Value'
                            }
                        }
                    }
                }
            });
        },

        // Render price chart
        renderPriceChart() {
            const canvas = this.$refs.priceChart;
            if (!canvas) return;

            // Destroy existing chart
            if (this.priceChart) {
                this.priceChart.destroy();
                this.priceChart = null;
            }

            if (!this.priceData.length) return;

            // FIXED: Sort data chronologically (oldest to newest)
            const sortedData = this.sortDataChronologically(this.priceData);
            console.log('📊 Price chart data sorted:', sortedData.length, 'records');

            const labels = sortedData.map(item => {
                const date = new Date(item.timestamp);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const closePrices = sortedData.map(item => item.close);
            const volumes = sortedData.map(item => item.volume);

            const ctx = canvas.getContext('2d');
            this.priceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Close Price',
                            data: closePrices,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4
                        },
                        {
                            label: 'Volume',
                            data: volumes,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.2)',
                            yAxisID: 'y1',
                            type: 'bar',
                            order: 2
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

                                    if (label.includes('Volume')) {
                                        return `${label}: ${this.formatVolume(value, this.selectedAsset)}`;
                                    } else {
                                        return `${label}: ${this.formatPrice(value, this.selectedAsset)}`;
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
                                text: `Price (${this.selectedAsset})`
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Volume'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        },

        // Load all data
        async loadAllData() {
            this.loading = true;
            try {
                await Promise.all([
                    this.loadMPIData(),
                    this.loadPriceData()
                ]);
                this.calculateCorrelation();
                
                // NEW: Update timestamp on successful load
                this.updateLastUpdated();
            } catch (error) {
                console.error('❌ Error loading mining & price data:', error);
            } finally {
                this.loading = false;
            }
        },

        // Load MPI data
        async loadMPIData() {
            this.loadingStates.mpi = true;
            try {
                const params = new URLSearchParams({
                    asset: this.selectedAsset,
                    window: 'day',  // Hardcoded to day only
                    limit: this.selectedLimit.toString()
                });

                const [mpiResponse, summaryResponse] = await Promise.all([
                    this.fetchAPI(`/api/onchain/miners/mpi?${params}`),
                    this.fetchAPI(`/api/onchain/miners/mpi/summary?${params}`)
                ]);

                this.mpiData = mpiResponse.data || [];
                this.mpiSummary = summaryResponse.data || null;

                this.renderMPIChart();

                console.log('✅ MPI data loaded:', this.mpiData.length, 'records');
            } catch (error) {
                console.error('❌ Error loading MPI data:', error);
                this.mpiData = [];
                this.mpiSummary = null;
            } finally {
                this.loadingStates.mpi = false;
            }
        },

        // Load price data
        async loadPriceData() {
            this.loadingStates.price = true;
            try {
                // Simplified: Only use OHLCV endpoint for BTC/ETH
                const endpoint = '/api/onchain/price/ohlcv';
                const params = new URLSearchParams({
                    window: 'day',  // Hardcoded to day only
                    limit: this.selectedLimit.toString(),
                    asset: this.selectedAsset
                });

                const response = await this.fetchAPI(`${endpoint}?${params}`);

                this.priceData = response.data || [];

                if (this.priceData.length > 0) {
                    this.latestPriceData = this.priceData[0];
                    this.currentPrice = this.latestPriceData.close || 0;
                    this.currentVolume = this.latestPriceData.volume || 0;
                }

                this.renderPriceChart();

                console.log('✅ Price data loaded:', this.priceData.length, 'records');
            } catch (error) {
                console.error('❌ Error loading price data:', error);
                this.priceData = [];
                this.latestPriceData = null;
            } finally {
                this.loadingStates.price = false;
            }
        },



        // Calculate MPI-Price correlation
        calculateCorrelation() {
            if (!this.mpiData.length || !this.priceData.length) {
                this.priceCorrelation = 0;
                return;
            }

            // Simple correlation calculation (placeholder)
            // In a real implementation, you'd align timestamps and calculate Pearson correlation
            this.priceCorrelation = Math.random() * 2 - 1; // Random between -1 and 1 for demo
        },

        // Refresh all data
        async refreshAll() {
            this.updateSharedState();
            await this.loadAllData();
        },

        // Refresh MPI data only
        async refreshMPIData() {
            await this.loadMPIData();
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
        formatMPI(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            return parseFloat(value).toFixed(4);
        },

        formatZScore(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            return parseFloat(value).toFixed(2);
        },

        formatPrice(value, asset) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            if (asset === 'BTC' && num >= 1000) return `$${(num / 1000).toFixed(1)}K`;
            return `$${num.toLocaleString('en-US', { maximumFractionDigits: 2 })}`;
        },

        formatVolume(value, asset) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            if (num >= 1e9) return `${(num / 1e9).toFixed(2)}B ${asset}`;
            if (num >= 1e6) return `${(num / 1e6).toFixed(2)}M ${asset}`;
            if (num >= 1e3) return `${(num / 1e3).toFixed(2)}K ${asset}`;
            return `${num.toFixed(2)} ${asset}`;
        },

        formatPercentage(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            return `${num >= 0 ? '+' : ''}${num.toFixed(2)}%`;
        },

        formatCorrelation(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            return parseFloat(value).toFixed(3);
        },

        // Style helpers
        getMPIClass() {
            if (!this.mpiSummary?.latest?.mpi) return 'text-secondary';
            const mpi = parseFloat(this.mpiSummary.latest.mpi);
            if (mpi > 2) return 'text-danger';
            if (mpi > 0) return 'text-warning';
            return 'text-success';
        },

        getMPIChangeClass() {
            if (!this.mpiSummary?.latest?.change_pct) return 'text-secondary';
            const change = parseFloat(this.mpiSummary.latest.change_pct);
            return change >= 0 ? 'text-danger' : 'text-success';
        },

        getZScoreClass() {
            if (!this.mpiSummary?.stats?.z_score) return 'text-secondary';
            const zscore = parseFloat(this.mpiSummary.stats.z_score);
            if (Math.abs(zscore) > 2) return 'text-danger';
            if (Math.abs(zscore) > 1) return 'text-warning';
            return 'text-success';
        },

        getZScoreInterpretation() {
            if (!this.mpiSummary?.stats?.z_score) return 'No data';
            const zscore = parseFloat(this.mpiSummary.stats.z_score);
            if (zscore > 2) return 'Extreme high';
            if (zscore > 1) return 'Above average';
            if (zscore > -1) return 'Normal range';
            if (zscore > -2) return 'Below average';
            return 'Extreme low';
        },

        getMinerSentimentClass() {
            if (!this.mpiSummary?.latest?.mpi) return 'text-secondary';
            const mpi = parseFloat(this.mpiSummary.latest.mpi);
            if (mpi > 2) return 'text-danger';
            if (mpi > 0) return 'text-warning';
            return 'text-success';
        },

        getMinerSentiment() {
            if (!this.mpiSummary?.latest?.mpi) return 'Unknown';
            const mpi = parseFloat(this.mpiSummary.latest.mpi);
            if (mpi > 2) return 'Distributing';
            if (mpi > 0) return 'Neutral';
            return 'Accumulating';
        },

        getPriceChangeClass() {
            if (!this.latestPriceData?.open || !this.latestPriceData?.close) return 'text-secondary';
            const change = this.latestPriceData.close - this.latestPriceData.open;
            return change >= 0 ? 'text-success' : 'text-danger';
        },

        formatPriceChange() {
            if (!this.latestPriceData?.open || !this.latestPriceData?.close) return 'N/A';
            const change = this.latestPriceData.close - this.latestPriceData.open;
            const changePercent = (change / this.latestPriceData.open) * 100;
            return `${change >= 0 ? '+' : ''}${changePercent.toFixed(2)}%`;
        },

        getCorrelationClass() {
            const corr = Math.abs(this.priceCorrelation);
            if (corr > 0.7) return 'text-danger';
            if (corr > 0.3) return 'text-warning';
            return 'text-success';
        },

        getCorrelationInterpretation() {
            const corr = Math.abs(this.priceCorrelation);
            if (corr > 0.7) return 'Strong correlation';
            if (corr > 0.3) return 'Moderate correlation';
            return 'Weak correlation';
        },

        getSignalStrengthClass() {
            const corr = Math.abs(this.priceCorrelation);
            if (corr > 0.7) return 'text-success';
            if (corr > 0.3) return 'text-warning';
            return 'text-danger';
        },

        getSignalStrength() {
            const corr = Math.abs(this.priceCorrelation);
            if (corr > 0.7) return 'Strong';
            if (corr > 0.3) return 'Moderate';
            return 'Weak';
        }
    };
}