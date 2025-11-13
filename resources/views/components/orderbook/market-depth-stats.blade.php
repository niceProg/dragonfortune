{{-- Market Depth Stats Card --}}
<div class="df-panel p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📈 Market Depth Stats</h5>
        <span class="badge text-bg-primary">Analysis</span>
    </div>

    <div class="row g-2">
        <div class="col-6">
            <div class="stat-item text-center">
                <div class="small text-secondary">Depth Score</div>
                <div class="h4 mb-0 text-primary" x-text="($parent.pressureData?.depth_score || 0).toFixed(1)">0.0</div>
                <small class="text-muted">Stability</small>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-item text-center">
                <div class="small text-secondary">Liquidity Score</div>
                <div class="h4 mb-0 text-info" x-text="($parent.pressureData?.liquidity_score || 0).toFixed(1)">0.0</div>
                <small class="text-muted">Levels</small>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-secondary">Total Volume</span>
            <span class="fw-semibold" x-text="formatVolume($parent.pressureData?.total_volume || 0)">$0</span>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-secondary">Bid Levels</span>
            <span class="fw-semibold text-success" x-text="($parent.marketDepthData?.depth_analysis?.bid_levels || 0)">0</span>
        </div>
        
        <div class="d-flex justify-content-between align-items-center">
            <span class="small text-secondary">Ask Levels</span>
            <span class="fw-semibold text-danger" x-text="($parent.marketDepthData?.depth_analysis?.ask_levels || 0)">0</span>
        </div>
    </div>

    <!-- Liquidity Walls Indicator -->
    <div class="mt-3 pt-3 border-top" x-show="($parent.marketDepthData?.depth_analysis?.liquidity_walls || []).length > 0">
        <div class="small text-secondary mb-2">🧱 Liquidity Walls Detected</div>
        <template x-for="wall in ($parent.marketDepthData?.depth_analysis?.liquidity_walls || []).slice(0, 3)" :key="wall.price">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small" :class="wall.side === 'bid' ? 'text-success' : 'text-danger'" x-text="wall.side.toUpperCase()">BID</span>
                <span class="small fw-semibold" x-text="formatPrice(wall.price)">$0</span>
                <span class="small" x-text="formatVolume(wall.volume)">$0</span>
            </div>
        </template>
    </div>

    <script>
        function formatVolume(value) {
            if (!value || isNaN(value)) return '$0';
            if (value >= 1e6) return '$' + (value / 1e6).toFixed(2) + 'M';
            if (value >= 1e3) return '$' + (value / 1e3).toFixed(2) + 'K';
            return '$' + value.toFixed(0);
        }

        function formatPrice(value) {
            if (!value || isNaN(value)) return '$0';
            return '$' + Number(value).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    </script>
</div>