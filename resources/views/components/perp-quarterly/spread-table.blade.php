{{--
    Komponen: Perp-Quarterly Spread Data Table
    Menampilkan historical spread data dalam bentuk tabel

    Props:
    - $symbol: string (default: 'BTC')
    - $exchange: string (default: 'Binance')
    - $limit: int (default: 50)
--}}

<div class="df-panel p-3"
     x-data="spreadDataTable('{{ $symbol ?? 'BTC' }}', '{{ $exchange ?? 'Binance' }}', {{ $limit ?? 50 }}, '{{ $quote ?? 'USDT' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">📋 Spread Data</h5>
            <small class="text-secondary">Recent spread measurements</small>
        </div>
        <div class="d-flex gap-2">
            <!-- Individual refresh button removed - using unified auto-refresh -->
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="sticky-top bg-white">
                <tr>
                    <th class="text-secondary small">Time</th>
                    <th class="text-secondary small">Perp Symbol</th>
                    <th class="text-secondary small">Quarterly Symbol</th>
                    <th class="text-secondary small text-end">Spread (Abs)</th>
                    <th class="text-secondary small text-end">Spread (BPS)</th>
                    <th class="text-secondary small">Structure</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading && tableData.length === 0">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-secondary">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Loading data...
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && tableData.length === 0">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-secondary">
                            No data available
                        </td>
                    </tr>
                </template>
                <template x-for="(row, idx) in tableData" :key="idx">
                    <tr>
                        <td class="small" x-text="formatTime(row.ts)">--</td>
                        <td class="small font-monospace" x-text="row.perp_symbol">--</td>
                        <td class="small font-monospace" x-text="row.quarterly_symbol">--</td>
                        <td class="small text-end" :class="getSpreadColor(row.spread_abs)">
                            <span x-text="formatSpread(row.spread_abs)">--</span>
                        </td>
                        <td class="small text-end fw-semibold" :class="getSpreadColor(row.spread_bps)">
                            <span x-text="formatBPS(row.spread_bps)">--</span>
                        </td>
                        <td class="small">
                            <span class="badge" :class="getStructureBadge(row.spread_bps)" x-text="getStructure(row.spread_bps)">
                                --
                            </span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Footer Info -->
    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center text-secondary small">
        <div x-show="tableData.length > 0">
            Displaying <span class="fw-semibold" x-text="tableData.length">0</span> of <span x-text="totalPoints">0</span> records
            <span x-show="parseInt(limit) > 1000" class="text-warning ms-2">
                (⚡ Limited to 100 rows for performance)
            </span>
        </div>
        <div>
            Last updated: <span x-text="lastUpdate">--</span>
        </div>
    </div>
</div>

<script>
function spreadDataTable(initialSymbol = 'BTC', initialExchange = 'Binance', displayLimit = 50, initialQuote = 'USDT') {
    return {
        symbol: initialSymbol,
        quote: initialQuote,
        exchange: initialExchange,
        interval: '5m',
        perpSymbol: '', // Auto-generated if empty
        limit: '100', // Data limit for API (will be updated by global filter)
        displayLimit: displayLimit, // Display limit for table
        loading: false,
        tableData: [],
        totalPoints: 0,
        lastUpdate: '--',

        init() {
            setTimeout(() => {
                this.loadData();
            }, 800);

            // Auto refresh every 60 seconds
            setInterval(() => this.loadData(), 60000);

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
                if (e.detail?.timeseries) {
                    this.updateFromOverview(e.detail.timeseries);
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

                console.log('📡 Fetching Perp-Quarterly Table Data:', params.toString());

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/perp-quarterly/history?${params}` : `/api/perp-quarterly/history?${params}`;

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                this.updateTable(data.data || []);
                this.lastUpdate = new Date().toLocaleTimeString();
                console.log('✅ Spread table data loaded:', data.data?.length, 'rows');
            } catch (error) {
                console.error('❌ Error loading spread table:', error);
                this.tableData = [];
            } finally {
                this.loading = false;
            }
        },

        updateTable(data) {
            this.totalPoints = data.length;
            // Sort by timestamp descending (newest first)
            const sortedData = data.sort((a, b) => {
                if (!a.ts) return 1;
                if (!b.ts) return -1;
                return new Date(b.ts) - new Date(a.ts);
            });
            
            // For performance: limit display to reasonable amount
            // If API limit > 1000, only show first 100 for table performance
            const maxDisplayRows = parseInt(this.limit) > 1000 ? 100 : Math.min(parseInt(this.limit), this.displayLimit);
            this.tableData = sortedData.slice(0, maxDisplayRows);
            
            console.log(`📊 Table: Showing ${this.tableData.length} of ${this.totalPoints} records (API limit: ${this.limit})`);
        },

        updateFromOverview(timeseries) {
            if (!Array.isArray(timeseries)) return;
            this.totalPoints = timeseries.length;
            // Sort by timestamp descending (newest first)
            this.tableData = timeseries
                .sort((a, b) => {
                    if (!a.ts) return 1;
                    if (!b.ts) return -1;
                    return new Date(b.ts) - new Date(a.ts);
                })
                .slice(0, this.displayLimit);
        },

        // refresh() method removed - using unified auto-refresh system

        formatTime(ts) {
            if (!ts) return '--';
            const date = new Date(ts);
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
        },

        formatSpread(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            return (num >= 0 ? '+' : '') + num.toFixed(2);
        },

        formatBPS(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            return (num >= 0 ? '+' : '') + num.toFixed(2);
        },

        getSpreadColor(value) {
            if (value === null || value === undefined) return 'text-secondary';
            if (value > 0) return 'text-success';
            if (value < 0) return 'text-danger';
            return 'text-secondary';
        },

        getStructure(bps) {
            if (bps > 50) return 'Strong Contango';
            if (bps > 0) return 'Contango';
            if (bps < -50) return 'Strong Back.';
            if (bps < 0) return 'Backwardation';
            return 'Neutral';
        },

        getStructureBadge(bps) {
            if (bps > 50) return 'bg-success text-white';
            if (bps > 0) return 'bg-success bg-opacity-25 text-success';
            if (bps < -50) return 'bg-danger text-white';
            if (bps < 0) return 'bg-danger bg-opacity-25 text-danger';
            return 'bg-secondary bg-opacity-25 text-secondary';
        }
    };
}
</script>

<style>
.table-responsive::-webkit-scrollbar {
    width: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
</style>

