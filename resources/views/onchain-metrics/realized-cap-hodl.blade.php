@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="realizedCapHodlModule()">
    @include('onchain-metrics.partials.global-controls')
    @include('onchain-metrics.partials.module-nav')

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Realized Cap</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.realizedCap"></div>
                <span class="small text-muted" x-text="metrics.realizedCapTrend"></span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">HODL Waves</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.hodlWaves"></div>
                <span class="small text-muted" x-text="metrics.hodlWavesTrend"></span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Market Maturity</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.marketMaturity"></div>
                <span class="small text-muted" x-text="metrics.maturityPhase"></span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Realized Cap vs Market Cap</h3>
                        <p class="text-muted small mb-0">Realized capitalization compared to market capitalization</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="realizedCapChart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">HODL Waves Distribution</h3>
                        <p class="text-muted small mb-0">Supply distribution by age cohorts</p>
                    </div>
                    <span class="badge bg-light text-dark border">Stacked Area</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="hodlWavesChart" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h6 mb-1">Current HODL Distribution</h3>
                        <p class="text-muted small mb-0">Latest supply age breakdown</p>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="hodlDistributionChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Realized Price Trend</h3>
                        <p class="text-muted small mb-0">Average cost basis over time</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="realizedPriceChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Supply Maturity Index</h3>
                        <p class="text-muted small mb-0">Market maturity based on HODL patterns</p>
                    </div>
                    <span class="badge bg-light text-dark border">Gauge</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="maturityChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="df-panel p-4">
                <h5 class="mb-3">📚 Understanding Realized Cap & HODL Waves</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                            <div class="fw-bold mb-2 text-success">💰 Realized Cap</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Sum of all BTC at their last move price</li>
                                    <li>Represents actual cost basis</li>
                                    <li>More stable than market cap</li>
                                    <li>Better valuation metric</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(139, 92, 246, 0.1); border-left: 4px solid #8b5cf6;">
                            <div class="fw-bold mb-2 text-primary">🌊 HODL Waves</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Supply distribution by age cohorts</li>
                                    <li>Shows market maturity</li>
                                    <li>Indicates holder behavior</li>
                                    <li>Cycle phase indicator</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                            <div class="fw-bold mb-2 text-info">📊 Market Maturity</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Mature markets: More long-term holders</li>
                                    <li>Immature markets: More short-term holders</li>
                                    <li>Transition phases indicate cycles</li>
                                    <li>Use for long-term positioning</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function realizedCapHodlModule() {
    return {
        metrics: {
            realizedCap: '$890.2B',
            realizedCapTrend: '+5.2% (30d)',
            hodlWaves: '73.2%',
            hodlWavesTrend: 'Long-term dominance',
            marketMaturity: 'Mature',
            maturityPhase: 'Accumulation phase'
        },

        init() {
            console.log('🚀 Realized Cap & HODL Waves Module initialized');
            this.renderCharts();
        },

        renderCharts() {
            this.$nextTick(() => {
                this.renderRealizedCapChart();
                this.renderHodlWavesChart();
                this.renderHodlDistributionChart();
                this.renderRealizedPriceChart();
                this.renderMaturityChart();
            });
        },

        renderRealizedCapChart() {
            const canvas = this.$refs.realizedCapChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for realized cap vs market cap
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const realizedCapData = [850, 860, 870, 865, 875, 880, 885, 890, 895, 890.2];
            const marketCapData = [900, 920, 950, 930, 960, 980, 970, 990, 1000, 1020];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Realized Cap ($B)',
                            data: realizedCapData,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Market Cap ($B)',
                            data: marketCapData,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            display: true,
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });
        },

        renderHodlWavesChart() {
            const canvas = this.$refs.hodlWavesChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for HODL waves
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const hodl1y = [15, 16, 17, 16, 18, 19, 18, 20, 21, 20];
            const hodl2y = [25, 26, 27, 26, 28, 29, 28, 30, 31, 30];
            const hodl3y = [20, 21, 22, 21, 23, 24, 23, 25, 26, 25];
            const hodl5y = [15, 16, 17, 16, 18, 19, 18, 20, 21, 20];
            const hodl10y = [25, 26, 27, 26, 28, 29, 28, 30, 31, 30];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '1-2 Years',
                            data: hodl1y,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.3)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: '2-3 Years',
                            data: hodl2y,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.3)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: '3-5 Years',
                            data: hodl3y,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.3)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: '5-10 Years',
                            data: hodl5y,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.3)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: '10+ Years',
                            data: hodl10y,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.3)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            display: true,
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });
        },

        renderHodlDistributionChart() {
            const canvas = this.$refs.hodlDistributionChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for current HODL distribution
            const labels = ['<1Y', '1-2Y', '2-3Y', '3-5Y', '5-10Y', '10Y+'];
            const distributionData = [15, 20, 18, 22, 15, 10];

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: distributionData,
                        backgroundColor: [
                            '#ef4444',
                            '#f59e0b',
                            '#22c55e',
                            '#3b82f6',
                            '#8b5cf6',
                            '#6b7280'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        renderRealizedPriceChart() {
            const canvas = this.$refs.realizedPriceChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for realized price
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const realizedPriceData = [42000, 43000, 44000, 43500, 45000, 46000, 45500, 47000, 48000, 47500];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Realized Price ($)',
                        data: realizedPriceData,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            display: true,
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });
        },

        renderMaturityChart() {
            const canvas = this.$refs.maturityChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample gauge chart for market maturity
            const data = {
                datasets: [{
                    data: [73], // Current maturity percentage
                    backgroundColor: ['#3b82f6'],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            };

            new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    };
}
</script>
@endsection
