{{--
    Komponen: Analytics Insights Cards
    Menampilkan summary, trend, extremes dari /api/funding-rate/analytics

    Props:
    - $symbol: string (default: 'BTC')
--}}

<div class="df-panel p-3" x-data="analyticsInsights('{{ $symbol ?? 'BTC' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📈 Analytics Insights</h5>
        <button class="btn btn-sm btn-outline-secondary" @click="refresh()" :disabled="loading">
            <span x-show="!loading">🔄</span>
            <span x-show="loading" class="spinner-border spinner-border-sm"></span>
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-3" x-show="summary">
        <div class="col-md-6">
            <div class="p-3 rounded bg-light">
                <div class="small text-secondary mb-1">Current Funding Rate</div>
                <div class="h4 mb-1" :class="summary?.current >= 0 ? 'text-success' : 'text-danger'" x-text="formatRate(summary?.current)">--</div>
                <div class="small">
                    <span class="text-secondary">Avg:</span> <span x-text="formatRate(summary?.average)">--</span>
                    <span class="text-secondary mx-2">|</span>
                    <span class="text-secondary">Median:</span> <span x-text="formatRate(summary?.median)">--</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded bg-light">
                <div class="small text-secondary mb-1">Volatility</div>
                <div class="h4 mb-1" x-text="summary?.volatility || 'N/A'">--</div>
                <div class="small">
                    <span class="text-secondary">Std Dev:</span> <span x-text="formatRate(summary?.std_dev)">--</span>
                    <span class="text-secondary mx-2">|</span>
                    <span class="text-secondary">Range:</span> <span x-text="formatRate(summary?.range)">--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Trend Card -->
    <div class="alert mb-3" :class="getTrendAlertClass()" x-show="trend">
        <div class="d-flex align-items-start gap-2">
            <div x-text="getTrendIcon()">📊</div>
            <div class="flex-grow-1">
                <div class="fw-semibold small mb-1">
                    Trend: <span x-text="trend?.direction || 'Unknown'" class="text-uppercase"></span>
                </div>
                <div class="small">
                    Recent average (<span x-text="formatRate(trend?.recent_avg)">--</span>)
                    <template x-if="trend && trend.direction === 'increasing'">is higher</template>
                    <template x-if="trend && trend.direction === 'decreasing'">is lower</template>
                    than older average (<span x-text="formatRate(trend?.older_avg)">--</span>).
                    Change: <span x-text="formatRate(trend?.change)">--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Extremes Alert -->
    <div class="alert alert-danger mb-3" x-show="extremes && extremes.percentage > 3">
        <div class="d-flex align-items-start gap-2">
            <div>🚨</div>
            <div class="flex-grow-1">
                <div class="fw-semibold small mb-1">Extreme Funding Detected</div>
                <div class="small">
                    <span x-text="extremes?.count || 0">0</span> extreme events
                    (<span x-text="(extremes?.percentage || 0).toFixed(1)">0</span>% of data)
                    detected beyond threshold of <span x-text="formatRate(extremes?.threshold)">--</span>.
                    High volatility and potential squeeze risk.
                </div>
            </div>
        </div>
    </div>

    <!-- API Insights -->
    <div class="mb-0" x-show="insights && insights.length > 0">
        <div class="small text-secondary mb-2 fw-semibold">💡 Trading Insights</div>
        <template x-for="insight in insights" :key="insight.type">
            <div class="alert mb-2" :class="getInsightAlertClass(insight)">
                <div class="small" x-text="insight.message"></div>
            </div>
        </template>
    </div>

    <!-- No Data State -->
    <div class="text-center py-4 text-secondary" x-show="!summary && !loading">
        No analytics data available. Try changing filters.
    </div>
</div>

<script>
function analyticsInsights(initialSymbol = 'BTC') {
    return {
        symbol: initialSymbol,
        loading: false,
        summary: null,
        bias: null,
        trend: null,
        extremes: null,
        insights: [],

        async init() {
            this.symbol = this.$root?.globalSymbol || initialSymbol;
            await this.loadData();

            // Listen to global filter changes
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.loadData();
            });
            window.addEventListener('refresh-all', () => this.loadData());

            // Listen to overview event
            window.addEventListener('funding-overview-ready', (e) => {
                const o = e.detail?.analytics;
                if (o) {
                    this.summary = o.summary || null;
                    this.bias = o.bias || null;
                    this.trend = o.trend || null;
                    this.extremes = o.extremes || null;
                    this.insights = o.insights || [];
                }
            });
        },

        async loadData() {
            this.loading = true;
            try {
                const pair = `${this.symbol}USDT`;
                const params = new URLSearchParams({
                    symbol: pair,
                    limit: '2000'
                });
                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/funding-rate/analytics?${params}` : `/api/funding-rate/analytics?${params}`;

                const response = await fetch(url);
                const data = await response.json();

                this.summary = data.summary || null;
                this.bias = data.bias || null;
                this.trend = data.trend || null;
                this.extremes = data.extremes || null;
                this.insights = data.insights || [];

                console.log('✅ Analytics loaded:', data);
            } catch (error) {
                console.error('❌ Error loading analytics:', error);
            } finally {
                this.loading = false;
            }
        },

        refresh() {
            this.loadData();
        },

        getTrendAlertClass() {
            if (!this.trend || !this.trend.direction) return 'alert-secondary';
            if (this.trend.direction === 'increasing') return 'alert-success';
            if (this.trend.direction === 'decreasing') return 'alert-danger';
            return 'alert-secondary';
        },

        getTrendIcon() {
            if (!this.trend || !this.trend.direction) return '📊';
            if (this.trend.direction === 'increasing') return '📈';
            if (this.trend.direction === 'decreasing') return '📉';
            return '➡️';
        },

        getInsightAlertClass(insight) {
            if (insight.severity === 'high') return 'alert-danger';
            if (insight.severity === 'medium') return 'alert-warning';
            return 'alert-info';
        },

        formatRate(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const percent = (parseFloat(value) * 100).toFixed(4);
            return (parseFloat(value) >= 0 ? '+' : '') + percent + '%';
        }
    };
}
</script>

