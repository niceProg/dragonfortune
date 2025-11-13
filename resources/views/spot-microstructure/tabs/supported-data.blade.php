<!-- Supported Data Tab -->
<div class="d-flex flex-column gap-3">
    <div class="row g-3">
        <!-- Supported Coins -->
        <div class="col-md-6">
            <div class="df-panel p-3">
                <h5 class="mb-3">Supported Coins (<span x-text="supportedCoins.length"></span>)</h5>
                <div style="max-height: 400px; overflow-y: auto;">
                    <div class="d-flex flex-wrap gap-2">
                        <template x-for="coin in supportedCoins" :key="coin">
                            <span class="badge text-bg-primary" x-text="coin"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supported Exchanges -->
        <div class="col-md-6">
            <div class="df-panel p-3">
                <h5 class="mb-3">Supported Exchanges</h5>
                <div style="max-height: 400px; overflow-y: auto;">
                    <template x-for="(pairs, exchange) in supportedExchangePairs" :key="exchange">
                        <div class="mb-3">
                            <h6 class="text-primary">
                                <span x-text="exchange"></span>
                                <span class="badge text-bg-secondary" x-text="pairs.length + ' pairs'"></span>
                            </h6>
                            <div class="d-flex flex-wrap gap-1">
                                <template x-for="pair in pairs.slice(0, 10)" :key="pair.instrument_id">
                                    <span class="badge text-bg-light text-dark small" x-text="pair.base_asset + '/' + pair.quote_asset"></span>
                                </template>
                                <span class="badge text-bg-secondary small" x-show="pairs.length > 10" x-text="'+ ' + (pairs.length - 10) + ' more'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
