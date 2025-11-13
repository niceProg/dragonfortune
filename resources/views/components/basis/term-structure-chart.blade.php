{{--
    Komponen: Term Structure Chart (Bar Chart)
    Menampilkan basis across different contract expiries dari endpoint /api/basis/term-structure

    Props:
    - $symbol: string (default: 'BTC')
--}}

<div class="df-panel p-3" x-data="termStructureChart('{{ $symbol ?? 'BTC' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">Term Structure Analysis</h5>
            <small class="text-secondary">Basis across different contract expiries</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span x-show="loading" class="spinner-border spinner-border-sm text-primary"></span>
            <small class="text-secondary" x-show="termData.length > 0" x-text="termData.length + ' contracts'">Loading...</small>
        </div>
    </div>

    <!-- Chart Canvas -->
    <div style="height: 300px; max-height: 300px; position: relative;">
        <canvas :id="chartId" style="display: block; box-sizing: border-box; height: 300px; width: 100%;"></canvas>
    </div>
</div>

<script>
function termStructureChart(initialSymbol = 'BTC') {
    return {
        symbol: initialSymbol,
        exchange: 'Binance',
        limit: '2000',
        chartId: 'termStructureChart_' + Math.random().toString(36).substr(2, 9),
        chart: null,
        loading: false,
        termData: [],

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
                    max_contracts: '10'
                });

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/basis/term-structure?${params}` : `/api/basis/term-structure?${params}`;

                console.log('📡 Fetching Term Structure:', url);

                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                this.termData = (data.data || [])
                    .filter(item => item.basis_abs !== null && !isNaN(parseFloat(item.basis_abs)))
                    .sort((a, b) => (a.expiry || 0) - (b.expiry || 0))
                    .slice(0, 8); // Max 8 contracts for readability

                this.updateChart();
                console.log('✅ Term Structure loaded:', this.termData.length, 'contracts');
            } catch (error) {
                console.error('❌ Error loading term structure:', error);
                this.termData = [];
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

            if (this.termData.length === 0) {
                // Show empty state
                this.chart = new Chart(ctx, {
                    type: 'bar',
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

            // Prepare data
            const labels = this.termData.map((item, index) => `Contract ${index + 1}`);
            const basisValues = this.termData.map(item => parseFloat(item.basis_abs));

            // Color code based on basis value
            const colors = basisValues.map(value => {
                if (value > 0) return 'rgba(34, 197, 94, 0.7)'; // Green for contango
                if (value < 0) return 'rgba(239, 68, 68, 0.7)'; // Red for backwardation
                return 'rgba(156, 163, 175, 0.7)'; // Gray for neutral
            });

            const borderColors = basisValues.map(value => {
                if (value > 0) return 'rgb(34, 197, 94)';
                if (value < 0) return 'rgb(239, 68, 68)';
                return 'rgb(156, 163, 175)';
            });

            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Basis by Expiry',
                        data: basisValues,
                        backgroundColor: colors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            callbacks: {
                                label: (context) => {
                                    const val = context.parsed.y;
                                    return `Basis: $${val.toFixed(2)}`;
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
