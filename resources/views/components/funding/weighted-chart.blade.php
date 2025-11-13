{{--
    Komponen: OI-Weighted Funding Chart
    Menampilkan funding rate yang di-weighted berdasarkan Open Interest

    Props:
    - $symbol: string (default: 'BTC')
    - $interval: string (default: '4h')

    Interpretasi:
    - OI-weighted lebih akurat untuk melihat real positioning
    - Exchange dengan OI besar memiliki pengaruh lebih besar
    - Trend naik → Long positioning increasing
    - Trend turun → Short positioning increasing
--}}

<div class="df-panel p-3" style="min-height: 350px;" x-data="weightedFundingChart('{{ $symbol ?? 'BTC' }}', '{{ $interval ?? '4h' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">⚖️ OI-Weighted Funding</h5>
            <span class="badge text-bg-info" x-text="'Open Interest Weighted'">Open Interest Weighted</span>
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

    <!-- Insight -->
    <div class="small text-secondary mt-2">
        <div class="d-flex align-items-center gap-2">
            <span>💡</span>
            <span>Weighted by open interest to show true market positioning. Higher OI exchanges have more influence.</span>
        </div>
    </div>

    <!-- Current Stats -->
    <div class="row g-2 mt-2">
        <div class="col-4">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Current</div>
                <div class="fw-bold" :class="currentRate >= 0 ? 'text-success' : 'text-danger'" x-text="formatRate(currentRate)">
                    +0.0125%
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">24h Avg</div>
                <div class="fw-bold" x-text="formatRate(avg24h)">
                    +0.0108%
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Trend</div>
                <div class="fw-bold" :class="getTrendClass()" x-text="getTrendText()">
                    ↗️ Rising
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function weightedFundingChart(initialSymbol = 'BTC', initialInterval = '1h') {
    return {
        symbol: initialSymbol,
        interval: '1h', // API only supports 1h currently
        marginType: '',
        loading: false,
        // DO NOT store chart here - Alpine will track it!
        // chart: null,
        chartId: 'weightedChart_' + Math.random().toString(36).substr(2, 9),
        chartData: [],
        currentRate: 0,
        avg24h: 0,
        trend: 0,
        updatePending: false,

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

                console.log(`💜 Weighted Chart Init Attempt ${attempts}: Canvas found, Parent width: ${parentWidth}px`);

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
                console.warn('⚠️ Canvas not found for weighted chart');
                return;
            }

            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js not loaded');
                return;
            }

            const ctx = canvas.getContext('2d');

            // CRITICAL: Create chart OUTSIDE Alpine reactivity scope using queueMicrotask
            queueMicrotask(() => {
                // Create gradient outside Alpine reactivity to prevent infinite loop
                const gradient = ctx.createLinearGradient(0, 0, 0, 280);
                gradient.addColorStop(0, 'rgba(139, 92, 246, 0.3)');
                gradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

                const chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'OI-Weighted Rate',
                        data: [],
                        borderColor: '#8b5cf6',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 6
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
                                    const item = items[0];
                                    return 'Time: ' + item.label;
                                },
                                label: (context) => {
                                    const value = context.parsed.y;
                                    return `Weighted Rate: ${(value >= 0 ? '+' : '')}${value.toFixed(4)}%`;
                                },
                                afterLabel: (context) => {
                                    return 'Based on exchange OI weight';
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

                this.setChart(chartInstance);
                console.log('✅ Weighted chart initialized');
            });
        },

        async loadData() {
            this.loading = true;
            try {
                // API hanya support interval 1h saat ini, dan returns oi_weight array
                const params = new URLSearchParams({
                    symbol: this.symbol,
                    interval: '1h',
                    limit: '100'
                });

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/funding-rate/weighted?${params}` : `/api/funding-rate/weighted?${params}`;
                const response = await fetch(url);
                const data = await response.json();

                // API returns {oi_weight: [], vol_weight: []}
                this.chartData = data.oi_weight || data.data || [];
                console.log('📊 Weighted data received:', this.chartData.length, 'items');

                if (this.chartData.length > 0) {
                    // Calculate stats
                    this.currentRate = parseFloat(this.chartData[this.chartData.length - 1]?.close || 0);

                    // Calculate 24h average
                    const last24Items = this.chartData.slice(-24);
                    if (last24Items.length > 0) {
                        const sum = last24Items.reduce((acc, item) => acc + parseFloat(item.close || 0), 0);
                        this.avg24h = sum / last24Items.length;
                    }

                    // Calculate trend
                    if (this.chartData.length >= 2) {
                        const prevRate = parseFloat(this.chartData[this.chartData.length - 2]?.close || 0);
                        this.trend = this.currentRate - prevRate;
                    }
                }

                this.updateChart();

                console.log('✅ Weighted data loaded:', this.chartData.length, 'points');
            } catch (error) {
                console.error('❌ Error loading weighted data:', error);
            } finally {
                this.loading = false;
            }
        },

        updateChart() {
            const chart = this.getChart();

            if (!chart || !this.chartData || this.chartData.length === 0) {
                console.warn('⚠️ Cannot update weighted chart: missing chart or data');
                return;
            }

            // Prevent multiple simultaneous updates
            if (this.updatePending) {
                console.warn('⚠️ Chart update already pending, skipping...');
                return;
            }

            this.updatePending = true;

            try {
                const labels = this.chartData.map(item => {
                    const date = new Date(item.time);
                    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                });

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
                            console.error('❌ Weighted chart update error:', updateError);
                        } finally {
                            this.updatePending = false;
                        }
                    });
                } else {
                    this.updatePending = false;
                }
            } catch (error) {
                console.error('❌ Error updating weighted chart:', error);
                this.updatePending = false;
            }
        },

        refresh() {
            this.loadData();
        },

        // remove mock generator: all data must come from API

        getTrendClass() {
            if (this.trend > 0.0001) return 'text-success';
            if (this.trend < -0.0001) return 'text-danger';
            return 'text-secondary';
        },

        getTrendText() {
            if (this.trend > 0.0001) return '↗️ Rising';
            if (this.trend < -0.0001) return '↘️ Falling';
            return '→ Stable';
        },

        formatRate(value) {
            if (value === null || value === undefined) return 'N/A';
            const percent = (value * 100).toFixed(4);
            return (value >= 0 ? '+' : '') + percent + '%';
        }
    };
}
</script>

