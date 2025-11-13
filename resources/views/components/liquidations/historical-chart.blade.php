{{--
    Historical Liquidations Table Component
    Time series data of liquidations displayed in table format
    Uses pair-history API endpoint: /api/liquidations/pair-history
--}}

<div class="df-panel p-4 h-100"
     x-data="liquidationsHistoricalTable()"
     x-init="init()">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-0">📈 Historical Liquidations</h5>
            <small class="text-secondary">Time series liquidation data</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span x-show="loading" class="spinner-border spinner-border-sm text-primary"></span>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="row g-2 mb-3">
        <div class="col-md-3 col-6">
            <div class="p-2 rounded bg-primary bg-opacity-10 text-center">
                <div class="small text-secondary">Data Points</div>
                <div class="fw-bold" x-text="dataPoints">0</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 rounded bg-danger bg-opacity-10 text-center">
                <div class="small text-secondary">Avg Long</div>
                <div class="fw-bold text-danger" x-text="formatUSD(avgLong)">$0</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 rounded bg-success bg-opacity-10 text-center">
                <div class="small text-secondary">Avg Short</div>
                <div class="fw-bold text-success" x-text="formatUSD(avgShort)">$0</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 rounded bg-warning bg-opacity-10 text-center">
                <div class="small text-secondary">Peak Total</div>
                <div class="fw-bold text-warning" x-text="formatUSD(peakTotal)">$0</div>
            </div>
        </div>
    </div>

    <!-- Historical Data Table -->
    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-sm table-striped">
            <thead class="sticky-top bg-white">
                <tr>
                    <th>Time</th>
                    <th>Exchange</th>
                    <th>Pair</th>
                    <th class="text-end text-danger">Long USD</th>
                    <th class="text-end text-success">Short USD</th>
                    <th class="text-end">Total USD</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, index) in pairHistoryData" :key="'history-' + index + '-' + item.ts">
                    <tr>
                        <td x-text="formatTimestamp(item.ts)">--</td>
                        <td>
                            <span class="badge bg-secondary" x-text="item.exchange">--</span>
                        </td>
                        <td x-text="item.pair">--</td>
                        <td class="text-end text-danger fw-bold" x-text="formatUSD(item.long_liquidation_usd)">--</td>
                        <td class="text-end text-success fw-bold" x-text="formatUSD(item.short_liquidation_usd)">--</td>
                        <td class="text-end fw-bold" x-text="formatUSD(item.liq_usd)">--</td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- No Data State -->
    <div x-show="!loading && (!pairHistoryData || pairHistoryData.length === 0)" class="text-center py-4">
        <div class="text-secondary mb-2" style="font-size: 3rem;">📈</div>
        <div class="text-secondary">No historical liquidation data available</div>
        <div class="small text-muted mt-2">Try changing symbol, exchange, or time interval</div>
    </div>
</div>

<script>
function liquidationsHistoricalTable() {
    return {
        loading: false,
        pairHistoryData: [],

        // Stats
        dataPoints: 0,
        avgLong: 0,
        avgShort: 0,
        peakTotal: 0,

        async init() {
            console.log('📊 Historical Table: Initializing component');

            // Listen for filter changes
            window.addEventListener('symbol-changed', () => {
                this.loadData();
            });

            window.addEventListener('exchange-changed', () => {
                this.loadData();
            });

            window.addEventListener('interval-changed', () => {
                this.loadData();
            });

            window.addEventListener('refresh-all', () => {
                this.loadData();
            });

            // Initial load
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            console.log('📊 Historical Table: Loading data...');

            try {
                // Get current filters from global state
                const symbol = this.$root?.globalSymbol || 'BTCUSDT';
                const interval = this.$root?.globalInterval || '1m';
                const exchange = this.$root?.globalExchange || '';

                // Build API URL
                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || "").trim();
                const getApiBaseUrl = () => {
                    if (configuredBase) {
                        return configuredBase.endsWith("/") ? configuredBase.slice(0, -1) : configuredBase;
                    }
                    return "";
                };
                let apiUrl = `${getApiBaseUrl()}/api/liquidations/pair-history?symbol=${symbol}&interval=${interval}&limit=50`;
                if (exchange) {
                    apiUrl += `&exchange=${exchange}`;
                }

                console.log('📊 Historical Table: Fetching from:', apiUrl);

                const response = await fetch(apiUrl);
                const result = await response.json();

                console.log('📊 Historical Table: API Response:', result);

                if (result.data && Array.isArray(result.data)) {
                    this.pairHistoryData = result.data
                        .sort((a, b) => b.ts - a.ts) // Sort by newest first
                        .slice(0, 100); // Limit to 100 records for performance
                    
                    this.calculateStats();
                    console.log('📊 Historical Table: Loaded', this.pairHistoryData.length, 'records');
                } else {
                    console.warn('📊 Historical Table: No data in response');
                    this.pairHistoryData = [];
                }

            } catch (error) {
                console.error('📊 Historical Table: Error loading data:', error);
                this.pairHistoryData = [];
            } finally {
                this.loading = false;
            }
        },

        calculateStats() {
            if (this.pairHistoryData.length === 0) {
                this.dataPoints = 0;
                this.avgLong = 0;
                this.avgShort = 0;
                this.peakTotal = 0;
                return;
            }

            this.dataPoints = this.pairHistoryData.length;

            const longValues = this.pairHistoryData.map(d => parseFloat(d.long_liquidation_usd || 0));
            const shortValues = this.pairHistoryData.map(d => parseFloat(d.short_liquidation_usd || 0));
            const totalValues = this.pairHistoryData.map(d => parseFloat(d.liq_usd || 0));

            this.avgLong = longValues.reduce((a, b) => a + b, 0) / longValues.length;
            this.avgShort = shortValues.reduce((a, b) => a + b, 0) / shortValues.length;
            this.peakTotal = Math.max(...totalValues);
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
    };
}
</script>

