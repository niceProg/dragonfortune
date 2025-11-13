{{-- Liquidity Imbalance Card --}}
<div class="df-panel p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">⚖️ Liquidity Imbalance</h5>
        <span class="badge text-bg-info">Real-time</span>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-secondary">Bid Volume</span>
            <span class="fw-semibold text-success" x-text="formatVolume($parent.pressureData?.bid_volume || 0)">$0</span>
        </div>
        <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar bg-success" 
                 role="progressbar" 
                 :style="`width: ${getBidVolumePercentage()}%`">
            </div>
        </div>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-secondary">Ask Volume</span>
            <span class="fw-semibold text-danger" x-text="formatVolume($parent.pressureData?.ask_volume || 0)">$0</span>
        </div>
        <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar bg-danger" 
                 role="progressbar" 
                 :style="`width: ${getAskVolumePercentage()}%`">
            </div>
        </div>
    </div>

    <div class="border-top pt-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="small text-secondary">Net Imbalance</span>
            <span class="fw-bold" 
                  :class="($parent.pressureData?.imbalance || 0) >= 0 ? 'text-success' : 'text-danger'"
                  x-text="formatVolume($parent.pressureData?.imbalance || 0)">$0</span>
        </div>
        <div class="small text-muted mt-1">
            Ratio: <span x-text="formatRatio($parent.pressureData?.imbalance_ratio || 0)">0.0%</span>
        </div>
    </div>

    <script>
        function getBidVolumePercentage() {
            const bidVolume = this.$parent.pressureData?.bid_volume || 0;
            const totalVolume = this.$parent.pressureData?.total_volume || 1;
            return ((bidVolume / totalVolume) * 100).toFixed(1);
        }

        function getAskVolumePercentage() {
            const askVolume = this.$parent.pressureData?.ask_volume || 0;
            const totalVolume = this.$parent.pressureData?.total_volume || 1;
            return ((askVolume / totalVolume) * 100).toFixed(1);
        }

        function formatVolume(value) {
            if (!value || isNaN(value)) return '$0';
            if (Math.abs(value) >= 1e6) return (value >= 0 ? '+$' : '-$') + (Math.abs(value) / 1e6).toFixed(2) + 'M';
            if (Math.abs(value) >= 1e3) return (value >= 0 ? '+$' : '-$') + (Math.abs(value) / 1e3).toFixed(2) + 'K';
            return (value >= 0 ? '+$' : '-$') + Math.abs(value).toFixed(0);
        }

        function formatRatio(value) {
            return ((value || 0) * 100).toFixed(1) + '%';
        }
    </script>
</div>