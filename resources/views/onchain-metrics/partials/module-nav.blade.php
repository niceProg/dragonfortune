<div class="row mb-3">
    <div class="col-12">
        <div class="df-panel p-3 shadow-sm rounded">
            <div class="d-flex flex-wrap gap-2">
                <a href="/onchain-metrics/mvrv-zscore"
                   class="btn btn-sm {{ request()->routeIs('onchain-metrics.mvrv-zscore') ? 'btn-primary' : 'btn-outline-primary' }}">
                    MVRV & Z-Score
                </a>
                <a href="/onchain-metrics/lth-sth-supply"
                   class="btn btn-sm {{ request()->routeIs('onchain-metrics.lth-sth-supply') ? 'btn-primary' : 'btn-outline-primary' }}">
                    LTH vs STH Supply
                </a>
                <a href="/onchain-metrics/exchange-netflow"
                   class="btn btn-sm {{ request()->routeIs('onchain-metrics.exchange-netflow') ? 'btn-primary' : 'btn-outline-primary' }}">
                    Exchange Netflow
                </a>
                <a href="/onchain-metrics/realized-cap-hodl"
                   class="btn btn-sm {{ request()->routeIs('onchain-metrics.realized-cap-hodl') ? 'btn-primary' : 'btn-outline-primary' }}">
                    Realized Cap & HODL
                </a>
                <a href="/onchain-metrics/reserve-risk-sopr"
                   class="btn btn-sm {{ request()->routeIs('onchain-metrics.reserve-risk-sopr') ? 'btn-primary' : 'btn-outline-primary' }}">
                    Reserve Risk / SOPR
                </a>
                <a href="/onchain-metrics/miner-metrics"
                   class="btn btn-sm {{ request()->routeIs('onchain-metrics.miner-metrics') ? 'btn-primary' : 'btn-outline-primary' }}">
                    Miner Metrics
                </a>
                <a href="/onchain-metrics/whale-holdings"
                   class="btn btn-sm {{ request()->routeIs('onchain-metrics.whale-holdings') ? 'btn-primary' : 'btn-outline-primary' }}">
                    Whale Holdings
                </a>
            </div>
        </div>
    </div>
</div>
