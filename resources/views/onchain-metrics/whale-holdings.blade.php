@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="whaleHoldingsModule()">
    @include('onchain-metrics.partials.global-controls')
    @include('onchain-metrics.partials.module-nav')

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Whale Count</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.whaleCount"></div>
                <span class="small text-muted" x-text="metrics.whaleCountTrend"></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Whale Holdings</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.whaleHoldings"></div>
                <span class="small text-muted" x-text="metrics.whaleHoldingsTrend"></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Whale Activity</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.whaleActivity"></div>
                <span class="small text-muted" x-text="metrics.whaleActivityTrend"></span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Whale Holdings Distribution</h3>
                        <p class="text-muted small mb-0">BTC distribution across whale address sizes</p>
                    </div>
                    <span class="badge bg-light text-dark border">Bar Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="whaleDistributionChart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Whale Transaction Volume</h3>
                        <p class="text-muted small mb-0">Daily whale transaction activity</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="whaleVolumeChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Whale Accumulation</h3>
                        <p class="text-muted small mb-0">Net whale accumulation over time</p>
                    </div>
                    <span class="badge bg-light text-dark border">Area Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="whaleAccumulationChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Whale vs Retail Holdings</h3>
                        <p class="text-muted small mb-0">Comparison of whale vs retail supply</p>
                    </div>
                    <span class="badge bg-light text-dark border">Doughnut</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="whaleRetailChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Whale Concentration Risk</h3>
                        <p class="text-muted small mb-0">Market concentration in whale hands</p>
                    </div>
                    <span class="badge bg-light text-dark border">Gauge</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="concentrationChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="df-panel p-4">
                <h5 class="mb-3">📚 Understanding Whale Holdings</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                            <div class="fw-bold mb-2 text-success">🐋 Whale Definition</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Addresses with >1,000 BTC</li>
                                    <li>Top 0.01% of addresses</li>
                                    <li>Significant market influence</li>
                                    <li>Institutional and exchange wallets</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                            <div class="fw-bold mb-2 text-danger">⚠️ Whale Risk</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>High concentration = market risk</li>
                                    <li>Whale selling = price impact</li>
                                    <li>Exchange wallets = selling pressure</li>
                                    <li>Monitor whale movements</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                            <div class="fw-bold mb-2 text-info">📊 Whale Signals</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Accumulation = bullish</li>
                                    <li>Distribution = bearish</li>
                                    <li>Exchange inflow = selling</li>
                                    <li>Exchange outflow = buying</li>
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
function whaleHoldingsModule() {
    return {
        metrics: {
            whaleCount: '2,847',
            whaleCountTrend: '+12 (30d)',
            whaleHoldings: '4.2M BTC',
            whaleHoldingsTrend: '+0.8% (30d)',
            whaleActivity: 'Moderate',
            whaleActivityTrend: 'Normal Levels'
        },

        init() {
            console.log('🚀 Whale Holdings Module initialized');
            this.renderCharts();
        },

        renderCharts() {
            this.$nextTick(() => {
                this.renderWhaleDistributionChart();
                this.renderWhaleVolumeChart();
                this.renderWhaleAccumulationChart();
                this.renderWhaleRetailChart();
                this.renderConcentrationChart();
            });
        },

        renderWhaleDistributionChart() {
            const canvas = this.$refs.whaleDistributionChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for whale distribution
            const labels = ['1K-5K', '5K-10K', '10K-50K', '50K-100K', '100K+'];
            const distributionData = [1250, 890, 567, 123, 17];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Whale Count',
                        data: distributionData,
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(239, 68, 68, 0.7)'
                        ],
                        borderWidth: 1
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

        renderWhaleVolumeChart() {
            const canvas = this.$refs.whaleVolumeChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for whale transaction volume
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const volumeData = [45000, 52000, 48000, 55000, 60000, 58000, 62000, 59000, 61000, 58500];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Whale Volume (BTC)',
                        data: volumeData,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
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

        renderWhaleAccumulationChart() {
            const canvas = this.$refs.whaleAccumulationChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for whale accumulation
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const accumulationData = [4.15, 4.16, 4.17, 4.18, 4.19, 4.20, 4.21, 4.22, 4.23, 4.2];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Whale Holdings (M BTC)',
                        data: accumulationData,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.3)',
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

        renderWhaleRetailChart() {
            const canvas = this.$refs.whaleRetailChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for whale vs retail holdings
            const labels = ['Whale Holdings', 'Retail Holdings'];
            const holdingsData = [4.2, 15.8];

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: holdingsData,
                        backgroundColor: [
                            'rgba(139, 92, 246, 0.8)',
                            'rgba(59, 130, 246, 0.8)'
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

        renderConcentrationChart() {
            const canvas = this.$refs.concentrationChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample gauge chart for concentration risk
            const data = {
                datasets: [{
                    data: [21], // Current concentration percentage
                    backgroundColor: ['#f59e0b'],
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
