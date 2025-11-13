{{-- Liquidity Heatmap Chart --}}
<div class="df-panel p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">🔥 Liquidity Heatmap</h5>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary active">Depth 10</button>
            <button class="btn btn-outline-secondary">Depth 20</button>
            <button class="btn btn-outline-secondary">Depth 50</button>
        </div>
    </div>

    <div class="chart-container" style="height: 300px; position: relative;">
        <canvas id="liquidityChart"></canvas>
        
        <!-- Chart Loading State -->
        <div x-show="$parent.loading" class="position-absolute top-50 start-50 translate-middle">
            <div class="text-center">
                <div class="spinner-border text-primary"></div>
                <div class="small text-secondary mt-2">Loading liquidity chart...</div>
            </div>
        </div>
        
        <!-- No Data State -->
        <div x-show="!$parent.loading && !$parent.marketDepthData" class="position-absolute top-50 start-50 translate-middle text-center">
            <div class="text-muted">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <div class="mt-2">No liquidity data available</div>
            </div>
        </div>
    </div>

    <!-- Chart Legend -->
    <div class="mt-3 d-flex justify-content-center gap-4">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle" style="width: 12px; height: 12px; background-color: rgba(34, 197, 94, 0.6);"></div>
            <small class="text-secondary">Bid Volume</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle" style="width: 12px; height: 12px; background-color: rgba(239, 68, 68, 0.6);"></div>
            <small class="text-secondary">Ask Volume</small>
        </div>
    </div>
</div>