<!-- Pairs Markets Tab -->
<div class="d-flex flex-column gap-3">
    <!-- Filters -->
    <div class="df-panel p-3">
        <div class="row g-3 align-items-end">
            <!-- Symbol (Required) -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Symbol <span class="text-danger">*</span></label>
                <input type="text" class="form-control" x-model="pairsMarkets.symbol" placeholder="BTC">
            </div>

            <!-- Load Button -->
            <div class="col-md-2">
                <button class="btn btn-primary w-100" @click="loadPairsMarkets()" :disabled="isLoading">
                    <i class="fas fa-search"></i> Load Pairs
                </button>
            </div>

            <!-- Time Interval (1h, 4h, 12h, 24h, 1w only) -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Time Interval</label>
                <select class="form-select" x-model="pairsMarkets.timeInterval" @change="processPairsMarkets()">
                    <option value="1h">1 Hour</option>
                    <option value="4h">4 Hours</option>
                    <option value="12h">12 Hours</option>
                    <option value="24h" selected>24 Hours</option>
                    <option value="1w">1 Week</option>
                </select>
            </div>

            <!-- Exchange Filter -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Exchange</label>
                <select class="form-select" x-model="pairsMarkets.exchange" @change="applyPairsMarketsFilters()">
                    <option value="">All Exchanges</option>
                    <template x-for="ex in pairsMarkets.exchanges" :key="ex">
                        <option :value="ex" x-text="ex"></option>
                    </template>
                </select>
            </div>

            <!-- Sort By -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Sort By</label>
                <select class="form-select" x-model="pairsMarkets.sortBy" @change="applyPairsMarketsFilters()">
                    <option value="volume_usd">Volume</option>
                    <option value="price_change_percent">Price Change %</option>
                    <option value="net_flows_usd">Net Flow</option>
                </select>
            </div>

            <!-- Search -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Search Pair</label>
                <input type="text" class="form-control" placeholder="Filter..." 
                       x-model="pairsMarkets.search" @input="applyPairsMarketsFilters()">
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="df-panel p-3 h-100">
                <div class="small text-secondary mb-1">Total Pairs</div>
                <div class="h3 mb-0" x-text="pairsMarkets.filtered.length"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="df-panel p-3 h-100">
                <div class="small text-secondary mb-1">Total Volume</div>
                <div class="h3 mb-0" x-text="'$' + (pairsMarkets.totalVolume / 1e9).toFixed(2) + 'B'"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="df-panel p-3 h-100">
                <div class="small text-secondary mb-1">Net Flow</div>
                <div class="h3 mb-0" :class="pairsMarkets.totalNetFlow >= 0 ? 'text-success' : 'text-danger'">
                    <span x-text="(pairsMarkets.totalNetFlow >= 0 ? '+' : '') + '$' + (pairsMarkets.totalNetFlow / 1e6).toFixed(1) + 'M'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="df-panel">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Pair</th>
                        <th>Exchange</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Price Change</th>
                        <th class="text-end">Volume</th>
                        <th class="text-end">Buy Volume</th>
                        <th class="text-end">Sell Volume</th>
                        <th class="text-end">Net Flow</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="isLoading">
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!isLoading && pairsMarkets.filtered.length === 0">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                No data available
                            </td>
                        </tr>
                    </template>
                    <template x-for="pair in pairsMarkets.filtered.slice(0, 50)" :key="pair.symbol + pair.exchange_name">
                        <tr>
                            <td><strong x-text="pair.symbol"></strong></td>
                            <td><span class="badge text-bg-secondary" x-text="pair.exchange_name"></span></td>
                            <td class="text-end" x-text="'$' + pair.current_price.toLocaleString()"></td>
                            <td class="text-end">
                                <span :class="pair.price_change_percent >= 0 ? 'text-success' : 'text-danger'">
                                    <span x-text="(pair.price_change_percent >= 0 ? '+' : '') + pair.price_change_percent.toFixed(2) + '%'"></span>
                                </span>
                            </td>
                            <td class="text-end" x-text="'$' + (pair.volume_usd / 1e6).toFixed(2) + 'M'"></td>
                            <td class="text-end text-success" x-text="'$' + (pair.buy_volume_usd / 1e6).toFixed(2) + 'M'"></td>
                            <td class="text-end text-danger" x-text="'$' + (pair.sell_volume_usd / 1e6).toFixed(2) + 'M'"></td>
                            <td class="text-end">
                                <span :class="pair.net_flows_usd >= 0 ? 'text-success' : 'text-danger'">
                                    <span x-text="(pair.net_flows_usd >= 0 ? '+' : '') + '$' + (pair.net_flows_usd / 1e6).toFixed(2) + 'M'"></span>
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="p-3 text-center text-secondary small" x-show="pairsMarkets.filtered.length > 50">
            Showing top 50 of <span x-text="pairsMarkets.filtered.length"></span> pairs
        </div>
    </div>
</div>
