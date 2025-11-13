@extends('layouts.app')

@section('content')
    {{--
        Macro Overlay Dashboard
        Think like a trader • Build like an engineer • Visualize like a designer

        Interpretasi Trading:
        - DXY naik → USD kuat → BTC cenderung turun (inverse correlation)
        - Yields naik → Risk-off → Crypto bearish
        - Fed Funds naik → Biaya modal naik → Leverage turun
        - CPI tinggi → Inflasi tinggi → Fed hawkish → Risk assets turun
        - M2 naik → Liquidity naik → Risk assets bullish
        - RRP turun → Liquidity ke market → Bullish signal
        - Yield curve inversion → Recession signal
        - NFP strong → Fed hawkish → Risk-off
    --}}

    <div class="d-flex flex-column h-100 gap-3" x-data="macroOverlayController()">
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">Macro Overlay Dashboard</h1>
                        <span class="pulse-dot pulse-warning"></span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Monitor makro ekonomi global - DXY, Yields, Fed Funds, Inflasi & Likuiditas
                    </p>
                </div>

                <!-- Global Controls -->
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <select class="form-select" style="width: 150px;" x-model="selectedTimeframe" @change="refreshAll()">
                        <option value="1M">1 Month</option>
                        <option value="3M" selected>3 Months</option>
                        <option value="6M">6 Months</option>
                        <option value="1Y">1 Year</option>
                        <option value="YTD">Year to Date</option>
                    </select>

                    <!-- <button class="btn btn-primary" @click="refreshAll()" :disabled="loading">
                        <span x-show="!loading">Refresh Data</span>
                        <span x-show="loading" class="spinner-border spinner-border-sm"></span>
                    </button> -->
                </div>
            </div>
        </div>

        <!-- Key Metrics Cards Row 1 -->
        <div class="row g-3">
            <!-- DXY Card -->
            <div class="col-md-6 col-lg-3">
                <div class="df-panel p-3 h-100" :class="{ 'border-danger': metrics.dxy.change < 0, 'border-success': metrics.dxy.change > 0 }">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small text-secondary">DXY (Dollar Index)</div>
                            <div class="h3 mb-0 fw-bold" x-text="metrics.dxy.value">--</div>
                        </div>
                        <div class="text-end">
                            <div class="badge" :class="metrics.dxy.change >= 0 ? 'text-bg-success' : 'text-bg-danger'" x-text="formatChange(metrics.dxy.change)">--</div>
                            <div class="small text-secondary mt-1">24h</div>
                        </div>
                    </div>
                    <div class="small" :class="metrics.dxy.change >= 0 ? 'text-success' : 'text-danger'">
                        <span x-show="metrics.dxy.change >= 0">USD Strengthening</span>
                        <span x-show="metrics.dxy.change < 0">USD Weakening</span>
                    </div>
                    <div class="mt-2 small text-secondary">
                        Correlation with BTC: -0.72
                    </div>
                </div>
            </div>

            <!-- 10Y Treasury Yield -->
            <div class="col-md-6 col-lg-3">
                <div class="df-panel p-3 h-100" :class="{ 'border-warning': metrics.yield10y.value > 4.5 }">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small text-secondary">10Y Treasury Yield</div>
                            <div class="h3 mb-0 fw-bold" x-text="metrics.yield10y.value + '%'">--</div>
                        </div>
                        <div class="text-end">
                            <div class="badge" :class="metrics.yield10y.change >= 0 ? 'text-bg-danger' : 'text-bg-success'" x-text="formatChange(metrics.yield10y.change) + ' bps'">--</div>
                            <div class="small text-secondary mt-1">24h</div>
                        </div>
                    </div>
                    <div class="small text-secondary">
                        <span x-show="metrics.yield10y.value > 4.5">High yields - Risk-off</span>
                        <span x-show="metrics.yield10y.value <= 4.5">Moderate yields</span>
                    </div>
                    <div class="mt-2 small text-secondary">
                        Correlation with BTC: -0.65
                    </div>
                </div>
            </div>

            <!-- Fed Funds Rate -->
            <div class="col-md-6 col-lg-3">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small text-secondary">Fed Funds Rate</div>
                            <div class="h3 mb-0 fw-bold" x-text="metrics.fedFunds.value + '%'">--</div>
                        </div>
                        <div class="text-end">
                            <div class="badge text-bg-info" x-text="metrics.fedFunds.status">--</div>
                            <div class="small text-secondary mt-1">Current</div>
                        </div>
                    </div>
                    <div class="small text-secondary">
                        Next meeting: <span class="fw-semibold" x-text="metrics.fedFunds.nextMeeting">--</span>
                    </div>
                    <div class="mt-2 small text-secondary">
                        Fed Watch: <span class="fw-semibold" x-text="metrics.fedFunds.probability + '% probability'">--</span>
                    </div>
                </div>
            </div>

            <!-- CPI -->
            <div class="col-md-6 col-lg-3">
                <div class="df-panel p-3 h-100" :class="{ 'border-danger': metrics.cpi.value > 3.5 }">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small text-secondary">CPI (Inflation YoY)</div>
                            <div class="h3 mb-0 fw-bold" x-text="metrics.cpi.value + '%'">--</div>
                        </div>
                        <div class="text-end">
                            <div class="badge" :class="metrics.cpi.value > 3.0 ? 'text-bg-danger' : 'text-bg-success'" x-text="metrics.cpi.value > 3.0 ? 'High' : 'Cooling'">--</div>
                            <div class="small text-secondary mt-1" x-text="metrics.cpi.date">--</div>
                        </div>
                    </div>
                    <div class="small text-secondary">
                        Fed target: 2.0%
                    </div>
                    <div class="mt-2 small text-secondary">
                        Core CPI: <span class="fw-semibold" x-text="metrics.cpi.core + '%'">--</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics Cards Row 2: Yield Curve & NFP -->
        <div class="row g-3">
            <!-- Yield Curve Spread -->
            <div class="col-md-6 col-lg-3">
                <div class="df-panel p-3 h-100" :class="{ 'border-danger': metrics.yieldSpread.value < 0 }">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small text-secondary">Yield Curve Spread (10Y-2Y)</div>
                            <div class="h3 mb-0 fw-bold" x-text="metrics.yieldSpread.value + ' bps'">--</div>
                        </div>
                        <div class="text-end">
                            <div class="badge" :class="metrics.yieldSpread.value < 0 ? 'text-bg-danger' : 'text-bg-success'" x-text="metrics.yieldSpread.value < 0 ? 'Inverted' : 'Normal'">--</div>
                        </div>
                    </div>
                    <div class="small text-secondary">
                        <span x-show="metrics.yieldSpread.value < 0">Recession signal detected</span>
                        <span x-show="metrics.yieldSpread.value >= 0">Healthy yield curve</span>
                    </div>
                </div>
            </div>

            <!-- NFP (Non-Farm Payrolls) -->
            <div class="col-md-6 col-lg-3">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small text-secondary">NFP (Non-Farm Payrolls)</div>
                            <div class="h3 mb-0 fw-bold" x-text="metrics.nfp.value + 'K'">--</div>
                        </div>
                        <div class="text-end">
                            <div class="badge" :class="metrics.nfp.change >= 0 ? 'text-bg-success' : 'text-bg-danger'" x-text="metrics.nfp.change >= 0 ? 'Beat' : 'Miss'">--</div>
                            <div class="small text-secondary mt-1" x-text="metrics.nfp.date">--</div>
                        </div>
                    </div>
                    <div class="small text-secondary">
                        Expected: <span class="fw-semibold" x-text="metrics.nfp.expected + 'K'">--</span>
                    </div>
                    <div class="mt-2 small text-secondary">
                        Unemployment: <span class="fw-semibold" x-text="metrics.nfp.unemployment + '%'">--</span>
                    </div>
                </div>
            </div>

            <!-- M2 Money Supply -->
            <div class="col-md-6 col-lg-3">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small text-secondary">M2 Money Supply</div>
                            <div class="h3 mb-0 fw-bold" x-text="'$' + metrics.m2.value + 'T'">--</div>
                        </div>
                        <div class="text-end">
                            <div class="badge" :class="metrics.m2.change >= 0 ? 'text-bg-success' : 'text-bg-danger'" x-text="formatChange(metrics.m2.change) + '%'">--</div>
                            <div class="small text-secondary mt-1">MoM</div>
                        </div>
                    </div>
                    <div class="small" :class="metrics.m2.change >= 0 ? 'text-success' : 'text-danger'">
                        <span x-show="metrics.m2.change >= 0">Liquidity expanding</span>
                        <span x-show="metrics.m2.change < 0">Liquidity contracting</span>
                    </div>
                    <div class="mt-2 small text-secondary">
                        Correlation with BTC: +0.81
                    </div>
                </div>
            </div>

            <!-- RRP (Reverse Repo) -->
            <div class="col-md-6 col-lg-3">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small text-secondary">RRP (Reverse Repo)</div>
                            <div class="h3 mb-0 fw-bold" x-text="'$' + metrics.rrp.value + 'B'">--</div>
                        </div>
                        <div class="text-end">
                            <div class="badge" :class="metrics.rrp.change <= 0 ? 'text-bg-success' : 'text-bg-danger'" x-text="formatChange(metrics.rrp.change) + '%'">--</div>
                            <div class="small text-secondary mt-1">WoW</div>
                        </div>
                    </div>
                    <div class="small" :class="metrics.rrp.change <= 0 ? 'text-success' : 'text-danger'">
                        <span x-show="metrics.rrp.change <= 0">Money leaving RRP (Bullish)</span>
                        <span x-show="metrics.rrp.change > 0">Money parking in RRP (Bearish)</span>
                    </div>
                    <div class="mt-2 small text-secondary">
                        Correlation with BTC: +0.68 (inverse)
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: DXY & Yields -->
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="df-panel p-3 h-100">
                    <h5 class="mb-3">DXY - Dollar Strength Index</h5>
                    <div style="height: 300px;">
                        <canvas id="dxyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="df-panel p-3 h-100">
                    <h5 class="mb-3">Treasury Yields Curve</h5>
                    <div style="height: 300px;">
                        <canvas id="yieldsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: NFP Impact & Yield Spread -->
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="df-panel p-3 h-100">
                    <h5 class="mb-3">NFP Employment Data - Historical</h5>
                    <div style="height: 300px;">
                        <canvas id="nfpChart"></canvas>
                    </div>
                    <div class="mt-3 p-2 rounded" style="background: rgba(59, 130, 246, 0.1);">
                        <div class="small text-secondary">
                            <strong>Insight:</strong> Strong NFP (>200K) → Fed hawkish → Higher rates → Risk-off for crypto.
                            Weak NFP (<150K) → Fed dovish → Potential rate cuts → Risk-on.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="df-panel p-3 h-100">
                    <h5 class="mb-3">Yield Curve Spread (10Y-2Y) - Recession Indicator</h5>
                    <div style="height: 300px;">
                        <canvas id="yieldSpreadChart"></canvas>
                    </div>
                    <div class="mt-3 p-2 rounded" style="background: rgba(239, 68, 68, 0.1);">
                        <div class="small text-secondary">
                            <strong>Recession Signal:</strong> Negative spread (inversion) historically precedes recession by 12-18 months.
                            Currently <span class="fw-semibold" x-text="metrics.yieldSpread.value + ' bps'">--</span>
                            <span x-text="metrics.yieldSpread.value < 0 ? '(INVERTED - High Risk)' : '(Normal)'">--</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liquidity Metrics -->
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="df-panel p-3 h-100">
                    <h5 class="mb-3">Liquidity Indicators (M2, RRP, TGA)</h5>
                    <div style="height: 350px;">
                        <canvas id="liquidityChart"></canvas>
                    </div>
                    <div class="mt-3 p-2 rounded" style="background: rgba(34, 197, 94, 0.1);">
                        <div class="small text-secondary">
                            <strong>Liquidity Insight:</strong> M2 naik + RRP turun = Lebih banyak uang beredar di market (bullish untuk risk assets).
                            TGA naik = Treasury menarik uang dari market (bearish). Net liquidity = M2 - RRP - TGA.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="df-panel p-3 h-100">
                    <h5 class="mb-3">Economic Calendar</h5>
                    <div class="d-flex flex-column gap-2" style="max-height: 400px; overflow-y: auto;">
                        <template x-for="event in upcomingEvents" :key="event.id">
                            <div class="p-2 rounded" :class="getEventClass(event.impact)">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold small" x-text="event.name">--</div>
                                        <div class="small text-secondary" x-text="event.date">--</div>
                                    </div>
                                    <div class="badge" :class="getImpactBadge(event.impact)" x-text="event.impact">--</div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Macro Correlation Matrix -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4">
                    <h5 class="mb-3">Macro Correlation with BTC</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                                <div class="fw-bold mb-2 text-danger">Inverse Correlation (Bearish Signals)</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li><strong>DXY ↑</strong> → BTC ↓ (r = -0.72)</li>
                                        <li><strong>Yields ↑</strong> → BTC ↓ (r = -0.65)</li>
                                        <li><strong>Fed Funds ↑</strong> → BTC ↓ (r = -0.58)</li>
                                        <li><strong>CPI ↑</strong> → Fed hawkish → BTC ↓</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                                <div class="fw-bold mb-2 text-success">Positive Correlation (Bullish Signals)</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li><strong>M2 ↑</strong> → BTC ↑ (r = +0.81)</li>
                                        <li><strong>RRP ↓</strong> → BTC ↑ (r = +0.68)</li>
                                        <li><strong>Risk Assets ↑</strong> → BTC ↑ (r = +0.75)</li>
                                        <li><strong>DXY ↓</strong> → Liquidity flows to BTC</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b;">
                                <div class="fw-bold mb-2 text-warning">Event-Based Impact</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li><strong>CPI > Expected</strong> → Volatility spike</li>
                                        <li><strong>NFP Strong</strong> → Fed hawkish → Risk-off</li>
                                        <li><strong>FOMC Meeting</strong> → High volatility window</li>
                                        <li><strong>Yield Inversion</strong> → Recession fears → Flight to safety</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fed Watch Tool -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4">
                    <h5 class="mb-3">Fed Watch Tool - Interest Rate Probabilities</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Meeting Date</th>
                                    <th>Current Rate</th>
                                    <th>-50 bps</th>
                                    <th>-25 bps</th>
                                    <th>No Change</th>
                                    <th>+25 bps</th>
                                    <th>+50 bps</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="meeting in fedWatch" :key="meeting.date">
                                    <tr>
                                        <td class="fw-semibold" x-text="meeting.date">--</td>
                                        <td x-text="meeting.currentRate + '%'">--</td>
                                        <td><span class="badge text-bg-success" x-text="meeting.cut50 + '%'">--</span></td>
                                        <td><span class="badge text-bg-success" x-text="meeting.cut25 + '%'">--</span></td>
                                        <td><span class="badge text-bg-secondary" x-text="meeting.hold + '%'">--</span></td>
                                        <td><span class="badge text-bg-danger" x-text="meeting.hike25 + '%'">--</span></td>
                                        <td><span class="badge text-bg-danger" x-text="meeting.hike50 + '%'">--</span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 small text-secondary">
                        <strong>Note:</strong> Probabilities based on Fed Funds futures pricing. Higher cut probability = More dovish = Bullish for crypto.
                        Higher hike probability = More hawkish = Bearish for crypto.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

    <script>
        function macroOverlayController() {
            return {
                selectedTimeframe: '3M',
                loading: false,
                metrics: {
                    dxy: { value: 104.25, change: -0.35 },
                    yield10y: { value: 4.28, change: 5.2 },
                    fedFunds: { value: 5.50, status: 'Restrictive', nextMeeting: 'Dec 18, 2024', probability: 68 },
                    cpi: { value: 3.2, date: 'Oct 2024', core: 3.8 },
                    yieldSpread: { value: -12 }, // 10Y-2Y spread in bps (negative = inverted)
                    nfp: { value: 187, expected: 180, change: 7, date: 'Nov 2024', unemployment: 3.9 },
                    m2: { value: 20.8, change: 0.3 },
                    rrp: { value: 850, change: -2.5 }
                },
                upcomingEvents: [
                    { id: 1, name: 'FOMC Meeting', date: 'Dec 18, 2024', impact: 'High' },
                    { id: 2, name: 'CPI Data Release', date: 'Dec 12, 2024', impact: 'High' },
                    { id: 3, name: 'NFP (Non-Farm Payrolls)', date: 'Dec 6, 2024', impact: 'High' },
                    { id: 4, name: 'PPI Data', date: 'Dec 14, 2024', impact: 'Medium' },
                    { id: 5, name: 'Retail Sales', date: 'Dec 15, 2024', impact: 'Medium' },
                    { id: 6, name: 'Fed Chair Speech', date: 'Dec 10, 2024', impact: 'High' },
                    { id: 7, name: 'Treasury Auctions', date: 'Dec 11, 2024', impact: 'Low' }
                ],
                fedWatch: [
                    { date: 'Dec 18, 2024', currentRate: 5.50, cut50: 5, cut25: 63, hold: 30, hike25: 2, hike50: 0 },
                    { date: 'Jan 29, 2025', currentRate: 5.50, cut50: 15, cut25: 55, hold: 25, hike25: 5, hike50: 0 },
                    { date: 'Mar 19, 2025', currentRate: 5.50, cut50: 25, cut25: 45, hold: 22, hike25: 7, hike50: 1 }
                ],
                dxyChart: null,
                yieldsChart: null,
                liquidityChart: null,
                nfpChart: null,
                yieldSpreadChart: null,

                init() {
                    // Wait for Chart.js to be ready
                    if (typeof Chart !== 'undefined') {
                        this.initCharts();
                    } else {
                        setTimeout(() => this.initCharts(), 100);
                    }
                },

                initCharts() {
                    // DXY Chart
                    const dxyCtx = document.getElementById('dxyChart');
                    if (dxyCtx) {
                        this.dxyChart = new Chart(dxyCtx, {
                            type: 'line',
                            data: {
                                labels: this.generateDateLabels(90),
                                datasets: [{
                                    label: 'DXY',
                                    data: this.generateDXYData(90),
                                    borderColor: 'rgb(239, 68, 68)',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (context) => `DXY: ${context.parsed.y.toFixed(2)}`
                                        }
                                    }
                                },
                                scales: {
                                    x: { display: true },
                                    y: { display: true, position: 'right' }
                                }
                            }
                        });
                    }

                    // Yields Chart
                    const yieldsCtx = document.getElementById('yieldsChart');
                    if (yieldsCtx) {
                        this.yieldsChart = new Chart(yieldsCtx, {
                            type: 'line',
                            data: {
                                labels: this.generateDateLabels(90),
                                datasets: [
                                    {
                                        label: '10Y Yield',
                                        data: this.generateYieldData(90, 4.3, 0.3),
                                        borderColor: 'rgb(59, 130, 246)',
                                        tension: 0.4
                                    },
                                    {
                                        label: '2Y Yield',
                                        data: this.generateYieldData(90, 4.8, 0.25),
                                        borderColor: 'rgb(34, 197, 94)',
                                        tension: 0.4
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: true, position: 'top' }
                                },
                                scales: {
                                    y: { display: true, position: 'right' }
                                }
                            }
                        });
                    }

                    // NFP Chart
                    const nfpCtx = document.getElementById('nfpChart');
                    if (nfpCtx) {
                        this.nfpChart = new Chart(nfpCtx, {
                            type: 'bar',
                            data: {
                                labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec (F)'],
                                datasets: [
                                    {
                                        label: 'Actual',
                                        data: [215, 142, 254, 150, 187, null],
                                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                        borderColor: 'rgb(34, 197, 94)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Expected',
                                        data: [200, 170, 170, 160, 180, 185],
                                        backgroundColor: 'rgba(156, 163, 175, 0.5)',
                                        borderColor: 'rgb(156, 163, 175)',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: true, position: 'top' }
                                },
                                scales: {
                                    y: {
                                        display: true,
                                        position: 'right',
                                        title: { display: true, text: 'Jobs Added (Thousands)' }
                                    }
                                }
                            }
                        });
                    }

                    // Yield Spread Chart
                    const yieldSpreadCtx = document.getElementById('yieldSpreadChart');
                    if (yieldSpreadCtx) {
                        const spreadData = this.generateYieldSpreadData(90);
                        this.yieldSpreadChart = new Chart(yieldSpreadCtx, {
                            type: 'line',
                            data: {
                                labels: this.generateDateLabels(90),
                                datasets: [{
                                    label: '10Y-2Y Spread (bps)',
                                    data: spreadData,
                                    borderColor: function(context) {
                                        // Color based on average value
                                        const data = context.dataset.data;
                                        const avg = data.reduce((a, b) => a + b, 0) / data.length;
                                        return avg < 0 ? 'rgb(239, 68, 68)' : 'rgb(34, 197, 94)';
                                    },
                                    backgroundColor: function(context) {
                                        const data = context.dataset.data;
                                        const avg = data.reduce((a, b) => a + b, 0) / data.length;
                                        return avg < 0 ? 'rgba(239, 68, 68, 0.2)' : 'rgba(34, 197, 94, 0.2)';
                                    },
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    y: {
                                        display: true,
                                        position: 'right',
                                        title: { display: true, text: 'Spread (bps)' }
                                    }
                                }
                            }
                        });
                    }

                    // Liquidity Chart
                    const liquidityCtx = document.getElementById('liquidityChart');
                    if (liquidityCtx) {
                        try {
                            this.liquidityChart = new Chart(liquidityCtx, {
                                type: 'line',
                                data: {
                                    labels: this.generateDateLabels(90),
                                    datasets: [
                                        {
                                            label: 'M2 Money Supply ($T)',
                                            data: this.generateM2Data(90),
                                            borderColor: 'rgb(34, 197, 94)',
                                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                            yAxisID: 'y',
                                            tension: 0.4,
                                            fill: false
                                        },
                                        {
                                            label: 'RRP ($B)',
                                            data: this.generateRRPData(90),
                                            borderColor: 'rgb(239, 68, 68)',
                                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                            yAxisID: 'y1',
                                            tension: 0.4,
                                            fill: false
                                        },
                                        {
                                            label: 'TGA ($B)',
                                            data: this.generateTGAData(90),
                                            borderColor: 'rgb(245, 158, 11)',
                                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                            yAxisID: 'y1',
                                            tension: 0.4,
                                            fill: false
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: { mode: 'index', intersect: false },
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'top',
                                            labels: {
                                                usePointStyle: true,
                                                padding: 15
                                            }
                                        },
                                        tooltip: {
                                            mode: 'index',
                                            intersect: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            type: 'linear',
                                            display: true,
                                            position: 'left',
                                            title: {
                                                display: true,
                                                text: 'M2 ($T)',
                                                font: { size: 12 }
                                            },
                                            ticks: {
                                                callback: function(value) {
                                                    return '$' + value.toFixed(1) + 'T';
                                                }
                                            }
                                        },
                                        y1: {
                                            type: 'linear',
                                            display: true,
                                            position: 'right',
                                            title: {
                                                display: true,
                                                text: 'RRP/TGA ($B)',
                                                font: { size: 12 }
                                            },
                                            grid: { drawOnChartArea: false },
                                            ticks: {
                                                callback: function(value) {
                                                    return '$' + value.toFixed(0) + 'B';
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        } catch (error) {
                            console.error('Error creating liquidity chart:', error);
                        }
                    }
                },

                generateDateLabels(days) {
                    const labels = [];
                    const today = new Date();
                    for (let i = days - 1; i >= 0; i--) {
                        const date = new Date(today);
                        date.setDate(date.getDate() - i);
                        labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                    }
                    return labels;
                },

                generateDXYData(days) {
                    const data = [];
                    let value = 105.5;
                    for (let i = 0; i < days; i++) {
                        value += (Math.random() - 0.52) * 0.8; // Slight downtrend
                        data.push(parseFloat(value.toFixed(2)));
                    }
                    return data;
                },

                generateYieldData(days, base, volatility) {
                    const data = [];
                    let value = base;
                    for (let i = 0; i < days; i++) {
                        value += (Math.random() - 0.5) * volatility;
                        data.push(parseFloat(value.toFixed(3)));
                    }
                    return data;
                },

                generateYieldSpreadData(days) {
                    const data = [];
                    let value = -20; // Start with inverted curve
                    for (let i = 0; i < days; i++) {
                        value += (Math.random() - 0.45) * 3; // Gradual steepening
                        data.push(parseFloat(value.toFixed(1)));
                    }
                    return data;
                },

                generateM2Data(days) {
                    const data = [];
                    let value = 20.8; // $20.8 Trillion
                    for (let i = 0; i < days; i++) {
                        value += (Math.random() - 0.45) * 0.05; // Slight uptrend
                        data.push(parseFloat(value.toFixed(2)));
                    }
                    return data;
                },

                generateRRPData(days) {
                    const data = [];
                    let value = 850; // $850 Billion
                    for (let i = 0; i < days; i++) {
                        value += (Math.random() - 0.55) * 15; // Downtrend (money leaving RRP)
                        data.push(parseFloat(value.toFixed(0)));
                    }
                    return data;
                },

                generateTGAData(days) {
                    const data = [];
                    let value = 680; // $680 Billion
                    for (let i = 0; i < days; i++) {
                        value += (Math.random() - 0.5) * 20;
                        data.push(parseFloat(value.toFixed(0)));
                    }
                    return data;
                },

                formatChange(value) {
                    return (value >= 0 ? '+' : '') + value.toFixed(2);
                },

                getEventClass(impact) {
                    if (impact === 'High') return 'bg-danger bg-opacity-10';
                    if (impact === 'Medium') return 'bg-warning bg-opacity-10';
                    return 'bg-info bg-opacity-10';
                },

                getImpactBadge(impact) {
                    if (impact === 'High') return 'text-bg-danger';
                    if (impact === 'Medium') return 'text-bg-warning';
                    return 'text-bg-info';
                },

                refreshAll() {
                    this.loading = true;
                    setTimeout(() => {
                        this.loading = false;
                        // Simulate data refresh
                        this.metrics.dxy.value = (104 + Math.random() * 2).toFixed(2);
                        this.metrics.yield10y.value = (4.2 + Math.random() * 0.3).toFixed(2);
                        this.metrics.yieldSpread.value = (Math.random() * 40 - 20).toFixed(0);
                    }, 1000);
                }
            };
        }
    </script>

    <style>
        .pulse-warning {
            background-color: #f59e0b;
            box-shadow: 0 0 0 rgba(245, 158, 11, 0.7);
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(245, 158, 11, 0);
            }
        }
    </style>
@endsection
