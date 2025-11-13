@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="exchangeNetflowModule()">
    @include('onchain-metrics.partials.global-controls')
    @include('onchain-metrics.partials.module-nav')

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">BTC Exchange Netflow</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.btcNetflow"></div>
                <span class="small text-muted" x-text="metrics.btcNetflowTone"></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Stablecoin Netflow</span>
                <div class="fs-4 fw-bold text-dark" x-text="metrics.stablecoinNet"></div>
                <span class="small text-muted" x-text="metrics.stablecoinTone"></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-3 shadow-sm rounded h-100">
                <span class="text-uppercase text-muted small fw-semibold d-block mb-1">Dominant Venue</span>
                <div class="fs-5 fw-bold text-dark" x-text="metrics.dominantVenue"></div>
                <span class="small text-muted">Leading venue flow bias</span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="df-panel p-4 shadow-sm rounded h-100 d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Exchange Netflow by Asset</h3>
                        <p class="text-muted small mb-0">Directional pressure measured by net inflow versus outflow</p>
                    </div>
                    <span class="badge bg-light text-dark border">Bar</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="netflowChart" style="max-height: 320px;"></canvas>
                </div>
                <div class="mt-3">
                    <div class="p-3 rounded bg-light">
                        <span class="text-uppercase small fw-semibold text-muted d-block mb-1">Insight</span>
                        <p class="mb-0 small" x-text="insights.netflow"></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="df-panel p-4 shadow-sm rounded h-100 d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h6 mb-1">Exchange Breakdown</h3>
                        <p class="text-muted small mb-0">Relative venue contribution</p>
                    </div>
                </div>
                <div class="table-responsive flex-grow-1">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th scope="col">Exchange</th>
                                <th scope="col" class="text-end">Netflow</th>
                                <th scope="col" class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in exchangeRows" :key="row.venue">
                                <tr>
                                    <td x-text="row.venue"></td>
                                    <td class="text-end">
                                        <span :class="row.netflow >= 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold'"
                                              x-text="`${row.netflow >= 0 ? '+' : ''}${row.netflow.toFixed(2)}%`"></span>
                                    </td>
                                    <td class="text-end"
                                        x-text="`${row.balance.toFixed(2)}M`"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100 d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Stablecoin Liquidity Pulse</h3>
                        <p class="text-muted small mb-0">Liquidity runway inferred from stablecoin circulation</p>
                    </div>
                    <span class="badge bg-light text-dark border">Line</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="stablecoinChart" style="max-height: 300px;"></canvas>
                </div>
                <div class="mt-3">
                    <div class="p-3 rounded bg-light">
                        <span class="text-uppercase small fw-semibold text-muted d-block mb-1">Insight</span>
                        <p class="mb-0 small" x-text="insights.liquidity"></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="df-panel p-4 shadow-sm rounded h-100 d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="h5 mb-1">Exchange Comparison Heatmap</h3>
                        <p class="text-muted small mb-0">Venue intensity map to spot liquidity rotations</p>
                    </div>
                    <span class="badge bg-light text-dark border">Heatmap</span>
                </div>
                <div class="flex-grow-1">
                    <canvas x-ref="heatmapChart" style="max-height: 300px;"></canvas>
                </div>
                <div class="mt-3">
                    <div class="p-3 rounded bg-light">
                        <span class="text-uppercase small fw-semibold text-muted d-block mb-1">Insight</span>
                        <p class="mb-0 small" x-text="insights.heatmap"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="df-panel p-4">
                <h5 class="mb-3">📚 Understanding Exchange Netflow</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                            <div class="fw-bold mb-2 text-success">🟩 BTC Outflow</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>BTC leaving exchanges = bullish</li>
                                    <li>Indicates accumulation</li>
                                    <li>Reduces selling pressure</li>
                                    <li>Long-term bullish signal</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                            <div class="fw-bold mb-2 text-danger">🔴 BTC Inflow</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>BTC entering exchanges = bearish</li>
                                    <li>Indicates distribution</li>
                                    <li>Increases selling pressure</li>
                                    <li>Short-term bearish signal</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                            <div class="fw-bold mb-2 text-info">💰 Stablecoin Flow</div>
                            <div class="small text-secondary">
                                <ul class="mb-0 ps-3">
                                    <li>Stablecoin inflow = buying power</li>
                                    <li>Stablecoin outflow = selling pressure</li>
                                    <li>Liquidity indicator</li>
                                    <li>Market sentiment gauge</li>
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
function exchangeNetflowModule() {
    return {
        metrics: {
            btcNetflow: '-2,847 BTC',
            btcNetflowTone: 'Outflow (Bullish)',
            stablecoinNet: '+$156M',
            stablecoinTone: 'Inflow (Buying Power)',
            dominantVenue: 'Binance'
        },

        exchangeRows: [
            { venue: 'Binance', netflow: -1.2, balance: 245.6 },
            { venue: 'Coinbase', netflow: -0.8, balance: 189.3 },
            { venue: 'Kraken', netflow: 0.3, balance: 156.7 },
            { venue: 'Bitfinex', netflow: -0.5, balance: 98.4 }
        ],

        insights: {
            netflow: 'Strong BTC outflow indicates accumulation phase. Stablecoin inflow provides buying power for potential upward movement.',
            liquidity: 'Stablecoin supply increasing, providing liquidity runway for continued market expansion.',
            heatmap: 'Binance showing consistent outflow pattern, indicating strong accumulation trend across major venues.'
        },

        init() {
            console.log('🚀 Exchange Netflow Module initialized');
            this.renderCharts();
        },

        renderCharts() {
            this.$nextTick(() => {
                this.renderNetflowChart();
                this.renderStablecoinChart();
                this.renderHeatmapChart();
            });
        },

        renderNetflowChart() {
            const canvas = this.$refs.netflowChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for netflow
            const labels = ['Binance', 'Coinbase', 'Kraken', 'Bitfinex', 'Others'];
            const btcData = [-1200, -800, 300, -500, -200];
            const stablecoinData = [500, 300, -100, 200, 150];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'BTC Netflow (BTC)',
                            data: btcData,
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderColor: '#ef4444',
                            borderWidth: 1
                        },
                        {
                            label: 'Stablecoin Netflow ($M)',
                            data: stablecoinData,
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderColor: '#22c55e',
                            borderWidth: 1
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
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });
        },

        renderStablecoinChart() {
            const canvas = this.$refs.stablecoinChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample data for stablecoin liquidity
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
            const liquidityData = [120, 125, 130, 128, 135, 140, 138, 145, 150, 156];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Stablecoin Liquidity ($B)',
                        data: liquidityData,
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

        renderHeatmapChart() {
            const canvas = this.$refs.heatmapChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Sample heatmap data
            const exchanges = ['Binance', 'Coinbase', 'Kraken', 'Bitfinex'];
            const timeframes = ['24h', '7d', '30d'];
            const heatmapData = [
                [-1.2, -0.8, -1.5],
                [-0.8, -0.6, -1.2],
                [0.3, 0.1, -0.2],
                [-0.5, -0.3, -0.8]
            ];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: exchanges,
                    datasets: timeframes.map((timeframe, i) => ({
                        label: timeframe,
                        data: heatmapData.map(row => row[i]),
                        backgroundColor: heatmapData.map(row =>
                            row[i] < 0 ? 'rgba(34, 197, 94, 0.7)' : 'rgba(239, 68, 68, 0.7)'
                        ),
                        borderWidth: 1
                    }))
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
