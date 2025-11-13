<!-- Coins Markets Tab -->
<div class="d-flex flex-column gap-3">
    <!-- Filters -->
    <div class="df-panel p-3">
        <div class="row g-3 align-items-end">
            <!-- Time Interval -->
            <div class="col-md-3">
                <label class="form-label small text-secondary mb-1">Time Interval</label>
                <select class="form-select" x-model="coinsMarkets.timeInterval" @change="processCoinsMarkets()">
                    <option value="5m">5 Minutes</option>
                    <option value="15m">15 Minutes</option>
                    <option value="30m">30 Minutes</option>
                    <option value="1h">1 Hour</option>
                    <option value="4h">4 Hours</option>
                    <option value="12h">12 Hours</option>
                    <option value="24h" selected>24 Hours</option>
                    <option value="1w">1 Week</option>
                </select>
            </div>

            <!-- Sort By -->
            <div class="col-md-3">
                <label class="form-label small text-secondary mb-1">Sort By</label>
                <select class="form-select" x-model="coinsMarkets.sortBy" @change="applyCoinsMarketsFilters()">
                    <option value="volume_change_percent">Volume Change %</option>
                    <option value="price_change">Price Change %</option>
                    <option value="market_cap">Market Cap</option>
                    <option value="volume_usd">Volume USD</option>
                    <option value="volume_flow_usd">Volume Flow</option>
                </select>
            </div>

            <!-- Sort Direction -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Direction</label>
                <select class="form-select" x-model="coinsMarkets.sortDirection" @change="applyCoinsMarketsFilters()">
                    <option value="desc">Highest First</option>
                    <option value="asc">Lowest First</option>
                </select>
            </div>

            <!-- Search -->
            <div class="col-md-4">
                <label class="form-label small text-secondary mb-1">Search Symbol</label>
                <input type="text" class="form-control" placeholder="e.g. BTC, ETH..." 
                       x-model="coinsMarkets.search" @input="applyCoinsMarketsFilters()">
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3">
        <div class="col-md-3">
            <div class="df-panel p-3 h-100">
                <div class="small text-secondary mb-1">Total Coins</div>
                <div class="h3 mb-0" x-text="coinsMarkets.filtered.length"></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="df-panel p-3 h-100">
                <div class="small text-secondary mb-1">Avg Price Change</div>
                <div class="h3 mb-0" :class="coinsMarkets.avgPriceChange >= 0 ? 'text-success' : 'text-danger'">
                    <span x-text="(coinsMarkets.avgPriceChange >= 0 ? '+' : '') + coinsMarkets.avgPriceChange.toFixed(2) + '%'"></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="df-panel p-3 h-100">
                <div class="small text-secondary mb-1">Total Volume</div>
                <div class="h3 mb-0" x-text="'$' + (coinsMarkets.totalVolume / 1e9).toFixed(2) + 'B'"></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="df-panel p-3 h-100">
                <div class="small text-secondary mb-1">Net Flow</div>
                <div class="h3 mb-0" :class="coinsMarkets.totalNetFlow >= 0 ? 'text-danger' : 'text-success'">
                    <span x-text="(coinsMarkets.totalNetFlow >= 0 ? '+' : '') + '$' + (coinsMarkets.totalNetFlow / 1e6).toFixed(1) + 'M'"></span>
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
                        <th>Symbol</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Price Change</th>
                        <th class="text-end">Volume</th>
                        <th class="text-end">Volume Change</th>
                        <th class="text-end">Buy Volume</th>
                        <th class="text-end">Sell Volume</th>
                        <th class="text-end">Net Flow</th>
                        <th class="text-end">Market Cap</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="isLoading">
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!isLoading && coinsMarkets.filtered.length === 0">
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">
                                No data available
                            </td>
                        </tr>
                    </template>
                    <template x-for="coin in coinsMarkets.filtered.slice(0, 50)" :key="coin.symbol">
                        <tr>
                            <td><strong x-text="coin.symbol"></strong></td>
                            <td class="text-end" x-text="'$' + coin.current_price.toLocaleString()"></td>
                            <td class="text-end">
                                <span :class="coin.price_change >= 0 ? 'text-success' : 'text-danger'">
                                    <span x-text="(coin.price_change >= 0 ? '+' : '') + coin.price_change.toFixed(2) + '%'"></span>
                                </span>
                            </td>
                            <td class="text-end" x-text="'$' + (coin.volume_usd / 1e6).toFixed(2) + 'M'"></td>
                            <td class="text-end">
                                <span :class="coin.volume_change_percent >= 0 ? 'text-success' : 'text-danger'">
                                    <span x-text="(coin.volume_change_percent >= 0 ? '+' : '') + coin.volume_change_percent.toFixed(2) + '%'"></span>
                                </span>
                            </td>
                            <td class="text-end text-success" x-text="'$' + (coin.buy_volume_usd / 1e6).toFixed(2) + 'M'"></td>
                            <td class="text-end text-danger" x-text="'$' + (coin.sell_volume_usd / 1e6).toFixed(2) + 'M'"></td>
                            <td class="text-end">
                                <span :class="coin.volume_flow_usd >= 0 ? 'text-danger' : 'text-success'">
                                    <span x-text="(coin.volume_flow_usd >= 0 ? '+' : '') + '$' + (coin.volume_flow_usd / 1e6).toFixed(2) + 'M'"></span>
                                </span>
                            </td>
                            <td class="text-end" x-text="'$' + (coin.market_cap / 1e9).toFixed(2) + 'B'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="p-3 text-center text-secondary small" x-show="coinsMarkets.filtered.length > 50">
            Showing top 50 of <span x-text="coinsMarkets.filtered.length"></span> coins
        </div>
    </div>
</div>
