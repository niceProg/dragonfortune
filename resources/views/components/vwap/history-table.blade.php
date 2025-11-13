{{--
    Komponen: VWAP Historical Data Table
    Menampilkan data historis VWAP dalam format tabel

    Props:
    - $symbol: string (default: 'BTCUSDT')
    - $timeframe: string (default: '5min')
    - $exchange: string (default: 'binance')
    - $limit: int (default: 50)

    Display:
    - Timestamp
    - VWAP Value
    - Upper & Lower Bands
    - Band Width
--}}

<div class="df-panel p-4" x-data="vwapHistoryTable('{{ $symbol ?? 'BTCUSDT' }}', '{{ $timeframe ?? '5min' }}', '{{ $exchange ?? 'binance' }}', {{ $limit ?? 50 }})">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">📋 Historical VWAP Data</h5>
            <p class="small text-secondary mb-0">Recent VWAP calculations with bands</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="width: 100px;" x-model="displayLimit" @change="updateDisplay()">
                <option value="10">10 rows</option>
                <option value="20">20 rows</option>
                <option value="50" selected>50 rows</option>
                <option value="100">100 rows</option>
            </select>
        </div>
    </div>

    <!-- Loading State -->
    <template x-if="loading && data.length === 0">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-secondary mt-2 mb-0">Loading historical data...</p>
        </div>
    </template>

    <!-- Error State -->
    <template x-if="!loading && error">
        <div class="alert alert-warning text-center py-4">
            <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
            <p class="mb-0" x-text="error">Unable to fetch data</p>
        </div>
    </template>

    <!-- Table -->
    <template x-if="!loading && data.length > 0 && !error">
        <div>
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th scope="col" class="text-start">Timestamp</th>
                            <th scope="col" class="text-end">VWAP</th>
                            <th scope="col" class="text-end">Upper Band</th>
                            <th scope="col" class="text-end">Lower Band</th>
                            <th scope="col" class="text-end">Band Width</th>
                            <th scope="col" class="text-center">Signal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in displayedData" :key="index">
                            <tr>
                                <td class="small text-secondary" x-text="formatTimestamp(row)">--</td>
                                <td class="text-end">
                                    <span class="fw-semibold" x-text="formatPrice(row.vwap)">$0.00</span>
                                </td>
                                <td class="text-end text-danger" x-text="formatPrice(row.upper_band)">$0.00</td>
                                <td class="text-end text-danger" x-text="formatPrice(row.lower_band)">$0.00</td>
                                <td class="text-end">
                                    <span :class="getBandWidthClass(row)" x-text="calculateBandWidth(row)">0%</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge" :class="getSignalBadge(row)" x-text="getSignalText(row)">
                                        Neutral
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                <div class="small text-secondary">
                    Showing <strong x-text="displayedData.length">0</strong> of <strong x-text="data.length">0</strong> records
                </div>
                <div class="small text-secondary">
                    Exchange: <strong x-text="exchange">--</strong> •
                    Timeframe: <strong x-text="timeframe">--</strong>
                </div>
            </div>
        </div>
    </template>

    <!-- Empty State -->
    <template x-if="!loading && data.length === 0 && !error">
        <div class="text-center py-5">
            <div class="fs-1 mb-3">📊</div>
            <p class="text-secondary mb-0">No historical data available</p>
        </div>
    </template>

    <!-- Last Updated -->
    <div class="text-center mt-3">
        <small class="text-secondary">
            Last updated: <span x-text="lastUpdate">--</span>
        </small>
    </div>
</div>

