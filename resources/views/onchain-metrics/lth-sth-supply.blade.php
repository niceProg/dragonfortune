@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="lthSthSupplyModule()">
    @include('onchain-metrics.partials.global-controls')
    @include('onchain-metrics.partials.module-nav')

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">LTH Share</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.lthShare"></div>
                <span class="small text-muted" x-text="metrics.lthTrend"></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">STH Share</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.sthShare"></div>
                <span class="small text-muted" x-text="metrics.sthTrend"></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Supply Balance</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.supplyBalance"></div>
                <span class="small text-muted" x-text="metrics.balanceTrend"></span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">LTH vs STH Supply Distribution</h3>
                        <p class="text-muted small mb-0">Long-term vs Short-term holder supply over time</p>
                    </div>
                    <span class="badge bg-light text-dark border">Area Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="supplyChart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">LTH Accumulation Rate</h3>
                        <p class="text-muted small mb-0">Rate of long-term holder accumulation</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="lthChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">STH Distribution Rate</h3>
                        <p class="text-muted small mb-0">Rate of short-term holder distribution</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="sthChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="df-panel p-4">
                <h5 class="mb-3">📚 Understanding LTH vs STH Supply</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                            <div class="fw-bold mb-2 text-success">🟩 Long-Term Holders (LTH)</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Holders with >155 days UTXO age</li>
                                    <li>Higher LTH share = bullish signal</li>
                                    <li>Indicates strong conviction</li>
                                    <li>Reduces selling pressure</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                            <div class="fw-bold mb-2 text-danger">🔴 Short-Term Holders (STH)</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Holders with <155 days UTXO age</li>
                                    <li>Higher STH share = bearish signal</li>
                                    <li>Indicates weak hands</li>
                                    <li>Increases selling pressure</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                            <div class="fw-bold mb-2 text-info">⚖️ Supply Balance</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>LTH/STH ratio indicates market phase</li>
                                    <li>Bull market: LTH accumulation</li>
                                    <li>Bear market: STH distribution</li>
                                    <li>Watch for trend reversals</li>
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
function lthSthSupplyModule() {
    return {
        metrics: {
            lthShare: '73.2%',
            lthTrend: '+2.1% (30d)',
            sthShare: '26.8%',
            sthTrend: '-2.1% (30d)',
            supplyBalance: '2.73',
            balanceTrend: 'LTH Dominance'
        },

        init() {
            console.log('🚀 LTH vs STH Supply Module initialized');
            this.renderCharts();
        },

        renderCharts() {
            this.$nextTick(() => {
                this.renderSupplyChart();
                this.renderLTHChart();
                this.renderSTHChart();
            });
        },

        renderSupplyChart() {
            const canvas = this.$refs.supplyChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for supply distribution
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const lthData = [70, 71, 72, 71, 72, 73, 72, 73, 73, 73.2];
            const sthData = [30, 29, 28, 29, 28, 27, 28, 27, 27, 26.8];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'LTH Share (%)',
                            data: lthData,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'STH Share (%)',
                            data: sthData,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
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

        renderLTHChart() {
            const canvas = this.$refs.lthChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for LTH accumulation
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const lthAccumulation = [0.5, 0.8, 1.2, 0.9, 1.1, 1.3, 1.0, 1.2, 1.1, 1.2];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'LTH Accumulation Rate',
                        data: lthAccumulation,
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

        renderSTHChart() {
            const canvas = this.$refs.sthChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for STH distribution
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const sthDistribution = [1.2, 1.0, 0.8, 1.1, 0.9, 0.7, 1.0, 0.8, 0.9, 0.8];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'STH Distribution Rate',
                        data: sthDistribution,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
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
        }
    };
}
</script>
@endsection
