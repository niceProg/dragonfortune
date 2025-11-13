{{--
    Komponen: Market Funding Bias Card
    Menampilkan bias pasar (Long/Short/Neutral) dengan warna dinamis

    Props:
    - $symbol: string (default: 'BTC')

    Interpretasi:
    - Funding rate positif → Longs membayar shorts → Pasar terlalu bullish → Potensi koreksi
    - Funding rate negatif → Shorts membayar longs → Pasar terlalu bearish → Potensi short squeeze
    - Strength tinggi → Posisi sangat crowded → Risk tinggi
--}}

<div class="df-panel p-4" x-data="fundingBiasCard('{{ $symbol ?? 'BTC' }}')">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">🎯 Market Funding Bias</h5>
            <span class="badge text-bg-secondary" x-text="symbol">BTC</span>
        </div>
        <button class="btn btn-sm btn-outline-secondary" @click="refresh()" :disabled="loading">
            <span x-show="!loading">🔄</span>
            <span x-show="loading" class="spinner-border spinner-border-sm"></span>
        </button>
    </div>

    <!-- Bias Indicator -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="bias-indicator p-4 rounded-3 text-center"
                 :class="getBiasClass()"
                 :style="getBiasGradient()">
                <div class="mb-2">
                    <span class="badge bg-white bg-opacity-25 text-white small">Current Bias</span>
                </div>
                <div class="display-6 fw-bold text-white text-uppercase mb-2" x-text="bias || 'Loading...'">
                    Neutral
                </div>
                <div class="small text-white text-opacity-75" x-text="getBiasDescription()">
                    Balanced market conditions
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Strength Meter -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold">Bias Strength</span>
                    <span class="badge" :class="getStrengthBadge()" x-text="(strength || 0) + '%'">0%</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar"
                         :class="getStrengthColor()"
                         :style="'width: ' + (strength || 0) + '%'"
                         role="progressbar"></div>
                </div>
                <div class="small text-secondary mt-1" x-text="getStrengthInterpretation()">
                    Calculating...
                </div>
            </div>

            <!-- Average Funding -->
            <div class="p-3 rounded bg-light">
                <div class="small text-secondary mb-1">Average Funding Rate</div>
                <div class="h5 mb-0"
                     :class="(avgFundingClose >= 0 ? 'text-success' : 'text-danger')"
                     x-text="formatRate(avgFundingClose)">
                    +0.0000%
                </div>
                <div class="small text-secondary mt-1">
                    <template x-if="avgFundingClose > 0">
                        <span>💸 Longs paying shorts</span>
                    </template>
                    <template x-if="avgFundingClose < 0">
                        <span>💸 Shorts paying longs</span>
                    </template>
                    <template x-if="avgFundingClose === 0">
                        <span>⚖️ Balanced</span>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Trading Insight -->
    <div class="alert mb-0" :class="getAlertClass()" role="alert">
        <div class="d-flex align-items-start gap-2">
            <div x-text="getAlertIcon()">💡</div>
            <div class="flex-grow-1">
                <div class="fw-semibold small" x-text="getInsightTitle()">Market Insight</div>
                <div class="small" x-text="getInsightMessage()">Loading market data...</div>
            </div>
        </div>
    </div>

    <!-- Last Updated -->
    <div class="text-center mt-3">
        <small class="text-secondary">
            Last updated: <span x-text="lastUpdate">--</span>
        </small>
    </div>
</div>

