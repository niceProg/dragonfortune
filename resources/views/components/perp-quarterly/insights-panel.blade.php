{{--
    Komponen: Perp-Quarterly Spread Insights Panel
    Menampilkan trading insights dan market structure analysis

    Props:
    - $symbol: string (default: 'BTC')
    - $exchange: string (default: 'Binance')
--}}

<div class="df-panel p-3 h-100"
     x-data="spreadInsightsPanel('{{ $symbol ?? 'BTC' }}', '{{ $exchange ?? 'Binance' }}')">
    <h5 class="mb-3">💡 Trading Insights</h5>

    <div class="d-flex flex-column gap-3">
        <!-- Market Structure Summary -->
        <div class="stat-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-secondary">Market Structure</span>
                <span class="badge" :class="getStructureBadge()" x-text="marketStructure">Loading...</span>
            </div>
            <div class="h5 mb-0" :class="getStructureColor()" x-text="structureDescription">
                --
            </div>
            <div class="small text-secondary mt-1" x-text="structureInterpretation">
                Analyzing spread structure...
            </div>
        </div>

        <hr class="my-2">

        <!-- Trend Indicator -->
        <div class="stat-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-secondary">Spread Trend</span>
                <span class="badge" :class="getTrendBadge()" x-text="trendDirection">--</span>
            </div>
            <div class="h6 mb-0">
                <template x-if="trendChange !== null">
                    <span :class="getTrendColor()">
                        <span x-text="formatBPS(trendChange)">--</span> change
                    </span>
                </template>
                <template x-if="trendChange === null">
                    <span class="text-secondary">No trend data</span>
                </template>
            </div>
            <div class="small text-secondary mt-1" x-text="trendInterpretation">
                Calculating trend...
            </div>
        </div>

        <hr class="my-2">

        <!-- Arbitrage Opportunity Indicator -->
        <div class="stat-item">
            <div class="small text-secondary mb-2">Arbitrage Opportunity</div>
            <div class="progress mb-2" style="height: 20px;">
                <div class="progress-bar" :class="getArbitrageBarClass()"
                     :style="'width: ' + arbitrageScore + '%'" role="progressbar">
                    <span class="small fw-semibold" x-text="arbitrageScore + '%'">0%</span>
                </div>
            </div>
            <div class="small" x-text="arbitrageMessage">
                Analyzing arbitrage potential...
            </div>
        </div>

        <hr class="my-2">

        <!-- Key Metrics -->
        <div class="stat-item">
            <div class="small text-secondary mb-2">Key Metrics</div>
            <div class="row g-2">
                <div class="col-6">
                    <div class="small text-secondary">Avg Spread</div>
                    <div class="fw-semibold" :class="getSpreadColor(avgSpread)" x-text="formatBPS(avgSpread)">
                        --
                    </div>
                </div>
                <div class="col-6">
                    <div class="small text-secondary">Volatility</div>
                    <div class="fw-semibold" x-text="formatBPS(volatility)">
                        --
                    </div>
                </div>
                <div class="col-6">
                    <div class="small text-secondary">Min Spread</div>
                    <div class="fw-semibold text-danger" x-text="formatBPS(minSpread)">
                        --
                    </div>
                </div>
                <div class="col-6">
                    <div class="small text-secondary">Max Spread</div>
                    <div class="fw-semibold text-success" x-text="formatBPS(maxSpread)">
                        --
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-2">

        <!-- Trading Strategy Suggestion -->
        <div class="alert mb-0" :class="getStrategyAlertClass()">
            <div class="d-flex align-items-start gap-2">
                <div x-text="getStrategyIcon()">💡</div>
                <div class="flex-grow-1">
                    <div class="fw-semibold small mb-1" x-text="getStrategyTitle()">Trading Strategy</div>
                    <div class="small" x-text="getStrategyMessage()">Loading strategy analysis...</div>
                </div>
            </div>
        </div>

        <hr class="my-2">

        <!-- Contract Info -->
        <div class="small text-secondary">
            <div class="mb-1">
                <strong>Perp:</strong> <span class="font-monospace" x-text="perpSymbol">--</span>
            </div>
            <div>
                <strong>Quarterly:</strong> <span class="font-monospace" x-text="quarterlySymbol">--</span>
            </div>
        </div>
    </div>
