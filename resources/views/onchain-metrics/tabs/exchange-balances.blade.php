<div class="tab-pane fade" id="exchange-balances" role="tabpanel">
    <div class="d-flex flex-column gap-3">
        <!-- Filters -->
        <div class="df-panel p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-secondary mb-1">Symbol <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" x-model="exchangeBalances.symbol" placeholder="BTC">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" @click="loadExchangeBalances()" :disabled="isLoading">
                        <i class="fas fa-chart-line"></i> Load Balances
                    </button>
                </div>
            </div>
        </div>

        <!-- Balance List -->
        <div class="df-panel" x-show="exchangeBalances.list.length > 0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Exchange</th>
                            <th class="text-end">Total Balance</th>
                            <th class="text-end">24h Change</th>
                            <th class="text-end">7d Change</th>
                            <th class="text-end">30d Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="ex in exchangeBalances.list" :key="ex.exchange_name">
                            <tr>
                                <td><strong x-text="ex.exchange_name"></strong></td>
                                <td class="text-end" x-text="ex.total_balance.toLocaleString()"></td>
                                <td class="text-end">
                                    <span :class="ex.balance_change_percent_1d >= 0 ? 'text-success' : 'text-danger'">
                                        <span x-text="(ex.balance_change_percent_1d >= 0 ? '+' : '') + ex.balance_change_percent_1d.toFixed(2) + '%'"></span>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span :class="ex.balance_change_percent_7d >= 0 ? 'text-success' : 'text-danger'">
                                        <span x-text="(ex.balance_change_percent_7d >= 0 ? '+' : '') + ex.balance_change_percent_7d.toFixed(2) + '%'"></span>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span :class="ex.balance_change_percent_30d >= 0 ? 'text-success' : 'text-danger'">
                                        <span x-text="(ex.balance_change_percent_30d >= 0 ? '+' : '') + ex.balance_change_percent_30d.toFixed(2) + '%'"></span>
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Balance Chart -->
        <div class="df-panel p-3" x-show="exchangeBalances.chart.data">
            <h5 class="mb-3">Exchange Balance History</h5>
            <div style="height: 400px; position: relative;">
                <canvas id="balanceChart"></canvas>
            </div>
        </div>
    </div>
</div>
