<div class="df-panel p-4 h-100">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-1">ETH 2.0 Staking Deposits</h5>
            <small class="text-secondary">Daily staking inflows and momentum trends</small>
        </div>
        <div class="d-flex gap-2">
            <span x-show="loadingStates.staking" class="spinner-border spinner-border-sm text-primary"></span>
        </div>
    </div>
    
    <div style="height: 350px; position: relative;">
        <canvas x-ref="stakingChart"></canvas>
        
        <!-- Empty State -->
        <div x-show="!loadingStates.staking && (!stakingData || stakingData.length === 0)" 
             class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mb-3">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <p class="mb-0">No staking data available</p>
            <small>Try refreshing or adjusting the time window</small>
        </div>
    </div>
    
    <!-- Staking Metrics Summary -->
    <div class="row g-3 mt-3">
        <div class="col-4">
            <div class="text-center p-2 rounded" style="background: rgba(34, 197, 94, 0.1);">
                <div class="small text-muted">7-Day Avg</div>
                <div class="fw-bold" x-text="formatETH(stakingSummary?.averages?.avg_7d)">--</div>
            </div>
        </div>
        <div class="col-4">
            <div class="text-center p-2 rounded" style="background: rgba(59, 130, 246, 0.1);">
                <div class="small text-muted">30-Day Avg</div>
                <div class="fw-bold" x-text="formatETH(stakingSummary?.averages?.avg_30d)">--</div>
            </div>
        </div>
        <div class="col-4">
            <div class="text-center p-2 rounded" style="background: rgba(139, 92, 246, 0.1);">
                <div class="small text-muted">Momentum</div>
                <div class="fw-bold" :class="getMomentumClass()" x-text="formatPercentage(stakingSummary?.momentum_pct)">--</div>
            </div>
        </div>
    </div>
</div>