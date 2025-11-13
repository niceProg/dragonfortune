{{--
    Live Liquidation Stream Component
    Real-time liquidation orders displayed in table format
    Uses orders API endpoint: /api/liquidations/orders
--}}

<div class="df-panel p-4 h-100"
     x-data="liquidationsStreamTable()"
     x-init="init()">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-0">⚡ Live Liquidation Stream</h5>
            <small class="text-secondary">Real-time liquidation orders</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span x-show="isStreaming" class="pulse-dot pulse-danger"></span>
            <span x-show="loading" class="spinner-border spinner-border-sm text-primary"></span>
        </div>
    </div>

    <!-- Stream Stats Bar -->
    <div class="d-flex gap-3 mb-3 p-2 rounded bg-dark bg-opacity-10">
        <div class="flex-fill text-center">
            <div class="small text-secondary">Total Orders</div>
            <div class="fw-bold" x-text="filteredOrders.length">0</div>
        </div>
        <div class="flex-fill text-center">
            <div class="small text-secondary">Avg Size</div>
            <div class="fw-bold" x-text="formatUSD(getAverageSize())">--</div>
        </div>
        <div class="flex-fill text-center">
            <div class="small text-secondary">Largest</div>
            <div class="fw-bold" x-text="formatUSD(getLargestOrder())">--</div>
        </div>
    </div>

    <!-- Liquidation Orders Table -->
    <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
        <table class="table table-sm table-striped">
            <thead class="sticky-top bg-white">
                <tr>
                    <th>Time</th>
                    <th>Exchange</th>
                    <th>Pair</th>
                    <th>Side</th>
                    <th class="text-end">Amount USD</th>
                    <th class="text-end">Price</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(order, index) in filteredOrders.slice(0, 100)" :key="'order-' + index + '-' + order.ts">
                    <tr>
                        <td x-text="formatTime(order.ts)">--</td>
                        <td>
                            <span class="badge bg-secondary" x-text="order.exchange">--</span>
                        </td>
                        <td x-text="order.pair">--</td>
                        <td>
                            <span class="badge"
                                  :class="order.side_label === 'long' ? 'bg-danger' : 'bg-success'"
                                  x-text="order.side_label?.toUpperCase()">
                                --
                            </span>
                        </td>
                        <td class="text-end fw-bold"
                            :class="order.side_label === 'long' ? 'text-danger' : 'text-success'"
                            x-text="formatUSD(order.qty_usd)">--</td>
                        <td class="text-end" x-text="formatPrice(order.price)">--</td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Load More Info -->
    <div x-show="filteredOrders.length > 100" class="text-center mt-3">
        <small class="text-muted">Showing 100 of <span x-text="filteredOrders.length">0</span> orders</small>
    </div>

    <!-- No Data State -->
    <div x-show="!loading && filteredOrders.length === 0" class="text-center py-4">
        <div class="text-secondary mb-2" style="font-size: 3rem;">⚡</div>
        <div class="text-secondary">No liquidation orders available</div>
        <div class="small text-muted mt-2">Try adjusting filters or refresh data</div>
    </div>
</div>

