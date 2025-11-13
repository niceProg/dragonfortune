@props(['price' => 65420.00, 'change' => 1250.00, 'changePercent' => 1.95, 'high24h' => 66800.00, 'low24h' => 64200.00, 'volume' => 28500000000])

<div class="df-panel p-3" x-data="tradingChart()">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="text-muted small">Last Price</div>
            <div class="h4 fw-bold mb-0" x-text="formatPrice(price)">${{ number_format($price, 2) }}</div>
            <div class="small"
                 :class="changePercent >= 0 ? 'text-success' : 'text-danger'"
                 x-text="(change >= 0 ? '+' : '') + change.toFixed(2) + ' (' + (changePercent >= 0 ? '+' : '') + changePercent.toFixed(2) + '%)'">
                {{ ($change >= 0 ? '+' : '') . number_format($change, 2) }} ({{ ($changePercent >= 0 ? '+' : '') . number_format($changePercent, 2) }}%)
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">24h High</div>
            <div class="fw-semibold" x-text="formatPrice(high24h)">${{ number_format($high24h, 2) }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">24h Low</div>
            <div class="fw-semibold" x-text="formatPrice(low24h)">${{ number_format($low24h, 2) }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Volume</div>
            <div class="fw-semibold" x-text="formatVolume(volume)">{{ number_format($volume / 1000000000, 1) }}B BTC</div>
        </div>
    </div>
</div>
