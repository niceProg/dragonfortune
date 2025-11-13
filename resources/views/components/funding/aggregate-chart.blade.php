{{--
    Komponen: Funding Rate Aggregate Chart
    Menampilkan perbandingan funding rate per exchange dalam bar chart

    Props:
    - $symbol: string (default: 'BTC')
    - $rangeStr: string (default: '7d')

    Interpretasi:
    - Bar hijau tinggi → Exchange dengan funding rate positif tinggi → Longs crowded di exchange ini
    - Bar merah dalam → Shorts crowded di exchange ini
    - Perbandingan antar exchange → Arbitrage opportunities
--}}

<div class="df-panel p-3" x-data="aggregateFundingChart('{{ $symbol ?? 'BTC' }}', '{{ $rangeStr ?? '7d' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">📊 Funding Rate by Exchange</h5>
            <span class="badge text-bg-secondary" x-text="'(' + rangeStr + ' accumulated)'">( 7d accumulated)</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm" style="width: auto;" x-model="rangeStr" @change="loadData()">
                <option value="1d">1 Day</option>
                <option value="7d">7 Days</option>
                <option value="30d">30 Days</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" @click="refresh()" :disabled="loading">
                <span x-show="!loading">🔄</span>
                <span x-show="loading" class="spinner-border spinner-border-sm"></span>
            </button>
        </div>
    </div>

    <!-- Chart Canvas -->
    <div style="position: relative; height: 380px; min-width: 100px;">
        <canvas :id="chartId" style="display: block; box-sizing: border-box; height: 380px; width: 100%;"></canvas>
    </div>

    <!-- Exchange Spread Alert -->
    <template x-if="spreadAlert">
        <div class="alert alert-warning mt-3 mb-0" role="alert">
            <div class="d-flex align-items-start gap-2">
                <div>⚡</div>
                <div class="flex-grow-1">
                    <div class="fw-semibold small">Large Exchange Spread Detected</div>
                    <div class="small" x-text="spreadAlert"></div>
                </div>
            </div>
        </div>
    </template>

    <!-- Insight Panel -->
    <div class="row g-2 mt-3">
        <div class="col-md-4">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Highest Exchange</div>
                <div class="fw-bold text-success" x-text="highestExchange">--</div>
                <div class="small" x-text="formatRate(highestRate)">--</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Lowest Exchange</div>
                <div class="fw-bold text-danger" x-text="lowestExchange">--</div>
                <div class="small" x-text="formatRate(lowestRate)">--</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Spread</div>
                <div class="fw-bold text-warning" x-text="formatRate(spreadRate)">--</div>
                <div class="small" x-text="spreadPercentage + '% difference'">--</div>
            </div>
        </div>
    </div>
</div>

