{{--
    Liquidations Analytics Summary Component
    Displays:
    - Total liquidation summary (long/short/total USD)
    - Long/Short ratio
    - Cascade detection events
    - AI-powered insights & warnings
--}}

<div class="df-panel p-4 h-100"
     x-data="liquidationsAnalyticsSummary()"
     x-init="init()">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">📊 Analytics Summary</h5>
        <span x-show="loading" class="spinner-border spinner-border-sm text-primary"></span>
    </div>

    <!-- Liquidation Summary Stats -->
    <div class="row g-3 mb-4">
        <!-- Total Liquidations -->
        <div class="col-md-4">
            <div class="stat-card bg-primary bg-opacity-10 p-3 rounded">
                <div class="small text-secondary mb-1">Total Liquidations</div>
                <div class="h4 mb-0 fw-bold text-primary" x-text="formatUSD(summary.total_usd)">--</div>
                <div class="small text-secondary mt-1" x-text="dataPoints + ' data points'">--</div>
            </div>
        </div>

        <!-- Long Liquidations -->
        <div class="col-md-4">
            <div class="stat-card bg-danger bg-opacity-10 p-3 rounded">
                <div class="small text-secondary mb-1">Long Liquidations</div>
                <div class="h4 mb-0 fw-bold text-danger" x-text="formatUSD(summary.total_long_usd)">--</div>
                <div class="small" :class="summary.long_short_ratio >= 1 ? 'text-danger' : 'text-secondary'">
                    <span x-text="((summary.total_long_usd / summary.total_usd) * 100).toFixed(1)">--</span>% of total
                </div>
            </div>
        </div>

        <!-- Short Liquidations -->
        <div class="col-md-4">
            <div class="stat-card bg-success bg-opacity-10 p-3 rounded">
                <div class="small text-secondary mb-1">Short Liquidations</div>
                <div class="h4 mb-0 fw-bold text-success" x-text="formatUSD(summary.total_short_usd)">--</div>
                <div class="small" :class="summary.long_short_ratio < 1 ? 'text-success' : 'text-secondary'">
                    <span x-text="((summary.total_short_usd / summary.total_usd) * 100).toFixed(1)">--</span>% of total
                </div>
            </div>
        </div>
    </div>

    <!-- Long/Short Ratio Meter -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-secondary">Long/Short Ratio</span>
            <span class="badge" :class="getRatioBadgeClass(summary.long_short_ratio)">
                <span x-text="summary.long_short_ratio?.toFixed(2) || 'N/A'">--</span>x
            </span>
        </div>

        <!-- Visual ratio bar -->
        <div class="position-relative">
            <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-danger" role="progressbar"
                     :style="'width: ' + getLongPercentage() + '%'"
                     :aria-valuenow="getLongPercentage()">
                    <span class="fw-semibold small" x-show="getLongPercentage() > 20"
                          x-text="getLongPercentage().toFixed(0) + '%'"></span>
                </div>
                <div class="progress-bar bg-success" role="progressbar"
                     :style="'width: ' + getShortPercentage() + '%'"
                     :aria-valuenow="getShortPercentage()">
                    <span class="fw-semibold small" x-show="getShortPercentage() > 20"
                          x-text="getShortPercentage().toFixed(0) + '%'"></span>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <small class="text-danger">Long Squeeze Risk</small>
                <small class="text-success">Short Squeeze Risk</small>
            </div>
        </div>
    </div>

    <!-- Cascade Detection -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small text-secondary">Cascade Events</span>
            <span class="badge" :class="getCascadeBadgeClass(cascade.cascade_count)">
                <span x-text="cascade.cascade_count || 0">0</span> detected
            </span>
        </div>

        <div class="p-3 rounded" :class="getCascadeAlertClass(cascade.cascade_count)">
            <div class="d-flex align-items-start gap-2">
                <span x-text="getCascadeIcon(cascade.cascade_count)" style="font-size: 1.5rem;">⚡</span>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1" x-text="getCascadeTitle(cascade.cascade_count)">
                        Cascade Detection
                    </div>
                    <div class="small" x-text="getCascadeMessage(cascade.cascade_count, cascade.threshold_usd)">
                        Analyzing liquidation cascades...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Liquidation Events -->
    <div class="mb-4" x-show="topEvents.length > 0">
        <div class="small text-secondary mb-2">🔥 Largest Liquidations</div>
        <div class="list-group list-group-flush">
            <template x-for="(event, index) in topEvents.slice(0, 3)" :key="index">
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-warning text-dark me-2">#<span x-text="index + 1">1</span></span>
                        <span class="small text-secondary" x-text="formatTimestamp(event.ts)">--</span>
                    </div>
                    <div class="fw-bold text-danger" x-text="formatUSD(event.amount_usd)">--</div>
                </div>
            </template>
        </div>
    </div>

    <!-- AI Insights -->
    <div x-show="insights.length > 0">
        <div class="small text-secondary mb-2">💡 AI Insights</div>
        <div class="d-flex flex-column gap-2">
            <template x-for="(insight, index) in insights" :key="index">
                <div class="alert mb-0 py-2 px-3" :class="getInsightAlertClass(insight.severity)">
                    <div class="d-flex align-items-start gap-2">
                        <span x-text="getInsightIcon(insight.type)"></span>
                        <div class="small flex-grow-1" x-text="insight.message">Insight message</div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- No Data State -->
    <div x-show="!loading && !hasData" class="text-center py-5">
        <div class="text-secondary mb-2" style="font-size: 3rem;">📊</div>
        <div class="text-secondary">No analytics data available</div>
        <button class="btn btn-sm btn-primary mt-2" @click="loadData()">Retry</button>
    </div>
