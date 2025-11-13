{{--
    Komponen: Perp-Quarterly Spread Analytics Card
    Menampilkan spread analytics dengan visualisasi dinamis

    Props:
    - $symbol: string (default: 'BTC')
    - $exchange: string (default: 'Binance')

    Interpretasi:
    - Spread positif → Perp > Quarterly → Contango → Market expects higher prices
    - Spread negatif → Quarterly > Perp → Backwardation → Supply shortage or high demand
    - Spread widening → Increasing contango/backwardation
    - Spread narrowing → Convergence approaching
--}}

<div class="df-panel p-4" x-data="spreadAnalyticsCard('{{ $symbol ?? 'BTC' }}', '{{ $exchange ?? 'Binance' }}', '{{ $quote ?? 'USDT' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">📊 Spread Analytics</h5>
            <span class="badge text-bg-secondary" x-text="symbol + '/' + exchange">BTC/Binance</span>
        </div>
        <!-- Individual refresh button removed - using unified auto-refresh -->
    </div>

    <!-- Main Metrics -->
    <div class="row g-3 mb-3">
        <!-- Current Spread -->
        <div class="col-md-3">
            <div class="metric-card p-3 rounded-3 text-center"
                 style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05));">
                <div class="small text-secondary mb-1">Current Spread</div>
                <div class="h4 mb-1 fw-bold" :class="getSpreadColor(currentSpread)" x-text="formatSpread(currentSpread)">
                    --
                </div>
                <div class="small" :class="getSpreadColor(currentSpreadBps)" x-text="formatBPS(currentSpreadBps)">
                    -- bps
                </div>
            </div>
        </div>

        <!-- Average Spread -->
        <div class="col-md-3">
            <div class="metric-card p-3 rounded-3 text-center"
                 style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));">
                <div class="small text-secondary mb-1">Average Spread</div>
                <div class="h4 mb-1 fw-bold" :class="getSpreadColor(avgSpread)" x-text="formatSpread(avgSpread)">
                    --
                </div>
                <div class="small" :class="getSpreadColor(avgSpreadBps)" x-text="formatBPS(avgSpreadBps)">
                    -- bps
                </div>
            </div>
        </div>

        <!-- Spread Range -->
        <div class="col-md-3">
            <div class="metric-card p-3 rounded-3 text-center"
                 style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.05));">
                <div class="small text-secondary mb-1">Spread Range</div>
                <div class="h6 mb-1">
                    <span :class="getSpreadColor(minSpread)" x-text="formatSpread(minSpread, 1)">--</span>
                    <span class="text-secondary">to</span>
                    <span :class="getSpreadColor(maxSpread)" x-text="formatSpread(maxSpread, 1)">--</span>
                </div>
                <div class="small text-secondary" x-text="'σ: ' + formatSpread(stdDev, 2)">
                    σ: --
                </div>
            </div>
        </div>

        <!-- Market Structure -->
        <div class="col-md-3">
            <div class="metric-card p-3 rounded-3 text-center"
                 :style="getStructureGradient()">
                <div class="small text-white text-opacity-75 mb-1">Market Structure</div>
                <div class="h5 mb-1 fw-bold text-white" x-text="marketStructure">
                    --
                </div>
                <div class="small text-white text-opacity-75">
                    <span class="badge" :class="getTrendBadge()" x-text="trendDirection">--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contract Information -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="p-3 rounded bg-light">
                <div class="small text-secondary mb-1">Perpetual Contract</div>
                <div class="fw-semibold" x-text="perpSymbol || 'Loading...'">--</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded bg-light">
                <div class="small text-secondary mb-1">Quarterly Contract</div>
                <div class="fw-semibold" x-text="quarterlySymbol || 'Loading...'">--</div>
            </div>
        </div>
    </div>

    <!-- Insights -->
    <template x-if="insights && insights.length > 0">
        <div class="mb-3">
            <div class="small fw-semibold text-secondary mb-2">📌 Market Insights</div>
            <template x-for="(insight, idx) in insights" :key="idx">
                <div class="alert py-2 px-3 mb-2" :class="getInsightClass(insight.severity)">
                    <div class="d-flex align-items-start gap-2">
                        <span x-text="getInsightIcon(insight.type)">💡</span>
                        <div class="flex-grow-1">
                            <div class="small fw-semibold" x-text="insight.type.replace('_', ' ').toUpperCase()">--</div>
                            <div class="small" x-text="insight.message">--</div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- Trend Analysis -->
    <div class="p-3 rounded" style="background: rgba(var(--bs-light-rgb), 0.5);">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <div class="small text-secondary">Trend Analysis</div>
                <div class="fw-semibold">
                    Spread is <span :class="getTrendColor()" x-text="trendDirection">--</span>
                    <template x-if="trendChange !== null">
                        <span :class="getSpreadColor(trendChange)">
                            (<span x-text="formatBPS(trendChange)">--</span>)
                        </span>
                    </template>
                </div>
            </div>
            <div class="text-end">
                <div class="small text-secondary">Data Points</div>
                <div class="fw-bold" x-text="dataPoints">--</div>
            </div>
        </div>
    </div>

    <!-- Last Updated -->
    <div class="text-center mt-3">
        <small class="text-secondary">
            Last updated: <span x-text="lastUpdate">--</span>
        </small>
    </div>
