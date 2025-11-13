<div class="df-panel p-4 h-100">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-1">Network Gas Metrics</h5>
            <small class="text-secondary">Gas price, usage, and network utilization trends</small>
        </div>
        <div class="d-flex gap-2">
            <span x-show="loadingStates.gas" class="spinner-border spinner-border-sm text-primary"></span>
        </div>
    </div>
    
    <div style="height: 350px; position: relative;">
        <canvas x-ref="gasChart"></canvas>
        
        <!-- Empty State -->
        <div x-show="!loadingStates.gas && (!gasData || gasData.length === 0)" 
             class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mb-3">
                <path d="M3 3v18h18"/>
                <path d="M7 12l3-3 3 3 5-5"/>
            </svg>
            <p class="mb-0">No gas data available</p>
            <small>Try refreshing or adjusting the time window</small>
        </div>
    </div>
    
    <!-- Gas Metrics Legend -->
    <div class="row g-2 mt-3">
        <div class="col-4">
            <div class="d-flex align-items-center gap-2">
                <div class="legend-dot" style="background-color: #3b82f6;"></div>
                <small class="text-secondary">Gas Price (Gwei)</small>
            </div>
        </div>
        <div class="col-4">
            <div class="d-flex align-items-center gap-2">
                <div class="legend-dot" style="background-color: #22c55e;"></div>
                <small class="text-secondary">Gas Used %</small>
            </div>
        </div>
        <div class="col-4">
            <div class="d-flex align-items-center gap-2">
                <div class="legend-dot" style="background-color: #f59e0b;"></div>
                <small class="text-secondary">Gas Limit</small>
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