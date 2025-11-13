{{-- Market Summary --}}
<div class="df-panel p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📋 Market Summary</h5>
        <span class="badge text-bg-primary">Analysis</span>
    </div>

    <!-- Key Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="stat-item">
                <div class="small text-secondary mb-1">Market Pressure</div>
                <div class="d-flex align-items-center gap-2">
                    <div class="h4 mb-0" :class="getPressureClass()" x-text="$parent.pressureDirection.toUpperCase()">NEUTRAL</div>
                    <span class="badge" :class="getPressureBadgeClass()" x-text="$parent.pressureStrength.toFixed(1) + '%'">0.0%</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-item">
                <div class="small text-secondary mb-1">Liquidity Quality</div>
                <div class="d-flex align-items-center gap-2">
                    <div class="h4 mb-0" :class="getLiquidityClass()" x-text="getLiquidityStatus()">UNKNOWN</div>
                    <span class="badge" :class="getLiquidityBadgeClass()" x-text="($parent.liquidityScore || 0).toFixed(0)">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Trading Signals -->
    <div class="mb-4">
        <h6 class="mb-3">🎯 Trading Signals</h6>
        <div class="d-flex flex-column gap-2">
            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(var(--bs-dark-rgb), 0.3);">
                <span class="small">Pressure Signal</span>
                <span class="badge" :class="getPressureSignalBadge()" x-text="getPressureSignal()">NEUTRAL</span>
            </div>
            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(var(--bs-dark-rgb), 0.3);">
                <span class="small">Liquidity Signal</span>
                <span class="badge" :class="getLiquiditySignalBadge()" x-text="getLiquiditySignal()">NEUTRAL</span>
            </div>
            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(var(--bs-dark-rgb), 0.3);">
                <span class="small">Spread Signal</span>
                <span class="badge" :class="getSpreadSignalBadge()" x-text="getSpreadSignal()">NEUTRAL</span>
            </div>
        </div>
    </div>

    <!-- Market Conditions -->
    <div class="mb-4">
        <h6 class="mb-3">🌡️ Market Conditions</h6>
        <div class="row g-2">
            <div class="col-6">
                <div class="text-center p-2 rounded" style="background: rgba(var(--bs-primary-rgb), 0.1);">
                    <div class="small text-secondary">Volatility</div>
                    <div class="fw-semibold" x-text="getVolatilityLevel()">LOW</div>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center p-2 rounded" style="background: rgba(var(--bs-info-rgb), 0.1);">
                    <div class="small text-secondary">Stability</div>
                    <div class="fw-semibold" x-text="getStabilityLevel()">HIGH</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="alert alert-info mb-0">
        <div class="small">
            <strong>💡 Trading Insight:</strong>
            <span x-text="getTradingInsight()">Analyzing market conditions...</span>
        </div>
    </div>

    <script>
        function getPressureClass() {
            if (this.$parent.pressureDirection === 'bullish') return 'text-success';
            if (this.$parent.pressureDirection === 'bearish') return 'text-danger';
            return 'text-secondary';
        }

        function getPressureBadgeClass() {
            if (this.$parent.pressureDirection === 'bullish') return 'text-bg-success';
            if (this.$parent.pressureDirection === 'bearish') return 'text-bg-danger';
            return 'text-bg-secondary';
        }

        function getLiquidityStatus() {
            const score = this.$parent.liquidityScore || 0;
            if (score > 80) return 'EXCELLENT';
            if (score > 60) return 'GOOD';
            if (score > 40) return 'MODERATE';
            if (score > 20) return 'LOW';
            return 'POOR';
        }

        function getLiquidityClass() {
            const status = this.getLiquidityStatus();
            if (status === 'EXCELLENT' || status === 'GOOD') return 'text-success';
            if (status === 'MODERATE') return 'text-warning';
            return 'text-danger';
        }

        function getLiquidityBadgeClass() {
            const status = this.getLiquidityStatus();
            if (status === 'EXCELLENT' || status === 'GOOD') return 'text-bg-success';
            if (status === 'MODERATE') return 'text-bg-warning';
            return 'text-bg-danger';
        }

        function getPressureSignal() {
            const strength = this.$parent.pressureStrength || 0;
            if (strength > 70) return 'STRONG';
            if (strength > 40) return 'MODERATE';
            return 'WEAK';
        }

        function getPressureSignalBadge() {
            const signal = this.getPressureSignal();
            if (signal === 'STRONG') return this.getPressureBadgeClass();
            if (signal === 'MODERATE') return 'text-bg-warning';
            return 'text-bg-secondary';
        }

        function getLiquiditySignal() {
            const score = this.$parent.liquidityScore || 0;
            if (score > 70) return 'STRONG';
            if (score > 40) return 'MODERATE';
            return 'WEAK';
        }

        function getLiquiditySignalBadge() {
            const signal = this.getLiquiditySignal();
            if (signal === 'STRONG') return 'text-bg-success';
            if (signal === 'MODERATE') return 'text-bg-warning';
            return 'text-bg-danger';
        }

        function getSpreadSignal() {
            const spreadBps = this.$parent.spreadBps || 0;
            if (spreadBps < 5) return 'TIGHT';
            if (spreadBps < 15) return 'NORMAL';
            return 'WIDE';
        }

        function getSpreadSignalBadge() {
            const signal = this.getSpreadSignal();
            if (signal === 'TIGHT') return 'text-bg-success';
            if (signal === 'NORMAL') return 'text-bg-primary';
            return 'text-bg-warning';
        }

        function getVolatilityLevel() {
            const spreadBps = this.$parent.spreadBps || 0;
            if (spreadBps > 20) return 'HIGH';
            if (spreadBps > 10) return 'MEDIUM';
            return 'LOW';
        }

        function getStabilityLevel() {
            const depthScore = this.$parent.depthScore || 0;
            if (depthScore > 70) return 'HIGH';
            if (depthScore > 40) return 'MEDIUM';
            return 'LOW';
        }

        function getTradingInsight() {
            const direction = this.$parent.pressureDirection;
            const strength = this.$parent.pressureStrength || 0;
            const liquidityScore = this.$parent.liquidityScore || 0;
            const spreadBps = this.$parent.spreadBps || 0;

            if (direction === 'bullish' && strength > 60 && liquidityScore > 60) {
                return 'Strong bullish pressure with good liquidity - favorable for long positions.';
            }
            
            if (direction === 'bearish' && strength > 60 && liquidityScore > 60) {
                return 'Strong bearish pressure with good liquidity - consider short positions.';
            }
            
            if (spreadBps > 20) {
                return 'Wide spreads indicate low liquidity - exercise caution with large orders.';
            }
            
            if (liquidityScore < 30) {
                return 'Low liquidity conditions - expect higher slippage and volatility.';
            }
            
            if (strength < 20) {
                return 'Balanced market conditions - wait for clearer directional signals.';
            }
            
            return 'Monitor orderbook dynamics for trading opportunities.';
        }
    </script>
</div>