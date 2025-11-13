<div class="tab-pane fade show active" id="exchange-assets" role="tabpanel">
    <div class="d-flex flex-column gap-3">
        <!-- Filters -->
        <div class="df-panel p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-secondary mb-1">Exchange <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" x-model="exchangeAssets.exchange" placeholder="Binance">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-secondary mb-1">Per Page</label>
                    <select class="form-select" x-model="exchangeAssets.perPage">
                        <option value="20">20</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-secondary mb-1">Page</label>
                    <input type="number" class="form-control" x-model="exchangeAssets.page" min="1" value="1">
                </div>
                <div class="col-md-5">
                    <button class="btn btn-primary w-100" @click="loadExchangeAssets()" :disabled="isLoading">
                        <i class="fas fa-wallet"></i> Load Assets
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row g-3" x-show="exchangeAssets.data.length > 0">
            <div class="col-md-4">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Total Wallets</div>
                    <div class="h3 mb-0" x-text="exchangeAssets.data.length"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Total Balance (USD)</div>
                    <div class="h3 mb-0" x-text="'$' + (exchangeAssets.totalBalanceUSD / 1e9).toFixed(2) + 'B'"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Unique Assets</div>
                    <div class="h3 mb-0" x-text="exchangeAssets.uniqueAssets"></div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="df-panel">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Wallet Address</th>
                            <th>Asset</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Value (USD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="spinner-border text-primary"></div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && exchangeAssets.data.length === 0">
                            <tr>
                                <td colspan="5" class="text-center py-5 text-secondary">
                                    No data available. Click "Load Assets" to fetch data.
                                </td>
                            </tr>
                        </template>
                        <template x-for="(asset, index) in exchangeAssets.data" :key="index">
                            <tr>
                                <td>
                                    <code class="small" x-text="asset.wallet_address ? (asset.wallet_address.substring(0, 10) + '...' + asset.wallet_address.substring(asset.wallet_address.length - 8)) : 'N/A'"></code>
                                </td>
                                <td>
                                    <strong x-text="asset.symbol || 'N/A'"></strong>
                                    <small class="text-secondary d-block" x-text="asset.assets_name || ''"></small>
                                </td>
                                <td class="text-end" x-text="(asset.balance || 0).toLocaleString()"></td>
                                <td class="text-end" x-text="'$' + (asset.price || 0).toLocaleString()"></td>
                                <td class="text-end">
                                    <strong x-text="'$' + ((asset.balance_usd || 0) / 1e6).toFixed(2) + 'M'"></strong>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
