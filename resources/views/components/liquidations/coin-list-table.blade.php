{{--
    Coin List Table Component
    Displays multi-range liquidation data per exchange from coin-list endpoint
    Shows: 1h, 4h, 12h, 24h breakdown per exchange
--}}

<div class="df-panel p-4 h-100"
     x-data="liquidationsCoinListTable()"
     x-init="init()">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-0">🏦 Exchange Breakdown</h5>
            <small class="text-secondary">Multi-range liquidation data per exchange</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span x-show="loading" class="spinner-border spinner-border-sm text-primary"></span>
        </div>
    </div>

    <!-- Time Range Selector -->
    <div class="btn-group w-100 mb-3" role="group">
        <button type="button" class="btn btn-sm"
                :class="selectedRange === '1h' ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectedRange = '1h'">
            1 Hour
        </button>
        <button type="button" class="btn btn-sm"
                :class="selectedRange === '4h' ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectedRange = '4h'">
            4 Hours
        </button>
        <button type="button" class="btn btn-sm"
                :class="selectedRange === '12h' ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectedRange = '12h'">
            12 Hours
        </button>
        <button type="button" class="btn btn-sm"
                :class="selectedRange === '24h' ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectedRange = '24h'">
            24 Hours
        </button>
    </div>

    <!-- Table -->
    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
        <table class="table table-hover table-sm">
            <thead class="sticky-top bg-dark">
                <tr>
                    <th class="text-start">Exchange</th>
                    <th class="text-end">Total</th>
                    <th class="text-end text-danger">Long</th>
                    <th class="text-end text-success">Short</th>
                    <th class="text-end">Ratio</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, index) in displayedData" :key="index">
                    <tr>
                        <td class="text-start">
                            <span class="badge bg-secondary" x-text="item.exchange">Exchange</span>
                        </td>
                        <td class="text-end fw-bold" x-text="formatUSD(item.total)">--</td>
                        <td class="text-end text-danger" x-text="formatUSD(item.long)">--</td>
                        <td class="text-end text-success" x-text="formatUSD(item.short)">--</td>
                        <td class="text-end">
                            <span class="badge"
                                  :class="getRatioBadgeClass(item.ratio)"
                                  x-text="item.ratio?.toFixed(2) || 'N/A'">
                                --
                            </span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Summary Stats -->
    <div class="mt-3 pt-3 border-top">
        <div class="row g-2 small">
            <div class="col-6">
                <div class="text-secondary">Total Across Exchanges</div>
                <div class="fw-bold" x-text="formatUSD(totals.total)">--</div>
            </div>
            <div class="col-3">
                <div class="text-secondary">Long</div>
                <div class="fw-bold text-danger" x-text="formatUSD(totals.long)">--</div>
            </div>
            <div class="col-3">
                <div class="text-secondary">Short</div>
                <div class="fw-bold text-success" x-text="formatUSD(totals.short)">--</div>
            </div>
        </div>
    </div>

    <!-- No Data State -->
    <div x-show="!loading && displayedData.length === 0" class="text-center py-5">
        <div class="text-secondary mb-2" style="font-size: 3rem;">🏦</div>
        <div class="text-secondary">No exchange data available</div>
    </div>
</div>

<script>
function liquidationsCoinListTable() {
    return {
        symbol: 'BTC',
        loading: false,
        coinListData: [],
        selectedRange: '1h',
        displayedData: [],
        totals: {
            total: 0,
            long: 0,
            short: 0,
        },

        async init() {
            console.log('🏦 Exchange Breakdown: Initializing component');
            this.symbol = this.$root?.globalSymbol || 'BTC';

            // Listen for filter changes
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.loadData();
            });

            window.addEventListener('refresh-all', () => {
                this.loadData();
            });

            // Watch selected range
            this.$watch('selectedRange', () => {
                this.updateDisplayedData();
            });

            // Initial load
            this.loadData();
        },

        updateDisplayedData() {
            const range = this.selectedRange;
            this.displayedData = this.coinListData.map(item => {
                const total = parseFloat(item[`liquidation_usd_${range}`]) || 0;
                const long = parseFloat(item[`long_liquidation_usd_${range}`]) || 0;
                const short = parseFloat(item[`short_liquidation_usd_${range}`]) || 0;
                const ratio = short > 0 ? long / short : 0;

                return {
                    exchange: item.exchange,
                    total,
                    long,
                    short,
                    ratio,
                };
            });

            // Calculate totals
            this.totals = this.displayedData.reduce((acc, item) => {
                acc.total += item.total;
                acc.long += item.long;
                acc.short += item.short;
                return acc;
            }, { total: 0, long: 0, short: 0 });

            // Sort by total descending
            this.displayedData.sort((a, b) => b.total - a.total);
        },

        async loadData() {
            this.loading = true;
            console.log('🏦 Exchange Breakdown: Loading data...');

            try {
                // Build API URL
                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || "").trim();
                const getApiBaseUrl = () => {
                    if (configuredBase) {
                        return configuredBase.endsWith("/") ? configuredBase.slice(0, -1) : configuredBase;
                    }
                    return "";
                };
                const apiUrl = `${getApiBaseUrl()}/api/liquidations/coin-list?symbol=${this.symbol}&limit=100`;

                console.log('🏦 Exchange Breakdown: Fetching from:', apiUrl);

                const response = await fetch(apiUrl);
                const result = await response.json();

                console.log('🏦 Exchange Breakdown: API Response:', result);

                if (result.data && Array.isArray(result.data)) {
                    this.coinListData = result.data;
                    this.updateDisplayedData();
                    console.log('🏦 Exchange Breakdown: Loaded', this.coinListData.length, 'exchanges');
                } else {
                    console.warn('🏦 Exchange Breakdown: No data in response');
                    this.coinListData = [];
                    this.displayedData = [];
                }

            } catch (error) {
                console.error('🏦 Exchange Breakdown: Error loading data:', error);
                this.coinListData = [];
                this.displayedData = [];
            } finally {
                this.loading = false;
            }
        },

        formatUSD(value) {
            if (value === null || value === undefined) return 'N/A';
            const num = parseFloat(value);
            if (isNaN(num)) return 'N/A';

            if (num >= 1e9) return '$' + (num / 1e9).toFixed(2) + 'B';
            if (num >= 1e6) return '$' + (num / 1e6).toFixed(2) + 'M';
            if (num >= 1e3) return '$' + (num / 1e3).toFixed(2) + 'K';
            return '$' + num.toFixed(2);
        },

        getRatioBadgeClass(ratio) {
            if (ratio > 2) return 'text-bg-danger';
            if (ratio > 1.5) return 'text-bg-warning';
            if (ratio < 0.5) return 'text-bg-success';
            if (ratio < 0.67) return 'text-bg-info';
            return 'text-bg-secondary';
        },
    };
}
</script>

<style scoped>
.table thead {
    z-index: 1;
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}
</style>

