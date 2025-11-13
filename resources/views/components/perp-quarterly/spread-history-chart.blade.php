{{--
    Komponen: Perp-Quarterly Spread History Chart
    Menampilkan historical spread movement dengan Chart.js

    Props:
    - $symbol: string (default: 'BTC')
    - $exchange: string (default: 'Binance')
    - $height: string (default: '400px')
--}}

<div class="df-panel p-3 h-100 d-flex flex-column"
     x-data="spreadHistoryChart('{{ $symbol ?? 'BTC' }}', '{{ $exchange ?? 'Binance' }}')">
    <!-- Header -->
    <div class="mb-3 flex-shrink-0">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">📈 Spread History</h5>
                <small class="text-secondary">Perp-Quarterly spread movement over time</small>
            </div>
            <!-- Individual refresh button removed - using unified auto-refresh -->
        </div>
    </div>

    <!-- Data Display -->
    <div class="flex-grow-1" style="min-height: {{ $height ?? '400px' }};">
        <!-- Loading Display -->
        <div x-show="loading" class="text-center py-5">
            <div class="spinner-border text-primary mb-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mb-0 text-muted small">Loading spread history...</p>
        </div>
        
        <!-- Data Table -->
        <div x-show="!loading && chartData.length > 0" class="table-responsive" style="max-height: 800px; overflow-y: auto;">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="sticky-top bg-white">
                    <tr>
                        <th class="text-secondary small">Time</th>
                        <th class="text-secondary small">Exchange</th>
                        <th class="text-secondary small">Perp Symbol</th>
                        <th class="text-secondary small">Quarterly Symbol</th>
                        <th class="text-secondary small text-end">Spread (BPS)</th>
                        <th class="text-secondary small text-end">Spread (Abs)</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in getDisplayData()" :key="idx">
                        <tr>
                            <td class="small" x-text="formatTime(row.ts)">--</td>
                            <td class="small" x-text="row.exchange">--</td>
                            <td class="small font-monospace" x-text="row.perp_symbol">--</td>
                            <td class="small font-monospace" x-text="row.quarterly_symbol">--</td>
                            <td class="small text-end" :class="getSpreadColor(row.spread_bps)" x-text="formatBPS(row.spread_bps)">--</td>
                            <td class="small text-end" :class="getSpreadColor(row.spread_abs)" x-text="formatSpread(row.spread_abs)">--</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- No Data Display -->
        <div x-show="!loading && chartData.length === 0" class="text-center py-5">
            <div class="text-warning mb-2">⚠️</div>
            <p class="mb-0 text-muted small">No data available</p>
        </div>
    </div>

    <!-- Chart Legend Info -->
    <div class="mt-2 d-flex justify-content-between align-items-center text-secondary small">
        <div>
            <span class="badge bg-success bg-opacity-10 text-success">Contango (Perp > Quarterly)</span>
            <span class="badge bg-danger bg-opacity-10 text-danger ms-1">Backwardation (Quarterly > Perp)</span>
        </div>
        <div x-show="chartData.length > 0">
            Displaying <span class="fw-semibold" x-text="getDisplayData().length">0</span> of <span x-text="chartData.length">0</span> records
            <span x-show="parseInt(limit) > 1000" class="text-warning ms-2">
                (⚡ Limited to 100 rows for performance)
            </span>
        </div>
    </div>
</div>

