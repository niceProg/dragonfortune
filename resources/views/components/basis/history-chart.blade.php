{{--
    Komponen: Basis History Chart (Line Chart)
    Menampilkan historical basis movement dari endpoint /api/basis/history

    Props:
    - $symbol: string (default: 'BTC')
--}}

<div class="df-panel p-3 h-100 d-flex flex-column" x-data="basisHistoryChart('{{ $symbol ?? 'BTC' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
        <div>
            <h5 class="mb-1">Basis History</h5>
            <small class="text-secondary">Historical basis movement over time</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span x-show="loading" class="spinner-border spinner-border-sm text-primary"></span>
            <small class="text-secondary" x-show="historyData.length > 0" x-text="historyData.length + ' points'">Loading...</small>
        </div>
    </div>

    <!-- Chart Canvas -->
    <div class="flex-grow-1" style="height: 400px; max-height: 400px; position: relative;">
        <canvas :id="chartId" style="display: block; box-sizing: border-box; height: 400px; width: 100%;"></canvas>
    </div>
</div>

<script>
function basisHistoryChart(initialSymbol = 'BTC') {
    return {
        symbol: initialSymbol,
        exchange: 'Binance',
        limit: '2000',
        chartId: 'basisHistoryChart_' + Math.random().toString(36).substr(2, 9),
        chart: null,
        loading: false,
        historyData: [],

        async init() {
            this.symbol = this.$root?.globalSymbol || initialSymbol;
            this.exchange = this.$root?.globalExchange || 'Binance';
            this.limit = this.$root?.globalLimit || '2000';

            await window.chartJsReady;
            await this.loadData();

            // Listen to global filter changes
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.exchange = e.detail?.exchange || this.exchange;
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('exchange-changed', (e) => {
                this.exchange = e.detail?.exchange || this.exchange;
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('limit-changed', (e) => {
                this.limit = e.detail?.limit || this.limit;
                this.loadData();
            });
            window.addEventListener('refresh-all', () => this.loadData());
            
            // Listen for auto refresh events
            window.addEventListener('auto-refresh-tick', () => {
                if (!this.loading) this.loadData();
            });
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    exchange: this.exchange,
                    spot_pair: `${this.symbol}USDT`,
                    futures_symbol: `${this.symbol}USDT`,
                    interval: '5m',
                    limit: this.limit
                });

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/basis/history?${params}` : `/api/basis/history?${params}`;

                console.log('📡 Fetching Basis History:', url);

                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                this.historyData = (data.data || [])
                    .filter(item => item.basis_abs !== null && !isNaN(parseFloat(item.basis_abs)))
                    .sort((a, b) => a.ts - b.ts)
                    .slice(-500); // Last 500 points for performance

                this.updateChart();
                console.log('✅ Basis History loaded:', this.historyData.length, 'points');
            } catch (error) {
                console.error('❌ Error loading basis history:', error);
                this.historyData = [];
                this.updateChart();
            } finally {
                this.loading = false;
            }
        },

        updateChart() {
            const canvas = document.getElementById(this.chartId);
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            // Destroy existing chart
            if (this.chart) {
                this.chart.destroy();
            }

            if (this.historyData.length === 0) {
                // Show empty state
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: { labels: [], datasets: [] },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
                return;
            }

            // Prepare data with proper timestamps (oldest to newest)
            const labels = this.historyData.map(item => {
                const date = new Date(item.ts);
                return date.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: false 
                });
            });
            const basisValues = this.historyData.map(item => parseFloat(item.basis_abs));
            const zeroLine = new Array(this.historyData.length).fill(0);

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Basis (Absolute)',
                            data: basisValues,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 1,
                            pointHoverRadius: 4
                        },
                        {
                            label: 'Zero Line',
                            data: zeroLine,
                            borderColor: 'rgb(156, 163, 175)',
                            borderDash: [5, 5],
                            pointRadius: 0,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#94a3b8',
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            callbacks: {
                                label: (context) => {
                                    const val = context.parsed.y;
                                    return `${context.dataset.label}: $${val.toFixed(2)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                maxTicksLimit: 8
                            },
                            grid: { display: false }
                        },
                        y: {
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Basis ($)',
                                color: '#94a3b8'
                            },
                            ticks: {
                                color: '#94a3b8',
                                callback: (value) => '$' + value.toFixed(0)
                            },
                            grid: { color: 'rgba(148, 163, 184, 0.1)' }
                        }
                    }
                }
            });
        }
    };
}
</script>
