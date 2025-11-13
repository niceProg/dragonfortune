{{--
    Komponen: Exchange Funding Overview Table
    Menampilkan funding rate per exchange dengan next funding time

    Props:
    - $symbol: string (default: 'BTC')
    - $limit: int (default: 20)

    Features:
    - Sortable columns
    - Color-coded funding rates
    - Next funding countdown
    - Exchange comparison
--}}

<div class="df-panel p-3" x-data="exchangeFundingTable('{{ $symbol ?? 'BTC' }}', {{ $limit ?? 20 }})">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">🏢 Exchange Funding Overview</h5>
            <span class="badge text-bg-primary" x-text="exchanges.length + ' exchanges'">0 exchanges</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" @click="refresh()" :disabled="loading">
                <span x-show="!loading">🔄</span>
                <span x-show="loading" class="spinner-border spinner-border-sm"></span>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th @click="sortBy('exchange')" style="cursor: pointer;">
                        Exchange
                        <span x-show="sortColumn === 'exchange'">
                            <i class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                        </span>
                    </th>
                    <th>Symbol</th>
                    <th @click="sortBy('funding_rate')" class="text-end" style="cursor: pointer;">
                        Funding Rate
                        <span x-show="sortColumn === 'funding_rate'">
                            <i class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                        </span>
                    </th>
                    <th class="text-center">Interval</th>
                    <th class="text-center">Next Funding</th>
                    <th class="text-center">Margin Type</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading && exchanges.length === 0">
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </template>

                <template x-for="(exchange, index) in sortedExchanges" :key="exchange.exchange + index">
                    <tr>
                        <!-- Exchange Name -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold" x-text="exchange.exchange">Binance</span>
                                <template x-if="isHighest(exchange.funding_rate)">
                                    <span class="badge badge-sm text-bg-success">HIGH</span>
                                </template>
                                <template x-if="isLowest(exchange.funding_rate)">
                                    <span class="badge badge-sm text-bg-danger">LOW</span>
                                </template>
                            </div>
                        </td>

                        <!-- Symbol -->
                        <td>
                            <span class="badge text-bg-secondary" x-text="exchange.symbol">BTC</span>
                        </td>

                        <!-- Funding Rate -->
                        <td class="text-end">
                            <div class="d-flex flex-column align-items-end">
                                <span class="fw-bold"
                                      :class="getFundingClass(exchange.funding_rate)"
                                      x-text="formatRate(exchange.funding_rate)">
                                    +0.0125%
                                </span>
                                <small class="text-secondary" x-text="'APR: ' + calculateAPR(exchange.funding_rate, exchange.funding_rate_interval_hours)">
                                    APR: 13.7%
                                </small>
                            </div>
                        </td>

                        <!-- Interval -->
                        <td class="text-center">
                            <span class="badge text-bg-light text-dark">
                                <template x-if="exchange.funding_rate_interval_hours">
                                    <span x-text="exchange.funding_rate_interval_hours + 'h'">8h</span>
                                </template>
                                <template x-if="!exchange.funding_rate_interval_hours">
                                    <span>N/A</span>
                                </template>
                            </span>
                        </td>

                        <!-- Next Funding -->
                        <td class="text-center">
                            <div class="d-flex flex-column align-items-center">
                                <span class="small fw-semibold" x-text="formatNextFunding(exchange.next_funding_time)">
                                    2h 15m
                                </span>
                                <small class="text-secondary" x-text="formatTime(exchange.next_funding_time)">
                                    14:00 UTC
                                </small>
                            </div>
                        </td>

                        <!-- Margin Type -->
                        <td class="text-center">
                            <span class="badge"
                                  :class="exchange.margin_type === 'stablecoin' ? 'text-bg-primary' : 'text-bg-secondary'"
                                  x-text="exchange.margin_type">
                                stablecoin
                            </span>
                        </td>
                    </tr>
                </template>

                <template x-if="!loading && exchanges.length === 0">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-secondary">
                            No exchange data available
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Summary Stats -->
    <div class="row g-2 mt-3">
        <div class="col-md-3">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Avg Funding</div>
                <div class="fw-bold" :class="avgFunding >= 0 ? 'text-success' : 'text-danger'" x-text="formatRate(avgFunding)">
                    +0.0085%
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Positive Rate</div>
                <div class="fw-bold text-info" x-text="positivePercentage + '%'">
                    65%
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Highest</div>
                <div class="fw-bold text-success" x-text="formatRate(maxFunding)">
                    +0.0250%
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center p-2 bg-light rounded">
                <div class="small text-secondary">Lowest</div>
                <div class="fw-bold text-danger" x-text="formatRate(minFunding)">
                    -0.0150%
                </div>
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
function exchangeFundingTable(initialSymbol = 'BTC', initialLimit = 20) {
    return {
        symbol: initialSymbol,
        limit: initialLimit,
        marginType: '',
        loading: false,
        exchanges: [],
        sortColumn: 'funding_rate',
        sortDirection: 'desc',
        lastUpdate: '--',

        // Computed properties
        get sortedExchanges() {
            const sorted = [...this.exchanges].sort((a, b) => {
                let aVal = a[this.sortColumn];
                let bVal = b[this.sortColumn];

                // Handle funding_rate as number
                if (this.sortColumn === 'funding_rate') {
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                }

                if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
            return sorted;
        },

        get avgFunding() {
            if (this.exchanges.length === 0) return 0;
            const validRates = this.exchanges
                .map(e => parseFloat(e.funding_rate))
                .filter(r => !isNaN(r));
            if (validRates.length === 0) return 0;
            return validRates.reduce((sum, r) => sum + r, 0) / validRates.length;
        },

        get positivePercentage() {
            if (this.exchanges.length === 0) return 0;
            const positive = this.exchanges.filter(e => parseFloat(e.funding_rate) > 0).length;
            return Math.round((positive / this.exchanges.length) * 100);
        },

        get maxFunding() {
            if (this.exchanges.length === 0) return 0;
            const rates = this.exchanges.map(e => parseFloat(e.funding_rate) || 0);
            return Math.max(...rates);
        },

        get minFunding() {
            if (this.exchanges.length === 0) return 0;
            const rates = this.exchanges.map(e => parseFloat(e.funding_rate) || 0);
            return Math.min(...rates);
        },

        init() {
            this.loadExchangeData();
            // Auto refresh every 30 seconds
            setInterval(() => this.loadExchangeData(), 30000);
            // Update countdown every second - trigger reactive update
            setInterval(() => {
                // Force Alpine to re-evaluate computed properties
                this.exchanges = [...this.exchanges];
            }, 1000);

            // Listen to global filter changes
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.marginType = e.detail?.marginType ?? this.marginType;
                this.loadExchangeData();
            });
            window.addEventListener('margin-type-changed', (e) => {
                this.marginType = e.detail?.marginType ?? '';
                this.loadExchangeData();
            });
        },

        async loadExchangeData() {
            this.loading = true;
            try {
                // Convert symbol to pair format (BTC -> BTCUSDT)
                const pair = `${this.symbol}USDT`;
                const params = new URLSearchParams({
                    limit: this.limit,
                    ...(pair && { symbol: pair }),
                    ...(this.marginType && { margin_type: this.marginType })
                });

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/funding-rate/exchanges?${params}` : `/api/funding-rate/exchanges?${params}`;
                const response = await fetch(url);
                const data = await response.json();

                this.exchanges = data.data || [];
                this.lastUpdate = new Date().toLocaleTimeString();

                console.log('✅ Exchange data loaded:', this.exchanges.length, 'items');
            } catch (error) {
                console.error('❌ Error loading exchange data:', error);
                this.exchanges = [];
            } finally {
                this.loading = false;
            }
        },

        refresh() {
            this.loadExchangeData();
        },

        sortBy(column) {
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDirection = column === 'funding_rate' ? 'desc' : 'asc';
            }
        },

        isHighest(rate) {
            const numRate = parseFloat(rate) || 0;
            return numRate === this.maxFunding && numRate > 0;
        },

        isLowest(rate) {
            const numRate = parseFloat(rate) || 0;
            return numRate === this.minFunding && numRate < 0;
        },

        getFundingClass(rate) {
            const numRate = parseFloat(rate) || 0;
            if (numRate > 0.001) return 'text-success';
            if (numRate < -0.001) return 'text-danger';
            return 'text-secondary';
        },

        formatRate(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const percent = (parseFloat(value) * 100).toFixed(4);
            return (parseFloat(value) >= 0 ? '+' : '') + percent + '%';
        },

        calculateAPR(rate, interval) {
            if (!rate || !interval) return 'N/A';
            const numRate = parseFloat(rate);
            const periodsPerYear = (365 * 24) / interval;
            const apr = (numRate * periodsPerYear * 100).toFixed(1);
            return (numRate >= 0 ? '+' : '') + apr + '%';
        },

        formatNextFunding(timestamp) {
            if (!timestamp || timestamp <= Date.now()) return 'N/A';
            const diff = timestamp - Date.now();
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            return `${hours}h ${minutes}m`;
        },

        formatTime(timestamp) {
            if (!timestamp) return 'N/A';
            const date = new Date(timestamp);
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
        }
    };
}
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

thead th {
    background-color: var(--bs-light);
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-sm {
    font-size: 0.65rem;
    padding: 0.25em 0.5em;
}
</style>

