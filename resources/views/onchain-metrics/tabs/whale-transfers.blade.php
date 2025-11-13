<div class="tab-pane fade" id="whale-transfers" role="tabpanel">
    <div class="d-flex flex-column gap-3">
        <!-- Filters -->
        <div class="df-panel p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-secondary mb-1">Symbol <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" x-model="whaleTransfers.symbol" placeholder="BTC">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" @click="loadWhaleTransfers()" :disabled="isLoading">
                        <i class="fas fa-fish"></i> Load Whale Transfers
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3" x-show="whaleTransfers.data.length > 0">
            <div class="col-md-4">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Total Transfers</div>
                    <div class="h3 mb-0" x-text="whaleTransfers.data.length"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Total Volume</div>
                    <div class="h3 mb-0" x-text="'$' + (whaleTransfers.totalVolume / 1e6).toFixed(2) + 'M'"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Avg Transfer Size</div>
                    <div class="h3 mb-0" x-text="'$' + (whaleTransfers.avgSize / 1e6).toFixed(2) + 'M'"></div>
                </div>
            </div>
        </div>

        <!-- Whale Transfers Table -->
        <div class="df-panel">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Asset</th>
                            <th>Blockchain</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Value (USD)</th>
                            <th>From</th>
                            <th>To</th>
                            <th>TX Hash</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!isLoading && whaleTransfers.data.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-secondary">
                                    No data available. Click "Load Whale Transfers" to fetch data.
                                </td>
                            </tr>
                        </template>
                        <template x-for="tx in whaleTransfers.data" :key="tx.transaction_hash">
                            <tr>
                                <td x-text="new Date(tx.block_timestamp * 1000).toLocaleString()"></td>
                                <td><strong x-text="tx.asset_symbol"></strong></td>
                                <td><span class="badge text-bg-info" x-text="tx.blockchain_name"></span></td>
                                <td class="text-end" x-text="parseFloat(tx.asset_quantity).toFixed(4)"></td>
                                <td class="text-end">
                                    <strong x-text="'$' + (parseFloat(tx.amount_usd) / 1e6).toFixed(2) + 'M'"></strong>
                                </td>
                                <td><small class="text-secondary" x-text="tx.from"></small></td>
                                <td><small class="text-secondary" x-text="tx.to"></small></td>
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