</div>

<script>
function liquidationsAnalyticsSummary() {
    return {
        symbol: 'BTC',
        exchange: '',
        interval: '1m',
        loading: false,
        hasData: false,

        // Analytics data
        summary: {
            total_usd: 0,
            total_long_usd: 0,
            total_short_usd: 0,
            long_short_ratio: 0,
        },
        cascade: {
            cascade_count: 0,
            threshold_usd: 0,
        },
        topEvents: [],
        insights: [],
        dataPoints: 0,

        init() {
            this.symbol = this.$root?.globalSymbol || 'BTC';
            this.exchange = this.$root?.globalExchange || '';
            this.interval = this.$root?.globalInterval || '1m';

            // Listen for overview ready
            window.addEventListener('liquidations-overview-ready', (e) => {
                this.applyOverview(e.detail);
            });

            // Listen for filter changes
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.exchange = e.detail?.exchange || '';
                this.interval = e.detail?.interval || this.interval;
                this.loadData();
            });

            window.addEventListener('exchange-changed', (e) => {
                this.exchange = e.detail?.exchange || '';
                this.loadData();
            });

            window.addEventListener('interval-changed', (e) => {
                this.interval = e.detail?.interval || this.interval;
                this.loadData();
            });

            window.addEventListener('refresh-all', () => {
                this.loadData();
            });

            // Initial load with delay to ensure DOM is ready
            setTimeout(() => {
                if (this.$root?.overview) {
                    this.applyOverview(this.$root.overview);
                } else {
                    this.loadData();
                }
            }, 100);
        },

        applyOverview(overview) {
            if (!overview?.analytics) return;

            const analytics = overview.analytics;
            this.summary = analytics.liquidation_summary || this.summary;
            this.cascade = analytics.cascade_detection || this.cascade;
            this.topEvents = analytics.top_events || [];
            this.insights = analytics.insights || [];
            this.dataPoints = analytics.data_points || 0;
            this.hasData = true;
        },

        async loadData() {
            // Data will be loaded by parent controller
            // This method is just for manual refresh
            this.loading = true;
            setTimeout(() => {
                this.loading = false;
            }, 1000);
        },

        // Formatting utilities
        formatUSD(value) {
            if (value === null || value === undefined) return 'N/A';
            const num = parseFloat(value);
            if (isNaN(num)) return 'N/A';

            if (num >= 1e9) return '$' + (num / 1e9).toFixed(2) + 'B';
            if (num >= 1e6) return '$' + (num / 1e6).toFixed(2) + 'M';
            if (num >= 1e3) return '$' + (num / 1e3).toFixed(2) + 'K';
            return '$' + num.toFixed(2);
        },

        formatTimestamp(timestamp) {
            if (!timestamp) return 'N/A';
            const date = new Date(timestamp);
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            });
        },

        // Ratio calculations
        getLongPercentage() {
            const total = this.summary.total_long_usd + this.summary.total_short_usd;
            if (total === 0) return 50;
            return (this.summary.total_long_usd / total) * 100;
        },

        getShortPercentage() {
            return 100 - this.getLongPercentage();
        },

        getRatioBadgeClass(ratio) {
            if (ratio > 2) return 'text-bg-danger';
            if (ratio > 1.5) return 'text-bg-warning';
            if (ratio < 0.5) return 'text-bg-success';
            if (ratio < 0.67) return 'text-bg-info';
            return 'text-bg-secondary';
        },

        // Cascade styling
        getCascadeBadgeClass(count) {
            if (count > 50) return 'text-bg-danger';
            if (count > 20) return 'text-bg-warning';
            if (count > 0) return 'text-bg-info';
            return 'text-bg-secondary';
        },

        getCascadeAlertClass(count) {
            if (count > 50) return 'bg-danger bg-opacity-10 border border-danger';
            if (count > 20) return 'bg-warning bg-opacity-10 border border-warning';
            if (count > 0) return 'bg-info bg-opacity-10 border border-info';
            return 'bg-secondary bg-opacity-10';
        },

        getCascadeIcon(count) {
            if (count > 50) return '🚨';
            if (count > 20) return '⚠️';
            if (count > 0) return '⚡';
            return '✅';
        },

        getCascadeTitle(count) {
            if (count > 50) return 'Extreme Cascade Alert!';
            if (count > 20) return 'High Cascade Activity';
            if (count > 0) return 'Cascade Events Detected';
            return 'No Cascades Detected';
        },

        getCascadeMessage(count, threshold) {
            const thresholdStr = this.formatUSD(threshold);
            if (count > 50) {
                return `${count} cascade events with threshold ${thresholdStr}. Extreme volatility expected!`;
            }
            if (count > 20) {
                return `${count} cascade events detected with threshold ${thresholdStr}. Watch for volatility.`;
            }
            if (count > 0) {
                return `${count} cascade event(s) with threshold ${thresholdStr}. Minor liquidation chains detected.`;
            }
            return 'Market stable with no liquidation cascades detected.';
        },

        // Insight styling
        getInsightAlertClass(severity) {
            if (severity === 'high') return 'alert-danger';
            if (severity === 'medium') return 'alert-warning';
            return 'alert-info';
        },

        getInsightIcon(type) {
            if (type === 'long_dominated_liquidations') return '📉';
            if (type === 'short_dominated_liquidations') return '📈';
            if (type === 'liquidation_cascades') return '⚡';
            return '💡';
        },
    };
}
</script>

<style scoped>
.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.progress {
    background-color: rgba(148, 163, 184, 0.1);
}
</style>

