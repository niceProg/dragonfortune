{{-- Quick Stats Card --}}
<div class="df-panel p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">⚡ Quick Stats</h5>
        <span class="badge text-bg-warning">Live</span>
    </div>

    <div class="row g-2">
        <div class="col-12">
            <div class="stat-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary">Best Bid</span>
                    <span class="fw-semibold text-success" x-text="formatPrice(getBestBid())">$0</span>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <div class="stat-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary">Best Ask</span>
                    <span class="fw-semibold text-danger" x-text="formatPrice(getBestAsk())">$0</span>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <div class="stat-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary">Mid Price</span>
                    <span class="fw-semibold text-primary" x-text="formatPrice(getMidPrice())">$0</span>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <div class="stat-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary">Spread</span>
                    <span class="fw-semibold" x-text="formatSpread(getSpread())">$0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Market Status Indicator -->
    <div class="mt-3 pt-3 border-top">
        <div class="d-flex justify-content-between align-items-center">
            <span class="small text-secondary">Market Status</span>
            <span class="badge" :class="getMarketStatusBadge()" x-text="getMarketStatus()">UNKNOWN</span>
        </div>
        <div class="small text-muted mt-1" x-text="getMarketStatusDescription()">Analyzing market conditions...</div>
    </div>

    <script>
        function getBestBid() {
            const snapshot = (this.$parent.orderbookData || [])[0];
            return snapshot?.best_bid || 0;
        }

        function getBestAsk() {
            const snapshot = (this.$parent.orderbookData || [])[0];
            return snapshot?.best_ask || 0;
        }

        function getMidPrice() {
            const snapshot = (this.$parent.orderbookData || [])[0];
            return snapshot?.mid_price || 0;
        }

        function getSpread() {
            const snapshot = (this.$parent.orderbookData || [])[0];
            return snapshot?.spread || 0;
        }

        function getMarketStatus() {
            const spreadBps = this.$parent.pressureData?.spread_bps || 0;
            const liquidityScore = this.$parent.pressureData?.liquidity_score || 0;
            
            if (spreadBps < 5 && liquidityScore > 70) return 'TIGHT';
            if (spreadBps < 10 && liquidityScore > 50) return 'NORMAL';
            if (spreadBps < 20) return 'WIDE';
            return 'VOLATILE';
        }

        function getMarketStatusBadge() {
            const status = this.getMarketStatus();
            switch (status) {
                case 'TIGHT': return 'text-bg-success';
                case 'NORMAL': return 'text-bg-primary';
                case 'WIDE': return 'text-bg-warning';
                case 'VOLATILE': return 'text-bg-danger';
                default: return 'text-bg-secondary';
            }
        }

        function getMarketStatusDescription() {
            const status = this.getMarketStatus();
            switch (status) {
                case 'TIGHT': return 'Low spread, high liquidity';
                case 'NORMAL': return 'Balanced market conditions';
                case 'WIDE': return 'Higher spread, moderate liquidity';
                case 'VOLATILE': return 'High spread, low liquidity';
                default: return 'Analyzing market conditions...';
            }
        }

        function formatPrice(value) {
            if (!value || isNaN(value)) return '$0';
            return '$' + Number(value).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatSpread(value) {
            if (!value || isNaN(value)) return '$0';
            return '$' + value.toFixed(2);
        }
    </script>
</div>