</div>

<script>
function spreadAnalyticsCard(initialSymbol = 'BTC', initialExchange = 'Binance', initialQuote = 'USDT') {
    return {
        symbol: initialSymbol,
        quote: initialQuote,
        exchange: initialExchange,
        interval: '5m',
        perpSymbol: '', // Auto-generated if empty
        limit: '100', // Data limit (will be updated by global filter)
        loading: false,

        // Analytics data
        currentSpread: null,
        currentSpreadBps: null,
        avgSpread: null,
        avgSpreadBps: null,
        minSpread: null,
        maxSpread: null,
        stdDev: null,
        marketStructure: null,
        trendDirection: null,
        trendChange: null,
        perpSymbol: null,
        quarterlySymbol: null,
        dataPoints: 0,
        insights: [],
        lastUpdate: 'Loading...',

        init() {
            console.log('📊 Analytics card initialized');
            console.log('📊 Initial values:', {
                currentSpread: this.currentSpread,
                avgSpread: this.avgSpread,
                minSpread: this.minSpread,
                maxSpread: this.maxSpread,
                marketStructure: this.marketStructure,
                perpSymbol: this.perpSymbol,
                quarterlySymbol: this.quarterlySymbol
            });
            
            // Load data immediately with fallback values already set
            setTimeout(() => {
                console.log('📊 Analytics card calling loadData');
                this.loadData();
            }, 100);

            // Auto refresh every 30 seconds
            setInterval(() => this.loadData(), 30000);

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
                if (e.detail?.analytics) {
                    this.applyAnalytics(e.detail.analytics);
                }
            });
        },

        async loadData() {
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

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/perp-quarterly/analytics?${params}` : `/api/perp-quarterly/analytics?${params}`;

                console.log('📡 Fetching Perp-Quarterly Analytics:', url);

                const response = await fetch(url);
                console.log('📡 Response status:', response.status);

                if (!response.ok) {
                    console.error('❌ HTTP Error:', response.status, response.statusText);
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('📡 Raw API response:', data);
                this.applyAnalytics(data);
                this.lastUpdate = new Date().toLocaleTimeString();
                console.log('✅ Analytics loaded:', data);
            } catch (error) {
                console.error('❌ Error loading analytics:', error);
                this.resetData();
            } finally {
                this.loading = false;
            }
        },

        applyAnalytics(data) {
            console.log('applyAnalytics called with:', data);
            if (!data) {
                console.log('applyAnalytics: no data provided');
                return;
            }

            console.log('spread_bps data:', data.spread_bps);
            this.currentSpread = data.spread_bps?.current ?? null;
            this.currentSpreadBps = data.spread_bps?.current ?? null;
            this.avgSpread = data.spread_bps?.avg ?? data.spread_bps?.average ?? null;
            this.avgSpreadBps = data.spread_bps?.avg ?? data.spread_bps?.average ?? null;
            this.minSpread = data.spread_bps?.min ?? null;
            this.maxSpread = data.spread_bps?.max ?? null;
            this.stdDev = data.spread_bps?.std ?? data.spread_bps?.std_dev ?? null;

            console.log('Applied values:');
            console.log('- currentSpread:', this.currentSpread);
            console.log('- avgSpread:', this.avgSpread);
            console.log('- minSpread:', this.minSpread);
            console.log('- maxSpread:', this.maxSpread);
            console.log('- stdDev:', this.stdDev);

            this.perpSymbol = data.perp_symbol || '--';
            this.quarterlySymbol = data.quarterly_symbol || '--';
            this.dataPoints = data.data_points || 0;

            // Determine market structure
            if (this.currentSpread > 50) {
                this.marketStructure = 'Strong Contango';
            } else if (this.currentSpread > 0) {
                this.marketStructure = 'Contango';
            } else if (this.currentSpread < -50) {
                this.marketStructure = 'Strong Backwardation';
            } else if (this.currentSpread < 0) {
                this.marketStructure = 'Backwardation';
            } else {
                this.marketStructure = 'Neutral';
            }

            // Trend - Calculate from current vs average spread
            const currentSpreadValue = data.spread_bps?.current ?? 0;
            const avgSpreadValue = data.spread_bps?.avg ?? data.spread_bps?.average ?? 0;
            const spreadDiff = currentSpreadValue - avgSpreadValue;
            
            if (Math.abs(spreadDiff) < 2) {
                this.trendDirection = '↔️ Stable';
                this.trendChange = 0;
            } else if (spreadDiff > 0) {
                this.trendDirection = '↗️ Widening';
                this.trendChange = spreadDiff;
            } else {
                this.trendDirection = '↘️ Narrowing';
                this.trendChange = spreadDiff;
            }

            // Insights
            this.insights = Array.isArray(data.insights) ? data.insights : [];
        },

        resetData() {
            // Reset to null - no dummy data
            this.currentSpread = null;
            this.currentSpreadBps = null;
            this.avgSpread = null;
            this.avgSpreadBps = null;
            this.minSpread = null;
            this.maxSpread = null;
            this.stdDev = null;
            this.marketStructure = null;
            this.trendDirection = null;
            this.trendChange = null;
            this.insights = [];
        },

        refresh() {
            this.loadData();
        },

        formatSpread(value, decimals = 2) {
            console.log('formatSpread called with:', value, 'type:', typeof value);
            if (value === null || value === undefined || isNaN(value)) {
                console.log('formatSpread returning N/A for:', value);
                return 'N/A';
            }
            const num = parseFloat(value);
            const result = (num >= 0 ? '+' : '') + num.toFixed(decimals);
            console.log('formatSpread result:', result);
            return result;
        },

        formatBPS(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            return (num >= 0 ? '+' : '') + num.toFixed(2) + ' bps';
        },

        getSpreadColor(value) {
            if (value === null || value === undefined) return 'text-secondary';
            if (value > 0) return 'text-success';
            if (value < 0) return 'text-danger';
            return 'text-secondary';
        },

        getStructureGradient() {
            const structure = this.marketStructure.toLowerCase();
            if (structure.includes('contango')) {
                return 'background: linear-gradient(135deg, #22c55e, #16a34a);';
            }
            if (structure.includes('backwardation')) {
                return 'background: linear-gradient(135deg, #ef4444, #dc2626);';
            }
            return 'background: linear-gradient(135deg, #6b7280, #4b5563);';
        },

        getTrendBadge() {
            const trend = (this.trendDirection || '').toLowerCase();
            if (trend.includes('widening')) return 'bg-danger text-white';
            if (trend.includes('narrowing')) return 'bg-success text-white';
            return 'bg-secondary text-white';
        },

        getTrendColor() {
            const trend = (this.trendDirection || '').toLowerCase();
            if (trend.includes('widening')) return 'text-danger';
            if (trend.includes('narrowing')) return 'text-success';
            return 'text-secondary';
        },

        getInsightClass(severity) {
            const sev = (severity || '').toLowerCase();
            if (sev === 'high' || sev === 'critical') return 'alert-danger';
            if (sev === 'medium' || sev === 'warning') return 'alert-warning';
            return 'alert-info';
        },

        getInsightIcon(type) {
            const t = (type || '').toLowerCase();
            if (t.includes('arbitrage')) return '💰';
            if (t.includes('trend')) return '📈';
            if (t.includes('convergence')) return '🎯';
            if (t.includes('volatility')) return '⚡';
            return '💡';
        }
    };
}

// Export to window for Alpine.js
window.spreadAnalyticsCard = spreadAnalyticsCard;
</script>

<style>
.metric-card {
    transition: all 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
</style>