<script>
function liquidationsStreamTable() {
    return {
        orders: [],
        filteredOrders: [],
        loading: false,
        isStreaming: true,

        async init() {
            console.log('⚡ Liquidation Stream: Initializing component');

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

            // Initial load
            this.loadData();

            // Auto-refresh every 15 seconds for real-time feel
            setInterval(() => {
                if (!this.loading && this.isStreaming) {
                    this.loadData();
                }
            }, 15000);
        },

        async loadData() {
            this.loading = true;
            console.log('⚡ Liquidation Stream: Loading data...');

            try {
                // Get current filters from global state
                const globalSymbol = this.$root?.globalSymbol || 'BTC';
                const globalExchange = this.$root?.globalExchange || '';

                // Get API base URL from environment
                const getApiBaseUrl = () => {
                    const baseMeta = document.querySelector('meta[name="api-base-url"]');
                    const configuredBase = (baseMeta?.content || "").trim();
                    if (configuredBase) {
                        return configuredBase.endsWith("/") ? configuredBase.slice(0, -1) : configuredBase;
                    }
                    return "";
                };

                // Build API URL with filters
                let apiUrl = `${getApiBaseUrl()}/api/liquidations/orders?limit=500`;
                
                // Add symbol filter (convert BTC to BTCUSDT format)
                if (globalSymbol) {
                    const symbolPair = globalSymbol === 'BTC' ? 'BTCUSDT' : 
                                     globalSymbol === 'ETH' ? 'ETHUSDT' :
                                     globalSymbol === 'SOL' ? 'SOLUSDT' :
                                     globalSymbol === 'BNB' ? 'BNBUSDT' :
                                     globalSymbol === 'XRP' ? 'XRPUSDT' :
                                     globalSymbol === 'ADA' ? 'ADAUSDT' :
                                     globalSymbol === 'DOGE' ? 'DOGEUSDT' :
                                     globalSymbol === 'MATIC' ? 'MATICUSDT' :
                                     globalSymbol === 'DOT' ? 'DOTUSDT' :
                                     globalSymbol === 'AVAX' ? 'AVAXUSDT' : 'BTCUSDT';
                    apiUrl += `&symbol=${symbolPair}`;
                }

                // Add exchange filter
                if (globalExchange) {
                    apiUrl += `&exchange=${globalExchange}`;
                }

                console.log('⚡ Liquidation Stream: Fetching from:', apiUrl);

                const response = await fetch(apiUrl);
                const result = await response.json();

                console.log('⚡ Liquidation Stream: API Response:', result);

                if (result.data && Array.isArray(result.data)) {
                    this.orders = result.data.sort((a, b) => b.ts - a.ts); // Sort by newest first
                    this.filteredOrders = this.orders; // No need for client-side filtering since API handles it
                    console.log('⚡ Liquidation Stream: Loaded', this.orders.length, 'orders');
                } else {
                    console.warn('⚡ Liquidation Stream: No data in response');
                    this.orders = [];
                    this.filteredOrders = [];
                }

            } catch (error) {
                console.error('⚡ Liquidation Stream: Error loading data:', error);
                this.orders = [];
                this.filteredOrders = [];
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            // Since API handles filtering, just copy orders to filteredOrders
            this.filteredOrders = [...this.orders];
        },

        getAverageSize() {
            if (this.filteredOrders.length === 0) return 0;
            const total = this.filteredOrders.reduce((sum, o) => sum + parseFloat(o.qty_usd || 0), 0);
            return total / this.filteredOrders.length;
        },

        getLargestOrder() {
            if (this.filteredOrders.length === 0) return 0;
            return Math.max(...this.filteredOrders.map(o => parseFloat(o.qty_usd || 0)));
        },

        formatUSD(value) {
            if (value === null || value === undefined) return 'N/A';
            const num = parseFloat(value);
            if (isNaN(num)) return 'N/A';

            if (num >= 1e6) return '$' + (num / 1e6).toFixed(2) + 'M';
            if (num >= 1e3) return '$' + (num / 1e3).toFixed(1) + 'K';
            return '$' + num.toFixed(0);
        },

        formatPrice(value) {
            if (value === null || value === undefined) return 'N/A';
            const num = parseFloat(value);
            if (isNaN(num)) return 'N/A';
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
        },

        formatTime(timestamp) {
            if (!timestamp) return 'N/A';
            const date = new Date(timestamp);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        },
    };
}
</script>

<style scoped>
.liquidation-feed {
    scrollbar-width: thin;
    scrollbar-color: rgba(var(--bs-primary-rgb), 0.3) transparent;
}

.liquidation-feed::-webkit-scrollbar {
    width: 6px;
}

.liquidation-feed::-webkit-scrollbar-track {
    background: transparent;
}

.liquidation-feed::-webkit-scrollbar-thumb {
    background-color: rgba(var(--bs-primary-rgb), 0.3);
    border-radius: 3px;
}

.liquidation-item {
    transition: all 0.2s ease;
}

.liquidation-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pulse-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

.pulse-danger {
    background-color: #ef4444;
    box-shadow: 0 0 0 rgba(239, 68, 68, 0.7);
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
    }
}
</style>

