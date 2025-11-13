{{--
    Exchange Comparison Component
    Compares liquidation volumes across exchanges for different time ranges
    Uses exchange-list API endpoint: /api/liquidations/exchange-list
--}}

<div class="df-panel p-4 h-100"
     x-data="liquidationsExchangeComparisonTable()"
     x-init="init()">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-0">🏦 Binance Data</h5>
            <small class="text-secondary">Liquidation volume from Binance exchange</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm" style="width: 100px;" x-model="selectedRange" @change="loadData()">
                <option value="1h">1H</option>
                <option value="4h">4H</option>
                <option value="12h">12H</option>
                <option value="24h">24H</option>
            </select>
            <span x-show="loading" class="spinner-border spinner-border-sm text-primary"></span>
        </div>
    </div>

    <!-- Exchange Comparison Table -->
    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
        <table class="table table-sm table-striped">
            <thead class="sticky-top bg-white">
                <tr>
                    <th>Time Range</th>
                    <th class="text-end">Total USD</th>
                    <th class="text-end text-danger">Long USD</th>
                    <th class="text-end text-success">Short USD</th>
                    <th class="text-end">Ratio</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(exchange, index) in displayedExchanges" :key="'exchange-' + index + '-' + exchange.exchange">
                    <tr>
                        <td>
                            <span class="badge bg-primary" x-text="selectedRange.toUpperCase()">1H</span>
                        </td>
                        <td class="text-end fw-bold" x-text="formatUSD(exchange.liquidation_usd)">--</td>
                        <td class="text-end text-danger" x-text="formatUSD(exchange.long_liquidation_usd)">--</td>
                        <td class="text-end text-success" x-text="formatUSD(exchange.short_liquidation_usd)">--</td>
                        <td class="text-end">
                            <span class="badge" :class="getLongShortRatioClass(exchange)" x-text="getLongShortRatio(exchange)">--</span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Summary Stats -->
    <div class="mt-3 pt-3 border-top">
        <div class="row g-2 small">
            <div class="col-4">
                <div class="text-secondary">Total Volume</div>
                <div class="fw-bold" x-text="formatUSD(totalVolume)">--</div>
            </div>
            <div class="col-4">
                <div class="text-secondary">Total Long</div>
                <div class="fw-bold text-danger" x-text="formatUSD(totalLong)">--</div>
            </div>
            <div class="col-4">
                <div class="text-secondary">Total Short</div>
                <div class="fw-bold text-success" x-text="formatUSD(totalShort)">--</div>
            </div>
        </div>
    </div>

    <!-- No Data State -->
    <div x-show="!loading && displayedExchanges.length === 0" class="text-center py-4">
        <div class="text-secondary mb-2" style="font-size: 3rem;">🏦</div>
        <div class="text-secondary">No Binance liquidation data available</div>
        <div class="small text-muted mt-2">Try changing symbol or refresh data</div>
    </div>
</div>

<script>
function liquidationsExchangeComparisonTable() {
    return {
        loading: false,
        exchangeData: [],
        selectedRange: '1h',
        displayedExchanges: [],
        totalVolume: 0,
        totalLong: 0,
        totalShort: 0,

        async init() {
            console.log('📊 Exchange Comparison: Initializing component');

            // Listen for filter changes
            window.addEventListener('symbol-changed', () => {
                this.loadData();
            });

            window.addEventListener('exchange-changed', () => {
                this.loadData();
            });

            window.addEventListener('refresh-all', () => {
                this.loadData();
            });

            // Watch selected range
            this.$watch('selectedRange', () => {
                this.loadData();
            });

            // Initial load
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            console.log('📊 Exchange Comparison: Loading data...');

            try {
                // Get current symbol from global state
                const symbol = this.$root?.globalSymbol || 'BTC';

                // Build API URL
                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || "").trim();
                const getApiBaseUrl = () => {
                    if (configuredBase) {
                        return configuredBase.endsWith("/") ? configuredBase.slice(0, -1) : configuredBase;
                    }
                    return "";
                };
                const apiUrl = `${getApiBaseUrl()}/api/liquidations/exchange-list?symbol=${symbol}&range_str=${this.selectedRange}`;

                console.log('📊 Exchange Comparison: Fetching from:', apiUrl);

                const response = await fetch(apiUrl);
                const result = await response.json();

                console.log('📊 Exchange Comparison: API Response:', result);

                if (result.data && Array.isArray(result.data)) {
                    this.exchangeData = result.data;
                    this.updateDisplayedData();
                    console.log('📊 Exchange Comparison: Loaded', this.exchangeData.length, 'exchanges');
                } else {
                    console.warn('📊 Exchange Comparison: No data in response');
                    this.exchangeData = [];
                    this.displayedExchanges = [];
                }

            } catch (error) {
                console.error('📊 Exchange Comparison: Error loading data:', error);
                this.exchangeData = [];
                this.displayedExchanges = [];
            } finally {
                this.loading = false;
            }
        },

        updateDisplayedData() {
            // Filter to show only Binance data (since exchange filter is hidden and defaults to Binance)
            const exchanges = this.exchangeData
                .filter(ex => ex.exchange === 'Binance')
                .map(ex => ({
                    exchange: ex.exchange,
                    liquidation_usd: parseFloat(ex.liquidation_usd) || 0,
                    long_liquidation_usd: parseFloat(ex.long_liquidation_usd) || 0,
                    short_liquidation_usd: parseFloat(ex.short_liquidation_usd) || 0,
                }))
                .sort((a, b) => b.liquidation_usd - a.liquidation_usd);

            // Calculate totals
            this.totalVolume = exchanges.reduce((sum, ex) => sum + ex.liquidation_usd, 0);
            this.totalLong = exchanges.reduce((sum, ex) => sum + ex.long_liquidation_usd, 0);
            this.totalShort = exchanges.reduce((sum, ex) => sum + ex.short_liquidation_usd, 0);

            // Calculate percentages and prepare display data
            this.displayedExchanges = exchanges.map(ex => ({
                ...ex,
                percentage: this.totalVolume > 0 ? ((ex.liquidation_usd / this.totalVolume) * 100).toFixed(1) : '0.0',
            }));
        },

        formatUSD(value) {
            if (value === null || value === undefined) return 'N/A';
            const num = parseFloat(value);
            if (isNaN(num)) return 'N/A';

            if (num >= 1e9) return '$' + (num / 1e9).toFixed(2) + 'B';
            if (num >= 1e6) return '$' + (num / 1e6).toFixed(1) + 'M';
            if (num >= 1e3) return '$' + (num / 1e3).toFixed(0) + 'K';
            return '$' + num.toFixed(0);
        },

        getLongShortRatio(exchange) {
            const longUsd = parseFloat(exchange.long_liquidation_usd) || 0;
            const shortUsd = parseFloat(exchange.short_liquidation_usd) || 0;
            
            if (longUsd === 0 && shortUsd === 0) return 'N/A';
            if (shortUsd === 0) return '∞:1';
            
            const ratio = longUsd / shortUsd;
            return ratio.toFixed(2) + ':1';
        },

        getLongShortRatioClass(exchange) {
            const longUsd = parseFloat(exchange.long_liquidation_usd) || 0;
            const shortUsd = parseFloat(exchange.short_liquidation_usd) || 0;
            
            if (longUsd > shortUsd) return 'bg-danger';
            if (shortUsd > longUsd) return 'bg-success';
            return 'bg-secondary';
        },
    };
}
</script>

