{{-- Book Pressure Card - Simplified --}}
<div class="df-panel p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📊 Book Pressure Analysis</h5>
        <span class="badge" :class="getPressureBadgeClass()" x-text="pressureDirection.toUpperCase()">NEUTRAL</span>
    </div>

    <div class="row g-3">
        <!-- Current Pressure -->
        <div class="col-md-3">
            <div class="stat-item">
                <div class="small text-secondary mb-1">Current Pressure</div>
                <div class="h4 mb-0" :class="getPressureClass()" x-text="formatPressure(currentPressure)">50.0%</div>
                <small class="text-muted">Bid vs Ask Balance</small>
            </div>
        </div>

        <!-- Pressure Strength -->
        <div class="col-md-3">
            <div class="stat-item">
                <div class="small text-secondary mb-1">Strength</div>
                <div class="h4 mb-0" x-text="pressureStrength.toFixed(1) + '%'">0.0%</div>
                <small class="text-muted">Pressure Intensity</small>
            </div>
        </div>

        <!-- Imbalance -->
        <div class="col-md-3">
            <div class="stat-item">
                <div class="small text-secondary mb-1">Imbalance</div>
                <div class="h4 mb-0" :class="getImbalanceClass()" x-text="formatVolume(imbalance)">$0</div>
                <small class="text-muted">Bid - Ask Volume</small>
            </div>
        </div>

        <!-- Total Volume -->
        <div class="col-md-3">
            <div class="stat-item">
                <div class="small text-secondary mb-1">Total Volume</div>
                <div class="h4 mb-0" x-text="formatVolume(pressureData?.total_volume || 0)">$0</div>
                <small class="text-muted">Combined Volume</small>
            </div>
        </div>
    </div>

    <!-- Pressure Bar Visualization -->
    <div class="mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-success fw-semibold">Bid Pressure</small>
            <small class="text-danger fw-semibold">Ask Pressure</small>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-success" 
                 role="progressbar" 
                 :style="`width: ${(currentPressure * 100).toFixed(1)}%`"
                 :aria-valuenow="(currentPressure * 100).toFixed(1)" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                <span class="fw-semibold" x-text="formatPressure(currentPressure)">50.0%</span>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-success">Strong Bid</small>
            <small class="text-secondary">Balanced</small>
            <small class="text-danger">Strong Ask</small>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-3">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <div class="small text-secondary mt-2">Loading pressure data...</div>
    </div>
</div>

