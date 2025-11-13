<div class="df-panel p-4 h-100">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-1">⚖️ Market Indicators</h5>
            <small class="text-secondary">Estimated leverage ratios and market gauges</small>
        </div>
        <div class="d-flex gap-2">
            <span x-show="loadingStates.indicators" class="spinner-border spinner-border-sm text-primary"></span>
        </div>
    </div>
    
    <div style="height: 350px; position: relative;">
        <canvas x-ref="indicatorsChart"></canvas>
        
        <!-- Empty State -->
        <div x-show="!loadingStates.indicators && (!indicatorsData || indicatorsData.length === 0)" 
             class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mb-3">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <p class="mb-0">No market indicators available</p>
            <small>Try refreshing or adjusting filters</small>
        </div>
    </div>
    
    <!-- Risk Zones Legend -->
    <div class="row g-2 mt-3">
        <div class="col-4">
            <div class="d-flex align-items-center gap-2">
                <div class="legend-dot" style="background-color: #22c55e;"></div>
                <small class="text-secondary">Low Risk (< 0.3)</small>
            </div>
        </div>
        <div class="col-4">
            <div class="d-flex align-items-center gap-2">
                <div class="legend-dot" style="background-color: #f59e0b;"></div>
                <small class="text-secondary">Medium Risk (0.3-0.5)</small>
            </div>
        </div>
        <div class="col-4">
            <div class="d-flex align-items-center gap-2">
                <div class="legend-dot" style="background-color: #ef4444;"></div>
                <small class="text-secondary">High Risk (> 0.5)</small>
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