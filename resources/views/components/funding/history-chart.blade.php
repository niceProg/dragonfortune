{{--
    Komponen: Funding Rate History Chart
    Menampilkan historical funding rate dengan OHLC data

    Props:
    - $symbol: string (default: 'BTC')
    - $interval: string (default: '4h')

    Interpretasi:
    - Candlestick untuk melihat volatilitas funding rate
    - Wick panjang → High volatility dalam periode
    - Trend naik konsisten → Long bias strengthening
    - Spike tiba-tiba → Extreme positioning / squeeze risk
--}}

<div class="df-panel p-3" style="min-height: 350px;" x-data="historyFundingChart('{{ $symbol ?? 'BTC' }}', '{{ $interval ?? '4h' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">📉 Funding Rate History</h5>
            <span class="badge text-bg-secondary" x-text="chartData.length + ' periods'">0 periods</span>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="width: auto;" x-model="interval" @change="loadData()" disabled>
                <option value="1h">1 Hour (API only)</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" @click="refresh()" :disabled="loading">
                <span x-show="!loading">🔄</span>
                <span x-show="loading" class="spinner-border spinner-border-sm"></span>
            </button>
        </div>
    </div>

    <!-- Chart Canvas -->
    <div style="position: relative; height: 280px; min-width: 100px;">
        <canvas :id="chartId" style="display: block; box-sizing: border-box; height: 280px; width: 100%;"></canvas>
    </div>

    <!-- OHLC Stats -->
    <div class="row g-2 mt-2">
        <div class="col-3">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Open</div>
                <div class="fw-bold" x-text="formatRate(lastOHLC.open)">--</div>
            </div>
        </div>
        <div class="col-3">
            <div class="text-center p-2 bg-success bg-opacity-10 rounded">
                <div class="small text-secondary">High</div>
                <div class="fw-bold text-success" x-text="formatRate(lastOHLC.high)">--</div>
            </div>
        </div>
        <div class="col-3">
            <div class="text-center p-2 bg-danger bg-opacity-10 rounded">
                <div class="small text-secondary">Low</div>
                <div class="fw-bold text-danger" x-text="formatRate(lastOHLC.low)">--</div>
            </div>
        </div>
        <div class="col-3">
            <div class="text-center p-2 bg-primary bg-opacity-10 rounded">
                <div class="small text-secondary">Close</div>
                <div class="fw-bold text-primary" x-text="formatRate(lastOHLC.close)">--</div>
            </div>
        </div>
    </div>
</div>

