<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="df-panel p-3 shadow-sm rounded bg-white d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-end gap-4">
                <div class="d-flex flex-column">
                    <label class="form-label text-uppercase small fw-semibold mb-1 text-muted">Asset</label>
                    <select class="form-select form-select-sm rounded shadow-sm"
                            style="min-width: 160px; z-index: 1200;"
                            x-model="$store.onchainMetrics.selectedAsset"
                            @change="$store.onchainMetrics.setAsset($event.target.value)">
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="SOL">Solana (SOL)</option>
                        <option value="STABLECOINS">Stablecoins</option>
                    </select>
                </div>
                <div class="d-flex flex-column">
                    <label class="form-label text-uppercase small fw-semibold mb-1 text-muted">Time Range</label>
                    <select class="form-select form-select-sm rounded shadow-sm"
                            style="min-width: 140px; z-index: 1200;"
                            x-model="$store.onchainMetrics.selectedRange"
                            @change="$store.onchainMetrics.setRange($event.target.value)">
                        <option value="7D">7 Days</option>
                        <option value="30D">30 Days</option>
                        <option value="90D">90 Days</option>
                        <option value="180D">180 Days</option>
                    </select>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-muted small">
                    <span class="fw-semibold text-uppercase d-block">Active Asset</span>
                    <span x-text="$store.onchainMetrics.assetLabel()"></span>
                </div>
                <button class="btn btn-primary btn-sm d-flex align-items-center gap-2"
                        :class="{ 'disabled': $store.onchainMetrics.loading }"
                        @click="$store.onchainMetrics.refresh()">
                    <span class="spinner-border spinner-border-sm"
                          role="status"
                          aria-hidden="true"
                          x-show="$store.onchainMetrics.loading"></span>
                    <span x-text="$store.onchainMetrics.loading ? 'Refreshing...' : 'Refresh'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
