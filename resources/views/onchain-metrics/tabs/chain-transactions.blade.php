<div class="tab-pane fade" id="chain-transactions" role="tabpanel">
    <div class="d-flex flex-column gap-3">
        <!-- Filters -->
        <div class="df-panel p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-secondary mb-1">Symbol <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" x-model="chainTx.symbol" placeholder="USDT">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-secondary mb-1">Min USD</label>
                    <input type="number" class="form-control" x-model="chainTx.minUsd" value="10000">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-secondary mb-1">Per Page</label>
                    <select class="form-select" x-model="chainTx.perPage">
                        <option value="20">20</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-primary w-100" @click="loadChainTransactions()" :disabled="isLoading">
                        <i class="fas fa-exchange-alt"></i> Load Transactions
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3" x-show="chainTx.data.length > 0">
            <div class="col-md-3">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Total Transactions</div>
                    <div class="h3 mb-0" x-text="chainTx.data.length"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Inflows</div>
                    <div class="h3 mb-0 text-success" x-text="chainTx.inflowCount"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Outflows</div>
                    <div class="h3 mb-0 text-danger" x-text="chainTx.outflowCount"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Total Volume</div>
                    <div class="h3 mb-0" x-text="'$' + (chainTx.totalVolume / 1e6).toFixed(2) + 'M'"></div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="df-panel">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Exchange</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Value (USD)</th>
                            <th>TX Hash</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!isLoading && chainTx.data.length === 0">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    No data available. Click "Load Transactions" to fetch data.
                                </td>
                            </tr>
                        </template>
                        <template x-for="tx in chainTx.data" :key="tx.transaction_hash">
                            <tr>
                                <td x-text="new Date(tx.transaction_time * 1000).toLocaleString()"></td>
                                <td><span class="badge text-bg-secondary" x-text="tx.exchange_name"></span></td>
                                <td><strong x-text="tx.asset_symbol"></strong></td>
                                <td>
                                    <span class="badge" :class="tx.transfer_type === 1 ? 'text-bg-success' : tx.transfer_type === 2 ? 'text-bg-danger' : 'text-bg-warning'" x-text="tx.transfer_type === 1 ? 'Inflow' : tx.transfer_type === 2 ? 'Outflow' : 'Internal'"></span>
                                </td>
                                <td class="text-end" x-text="tx.asset_quantity.toLocaleString()"></td>
                                <td class="text-end" x-text="'$' + (tx.amount_usd / 1e3).toFixed(2) + 'K'"></td>
                                <td>
                                    <code class="small" x-text="tx.transaction_hash.substring(0, 10) + '...'"></code>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
