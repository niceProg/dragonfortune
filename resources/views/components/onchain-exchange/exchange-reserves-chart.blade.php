<div class="df-panel p-4 h-100">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-1">🏦 Exchange Reserves</h5>
            <small class="text-secondary">Reserve balances across major exchanges</small>
        </div>
        <div class="d-flex gap-2">
            <span x-show="loadingStates.reserves" class="spinner-border spinner-border-sm text-primary"></span>
        </div>
    </div>
    
    <div style="height: 350px; position: relative;">
        <canvas x-ref="reservesChart"></canvas>
        
        <!-- Empty State -->
        <div x-show="!loadingStates.reserves && (!reservesData || reservesData.length === 0)" 
             class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mb-3">
                <rect x="3" y="4" width="18" height="4"/>
                <rect x="3" y="10" width="18" height="4"/>
                <rect x="3" y="16" width="18" height="4"/>
            </svg>
            <p class="mb-0">No reserves data available</p>
            <small>Try selecting a different asset or exchange</small>
        </div>
    </div>
    
    <!-- Reserves Metrics Legend -->
    <div class="row g-2 mt-3">
        <div class="col-6">
            <div class="d-flex align-items-center gap-2">
                <div class="legend-dot" style="background-color: #3b82f6;"></div>
                <small class="text-secondary">Reserve Amount</small>
            </div>
        </div>
        <div class="col-6">
            <div class="d-flex align-items-center gap-2">
                <div class="legend-dot" style="background-color: #22c55e;"></div>
                <small class="text-secondary">USD Value</small>
            </div>
        </div>
    </div>
</div>

<style>
.legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
</style>