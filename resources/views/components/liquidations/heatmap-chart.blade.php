{{--
    Liquidation Heatmap Table Component
    Visualizes liquidation intensity across time and exchanges in table format
    Uses pair-history API endpoint: /api/liquidations/pair-history
--}}

<div class="df-panel p-4 h-100"
     x-data="liquidationsHeatmapTable()"
     x-init="init()">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-0">🔥 Liquidation Heatmap</h5>
            <small class="text-secondary">Intensity across time & exchanges</small>
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
                <div class="fw-bold" x-text="pairHistoryData.length">0</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 rounded bg-warning bg-opacity-10 text-center">
                <div class="small text-secondary">Peak Intensity</div>
                <div class="fw-bold text-warning" x-text="formatUSD(peakIntensity)">$0</div>
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
    </div>

    <!-- Heatmap Data Table -->
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
                    <th class="text-end">Intensity</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, index) in pairHistoryData" :key="'heatmap-' + index + '-' + item.ts">
                    <tr :class="getIntensityClass(item.liq_usd)">
                        <td x-text="formatTimestamp(item.ts)">--</td>
                        <td>
                            <span class="badge bg-secondary" x-text="item.exchange">--</span>
                        </td>
                        <td x-text="item.pair">--</td>
                        <td class="text-end text-danger fw-bold" x-text="formatUSD(item.long_liquidation_usd)">--</td>
                        <td class="text-end text-success fw-bold" x-text="formatUSD(item.short_liquidation_usd)">--</td>
                        <td class="text-end fw-bold" x-text="formatUSD(item.liq_usd)">--</td>
                        <td class="text-end">
                            <span class="badge" :class="getIntensityBadgeClass(item.liq_usd)" x-text="getIntensityLevel(item.liq_usd)">--</span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Intensity Legend -->
    <div class="mt-3 d-flex justify-content-center gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <div style="width: 20px; height: 20px; background: rgba(239, 68, 68, 0.8); border-radius: 4px;"></div>
            <span class="small">High Intensity</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div style="width: 20px; height: 20px; background: rgba(245, 158, 11, 0.8); border-radius: 4px;"></div>
            <span class="small">Medium Intensity</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div style="width: 20px; height: 20px; background: rgba(34, 197, 94, 0.8); border-radius: 4px;"></div>
            <span class="small">Low Intensity</span>
        </div>
    </div>

    <!-- No Data State -->
    <div x-show="!loading && pairHistoryData.length === 0" class="text-center py-4">
        <div class="text-secondary mb-2" style="font-size: 3rem;">🔥</div>
        <div class="text-secondary">No liquidation heatmap data available</div>
        <div class="small text-muted mt-2">Try changing symbol, exchange, or time interval</div>
    </div>
</div>

<script>
function liquidationsHeatmapTable() {
    return {
        loading: false,
        pairHistoryData: [],
        peakIntensity: 0,
        avgLong: 0,
        avgShort: 0,

        async init() {
            console.log('🔥 Heatmap Table: Initializing component');

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
            console.log('🔥 Heatmap Table: Loading data...');

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
                let apiUrl = `${getApiBaseUrl()}/api/liquidations/pair-history?symbol=${symbol}&interval=${interval}&limit=100`;
                if (exchange) {
                    apiUrl += `&exchange=${exchange}`;
                }

                console.log('🔥 Heatmap Table: Fetching from:', apiUrl);

                const response = await fetch(apiUrl);
                const result = await response.json();

                console.log('🔥 Heatmap Table: API Response:', result);

                if (result.data && Array.isArray(result.data)) {
                    // Sort by total liquidation volume (highest intensity first)
                    this.pairHistoryData = result.data
                        .sort((a, b) => parseFloat(b.liq_usd || 0) - parseFloat(a.liq_usd || 0))
                        .slice(0, 50); // Limit to 50 records for performance
                    
                    this.calculateStats();
                    console.log('🔥 Heatmap Table: Loaded', this.pairHistoryData.length, 'records');
                } else {
                    console.warn('🔥 Heatmap Table: No data in response');
                    this.pairHistoryData = [];
                }

            } catch (error) {
                console.error('🔥 Heatmap Table: Error loading data:', error);
                this.pairHistoryData = [];
            } finally {
                this.loading = false;
            }
        },

        calculateStats() {
            if (this.pairHistoryData.length === 0) {
                this.peakIntensity = 0;
                this.avgLong = 0;
                this.avgShort = 0;
                return;
            }

            const longValues = this.pairHistoryData.map(d => parseFloat(d.long_liquidation_usd || 0));
            const shortValues = this.pairHistoryData.map(d => parseFloat(d.short_liquidation_usd || 0));
            const totalValues = this.pairHistoryData.map(d => parseFloat(d.liq_usd || 0));

            this.peakIntensity = Math.max(...totalValues);
            this.avgLong = longValues.reduce((a, b) => a + b, 0) / longValues.length;
            this.avgShort = shortValues.reduce((a, b) => a + b, 0) / shortValues.length;
        },

        getIntensityLevel(value) {
            const num = parseFloat(value || 0);
            if (num >= 1e6) return 'HIGH';
            if (num >= 1e5) return 'MED';
            if (num >= 1e4) return 'LOW';
            return 'MIN';
        },

        getIntensityBadgeClass(value) {
            const level = this.getIntensityLevel(value);
            switch (level) {
                case 'HIGH': return 'bg-danger';
                case 'MED': return 'bg-warning';
                case 'LOW': return 'bg-success';
                default: return 'bg-secondary';
            }
        },

        getIntensityClass(value) {
            const level = this.getIntensityLevel(value);
            switch (level) {
                case 'HIGH': return 'table-danger';
                case 'MED': return 'table-warning';
                case 'LOW': return 'table-success';
                default: return '';
            }
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