<script>
function vwapHistoryTable(initialSymbol = 'BTCUSDT', initialTimeframe = '5min', initialExchange = 'binance', initialLimit = 50) {
    return {
        symbol: initialSymbol,
        timeframe: initialTimeframe,
        exchange: initialExchange,
        limit: initialLimit,
        displayLimit: 50,
        loading: false,
        error: null,
        data: [],
        lastUpdate: '--',

        get displayedData() {
            return this.data.slice(0, this.displayLimit);
        },

        init() {
            console.log('📋 VWAP History Table component initialized');
            
            // Set initial loading state
            this.loading = true;
            
            // Listen for centralized data (primary data source)
            window.addEventListener('vwap-data-ready', (e) => {
                if (e.detail?.historical && Array.isArray(e.detail.historical)) {
                    // Sort by timestamp descending (newest first) with proper timestamp handling
                    this.data = e.detail.historical.sort((a, b) => {
                        const timestampA = a.timestamp || a.ts;
                        const timestampB = b.timestamp || b.ts;
                        return new Date(timestampB) - new Date(timestampA);
                    });
                    
                    this.symbol = e.detail.symbol || this.symbol;
                    this.timeframe = e.detail.timeframe || this.timeframe;
                    this.exchange = e.detail.exchange || this.exchange;
                    this.lastUpdate = new Date().toLocaleTimeString();
                    this.error = null;
                    this.loading = false;
                    
                    console.log('✅ History Table received data:', this.data.length, 'records');
                }
            });

            // Listen for error events
            window.addEventListener('vwap-data-error', (e) => {
                this.error = e.detail?.error || 'Failed to load historical data';
                this.loading = false;
                console.error('❌ History Table received error:', this.error);
            });

            // Fallback: Load data directly if centralized data doesn't arrive within 3 seconds
            setTimeout(() => {
                if (this.loading && this.data.length === 0) {
                    console.log('⚠️ Centralized table data not received, loading directly...');
                    this.loadDataDirectly();
                }
            }, 3000);

            console.log('📋 History Table waiting for centralized data...');
        },

        // Fallback method to load table data directly
        async loadDataDirectly() {
            try {
                this.loading = true;
                this.error = null;
                
                const response = await fetch(`/api/spot-microstructure/vwap?symbol=${this.symbol}&interval=${this.timeframe}&exchange=${this.exchange}&limit=${this.limit}`);
                const result = await response.json();
                
                if (result.success && result.data && Array.isArray(result.data)) {
                    // Sort by timestamp descending (newest first)
                    this.data = result.data.sort((a, b) => {
                        const timestampA = a.timestamp || a.ts;
                        const timestampB = b.timestamp || b.ts;
                        return new Date(timestampB) - new Date(timestampA);
                    });
                    
                    this.lastUpdate = new Date().toLocaleTimeString();
                    console.log('✅ History Table loaded data directly:', this.data.length, 'records');
                } else {
                    this.error = result.error || 'Failed to load table data';
                    console.error('❌ Direct table load failed:', this.error);
                }
            } catch (error) {
                this.error = 'Network error: ' + error.message;
                console.error('❌ Direct table load error:', error);
            } finally {
                this.loading = false;
            }
        },

        // Removed individual loadData() and refresh() methods
        // Component now relies entirely on centralized data management

        updateDisplay() {
            // Just update the display, data is already loaded
            console.log('Display limit updated to:', this.displayLimit);
        },

        formatPrice(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(parseFloat(value));
        },

        formatTimestamp(timestampOrItem) {
            let timestamp;
            
            // Handle both direct timestamp and data object
            if (typeof timestampOrItem === 'object' && timestampOrItem !== null) {
                timestamp = timestampOrItem.timestamp || timestampOrItem.ts;
            } else {
                timestamp = timestampOrItem;
            }
            
            if (!timestamp) return 'N/A';
            
            // Validate and parse timestamp
            const date = new Date(timestamp);
            if (isNaN(date.getTime())) {
                console.warn('📋 Invalid timestamp in table:', timestamp);
                return 'Invalid Date';
            }
            
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            });
        },

        calculateBandWidth(row) {
            if (!row || !row.upper_band || !row.lower_band || !row.vwap) return 'N/A';
            const width = ((parseFloat(row.upper_band) - parseFloat(row.lower_band)) / parseFloat(row.vwap)) * 100;
            return width.toFixed(2) + '%';
        },

        getBandWidthClass(row) {
            if (!row || !row.upper_band || !row.lower_band || !row.vwap) return 'text-secondary';
            const width = ((parseFloat(row.upper_band) - parseFloat(row.lower_band)) / parseFloat(row.vwap)) * 100;
            if (width > 2) return 'text-danger fw-semibold';
            if (width > 1) return 'text-warning fw-semibold';
            return 'text-success fw-semibold';
        },

        getSignalText(row) {
            if (!row || !row.vwap || !row.upper_band || !row.lower_band) return 'N/A';

            // Assume price is approximately VWAP for this calculation
            // In a real scenario, you'd include actual price data
            const vwap = parseFloat(row.vwap);
            const upperBand = parseFloat(row.upper_band);
            const lowerBand = parseFloat(row.lower_band);

            const bandWidth = ((upperBand - lowerBand) / vwap) * 100;

            if (bandWidth > 2) return 'High Vol';
            if (bandWidth > 1) return 'Moderate';
            return 'Low Vol';
        },

        getSignalBadge(row) {
            if (!row || !row.vwap || !row.upper_band || !row.lower_band) return 'text-bg-secondary';

            const vwap = parseFloat(row.vwap);
            const upperBand = parseFloat(row.upper_band);
            const lowerBand = parseFloat(row.lower_band);

            const bandWidth = ((upperBand - lowerBand) / vwap) * 100;

            if (bandWidth > 2) return 'text-bg-danger';
            if (bandWidth > 1) return 'text-bg-warning';
            return 'text-bg-success';
        },
    };
}
</script>

<style>
.table-responsive {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>

