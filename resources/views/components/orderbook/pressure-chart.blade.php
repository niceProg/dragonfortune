{{-- Book Pressure History Chart --}}
<div class="df-panel p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📈 Book Pressure History</h5>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary active">1H</button>
            <button class="btn btn-outline-secondary">4H</button>
            <button class="btn btn-outline-secondary">1D</button>
        </div>
    </div>

    <div class="chart-container" style="height: 300px; position: relative;">
        <canvas id="pressureChart"></canvas>
        
        <!-- Chart Loading State -->
        <div x-show="$parent.loading" class="position-absolute top-50 start-50 translate-middle">
            <div class="text-center">
                <div class="spinner-border text-primary"></div>
                <div class="small text-secondary mt-2">Loading pressure chart...</div>
            </div>
        </div>
        
        <!-- No Data State -->
        <div x-show="!$parent.loading && !$parent.pressureData" class="position-absolute top-50 start-50 translate-middle text-center">
            <div class="text-muted">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3v18h18"/>
                    <path d="M8 17l4-4 4 4"/>
                    <path d="M8 12l4-4 4 4"/>
                </svg>
                <div class="mt-2">No pressure data available</div>
            </div>
        </div>
    </div>

    <!-- Chart Legend -->
    <div class="mt-3 d-flex justify-content-center gap-4">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle" style="width: 12px; height: 12px; background-color: #22c55e;"></div>
            <small class="text-secondary">Bid Pressure</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle" style="width: 12px; height: 12px; background-color: #ef4444;"></div>
            <small class="text-secondary">Ask Pressure</small>
        </div>
    </div>
</div>