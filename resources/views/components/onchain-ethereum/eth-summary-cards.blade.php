<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Average Gas Price</span>
            <span class="badge bg-primary bg-opacity-10 text-primary">Gas</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getGasPriceClass()" x-text="formatGasPrice(gasSummary?.latest?.gas_price_mean)">--</div>
        <div class="small" :class="getGasPriceChangeClass()" x-text="formatPercentage(gasSummary?.change_pct?.gas_price_mean)">--</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Network Utilization</span>
            <span class="badge bg-success bg-opacity-10 text-success">Usage</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getUtilizationClass()" x-text="formatUtilization(gasSummary?.latest?.gas_used_mean, gasSummary?.latest?.gas_limit_mean)">--</div>
        <div class="small text-muted">Gas Used / Gas Limit</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Latest Staking Inflow</span>
            <span class="badge bg-warning bg-opacity-10 text-warning">Staking</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getStakingInflowClass()" x-text="formatETH(stakingSummary?.latest?.staking_inflow_total)">--</div>
        <div class="small" :class="getStakingChangeClass()" x-text="formatPercentage(stakingSummary?.latest?.change_pct)">--</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Staking Momentum</span>
            <span class="badge bg-info bg-opacity-10 text-info">Trend</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getMomentumClass()" x-text="formatPercentage(stakingSummary?.momentum_pct)">--</div>
        <div class="small text-muted" x-text="getMomentumLabel()">--</div>
    </div>
</div>