</div>

<script>
function spreadInsightsPanel(initialSymbol = 'BTC', initialExchange = 'Binance') {
    return {
        symbol: initialSymbol,
        quote: 'USDT',
        exchange: initialExchange,
        interval: '5m',
        perpSymbol: '', // Auto-generated if empty
        limit: '2000', // Data limit
        loading: false,

        // Analytics data
        marketStructure: null,
        structureDescription: null,
        structureInterpretation: null,
        trendDirection: null,
        trendChange: null,
        trendInterpretation: null,
        arbitrageScore: 0,
        arbitrageMessage: null,
        avgSpread: null,
        minSpread: null,
        maxSpread: null,
        volatility: null,
        perpSymbol: null,
        quarterlySymbol: null,
        insights: [],

        init() {
            console.log('🔍 Insights panel initialized');
            console.log('🔍 Initial values:', {
                avgSpread: this.avgSpread,
                marketStructure: this.marketStructure,
                trendDirection: this.trendDirection,
                arbitrageScore: this.arbitrageScore,
                perpSymbol: this.perpSymbol,
                quarterlySymbol: this.quarterlySymbol
            });
            
            // Load data immediately
            this.loadData();

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
            console.log('🔍 Insights loadData called');
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

                console.log('📡 Fetching Perp-Quarterly Insights:', params.toString());

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/perp-quarterly/analytics?${params}` : `/api/perp-quarterly/analytics?${params}`;

                console.log('📡 Insights URL:', url);

                const response = await fetch(url);
                console.log('📡 Insights Response status:', response.status);

                if (!response.ok) {
                    console.error('❌ Insights HTTP Error:', response.status, response.statusText);
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('📡 Raw Insights API response:', data);
                this.applyAnalytics(data);
                console.log('✅ Insights loaded');
            } catch (error) {
                console.error('❌ Error loading insights:', error);
                // Reset to null values - no dummy data
                this.avgSpread = null;
                this.marketStructure = null;
                this.structureDescription = null;
                this.structureInterpretation = null;
                this.trendDirection = null;
                this.trendChange = null;
                this.trendInterpretation = null;
                this.arbitrageScore = 0;
                this.arbitrageMessage = null;
                this.minSpread = null;
                this.maxSpread = null;
                this.volatility = null;
                this.perpSymbol = null;
                this.quarterlySymbol = null;
                this.insights = [];
            } finally {
                this.loading = false;
            }
        },

        applyAnalytics(data) {
            console.log('🔍 Insights applyAnalytics called with:', data);
            if (!data) {
                console.log('🔍 No data provided to applyAnalytics');
                return;
            }

            const currentSpreadValue = data.spread_bps?.current ?? 0;
            this.avgSpread = data.spread_bps?.avg ?? data.spread_bps?.average ?? null;
            this.minSpread = data.spread_bps?.min ?? null;
            this.maxSpread = data.spread_bps?.max ?? null;
            this.volatility = data.spread_bps?.std ?? data.spread_bps?.std_dev ?? null;

            console.log('🔍 Applied insights values:');
            console.log('- currentSpread:', currentSpreadValue);
            console.log('- avgSpread:', this.avgSpread);
            console.log('- minSpread:', this.minSpread);
            console.log('- maxSpread:', this.maxSpread);
            console.log('- volatility:', this.volatility);
            console.log('- marketStructure:', this.marketStructure);
            console.log('- trendDirection:', this.trendDirection);
            console.log('- arbitrageScore:', this.arbitrageScore);
            console.log('- perpSymbol:', this.perpSymbol);
            console.log('- quarterlySymbol:', this.quarterlySymbol);

            this.perpSymbol = data.perp_symbol || '--';
            this.quarterlySymbol = data.quarterly_symbol || '--';

            // Determine market structure
            if (currentSpreadValue > 50) {
                this.marketStructure = 'Strong Contango';
                this.structureDescription = 'Perp >> Quarterly';
                this.structureInterpretation = 'Perpetual trading significantly above quarterly. Market has strong bullish expectations.';
            } else if (currentSpreadValue > 0) {
                this.marketStructure = 'Contango';
                this.structureDescription = 'Perp > Quarterly';
                this.structureInterpretation = 'Normal contango structure. Market expects higher prices in future.';
            } else if (currentSpreadValue < -50) {
                this.marketStructure = 'Strong Backwardation';
                this.structureDescription = 'Quarterly >> Perp';
                this.structureInterpretation = 'Quarterly trading significantly above perpetual. Strong supply shortage or high demand.';
            } else if (currentSpreadValue < 0) {
                this.marketStructure = 'Backwardation';
                this.structureDescription = 'Quarterly > Perp';
                this.structureInterpretation = 'Backwardation structure. Supply shortage or convenience yield premium.';
            } else {
                this.marketStructure = 'Neutral';
                this.structureDescription = 'Perp ≈ Quarterly';
                this.structureInterpretation = 'Spread near zero. Market showing balanced expectations.';
            }

            // Trend - Calculate from current vs average spread
            const avgSpreadValue = data.spread_bps?.avg ?? data.spread_bps?.average ?? 0;
            const spreadDiff = currentSpreadValue - avgSpreadValue;
            
            if (Math.abs(spreadDiff) < 2) {
                this.trendDirection = '↔️ Stable';
                this.trendChange = 0;
                this.trendInterpretation = 'Spread showing stable behavior.';
            } else if (spreadDiff > 0) {
                this.trendDirection = '↗️ Widening';
                this.trendChange = spreadDiff;
                this.trendInterpretation = 'Spread is expanding. Divergence between contracts increasing.';
            } else {
                this.trendDirection = '↘️ Narrowing';
                this.trendChange = spreadDiff;
                this.trendInterpretation = 'Spread is converging. Normal behavior approaching expiry.';
            }

            // Calculate arbitrage score (0-100)
            const absSpread = Math.abs(currentSpreadValue);
            if (absSpread > 100) {
                this.arbitrageScore = 100;
                this.arbitrageMessage = '🔥 Very high arbitrage opportunity detected!';
            } else if (absSpread > 50) {
                this.arbitrageScore = 75;
                this.arbitrageMessage = 'Strong arbitrage potential. Monitor execution costs.';
            } else if (absSpread > 20) {
                this.arbitrageScore = 50;
                this.arbitrageMessage = 'Moderate arbitrage opportunity. Consider position sizing.';
            } else if (absSpread > 10) {
                this.arbitrageScore = 25;
                this.arbitrageMessage = 'Small arbitrage window. May not cover fees.';
            } else {
                this.arbitrageScore = 10;
                this.arbitrageMessage = 'Minimal arbitrage opportunity. Spread too tight.';
            }

            // Process insights from API
            console.log('🔍 Processing insights:', data.insights);
            this.insights = Array.isArray(data.insights) ? data.insights : [];
            console.log('🔍 Final insights:', this.insights);
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

        getStructureBadge() {
            const structure = this.marketStructure.toLowerCase();
            if (structure.includes('contango')) return 'text-bg-success';
            if (structure.includes('backwardation')) return 'text-bg-danger';
            return 'text-bg-secondary';
        },

        getStructureColor() {
            const structure = this.marketStructure.toLowerCase();
            if (structure.includes('contango')) return 'text-success';
            if (structure.includes('backwardation')) return 'text-danger';
            return 'text-secondary';
        },

        getTrendBadge() {
            const trend = (this.trendDirection || '').toLowerCase();
            if (trend.includes('widening')) return 'text-bg-warning';
            if (trend.includes('narrowing')) return 'text-bg-info';
            return 'text-bg-secondary';
        },

        getTrendColor() {
            if (this.trendChange === null) return 'text-secondary';
            if (this.trendChange > 0) return 'text-success';
            if (this.trendChange < 0) return 'text-danger';
            return 'text-secondary';
        },

        getArbitrageBarClass() {
            if (this.arbitrageScore >= 75) return 'bg-success';
            if (this.arbitrageScore >= 50) return 'bg-warning';
            if (this.arbitrageScore >= 25) return 'bg-info';
            return 'bg-secondary';
        },

        getStrategyAlertClass() {
            const structure = this.marketStructure.toLowerCase();
            const absSpread = Math.abs(this.avgSpread || 0);

            if (absSpread > 50) return 'alert-warning';
            if (structure.includes('contango')) return 'alert-success';
            if (structure.includes('backwardation')) return 'alert-danger';
            return 'alert-info';
        },

        getStrategyIcon() {
            const structure = this.marketStructure.toLowerCase();
            if (structure.includes('strong')) return '🚨';
            if (structure.includes('contango')) return '📈';
            if (structure.includes('backwardation')) return '📉';
            return '💡';
        },

        getStrategyTitle() {
            const structure = this.marketStructure.toLowerCase();
            if (structure.includes('strong contango')) return 'Strong Contango Strategy';
            if (structure.includes('contango')) return 'Contango Spread Strategy';
            if (structure.includes('strong backwardation')) return 'Strong Backwardation Strategy';
            if (structure.includes('backwardation')) return 'Backwardation Spread Strategy';
            return 'Neutral Market Strategy';
        },

        getStrategyMessage() {
            const structure = this.marketStructure.toLowerCase();
            const trend = (this.trendDirection || '').toLowerCase();

            if (structure.includes('strong contango')) {
                return 'Consider calendar spread: Short perpetual / Long quarterly. High funding cost on perp creates arbitrage opportunity. Monitor for convergence.';
            }

            if (structure.includes('contango')) {
                if (trend.includes('widening')) {
                    return 'Contango widening. Consider shorting perp or going long quarterly for convergence play. Watch funding rates.';
                }
                return 'Normal contango. Perp holders paying for leverage. Monitor for spread normalization opportunities.';
            }

            if (structure.includes('strong backwardation')) {
                return 'Unusual backwardation. Consider calendar spread: Long perpetual / Short quarterly. Supply shortage or high spot demand. Risk: Further divergence.';
            }

            if (structure.includes('backwardation')) {
                if (trend.includes('narrowing')) {
                    return 'Backwardation narrowing. Spread approaching normal. Consider scaling out of calendar positions.';
                }
                return 'Backwardation present. Quarterly premium indicates spot strength. Monitor for normalization.';
            }

            return 'Spread near neutral. Wait for clearer directional signal or convergence opportunity. Focus on other market factors.';
        },

        getInsightClass(severity) {
            if (severity === 'high') return 'alert-danger';
            if (severity === 'medium') return 'alert-warning';
            if (severity === 'low') return 'alert-info';
            return 'alert-secondary';
        },

        getInsightIcon(type) {
            if (type === 'contango') return '📈';
            if (type === 'backwardation') return '📉';
            if (type === 'arbitrage') return '💰';
            if (type === 'convergence') return '🎯';
            if (type === 'divergence') return '📊';
            return '💡';
        }
    };
}

// Export to window for Alpine.js
window.spreadInsightsPanel = spreadInsightsPanel;
</script>

<style>
.stat-item {
    padding: 0.75rem;
    border-radius: 0.5rem;
    background: rgba(var(--bs-light-rgb), 0.5);
    transition: all 0.2s ease;
}

.stat-item:hover {
    background: rgba(var(--bs-light-rgb), 0.8);
    transform: translateX(2px);
}
</style>

