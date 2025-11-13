@extends('layouts.app')

@section('title', 'OnChain Exchange | DragonFortune')

@section('content')
    {{--
        Exchange Reserves & Market Indicators Dashboard
        Think like a trader • Build like an engineer • Visualize like a designer

        Interpretasi Trading:
        - Exchange outflow → Bullish accumulation (hodling)
        - Exchange inflow → Bearish distribution (selling pressure)
        - High leverage ratio → Risk of liquidation cascade
        - Reserve depletion → Supply shock potential
    --}}

    <div class="d-flex flex-column h-100 gap-3" x-data="onchainExchangeController()">
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">🏦 Exchange Reserves & Market Indicators</h1>
                        <span class="pulse-dot pulse-success"></span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Track institutional flows, exchange reserves, and market leverage ratios across major exchanges
                    </p>
                </div>

                <!-- Global Controls -->
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Asset Filter - Focused on Major Assets Only -->
                    <select class="form-select" style="width: 120px;" x-model="selectedAsset" @change="handleLimitChange()">
                        <option value="BTC">Bitcoin</option>
                    </select>

                    <!-- Data Limit - Enhanced with more options -->
                    <select class="form-select" style="width: 140px;" x-model="selectedLimit" @change="handleLimitChange()">
                        <option value="30">30 Records</option>
                        <option value="50">50 Records</option>
                        <option value="90">90 Records</option>
                        <option value="180">180 Records</option>
                        <option value="200">200 Records</option>
                        <option value="365">365 Records</option>
                        <option value="500">500 Records</option>
                        <option value="1000">1000 Records</option>
                        <option value="2000">2000 Records</option>
                    </select>

                    <!-- Manual Refresh Button - Moved before auto-refresh -->
                    <button class="btn btn-primary" @click="refreshAll()" :disabled="loading">
                        <span x-show="!loading">Refresh All</span>
                        <span x-show="loading" class="spinner-border spinner-border-sm"></span>
                    </button>

                    <!-- Auto-refresh Toggle -->
                    <button class="btn" @click="toggleAutoRefresh()" 
                            :class="autoRefreshEnabled ? 'btn-success' : 'btn-outline-secondary'">
                        <span x-text="autoRefreshEnabled ? 'Auto-refresh: ON' : '⏸️ Auto-refresh: OFF'"></span>
                    </button>

                    <!-- Last Updated -->
                    <div class="d-flex align-items-center gap-1 text-muted small" x-show="lastUpdated">
                        <span>Last updated:</span>
                        <span x-text="lastUpdated" class="fw-bold"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards Row -->
        <div class="row g-3">
            @include('components.onchain-exchange.exchange-summary-cards')
        </div>

        <!-- Charts Row -->
        <div class="row g-3">
            <!-- Exchange Reserves Chart -->
            <div class="col-lg-8">
                @include('components.onchain-exchange.exchange-reserves-chart')
            </div>

            <!-- Reserve Statistics Panel -->
            <div class="col-lg-4">
                <div class="df-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">📊 Reserve Analysis</h5>
                            <small class="text-secondary">Current exchange state</small>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1);">
                            <div class="small text-muted mb-1">Total Reserves</div>
                            <div class="h5 mb-0 fw-bold" x-text="formatReserve(reserveSummary?.totals?.latest_reserve, selectedAsset)">--</div>
                            <div class="small text-secondary" x-text="formatUSD(reserveSummary?.totals?.latest_reserve_usd)">--</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1);">
                            <div class="small text-muted mb-1">24h Change</div>
                            <div class="h5 mb-0 fw-bold" :class="getReserveChangeClass()" x-text="formatReserveChange(reserveSummary?.totals?.change, selectedAsset)">--</div>
                            <div class="small" :class="getReserveChangeClass()" x-text="formatUSD(reserveSummary?.totals?.change_usd)">--</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(139, 92, 246, 0.1);">
                            <div class="small text-muted mb-1">Flow Direction</div>
                            <div class="h5 mb-0 fw-bold" :class="getFlowDirectionClass()" x-text="getFlowDirection()">--</div>
                            <div class="small text-secondary">Based on reserve changes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Market Indicators Row -->
        <div class="row g-3">
            <!-- Market Indicators Chart -->
            <div class="col-lg-8">
                @include('components.onchain-exchange.market-indicators-chart')
            </div>

            <!-- Leverage Statistics Panel -->
            <div class="col-lg-4">
                <div class="df-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">⚖️ Market Risk</h5>
                            <small class="text-secondary">Leverage indicators</small>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1);">
                            <div class="small text-muted mb-1">Estimated Leverage Ratio</div>
                            <div class="h5 mb-0 fw-bold" :class="getLeverageRiskClass()" x-text="formatLeverage(currentLeverageRatio)">--</div>
                            <div class="small" :class="getLeverageRiskClass()" x-text="getLeverageRiskLabel()">--</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(255, 193, 7, 0.1);">
                            <div class="small text-muted mb-1">Risk Level</div>
                            <div class="h5 mb-0 fw-bold" :class="getRiskLevelClass()" x-text="getRiskLevel()">--</div>
                            <div class="small text-secondary">Based on leverage ratio</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1);">
                            <div class="small text-muted mb-1">Market Health</div>
                            <div class="h5 mb-0 fw-bold" :class="getMarketHealthClass()" x-text="getMarketHealth()">--</div>
                            <div class="small text-secondary">Overall assessment</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exchange Comparison Table -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">📈 Binance Reserve Data</h5>
                            <small class="text-secondary">Binance reserve trends and statistics</small>
                        </div>
                        <div class="d-flex gap-2">
                            <span x-show="loadingStates.reserves" class="spinner-border spinner-border-sm text-primary"></span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Exchange</th>
                                    <th class="text-end">Current Reserve</th>
                                    <th class="text-end">USD Value</th>
                                    <th class="text-end">24h Change</th>
                                    <th class="text-end">% Change</th>
                                    <th class="text-center">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(exchange, idx) in exchangeList" :key="idx">
                                    <tr>
                                        <td class="fw-semibold" x-text="exchange.exchange"></td>
                                        <td class="text-end" x-text="formatReserve(exchange.latest?.reserve, selectedAsset)"></td>
                                        <td class="text-end" x-text="formatUSD(exchange.latest?.reserve_usd)"></td>
                                        <td class="text-end" :class="exchange.change?.absolute >= 0 ? 'text-success' : 'text-danger'" 
                                            x-text="formatReserveChange(exchange.change?.absolute, selectedAsset)"></td>
                                        <td class="text-end" :class="exchange.change?.percentage >= 0 ? 'text-success' : 'text-danger'" 
                                            x-text="formatPercentage(exchange.change?.percentage)"></td>
                                        <td class="text-center">
                                            <span x-show="exchange.trend === 'rising'" class="text-success">📈</span>
                                            <span x-show="exchange.trend === 'falling'" class="text-danger">📉</span>
                                            <span x-show="exchange.trend === 'stable'" class="text-muted">➖</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="exchangeList.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <span x-show="!loadingStates.reserves">No exchange data available</span>
                                        <span x-show="loadingStates.reserves">Loading exchange data...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trading Insights -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4">
                    <h5 class="mb-3">📚 Understanding Exchange Flows & Market Indicators</h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                                <div class="fw-bold mb-2 text-success">🟩 Bullish Flow Signals</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>Exchange outflows → Accumulation phase</li>
                                        <li>Declining reserves → Supply reduction</li>
                                        <li>Low leverage ratios → Healthy market</li>
                                        <li>Stable institutional flows → Confidence</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                                <div class="fw-bold mb-2 text-danger">🟥 Bearish Flow Signals</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>Exchange inflows → Distribution phase</li>
                                        <li>Rising reserves → Selling pressure</li>
                                        <li>High leverage ratios → Liquidation risk</li>
                                        <li>Institutional dumping → Panic selling</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                                <div class="fw-bold mb-2 text-primary">⚡ Key Concepts</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li><strong>Exchange Reserves:</strong> Assets held on exchanges</li>
                                        <li><strong>Flow Direction:</strong> Net inflow/outflow trend</li>
                                        <li><strong>Leverage Ratio:</strong> Market leverage estimation</li>
                                        <li><strong>Institutional Flow:</strong> Large holder movements</li>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    
    <!-- Chart initialization helper -->
    <script src="{{ asset('js/chart-init-helper.js') }}"></script>
    
    <!-- Wait for Chart.js to load before initializing -->
    <script>
        window.chartJsReady = new Promise((resolve) => {
            if (typeof Chart !== 'undefined') {
                resolve();
            } else {
                setTimeout(() => resolve(), 100);
            }
        });
    </script>
    
    <script src="{{ asset('js/onchain-exchange-controller.js') }}"></script>
@endsection