<script>
function spreadHistoryChart(initialSymbol = 'BTC', initialExchange = 'Binance') {
    return {
        symbol: initialSymbol,
        quote: 'USDT',
        exchange: initialExchange,
        interval: '5m',
        perpSymbol: '', // Auto-generated if empty
        limit: '2000', // Data limit for API
        displayLimit: 50, // Display limit for table (will be optimized based on API limit)
        loading: false,
        chart: null,
        chartId: 'spreadHistoryChart_' + Math.random().toString(36).substr(2, 9),
        dataPoints: 0,
        chartInitializing: false,
        chartData: [],

        init() {
            console.log('📊 Chart component initialized');
            console.log('📊 Initial values:', {
                symbol: this.symbol,
                exchange: this.exchange,
                chartData: this.chartData.length,
                dataPoints: this.dataPoints
            });
            
            // Simple approach - just show data without complex chart
            this.loading = false;
            
            // Load data immediately
            setTimeout(() => {
                console.log('📊 Chart calling loadData');
                this.loadData();
            }, 100);

            // Listen to global filter changes
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.quote = e.detail?.quote || this.quote;
                this.exchange = e.detail?.exchange || this.exchange;
                this.interval = e.detail?.interval || this.interval;
                this.perpSymbol = e.detail?.perpSymbol || this.perpSymbol;
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('quote-changed', (e) => {
                this.quote = e.detail?.quote || this.quote;
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('exchange-changed', (e) => {
                this.exchange = e.detail?.exchange || this.exchange;
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('interval-changed', (e) => {
                this.interval = e.detail?.interval || this.interval;
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('perp-symbol-changed', (e) => {
                this.perpSymbol = e.detail?.perpSymbol || this.perpSymbol;
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('limit-changed', (e) => {
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('refresh-all', (e) => {
                // Update parameters from global filter
                this.symbol = e.detail?.symbol || this.symbol;
                this.quote = e.detail?.quote || this.quote;
                this.exchange = e.detail?.exchange || this.exchange;
                this.interval = e.detail?.interval || this.interval;
                this.perpSymbol = e.detail?.perpSymbol || this.perpSymbol;
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });

            // Listen to overview composite
            window.addEventListener('perp-quarterly-overview-ready', (e) => {
                if (e.detail?.timeseries) {
                    this.updateChartFromOverview(e.detail.timeseries);
                }
            });
        },

        initChart() {
            console.log('📊 initChart called');
            
            // Prevent multiple initialization
            if (this.chartInitializing) {
                console.log('📊 Chart already initializing, skipping');
                return;
            }
            
            this.chartInitializing = true;
            
            // Destroy existing chart if it exists
            if (this.chart) {
                console.log('📊 Destroying existing chart');
                this.chart.destroy();
                this.chart = null;
            }

            // Wait for Chart.js to be fully loaded with timeout
            let attempts = 0;
            const maxAttempts = 50; // 5 seconds max
            
            const waitForChartJS = () => {
                attempts++;
                
                if (typeof Chart === 'undefined') {
                    if (attempts < maxAttempts) {
                        console.log(`⏳ Waiting for Chart.js to load... (${attempts}/${maxAttempts})`);
                        setTimeout(waitForChartJS, 100);
                        return;
                    } else {
                        console.error('❌ Chart.js failed to load after maximum attempts');
                        this.loading = false;
                        return;
                    }
                }

                console.log('📊 Chart.js loaded, creating chart');
                
                // Wait for DOM to be ready
                setTimeout(() => {
                    const canvas = document.getElementById(this.chartId);
                    if (!canvas) {
                        console.warn('📊 Canvas not found:', this.chartId);
                        // Retry after a longer delay
                        setTimeout(() => {
                            const retryCanvas = document.getElementById(this.chartId);
                            if (retryCanvas) {
                                this.createChart(retryCanvas);
                            } else {
                                console.error('📊 Canvas still not found after retry');
                                this.loading = false;
                            }
                        }, 500);
                        return;
                    }
                    
                    this.createChart(canvas);
                }, 100);
            };

            waitForChartJS();
        },

        createChart(canvas) {
            console.log('📊 createChart called');
            
            if (!canvas) {
                console.error('❌ Canvas element not found');
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('❌ Cannot get 2D context from canvas');
                return;
            }

            // Check if Chart.js is available
            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js not loaded');
                return;
            }

            // Check if Chart.js is ready
            if (typeof Chart !== 'function') {
                console.error('❌ Chart.js not properly initialized');
                return;
            }

            try {
                console.log('📊 Creating Chart.js instance');
                this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [
                        {
                            label: 'Spread (BPS)',
                            data: [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: this.createGradient(ctx),
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            borderWidth: 2,
                        },
                        {
                            label: 'Zero Line',
                            data: [],
                            borderColor: 'rgb(156, 163, 175)',
                            borderDash: [5, 5],
                            pointRadius: 0,
                            borderWidth: 1,
                            fill: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Disable animations to prevent stack overflow
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            callbacks: {
                                label: (context) => {
                                    if (context.dataset.label === 'Zero Line') return null;
                                    const value = context.parsed.y;
                                    const sign = value >= 0 ? '+' : '';
                                    return `Spread: ${sign}${value.toFixed(2)} bps`;
                                },
                                afterLabel: (context) => {
                                    if (context.dataset.label === 'Zero Line') return null;
                                    const value = context.parsed.y;
                                    if (value > 0) return 'Contango (Perp > Quarterly)';
                                    if (value < 0) return 'Backwardation (Quarterly > Perp)';
                                    return 'Neutral';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 10 },
                                maxRotation: 45,
                                minRotation: 0,
                                callback: function(value, index) {
                                    // Show every 10th label to avoid crowding
                                    return index % 10 === 0 ? `#${index}` : '';
                                }
                            },
                            grid: {
                                display: false,
                            },
                        },
                        y: {
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Spread (BPS)',
                                color: '#94a3b8',
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 10 },
                                callback: (value) => {
                                    const sign = value >= 0 ? '+' : '';
                                    return sign + value.toFixed(0);
                                }
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)',
                            },
                        },
                    },
                }
                });

                console.log('✅ Spread history chart initialized');
                
                // Clear loading state and initialization flag
                this.loading = false;
                this.chartInitializing = false;
                
                // Chart initialized, ready for data
                
            } catch (error) {
                console.error('❌ Error creating chart:', error);
                this.chart = null;
                this.chartInitializing = false;
                this.loading = false;
                // Retry after delay
                setTimeout(() => {
                    console.log('📊 Retrying chart creation');
                    this.initChart();
                }, 1000);
            }
            
            // Chart ready for data
        },

        async loadData() {
            console.log('📊 Chart loadData called');
            this.loading = true;
            try {
                const actualPerpSymbol = this.perpSymbol || `${this.symbol}${this.quote}`;
                const params = new URLSearchParams({
                    exchange: this.exchange,
                    base: this.symbol,
                    quote: this.quote,
                    interval: this.interval,
                    limit: this.limit,
                    perp_symbol: actualPerpSymbol
                });
                
                console.log('📊 Chart params:', params.toString());

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/perp-quarterly/history?${params}` : `/api/perp-quarterly/history?${params}`;

                console.log('📡 Fetching Perp-Quarterly History:', url);

                const response = await fetch(url);
                console.log('📡 History Response status:', response.status);

                if (!response.ok) {
                    console.error('❌ History HTTP Error:', response.status, response.statusText);
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('📡 Raw History API response:', data);
                const rawData = data.data || [];
                console.log('📡 Chart data length:', rawData.length);
                
                // Sort by timestamp descending (newest first)
                const chartData = rawData.sort((a, b) => {
                    if (!a.ts) return 1;
                    if (!b.ts) return -1;
                    return new Date(b.ts) - new Date(a.ts);
                });
                
                this.chartData = chartData;
                this.dataPoints = chartData.length;
                
                console.log('✅ Spread history loaded:', this.dataPoints, 'points');
            } catch (error) {
                console.error('❌ Error loading spread history:', error);
                this.chartData = [];
                this.dataPoints = 0;
            } finally {
                this.loading = false;
            }
        },

        safeUpdateChart(historyData) {
            console.log('📊 safeUpdateChart called with', historyData.length, 'data points');
            
            // If chart doesn't exist, just update data without creating chart
            if (!this.chart) {
                console.log('📊 Chart not exists, updating data only');
                this.chartData = historyData;
                this.dataPoints = historyData.length;
                return;
            }

            // Check if chart is properly initialized
            if (!this.chart.data || !this.chart.data.datasets) {
                console.log('📊 Chart data not ready, waiting...');
                setTimeout(() => this.safeUpdateChart(historyData), 200);
                return;
            }

            // Check if datasets exist
            if (!this.chart.data.datasets[0] || !this.chart.data.datasets[1]) {
                console.log('📊 Chart datasets not ready, waiting...');
                setTimeout(() => this.safeUpdateChart(historyData), 200);
                return;
            }

            // Try to update chart
            try {
                console.log('📊 Updating chart with data');
                this.updateChart(historyData);
                console.log('📊 Chart updated successfully');
            } catch (error) {
                console.error('📊 Chart update failed:', error.message);
                // If update fails, recreate chart
                this.chart = null;
                setTimeout(() => this.safeUpdateChart(historyData), 500);
            }
        },

        updateChart(historyData) {
            if (!this.chart) {
                console.warn('⚠️ Chart not initialized, cannot update');
                return;
            }

            // Check if chart is properly initialized
            if (!this.chart.data || !this.chart.data.datasets) {
                console.warn('⚠️ Chart data not ready, retrying in 100ms');
                setTimeout(() => this.updateChart(historyData), 100);
                return;
            }

            // Check if datasets are properly initialized
            if (!this.chart.data.datasets[0] || !this.chart.data.datasets[1]) {
                console.warn('⚠️ Chart datasets not ready, retrying in 100ms');
                setTimeout(() => this.updateChart(historyData), 100);
                return;
            }

            // Ensure we have data
            if (!historyData || historyData.length === 0) {
                console.warn('⚠️ No data provided');
                return;
            }

            console.log('📊 Updating chart with', historyData.length, 'data points');

            // Limit data points for performance and slice to avoid stack overflow
            const limitedData = historyData.slice(-500);

            // Process data with simple index-based x values
            const chartData = limitedData.map((row, index) => ({
                x: index,
                y: parseFloat(row.spread_bps) || 0
            }));

            // Add zero line data
            const zeroData = chartData.map(point => ({
                x: point.x,
                y: 0
            }));

            // Update chart datasets
            this.chart.data.datasets[0].data = chartData;
            this.chart.data.datasets[1].data = zeroData;

            // Update gradient based on data (without triggering chart update)
            this.updateGradientColor(chartData, false);

            // Safe chart update with readiness check
            this.safeChartUpdate(chartData);
        },

        updateChartFromOverview(timeseries) {
            if (!this.chart || !Array.isArray(timeseries)) return;

            // Check if chart is properly initialized
            if (!this.chart.data || !this.chart.data.datasets) {
                console.warn('⚠️ Chart data not ready for overview update, retrying in 100ms');
                setTimeout(() => this.updateChartFromOverview(timeseries), 100);
                return;
            }

            // Limit data points for performance
            const limitedData = timeseries.slice(-500);

            const chartData = limitedData.map((row, index) => ({
                x: index,
                y: parseFloat(row.spread_bps) || 0
            }));

            const zeroData = chartData.map(point => ({
                x: point.x,
                y: 0
            }));

            this.chart.data.datasets[0].data = chartData;
            this.chart.data.datasets[1].data = zeroData;
            this.updateGradientColor(chartData, false);
            
            // Safe chart update with readiness check
            this.safeChartUpdate(chartData);
        },

        safeChartUpdate(chartData) {
            // Check if chart is fully ready
            if (!this.chart) {
                console.warn('⚠️ Chart not available for update');
                return;
            }

            // Check if chart has all required properties
            if (!this.chart.data || !this.chart.data.datasets || !this.chart.data.datasets[0] || !this.chart.data.datasets[1]) {
                console.warn('⚠️ Chart datasets not ready, scheduling update');
                setTimeout(() => this.safeChartUpdate(chartData), 200);
                return;
            }

            // Check if chart is properly initialized with all internal properties
            if (!this.chart.options || !this.chart.scales) {
                console.warn('⚠️ Chart options/scales not ready, scheduling update');
                setTimeout(() => this.safeChartUpdate(chartData), 200);
                return;
            }

            // Additional check for Chart.js internal state
            if (typeof this.chart.update !== 'function') {
                console.warn('⚠️ Chart update method not available, scheduling update');
                setTimeout(() => this.safeChartUpdate(chartData), 200);
                return;
            }

            // Perform the update with error handling
            try {
                this.chart.update('none');
                this.dataPoints = chartData.length;
                console.log('✅ Chart updated successfully with', chartData.length, 'points');
            } catch (error) {
                console.error('❌ Chart update failed:', error.message);
                
                // If it's a 'fullSize' error, wait longer and retry
                if (error.message.includes('fullSize')) {
                    console.log('🔄 FullSize error detected, waiting for chart initialization...');
                    setTimeout(() => this.safeChartUpdate(chartData), 500);
                } else {
                    // For other errors, retry with shorter delay
                    setTimeout(() => this.safeChartUpdate(chartData), 200);
                }
            }
        },

        updateGradientColor(data, shouldUpdateChart = true) {
            if (!this.chart || !data.length) return;

            const avgSpread = data.reduce((sum, d) => sum + d.y, 0) / data.length;
            const canvas = document.getElementById(this.chartId);
            const ctx = canvas?.getContext('2d');

            if (!ctx) return;

            let gradient;
            if (avgSpread > 10) {
                // Strong contango - green
                gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(34, 197, 94, 0.3)');
                gradient.addColorStop(1, 'rgba(34, 197, 94, 0)');
                this.chart.data.datasets[0].borderColor = 'rgb(34, 197, 94)';
            } else if (avgSpread < -10) {
                // Strong backwardation - red
                gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(239, 68, 68, 0.3)');
                gradient.addColorStop(1, 'rgba(239, 68, 68, 0)');
                this.chart.data.datasets[0].borderColor = 'rgb(239, 68, 68)';
            } else {
                // Neutral - blue
                gradient = this.createGradient(ctx);
                this.chart.data.datasets[0].borderColor = 'rgb(59, 130, 246)';
            }

            this.chart.data.datasets[0].backgroundColor = gradient;
            
            // Only update chart if explicitly requested to avoid recursion
            if (shouldUpdateChart) {
                this.chart.update('none');
            }
        },

        createGradient(ctx) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');
            return gradient;
        },

        // refresh() method removed - using unified auto-refresh system

        debugChart() {
            console.log('🔍 Chart Debug Info:');
            console.log('- Chart exists:', !!this.chart);
            console.log('- Chart ID:', this.chartId);
            console.log('- Data points:', this.dataPoints);
            console.log('- Loading:', this.loading);
            if (this.chart) {
                console.log('- Chart data length:', this.chart.data.datasets[0].data.length);
                console.log('- Chart canvas:', !!document.getElementById(this.chartId));
            }
        },

        getDisplayData() {
            // For performance: limit display to reasonable amount
            // If API limit > 1000, only show first 100 for table performance
            const maxDisplayRows = parseInt(this.limit) > 1000 ? 100 : Math.min(parseInt(this.limit), this.displayLimit);
            return this.chartData.slice(0, maxDisplayRows);
        },

        formatTime(timestamp) {
            if (!timestamp) return '--';
            const date = new Date(timestamp);
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
        },

        formatBPS(value) {
            if (value === null || value === undefined) return '--';
            const sign = value >= 0 ? '+' : '';
            return `${sign}${parseFloat(value).toFixed(2)} bps`;
        },

        formatSpread(value) {
            if (value === null || value === undefined) return '--';
            return `$${parseFloat(value).toFixed(2)}`;
        },

        getSpreadColor(value) {
            if (value === null || value === undefined) return '';
            const numValue = parseFloat(value);
            if (numValue > 10) return 'text-success';
            if (numValue < -10) return 'text-danger';
            return 'text-warning';
        }
    };
}

// Export to window for Alpine.js
window.spreadHistoryChart = spreadHistoryChart;
</script>

