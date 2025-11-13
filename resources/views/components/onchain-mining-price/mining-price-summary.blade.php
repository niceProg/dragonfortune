<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Current MPI</span>
            <span class="badge bg-primary bg-opacity-10 text-primary">MPI</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getMPIClass()" x-text="formatMPI(mpiSummary?.latest?.mpi)">--</div>
        <div class="small" :class="getMPIChangeClass()" x-text="formatPercentage(mpiSummary?.latest?.change_pct)">--</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Z-Score</span>
            <span class="badge bg-success bg-opacity-10 text-success">Stats</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getZScoreClass()" x-text="formatZScore(mpiSummary?.stats?.z_score)">--</div>
        <div class="small text-muted" x-text="getZScoreInterpretation()">--</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Current Price</span>
            <span class="badge bg-warning bg-opacity-10 text-warning">Price</span>
        </div>
        <div class="h3 mb-1 fw-bold" x-text="formatPrice(currentPrice, selectedAsset)">--</div>
        <div class="small" :class="getPriceChangeClass()" x-text="formatPriceChange()">--</div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="df-panel p-3 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Miner Sentiment</span>
            <span class="badge bg-info bg-opacity-10 text-info">Sentiment</span>
        </div>
        <div class="h3 mb-1 fw-bold" :class="getMinerSentimentClass()" x-text="getMinerSentiment()">--</div>
        <div class="small text-muted">Based on MPI value</div>
    </div>
</div>