{{--
    Komponen: VWAP Bands Chart
    Menampilkan chart VWAP dengan upper & lower bands

    Props:
    - $symbol: string (default: 'BTCUSDT')
    - $timeframe: string (default: '5min')
    - $exchange: string (default: 'binance')
    - $limit: int (default: 100)

    Visual:
    - Line chart dengan multiple datasets (VWAP, Upper Band, Lower Band)
    - Area fill untuk bands
    - Time-series X-axis
--}}

<div class="df-panel p-4" x-data="vwapBandsChart('{{ $symbol ?? 'BTCUSDT' }}', '{{ $timeframe ?? '5min' }}', '{{ $exchange ?? 'binance' }}', {{ $limit ?? 100 }})">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">📈 VWAP & Bands</h5>
            <p class="small text-secondary mb-0">Volume-Weighted Average Price with volatility bands</p>
        </div>
    </div>

    <!-- Loading State -->
    <template x-if="loading && !chartInstance">
        <div class="text-center py-5" style="height: 400px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-secondary mt-2 mb-0">Loading chart data...</p>
        </div>
    </template>

    <!-- Error State -->
    <template x-if="!loading && error">
        <div class="alert alert-warning text-center" style="height: 400px; display: flex; align-items: center; justify-content: center;">
            <div>
                <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                <p class="mb-0" x-text="error">Unable to fetch data</p>
            </div>
        </div>
    </template>

    <!-- Chart Canvas -->
    <div class="position-relative" style="height: 400px;">
        <canvas x-ref="chartCanvas"></canvas>
    </div>

    <!-- Legend Info -->
    <div class="row g-2 mt-3">
        <div class="col-auto">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 16px; height: 3px; background: #10b981;"></div>
                <span class="small">VWAP</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 16px; height: 3px; background: #ef4444; border-style: dashed;"></div>
                <span class="small">Upper Band</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 16px; height: 3px; background: #ef4444; border-style: dashed;"></div>
                <span class="small">Lower Band</span>
            </div>
        </div>
    </div>
</div>