<script>
function fundingBiasCard(initialSymbol = 'BTC') {
    return {
        symbol: initialSymbol,
        marginType: '',
        loading: false,
        bias: null,
        strength: 0,
        avgFundingClose: 0,
        sampleSize: 0,
        lastUpdate: '--',

        init() {
            // Delay untuk memastikan layout stabil
            setTimeout(() => {
                this.loadBiasData();
            }, 500);

            // Auto refresh every 30 seconds
            setInterval(() => this.loadBiasData(), 30000);

            // Listen to global filter changes
            window.addEventListener('symbol-changed', (e) => {
                this.symbol = e.detail?.symbol || this.symbol;
                this.marginType = e.detail?.marginType ?? this.marginType;
                this.loadBiasData();
            });
            window.addEventListener('margin-type-changed', (e) => {
                this.marginType = e.detail?.marginType ?? '';
                this.loadBiasData();
            });
        },

        async loadBiasData() {
            this.loading = true;
            try {
                // Use analytics endpoint for consistent data
                const pair = `${this.symbol}USDT`.toLowerCase();
                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const configuredBase = (baseMeta?.content || '').trim();
                const base = configuredBase ? (configuredBase.endsWith('/') ? configuredBase.slice(0, -1) : configuredBase) : '';
                const url = base ? `${base}/api/funding-rate/analytics?symbol=${pair}&exchange=binance&interval=1h&limit=2000` : `/api/funding-rate/analytics?symbol=${pair}&exchange=binance&interval=1h&limit=2000`;
                
                const response = await fetch(url);
                const data = await response.json();

                // Use bias data from analytics endpoint
                this.bias = data.bias?.direction || 'neutral';
                this.strength = Math.abs((data.bias?.strength || 0) * 100); // Convert to percentage
                this.avgFundingClose = parseFloat(data.summary?.average || 0);
                this.sampleSize = data.data_points || 0;
                this.lastUpdate = new Date().toLocaleTimeString();

                console.log('✅ Analytics data loaded:', data);
            } catch (error) {
                console.error('❌ Error loading analytics:', error);
                this.bias = null;
                this.strength = 0;
                this.avgFundingClose = 0;
            } finally {
                this.loading = false;
            }
        },

        refresh() {
            this.loadBiasData();
        },

        getBiasClass() {
            const biasLower = (this.bias || '').toLowerCase();
            if (biasLower.includes('long')) return 'bias-long';
            if (biasLower.includes('short')) return 'bias-short';
            return 'bias-neutral';
        },

        getBiasGradient() {
            const biasLower = (this.bias || '').toLowerCase();
            if (biasLower.includes('long')) {
                return 'background: linear-gradient(135deg, #22c55e, #16a34a);';
            }
            if (biasLower.includes('short')) {
                return 'background: linear-gradient(135deg, #ef4444, #dc2626);';
            }
            return 'background: linear-gradient(135deg, #6b7280, #4b5563);';
        },

        getBiasDescription() {
            const biasLower = (this.bias || '').toLowerCase();
            if (biasLower.includes('long')) {
                return 'Market heavily long biased • High funding cost';
            }
            if (biasLower.includes('short')) {
                return 'Market heavily short biased • Negative funding';
            }
            return 'Balanced positioning • No extreme bias';
        },

        getStrengthBadge() {
            if (this.strength > 70) return 'text-bg-danger';
            if (this.strength > 40) return 'text-bg-warning';
            return 'text-bg-success';
        },

        getStrengthColor() {
            if (this.strength > 70) return 'bg-danger';
            if (this.strength > 40) return 'bg-warning';
            return 'bg-success';
        },

        getStrengthInterpretation() {
            if (this.strength > 70) {
                return '⚠️ Extreme positioning • High squeeze risk';
            }
            if (this.strength > 40) {
                return '⚡ Moderate bias • Monitor for changes';
            }
            return '✅ Low bias • Healthy market balance';
        },

        getAlertClass() {
            const biasLower = (this.bias || '').toLowerCase();
            if (this.strength > 70) return 'alert-danger';
            if (this.strength > 40) return 'alert-warning';
            return 'alert-info';
        },

        getAlertIcon() {
            const biasLower = (this.bias || '').toLowerCase();
            if (this.strength > 70) return '🚨';
            if (biasLower.includes('long')) return '📈';
            if (biasLower.includes('short')) return '📉';
            return '💡';
        },

        getInsightTitle() {
            const biasLower = (this.bias || '').toLowerCase();
            if (this.strength > 70) {
                return 'High Risk Alert';
            }
            if (biasLower.includes('long')) {
                return 'Long Dominance Detected';
            }
            if (biasLower.includes('short')) {
                return 'Short Pressure Active';
            }
            return 'Market Insight';
        },

        getInsightMessage() {
            const biasLower = (this.bias || '').toLowerCase();

            if (this.strength > 70 && biasLower.includes('long')) {
                return `Extreme long positioning detected. Funding rate at ${this.formatRate(this.avgFundingClose)}. High risk of long squeeze if price reverses. Consider taking profits or hedging positions.`;
            }

            if (this.strength > 70 && biasLower.includes('short')) {
                return `Heavy short accumulation. Negative funding at ${this.formatRate(this.avgFundingClose)}. Watch for short squeeze on positive catalysts. Stops should be tight.`;
            }

            if (biasLower.includes('long')) {
                return `Long positions building up with positive funding (${this.formatRate(this.avgFundingClose)}). Longs are paying shorts. Monitor for funding rate spikes as squeeze indicator.`;
            }

            if (biasLower.includes('short')) {
                return `Short interest increasing with negative funding (${this.formatRate(this.avgFundingClose)}). Shorts paying longs. Potential short squeeze setup if price bounces.`;
            }

            return `Market showing neutral bias with funding rate at ${this.formatRate(this.avgFundingClose)}. No extreme positioning detected. Normal trading conditions.`;
        },

        formatRate(value) {
            if (value === null || value === undefined) return 'N/A';
            const percent = (value * 100).toFixed(4);
            return (value >= 0 ? '+' : '') + percent + '%';
        }
    };
}
</script>

<style>
.bias-indicator {
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.bias-indicator:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.progress-bar {
    transition: width 0.6s ease, background-color 0.3s ease;
}
</style>

