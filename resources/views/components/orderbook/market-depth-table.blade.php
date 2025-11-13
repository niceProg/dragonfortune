{{-- Market Depth Table --}}
<div class="df-panel p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📊 Market Depth Analysis</h5>
        <span class="badge text-bg-info">Top 20</span>
    </div>

    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-sm table-dark mb-0">
            <thead class="sticky-top">
                <tr>
                    <th>Level</th>
                    <th class="text-success">Bid Price</th>
                    <th class="text-success text-end">Bid Vol</th>
                    <th class="text-danger">Ask Price</th>
                    <th class="text-danger text-end">Ask Vol</th>
                    <th class="text-end">Imbalance</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(level, index) in getDepthLevels()" :key="index">
                    <tr>
                        <td class="text-muted small" x-text="index + 1">1</td>
                        <td class="text-success fw-semibold" x-text="level.bid ? formatPrice(level.bid.price) : '--'">--</td>
                        <td class="text-success text-end" x-text="level.bid ? formatVolume(level.bid.total) : '--'">--</td>
                        <td class="text-danger fw-semibold" x-text="level.ask ? formatPrice(level.ask.price) : '--'">--</td>
                        <td class="text-danger text-end" x-text="level.ask ? formatVolume(level.ask.total) : '--'">--</td>
                        <td class="text-end" :class="getImbalanceClass(level.imbalance)" x-text="formatImbalance(level.imbalance)">--</td>
                    </tr>
                </template>
                <tr x-show="getDepthLevels().length === 0">
                    <td colspan="6" class="text-center text-muted py-3">No market depth data available</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Summary Stats -->
    <div class="mt-3 pt-3 border-top">
        <div class="row g-3 text-center">
            <div class="col-md-3">
                <div class="small text-secondary">Total Bid Volume</div>
                <div class="fw-bold text-success" x-text="formatVolume(getTotalBidVolume())">$0</div>
            </div>
            <div class="col-md-3">
                <div class="small text-secondary">Total Ask Volume</div>
                <div class="fw-bold text-danger" x-text="formatVolume(getTotalAskVolume())">$0</div>
            </div>
            <div class="col-md-3">
                <div class="small text-secondary">Weighted Bid</div>
                <div class="fw-bold text-success" x-text="formatPrice(getWeightedBidPrice())">$0</div>
            </div>
            <div class="col-md-3">
                <div class="small text-secondary">Weighted Ask</div>
                <div class="fw-bold text-danger" x-text="formatPrice(getWeightedAskPrice())">$0</div>
            </div>
        </div>
    </div>

    <script>
        function getDepthLevels() {
            const bids = this.getBids();
            const asks = this.getAsks();
            const maxLevels = Math.max(bids.length, asks.length, 20);
            
            const levels = [];
            for (let i = 0; i < maxLevels; i++) {
                const bid = bids[i] || null;
                const ask = asks[i] || null;
                
                let imbalance = 0;
                if (bid && ask) {
                    imbalance = (bid.total - ask.total) / (bid.total + ask.total);
                } else if (bid) {
                    imbalance = 1;
                } else if (ask) {
                    imbalance = -1;
                }
                
                levels.push({ bid, ask, imbalance });
            }
            
            return levels.slice(0, 20);
        }

        function getBids() {
            return this.$parent.marketDepthData?.bids || [];
        }

        function getAsks() {
            return this.$parent.marketDepthData?.asks || [];
        }

        function getTotalBidVolume() {
            return this.$parent.marketDepthData?.depth_analysis?.total_bid_volume || 0;
        }

        function getTotalAskVolume() {
            return this.$parent.marketDepthData?.depth_analysis?.total_ask_volume || 0;
        }

        function getWeightedBidPrice() {
            return this.$parent.marketDepthData?.depth_analysis?.weighted_bid_price || 0;
        }

        function getWeightedAskPrice() {
            return this.$parent.marketDepthData?.depth_analysis?.weighted_ask_price || 0;
        }

        function getImbalanceClass(imbalance) {
            if (imbalance > 0.2) return 'text-success fw-bold';
            if (imbalance < -0.2) return 'text-danger fw-bold';
            return 'text-secondary';
        }

        function formatPrice(value) {
            if (!value || isNaN(value)) return '$0';
            return '$' + Number(value).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatVolume(value) {
            if (!value || isNaN(value)) return '$0';
            if (value >= 1e6) return '$' + (value / 1e6).toFixed(2) + 'M';
            if (value >= 1e3) return '$' + (value / 1e3).toFixed(2) + 'K';
            return '$' + value.toFixed(0);
        }

        function formatImbalance(value) {
            if (!value || isNaN(value)) return '0%';
            return (value >= 0 ? '+' : '') + (value * 100).toFixed(1) + '%';
        }
    </script>
</div>