<script>
function historyFundingChart(initialSymbol = 'BTC', initialInterval = '1h') {
    return {
        symbol: initialSymbol,
        interval: '1h', // API only supports 1h currently
        marginType: '',
        loading: false,
        // DO NOT store chart here - Alpine will track it!
        // chart: null,
        chartId: 'historyChart_' + Math.random().toString(36).substr(2, 9),
        chartData: [],
        lastOHLC: { open: 0, high: 0, low: 0, close: 0 },
        updatePending: false,

        // Helper to get chart from DOM storage (non-reactive)
        getChart() {
            const canvas = document.getElementById(this.chartId);
            return canvas ? canvas._chartInstance : null;
        },

        setChart(chartInstance) {
            const canvas = document.getElementById(this.chartId);
            if (canvas) canvas._chartInstance = chartInstance;
        },

        async init() {
            // Wait for Chart.js to be loaded
            if (typeof Chart === 'undefined') {
                console.log('⏳ Waiting for Chart.js to load...');
                await window.chartJsReady;
            }

            // Multiple retry strategy untuk memastikan chart init dengan proper width
            this.initChartWithRetry();

            // Listen for global refresh and symbol change
            this.$watch('symbol', () => this.loadData());
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.marginType = e.detail?.marginType ?? this.marginType;
                this.interval = e.detail?.interval || this.interval;
                this.loadData();
            });
            window.addEventListener('margin-type-changed', (e) => {
                this.marginType = e.detail?.marginType ?? '';
                this.loadData();
            });
            window.addEventListener('interval-changed', (e) => {
                this.interval = e.detail?.interval || this.interval;
                this.loadData();
            });

            // Setup observer untuk detect visibility changes
            this.setupVisibilityObserver();
        },

        initChartWithRetry() {
            let attempts = 0;
            const maxAttempts = 5;

            const tryInit = () => {
                attempts++;
                const canvas = document.getElementById(this.chartId);

                if (!canvas) {
                    if (attempts < maxAttempts) {
                        setTimeout(tryInit, 500);
                    }
                    return;
                }

                const parent = canvas.parentElement;
                const parentWidth = parent ? parent.offsetWidth : 0;

                console.log(`📉 History Chart Init Attempt ${attempts}: Canvas found, Parent width: ${parentWidth}px`);

                // Jika parent width masih 0 atau terlalu kecil, retry
                if (parentWidth < 100 && attempts < maxAttempts) {
                    console.warn(`⚠️ Parent width too small (${parentWidth}px), retrying in 500ms...`);
                    setTimeout(tryInit, 500);
                    return;
                }

                // Width cukup, init chart
                this.initChart();
                this.loadData();
            };

            // Start first attempt after 500ms
            setTimeout(tryInit, 500);
        },

        setupVisibilityObserver() {
            const canvas = document.getElementById(this.chartId);
            if (!canvas) return;

            const observer = new ResizeObserver(() => {
                const chart = this.getChart();
                if (chart && canvas.offsetParent !== null) {
                    // Canvas visible, resize chart
                    chart.resize();
                }
            });

            observer.observe(canvas.parentElement);

            // Also listen for sidebar toggle
            document.addEventListener('click', (e) => {
                if (e.target.closest('[data-bs-toggle="collapse"]')) {
                    setTimeout(() => {
                        const chart = this.getChart();
                        if (chart) {
                            chart.resize();
                        }
                    }, 350); // Bootstrap collapse animation = 350ms
                }
            });
        },

        initChart() {
            const canvas = document.getElementById(this.chartId);
            if (!canvas) {
                console.warn('⚠️ Canvas not found for history chart');
                return;
            }

            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js not loaded');
                return;
            }

            const ctx = canvas.getContext('2d');

            // CRITICAL: Create chart OUTSIDE Alpine reactivity scope
            queueMicrotask(() => {
                // Create gradient outside Alpine reactivity to prevent infinite loop
                const gradient = ctx.createLinearGradient(0, 0, 0, 280);
                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
                gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

                const chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Funding Rate',
                        data: [],
                        borderColor: '#3b82f6',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            callbacks: {
                                title: (items) => {
                                    return 'Time: ' + items[0].label;
                                },
                                label: (context) => {
                                    // Access raw data from chart to avoid Alpine reactivity
                                    const rawData = context.chart.data.datasets[0]._rawData || [];
                                    const dataPoint = rawData[context.dataIndex];
                                    if (!dataPoint) return '';

                                    const formatRate = (val) => {
                                        if (isNaN(val)) return '--';
                                        return (val >= 0 ? '+' : '') + (val * 100).toFixed(4) + '%';
                                    };

                                    return [
                                        `Close: ${formatRate(parseFloat(dataPoint.close))}`,
                                        `High: ${formatRate(parseFloat(dataPoint.high))}`,
                                        `Low: ${formatRate(parseFloat(dataPoint.low))}`,
                                        `Open: ${formatRate(parseFloat(dataPoint.open))}`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 10 },
                                maxRotation: 0
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 10 },
                                callback: (value) => {
                                    return (value >= 0 ? '+' : '') + value.toFixed(3) + '%';
                                }
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });

                // Store in DOM, NOT in Alpine reactive this
                this.setChart(chartInstance);
                console.log('✅ History chart initialized');
            });
        },

        async loadData() {
            this.loading = true;
            try {
                // API hanya support interval 1h saat ini
                const params = new URLSearchParams({
                    symbol: `${this.symbol}USDT`,
                    interval: '1h',
                    limit: '100'
                });

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/funding-rate/history?${params}` : `/api/funding-rate/history?${params}`;
                const response = await fetch(url);
                const data = await response.json();

                this.chartData = data.data || [];
                console.log('📊 History data received:', this.chartData.length, 'items');

                if (this.chartData.length > 0) {
                    const last = this.chartData[this.chartData.length - 1];
                    this.lastOHLC = {
                        open: parseFloat(last.open || 0),
                        high: parseFloat(last.high || 0),
                        low: parseFloat(last.low || 0),
                        close: parseFloat(last.close || 0)
                    };
                }

                this.updateChart();

                console.log('✅ History data loaded:', this.chartData.length, 'candles');
            } catch (error) {
                console.error('❌ Error loading history data:', error);
            } finally {
                this.loading = false;
            }
        },

        updateChart() {
            const chart = this.getChart();

            if (!chart || !this.chartData || this.chartData.length === 0) {
                console.warn('⚠️ Cannot update history chart: missing chart or data');
                return;
            }

            // Prevent multiple simultaneous updates
            if (this.updatePending) {
                console.warn('⚠️ History chart update already pending, skipping...');
                return;
            }

            this.updatePending = true;

            try {
                const labels = this.chartData.map(item => {
                    const date = new Date(item.time);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' +
                           date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                });

                // Use close values for the line
                const values = this.chartData.map(item => {
                    const close = parseFloat(item.close);
                    return isNaN(close) ? 0 : close * 100;
                });

                // Safely update chart data
                if (chart.data && chart.data.datasets[0]) {
                    chart.data.labels = labels;
                    chart.data.datasets[0].data = values;
                    // Store raw data for tooltip (deep clone to avoid Alpine reactivity)
                    chart.data.datasets[0]._rawData = JSON.parse(JSON.stringify(this.chartData));

                    // CRITICAL: Use queueMicrotask to break Alpine reactivity cycle
                    queueMicrotask(() => {
                        try {
                            if (chart && chart.update && typeof chart.update === 'function') {
                                chart.update('none');
                            }
                        } catch (updateError) {
                            console.error('❌ History chart update error:', updateError);
                        } finally {
                            this.updatePending = false;
                        }
                    });
                } else {
                    this.updatePending = false;
                }
            } catch (error) {
                console.error('❌ Error updating history chart:', error);
                this.updatePending = false;
            }
        },

        refresh() {
            this.loadData();
        },

        // remove mock generator: all data must come from API

        formatRate(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const percent = (value * 100).toFixed(4);
            return (value >= 0 ? '+' : '') + percent + '%';
        }
    };
}
</script>

