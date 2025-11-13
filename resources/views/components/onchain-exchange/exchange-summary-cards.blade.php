<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Total Reserves</span>
            <span class="badge bg-primary bg-opacity-10 text-primary">Reserves</span>
        </div>
        <div class="h3 mb-1 fw-bold" x-text="formatReserve(reserveSummary?.totals?.latest_reserve, selectedAsset)">--</div>
        <div class="small text-secondary" x-text="formatUSD(reserveSummary?.totals?.latest_reserve_usd)">--</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">24h Flow</span>
            <span class="badge bg-success bg-opacity-10 text-success">Flow</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getReserveChangeClass()" x-text="formatReserveChange(reserveSummary?.totals?.change, selectedAsset)">--</div>
        <div class="small" :class="getReserveChangeClass()" x-text="formatUSD(reserveSummary?.totals?.change_usd)">--</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Flow Direction</span>
            <span class="badge bg-warning bg-opacity-10 text-warning">Trend</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getFlowDirectionClass()" x-text="getFlowDirection()">--</div>
        <div class="small text-muted">Based on net changes</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Market Risk</span>
            <span class="badge bg-danger bg-opacity-10 text-danger">Risk</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getLeverageRiskClass()" x-text="formatLeverage(currentLeverageRatio)">--</div>
        <div class="small" :class="getLeverageRiskClass()" x-text="getLeverageRiskLabel()">--</div>
    </div>
</div>