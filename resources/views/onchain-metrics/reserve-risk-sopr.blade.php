@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="reserveRiskSoprModule()">
    @include('onchain-metrics.partials.global-controls')
    @include('onchain-metrics.partials.module-nav')

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Reserve Risk</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.reserveRisk"></div>
                <span class="small text-muted" x-text="metrics.reserveRiskTrend"></span>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">SOPR</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.sopr"></div>
                <span class="small text-muted" x-text="metrics.soprTrend"></span>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Dormancy</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.dormancy"></div>
                <span class="small text-muted" x-text="metrics.dormancyTrend"></span>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">CDD</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.cdd"></div>
                <span class="small text-muted" x-text="metrics.cddTrend"></span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Reserve Risk Historical</h3>
                        <p class="text-muted small mb-0">Risk-reward ratio for long-term holders</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="reserveRiskChart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">SOPR Analysis</h3>
                        <p class="text-muted small mb-0">Spent Output Profit Ratio over time</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="soprChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Dormancy Flow</h3>
                        <p class="text-muted small mb-0">Average dormancy of spent outputs</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="dormancyChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">CDD (Coin Days Destroyed)</h3>
                        <p class="text-muted small mb-0">Sum of all coin days destroyed per day</p>
                    </div>
                    <span class="badge bg-light text-dark border">Bar Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="cddChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Risk Assessment</h3>
                        <p class="text-muted small mb-0">Current market risk level</p>
                    </div>
                    <span class="badge bg-light text-dark border">Gauge</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="riskChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="df-panel p-4">
                <h5 class="mb-3">📚 Understanding Reserve Risk & SOPR</h5>

                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                            <div class="fw-bold mb-2 text-success">🛡️ Reserve Risk</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Risk-reward for long-term holders</li>
                                    <li>Low values = good buying opportunity</li>
                                    <li>High values = high risk</li>
                                    <li>Market cycle indicator</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="p-3 rounded" style="background: rgba(139, 92, 246, 0.1); border-left: 4px solid #8b5cf6;">
                            <div class="fw-bold mb-2 text-primary">📈 SOPR</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>SOPR > 1: Profit taking</li>
                                    <li>SOPR < 1: Loss realization</li>
                                    <li>SOPR = 1: Break-even</li>
                                    <li>Market sentiment gauge</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                            <div class="fw-bold mb-2 text-info">😴 Dormancy</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>High dormancy = old coins moving</li>
                                    <li>Low dormancy = recent coins moving</li>
                                    <li>Distribution indicator</li>
                                    <li>Market phase signal</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="p-3 rounded" style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b;">
                            <div class="fw-bold mb-2 text-warning">💥 CDD</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>High CDD = significant selling</li>
                                    <li>Low CDD = minimal selling</li>
                                    <li>Market stress indicator</li>
                                    <li>Distribution pressure</li>
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
function reserveRiskSoprModule() {
    return {
        metrics: {
            reserveRisk: '0.0023',
            reserveRiskTrend: 'Low Risk',
            sopr: '1.023',
            soprTrend: 'Profit Taking',
            dormancy: '156.7',
            dormancyTrend: 'Moderate Activity',
            cdd: '2.34M',
            cddTrend: 'Normal Levels'
        },

        init() {
            console.log('🚀 Reserve Risk & SOPR Module initialized');
            this.renderCharts();
        },

        renderCharts() {
            this.$nextTick(() => {
                this.renderReserveRiskChart();
                this.renderSoprChart();
                this.renderDormancyChart();
                this.renderCddChart();
                this.renderRiskChart();
            });
        },

        renderReserveRiskChart() {
            const canvas = this.$refs.reserveRiskChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for reserve risk
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const reserveRiskData = [0.0015, 0.0018, 0.0021, 0.0019, 0.0022, 0.0024, 0.0021, 0.0023, 0.0025, 0.0023];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Reserve Risk',
                        data: reserveRiskData,
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
        },

        renderSoprChart() {
            const canvas = this.$refs.soprChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for SOPR
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const soprData = [0.98, 1.02, 1.05, 1.01, 1.03, 1.06, 1.04, 1.02, 1.05, 1.023];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'SOPR',
                        data: soprData,
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

        renderDormancyChart() {
            const canvas = this.$refs.dormancyChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for dormancy
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const dormancyData = [120, 135, 150, 140, 160, 170, 155, 165, 158, 156.7];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Dormancy (Days)',
                        data: dormancyData,
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

        renderCddChart() {
            const canvas = this.$refs.cddChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for CDD
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const cddData = [1.8, 2.1, 2.5, 2.2, 2.4, 2.6, 2.3, 2.5, 2.4, 2.34];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'CDD (M)',
                        data: cddData,
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: '#f59e0b',
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

        renderRiskChart() {
            const canvas = this.$refs.riskChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample gauge chart for risk assessment
            const data = {
                datasets: [{
                    data: [35], // Current risk percentage
                    backgroundColor: ['#22c55e'],
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
