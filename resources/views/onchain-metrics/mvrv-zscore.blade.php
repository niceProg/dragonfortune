@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="mvrvZScoreModule()">
    @include('onchain-metrics.partials.global-controls')
    @include('onchain-metrics.partials.module-nav')

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Latest MVRV</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.mvrv"></div>
                <span class="small text-muted" x-text="metrics.mvrvDelta"></span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Z-Score Status</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.zScore"></div>
                <span class="small text-muted" x-text="metrics.zScoreDelta"></span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Market Cycle</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.marketCycle"></div>
                <span class="small text-muted" x-text="metrics.cyclePhase"></span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">MVRV Ratio Historical</h3>
                        <p class="text-muted small mb-0">Market Value to Realized Value ratio over time</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="mvrvChart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Z-Score Analysis</h3>
                        <p class="text-muted small mb-0">Statistical deviation from mean</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="zScoreChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Market Valuation Zones</h3>
                        <p class="text-muted small mb-0">Current position in valuation cycle</p>
                    </div>
                    <span class="badge bg-light text-dark border">Gauge</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="valuationChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="df-panel p-4">
                <h5 class="mb-3">📚 Understanding MVRV & Z-Score</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                            <div class="fw-bold mb-2 text-success">🟩 MVRV Ratio</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>MVRV > 3.7: Extreme overvaluation (sell zone)</li>
                                    <li>MVRV 1.0-3.7: Normal to high valuation</li>
                                    <li>MVRV < 1.0: Undervaluation (buy zone)</li>
                                    <li>Historical cycle indicator</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(139, 92, 246, 0.1); border-left: 4px solid #8b5cf6;">
                            <div class="fw-bold mb-2 text-primary">📊 Z-Score</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Z > 2: Extreme overvaluation</li>
                                    <li>Z 0-2: Above average valuation</li>
                                    <li>Z -2-0: Below average valuation</li>
                                    <li>Z < -2: Extreme undervaluation</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                            <div class="fw-bold mb-2 text-info">🎯 Trading Strategy</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Combine both metrics for confirmation</li>
                                    <li>Look for divergences in trends</li>
                                    <li>Use for long-term positioning</li>
                                    <li>Watch for cycle transitions</li>
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
function mvrvZScoreModule() {
    return {
        metrics: {
            mvrv: '2.34',
            mvrvDelta: '+0.12 (5.4%)',
            zScore: '1.87',
            zScoreDelta: '+0.23 (14.0%)',
            marketCycle: 'Bull Market',
            cyclePhase: 'Mid-cycle expansion'
        },

        init() {
            console.log('🚀 MVRV & Z-Score Module initialized');
            this.renderCharts();
        },

        renderCharts() {
            this.$nextTick(() => {
                this.renderMVRVChart();
                this.renderZScoreChart();
                this.renderValuationChart();
            });
        },

        renderMVRVChart() {
            const canvas = this.$refs.mvrvChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for MVRV
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const mvrvData = [1.2, 1.4, 1.8, 2.1, 2.3, 2.1, 1.9, 2.0, 2.2, 2.34];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'MVRV Ratio',
                        data: mvrvData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
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

        renderZScoreChart() {
            const canvas = this.$refs.zScoreChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for Z-Score
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const zScoreData = [0.5, 0.8, 1.2, 1.5, 1.7, 1.4, 1.1, 1.3, 1.6, 1.87];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Z-Score',
                        data: zScoreData,
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
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });
        },

        renderValuationChart() {
            const canvas = this.$refs.valuationChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample gauge chart data
            const data = {
                datasets: [{
                    data: [65], // Current position as percentage
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