<script>
function vwapBandsChart(initialSymbol = 'BTCUSDT', initialTimeframe = '5min', initialExchange = 'binance', initialLimit = 100) {
    return {
        symbol: initialSymbol,
        timeframe: initialTimeframe,
        exchange: initialExchange,
        limit: initialLimit,
        loading: false,
        error: null,
        chartInstance: null,
        data: [],

        init() {
            // Wait for Chart.js to be ready
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded yet, waiting...');
                setTimeout(() => this.init(), 200);
                return;
            }

            // Use centralized data approach only
            this.listenForData();
        },

        // Centralized chart destruction
        destroyChart() {
            if (this.chartInstance) {
                try {
                    if (typeof this.chartInstance.stop === 'function') {
                        this.chartInstance.stop();
                    }
                    this.chartInstance.destroy();
                } catch (e) {
                    console.warn('📈 Error destroying chart:', e);
                }
                this.chartInstance = null;
            }
        },

        listenForData() {
            console.log('📈 VWAP Chart listening for centralized data...');
            
            // Set initial loading state
            this.loading = true;
            
            // Listen for centralized data (primary source)
            const handleData = (e) => {
                if (e.detail?.historical && Array.isArray(e.detail.historical)) {
                    console.log('📈 Chart received data:', e.detail.historical.length, 'points');
                    this.data = e.detail.historical;
                    this.error = null;
                    this.loading = false;
                    
                    // Use requestAnimationFrame for smooth rendering
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            this.renderChart();
                        });
                    });
                }
            };

            window.addEventListener('vwap-data-ready', handleData);

            // Listen for error events
            window.addEventListener('vwap-data-error', (e) => {
                this.error = e.detail?.error || 'Failed to load chart data';
                this.loading = false;
                console.error('❌ Chart received error:', this.error);
            });

            // Fallback: Load data directly if centralized data doesn't arrive within 4 seconds
            setTimeout(() => {
                if (this.loading && (!this.data || this.data.length === 0)) {
                    console.log('⚠️ Centralized chart data not received, loading directly...');
                    this.loadDataDirectly();
                }
            }, 4000);

            // Listen to filter changes (will trigger controller to reload data)
            window.addEventListener('symbol-changed', () => {
                this.loading = true;
            });
            window.addEventListener('timeframe-changed', () => {
                this.loading = true;
            });
            window.addEventListener('exchange-changed', () => {
                this.loading = true;
            });
        },

        // Fallback method to load chart data directly
        async loadDataDirectly() {
            try {
                this.loading = true;
                this.error = null;
                
                const response = await fetch(`/api/spot-microstructure/vwap?symbol=${this.symbol}&interval=${this.timeframe}&exchange=${this.exchange}&limit=${this.limit}`);
                const result = await response.json();
                
                if (result.success && result.data && Array.isArray(result.data)) {
                    this.data = result.data;
                    console.log('✅ Chart loaded data directly:', this.data.length, 'points');
                    
                    // Render chart
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            this.renderChart();
                        });
                    });
                } else {
                    this.error = result.error || 'Failed to load chart data';
                    console.error('❌ Direct chart load failed:', this.error);
                }
            } catch (error) {
                this.error = 'Network error: ' + error.message;
                console.error('❌ Direct chart load error:', error);
            } finally {
                this.loading = false;
            }
        },

        renderChart() {
            if (!this.data || this.data.length === 0) {
                console.warn('📈 No data to render chart');
                return;
            }
            if (typeof Chart === 'undefined') {
                console.warn('📈 Chart.js not loaded');
                return;
            }

            const canvas = this.$refs.chartCanvas;
            if (!canvas) {
                console.warn('📈 Canvas not found');
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('📈 Cannot get canvas context');
                return;
            }

            // Prepare data - take only recent data points to avoid overload
            const sortedData = [...this.data]
                .filter(d => d && (d.timestamp || d.ts)) // Filter out items without timestamps
                .sort((a, b) => {
                    const timestampA = a.timestamp || a.ts;
                    const timestampB = b.timestamp || b.ts;
                    return new Date(timestampA) - new Date(timestampB);
                })
                .slice(-100); // Take last 100 points

            if (sortedData.length === 0) {
                console.warn('📈 No valid data points with timestamps');
                this.error = 'No valid data points found';
                return;
            }

            // Enhanced timestamp handling with validation
            const labels = sortedData.map(d => {
                const timestamp = d.timestamp || d.ts;
                if (!timestamp) {
                    console.warn('📈 Missing timestamp in data:', d);
                    return 'N/A';
                }
                
                const date = new Date(timestamp);
                if (isNaN(date.getTime())) {
                    console.warn('📈 Invalid timestamp:', timestamp);
                    return 'Invalid';
                }
                
                return date.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
            });

            const vwapData = sortedData.map(d => parseFloat(d.vwap) || 0);
            const upperBandData = sortedData.map(d => parseFloat(d.upper_band) || 0);
            const lowerBandData = sortedData.map(d => parseFloat(d.lower_band) || 0);

            // Check if we should update existing chart or create new one
            if (this.chartInstance && this.chartInstance.data) {
                try {
                    // Update existing chart data (prevents flickering)
                    this.chartInstance.data.labels = labels;
                    this.chartInstance.data.datasets[0].data = vwapData;
                    this.chartInstance.data.datasets[1].data = upperBandData;
                    this.chartInstance.data.datasets[2].data = lowerBandData;
                    this.chartInstance.update('none'); // No animation for smoother updates
                    
                    console.log('✅ Chart updated with', sortedData.length, 'data points');
                    return;
                } catch (updateError) {
                    console.warn('📈 Chart update failed, recreating:', updateError);
                    // If update fails, destroy and recreate
                    this.destroyChart();
                }
            }

            // Destroy existing chart instance if it exists but is corrupted
            this.destroyChart();

            try {
                // Create new chart with DISABLED ANIMATIONS (prevents stack overflow)
                this.chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'VWAP',
                                data: vwapData,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 4,
                                fill: false,
                                tension: 0.4,
                            },
                            {
                                label: 'Upper Band',
                                data: upperBandData,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.05)',
                                borderWidth: 1.5,
                                borderDash: [5, 5],
                                pointRadius: 0,
                                pointHoverRadius: 3,
                                fill: false,
                                tension: 0.4,
                            },
                            {
                                label: 'Lower Band',
                                data: lowerBandData,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.05)',
                                borderWidth: 1.5,
                                borderDash: [5, 5],
                                pointRadius: 0,
                                pointHoverRadius: 3,
                                fill: '-1',
                                tension: 0.4,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 0  // ← CRITICAL: Disable animations to prevent stack overflow
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('en-US', {
                                                style: 'currency',
                                                currency: 'USD',
                                            }).format(context.parsed.y);
                                        }
                                        return label;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#94a3b8',
                                    font: { size: 10 },
                                    maxRotation: 0,
                                    autoSkipPadding: 30,
                                    maxTicksLimit: 10,
                                },
                                grid: {
                                    display: false,
                                },
                            },
                            y: {
                                ticks: {
                                    color: '#94a3b8',
                                    font: { size: 10 },
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    },
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.1)',
                                },
                            },
                        },
                    },
                });

                console.log('✅ VWAP chart rendered with', sortedData.length, 'data points');
            } catch (error) {
                console.error('❌ Error creating chart:', error);
                this.error = 'Failed to render chart. Please refresh the page.';
                this.handleChartError(error);
            }
        },

        // Chart error recovery mechanism
        handleChartError(error) {
            console.error('📈 Chart rendering error:', error);
            
            // Destroy problematic chart instance
            this.destroyChart();
            
            // Set error state
            this.error = 'Chart rendering failed. Data will retry on next update.';
            
            // Retry after delay
            setTimeout(() => {
                if (this.data && this.data.length > 0) {
                    console.log('📈 Retrying chart render...');
                    this.renderChart();
                }
            }, 2000);
        },

        // Cleanup on component destroy
        beforeDestroy() {
            this.destroyChart();
        }
    };
}
</script>