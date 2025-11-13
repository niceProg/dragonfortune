@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="minerMetricsModule()">
    @include('onchain-metrics.partials.global-controls')
    @include('onchain-metrics.partials.module-nav')

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Miner Reserve</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.minerReserve"></div>
                <span class="small text-muted" x-text="metrics.minerReserveTrend"></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Miner Flow</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.minerFlow"></div>
                <span class="small text-muted" x-text="metrics.minerFlowTrend"></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Hash Rate</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.hashRate"></div>
                <span class="small text-muted" x-text="metrics.hashRateTrend"></span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Miner Reserve vs Price</h3>
                        <p class="text-muted small mb-0">Miner holdings correlation with price movements</p>
                    </div>
                    <span class="badge bg-light text-dark border">Dual Axis</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="minerReserveChart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Miner Flow Analysis</h3>
                        <p class="text-muted small mb-0">Daily miner selling pressure</p>
                    </div>
                    <span class="badge bg-light text-dark border">Bar Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="minerFlowChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Hash Rate Trend</h3>
                        <p class="text-muted small mb-0">Network security and mining activity</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="hashRateChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Miner Revenue</h3>
                        <p class="text-muted small mb-0">Daily mining revenue in BTC</p>
                    </div>
                    <span class="badge bg-light text-dark border">Area Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="minerRevenueChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Mining Difficulty</h3>
                        <p class="text-muted small mb-0">Network difficulty adjustment</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="difficultyChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="df-panel p-4">
                <h5 class="mb-3">📚 Understanding Miner Metrics</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                            <div class="fw-bold mb-2 text-success">⛏️ Miner Reserve</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Total BTC held by miners</li>
                                    <li>Decreasing = selling pressure</li>
                                    <li>Increasing = accumulation</li>
                                    <li>Price correlation indicator</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                            <div class="fw-bold mb-2 text-danger">💸 Miner Flow</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Daily BTC sold by miners</li>
                                    <li>High flow = bearish pressure</li>
                                    <li>Low flow = bullish signal</li>
                                    <li>Market impact indicator</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                            <div class="fw-bold mb-2 text-info">🔒 Hash Rate</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Network security measure</li>
                                    <li>Rising = more miners</li>
                                    <li>Falling = miner capitulation</li>
                                    <li>Network health indicator</li>
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
function minerMetricsModule() {
    return {
        metrics: {
            minerReserve: '1.847M BTC',
            minerReserveTrend: '-0.8% (30d)',
            minerFlow: '847 BTC',
            minerFlowTrend: 'Normal Levels',
            hashRate: '456.7 EH/s',
            hashRateTrend: '+2.3% (7d)'
        },

        init() {
            console.log('🚀 Miner Metrics Module initialized');
            this.renderCharts();
        },

        renderCharts() {
            this.$nextTick(() => {
                this.renderMinerReserveChart();
                this.renderMinerFlowChart();
                this.renderHashRateChart();
                this.renderMinerRevenueChart();
                this.renderDifficultyChart();
            });
        },

        renderMinerReserveChart() {
            const canvas = this.$refs.minerReserveChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for miner reserve vs price
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const minerReserveData = [1.85, 1.84, 1.83, 1.84, 1.82, 1.81, 1.82, 1.80, 1.79, 1.847];
            const priceData = [42000, 45000, 48000, 46000, 50000, 52000, 51000, 53000, 55000, 57000];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Miner Reserve (M BTC)',
                            data: minerReserveData,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Price ($)',
                            data: priceData,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y1'
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
                            type: 'linear',
                            display: true,
                            position: 'left',
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            }
                        }
                    }
                }
            });
        },

        renderMinerFlowChart() {
            const canvas = this.$refs.minerFlowChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for miner flow
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const minerFlowData = [650, 720, 800, 750, 850, 900, 820, 880, 860, 847];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Miner Flow (BTC)',
                        data: minerFlowData,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: '#ef4444',
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

        renderHashRateChart() {
            const canvas = this.$refs.hashRateChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for hash rate
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const hashRateData = [420, 430, 440, 435, 450, 460, 455, 465, 470, 456.7];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Hash Rate (EH/s)',
                        data: hashRateData,
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

        renderMinerRevenueChart() {
            const canvas = this.$refs.minerRevenueChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for miner revenue
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const revenueData = [6.25, 6.25, 6.25, 6.25, 6.25, 6.25, 6.25, 6.25, 6.25, 6.25];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Miner Revenue (BTC)',
                        data: revenueData,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.3)',
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

        renderDifficultyChart() {
            const canvas = this.$refs.difficultyChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for mining difficulty
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const difficultyData = [52.3, 53.1, 54.2, 53.8, 55.1, 56.3, 55.9, 57.2, 58.1, 57.8];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Mining Difficulty (T)',
                        data: difficultyData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
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