<script>
function aggregateFundingChart(initialSymbol = 'BTC', initialRangeStr = '7d') {
    return {
        symbol: initialSymbol,
        rangeStr: initialRangeStr,
        marginType: '',
        loading: false,
        aggregateData: [],
        // DO NOT store chart here - Alpine will track it!
        // chart: null,
        chartId: 'aggregateChart_' + Math.random().toString(36).substr(2, 9),
        highestExchange: '--',
        highestRate: 0,
        lowestExchange: '--',
        lowestRate: 0,

        // Helper to get chart from DOM storage (non-reactive)
        getChart() {
            const canvas = document.getElementById(this.chartId);
            return canvas ? canvas._chartInstance : null;
        },

        setChart(chartInstance) {
            const canvas = document.getElementById(this.chartId);
            if (canvas) canvas._chartInstance = chartInstance;
        },

        get spreadRate() {
            return this.highestRate - this.lowestRate;
        },

        get spreadPercentage() {
            if (this.lowestRate === 0) return 0;
            return Math.abs((this.spreadRate / this.lowestRate) * 100).toFixed(1);
        },

        get spreadAlert() {
            const spreadPercent = parseFloat(this.spreadPercentage);
            if (spreadPercent > 50) {
                return `Extreme spread of ${this.spreadPercentage}% between ${this.highestExchange} (${this.formatRate(this.highestRate)}) and ${this.lowestExchange} (${this.formatRate(this.lowestRate)}). Potential arbitrage opportunity or exchange-specific risk.`;
            }
            return null;
        },

        async init() {
            // Wait for Chart.js to be loaded
            if (typeof Chart === 'undefined') {
                console.log('⏳ Waiting for Chart.js to load...');
                await window.chartJsReady;
            }

            // Multiple retry strategy untuk memastikan chart init dengan proper width
            this.initChartWithRetry();

            // Listen to global filter changes
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.marginType = e.detail?.marginType ?? this.marginType;
                this.loadData();
            });
            window.addEventListener('margin-type-changed', (e) => {
                this.marginType = e.detail?.marginType ?? '';
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

                console.log(`🎨 Aggregate Chart Init Attempt ${attempts}: Canvas found, Parent width: ${parentWidth}px`);

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

            // Window resize dengan debounce
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    const chart = this.getChart();
                    if (chart) {
                        chart.resize();
                    }
                }, 250);
            });

            // Listen untuk sidebar toggle
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
                console.warn('⚠️ Canvas not found for aggregate chart:', this.chartId);
                return;
            }

            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js not loaded');
                return;
            }

            const ctx = canvas.getContext('2d');

            const chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Funding Rate (%)',
                        data: [],
                        backgroundColor: [],
                        borderColor: [],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            callbacks: {
                                label: (context) => {
                                    const value = context.parsed.y;
                                    return `Funding Rate: ${(value >= 0 ? '+' : '')}${value.toFixed(4)}%`;
                                },
                                afterLabel: (context) => {
                                    // Access data directly from chart to avoid Alpine reactivity
                                    const datasets = context.chart.data.datasets[0];
                                    const rawData = datasets._rawData || [];
                                    const item = rawData[context.dataIndex];
                                    if (!item) return '';
                                    return [
                                        `Margin: ${item.margin_type || 'N/A'}`,
                                        `Period: ${item.range_str || 'N/A'}`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11 }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11 },
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
            console.log('✅ Aggregate chart initialized');
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    limit: '2000',
                    ...(this.symbol && { symbol: this.symbol }),
                    ...(this.rangeStr && { range_str: this.rangeStr }),
                    ...(this.marginType && { margin_type: this.marginType })
                });

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/funding-rate/aggregate?${params}` : `/api/funding-rate/aggregate?${params}`;
                const response = await fetch(url);
                const data = await response.json();

                this.aggregateData = (data.data || []).filter(item => item.funding_rate !== null);

                // Group by exchange and get the latest
                const exchangeMap = {};
                this.aggregateData.forEach(item => {
                    if (!exchangeMap[item.exchange] || item.time_ms > exchangeMap[item.exchange].time_ms) {
                        exchangeMap[item.exchange] = item;
                    }
                });

                const latestData = Object.values(exchangeMap)
                    .sort((a, b) => parseFloat(b.funding_rate) - parseFloat(a.funding_rate));

                // Calculate highest and lowest
                if (latestData.length > 0) {
                    this.highestExchange = latestData[0].exchange;
                    this.highestRate = parseFloat(latestData[0].funding_rate);
                    this.lowestExchange = latestData[latestData.length - 1].exchange;
                    this.lowestRate = parseFloat(latestData[latestData.length - 1].funding_rate);
                }

                this.updateChart(latestData);

                console.log('✅ Aggregate data loaded:', latestData.length, 'exchanges');
            } catch (error) {
                console.error('❌ Error loading aggregate data:', error);
            } finally {
                this.loading = false;
            }
        },

        updateChart(latestData) {
            const chart = this.getChart();

            if (!chart || !latestData || latestData.length === 0) {
                console.warn('⚠️ Cannot update chart: missing chart or data');
                return;
            }

            try {
                const labels = latestData.map(item => item.exchange || 'Unknown');
                const values = latestData.map(item => {
                    const rate = parseFloat(item.funding_rate);
                    return isNaN(rate) ? 0 : rate * 100;
                });

                // Color based on value (green for positive, red for negative)
                const backgroundColors = values.map(value => {
                    if (value > 0.1) return 'rgba(34, 197, 94, 0.8)';
                    if (value > 0) return 'rgba(134, 239, 172, 0.8)';
                    if (value < -0.1) return 'rgba(239, 68, 68, 0.8)';
                    return 'rgba(252, 165, 165, 0.8)';
                });

                const borderColors = values.map(value => {
                    if (value > 0) return '#16a34a';
                    return '#dc2626';
                });

                // Safely update chart data
                if (chart.data) {
                    chart.data.labels = labels;
                    if (chart.data.datasets[0]) {
                        chart.data.datasets[0].data = values;
                        chart.data.datasets[0].backgroundColor = backgroundColors;
                        chart.data.datasets[0].borderColor = borderColors;
                        // Store raw data for tooltip (deep clone to avoid Alpine reactivity)
                        chart.data.datasets[0]._rawData = JSON.parse(JSON.stringify(latestData));
                    }

                    // CRITICAL: Use queueMicrotask to break Alpine reactivity cycle
                    queueMicrotask(() => {
                        try {
                            if (chart && chart.update && typeof chart.update === 'function') {
                                chart.update('none');
                            }
                        } catch (updateError) {
                            console.error('❌ Chart update error:', updateError);
                        }
                    });
                }
            } catch (error) {
                console.error('❌ Error updating aggregate chart:', error);
            }
        },

        refresh() {
            this.loadData();
        },

        formatRate(value) {
            if (value === null || value === undefined) return 'N/A';
            const percent = (value * 100).toFixed(4);
            return (value >= 0 ? '+' : '') + percent + '%';
        }
    };
}
</script>

