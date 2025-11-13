@extends('layouts.app')

@section('title', 'OnChain Ethereum | DragonFortune')

@section('content')
    {{--
        Ethereum On-Chain Metrics Dashboard
        Think like a trader • Build like an engineer • Visualize like a designer

        Interpretasi Trading:
        - Gas price tinggi → Network congestion → Potensi penurunan aktivitas DeFi
        - Staking inflow tinggi → Bullish sentiment → Supply reduction
        - Gas usage trend → Network adoption dan aktivitas
        - Staking momentum → Long-term holder confidence
    --}}

    <div class="d-flex flex-column h-100 gap-3" x-data="onchainEthereumController()">
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">⚡ Ethereum Network Metrics</h1>
                        <span class="pulse-dot pulse-success"></span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Monitor Ethereum network health: gas fees, network utilization, and ETH 2.0 staking trends
                    </p>
                </div>

                <!-- Global Controls -->
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Data Limit - Enhanced with more options including 1000 and 2000 -->
                    <select class="form-select" style="width: 140px;" x-model="selectedLimit" @change="handleLimitChange()">
                        <option value="30">30 Records</option>
                        <option value="50">50 Records</option>
                        <option value="100">100 Records</option>
                        <option value="200">200 Records</option>
                        <option value="365">365 Records</option>
                        <option value="500">500 Records</option>
                        <option value="1000">1000 Records</option>
                        <option value="2000">2000 Records</option>
                    </select>

                    <!-- Auto-refresh Toggle - Fixed styling and positioning -->
                    <button class="btn" @click="toggleAutoRefresh()" 
                            :class="autoRefreshEnabled ? 'btn-success' : 'btn-outline-secondary'">
                        <span x-text="autoRefreshEnabled ? 'Auto-refresh: ON' : '⏸️ Auto-refresh: OFF'"></span>
                    </button>

                    <!-- Manual Refresh Button - Moved before auto-refresh -->
                    <button class="btn btn-primary" @click="refreshAll()" :disabled="loading">
                        <span x-show="!loading">Refresh All</span>
                        <span x-show="loading" class="spinner-border spinner-border-sm"></span>
                    </button>

                    <!-- Last Updated -->
                    <div class="d-flex align-items-center gap-1 text-muted small" x-show="lastUpdated">
                        <span>Last updated:</span>
                        <span x-text="lastUpdated" class="fw-bold"></span>
                    </div>
                    
                    <!-- Debug Button (temporary) -->
                    <!-- <button class="btn btn-warning btn-sm" @click="console.log('Test functions:', typeof formatPercentage, typeof getMomentumClass)">
                        🔍 Test Functions
                    </button> -->
                </div>
            </div>
        </div>

        <!-- Summary Cards Row -->
        <div class="row g-3">
            @include('components.onchain-ethereum.eth-summary-cards')
        </div>

        <!-- Charts Row -->
        <div class="row g-3">
            <!-- Network Gas Chart -->
            <div class="col-lg-8">
                @include('components.onchain-ethereum.network-gas-chart')
            </div>

            <!-- Gas Statistics Panel -->
            <div class="col-lg-4">
                <div class="df-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">📊 Gas Statistics</h5>
                            <small class="text-secondary">Current network state</small>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1);">
                            <div class="small text-muted mb-1">Average Gas Price</div>
                            <div class="h5 mb-0 fw-bold" x-text="formatGasPrice(gasSummary?.latest?.gas_price_mean)">--</div>
                            <div class="small" :class="getGasPriceChangeClass()" x-text="formatPercentage(gasSummary?.change_pct?.gas_price_mean)"></div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1);">
                            <div class="small text-muted mb-1">Network Utilization</div>
                            <div class="h5 mb-0 fw-bold" x-text="formatUtilization(gasSummary?.latest?.gas_used_mean, gasSummary?.latest?.gas_limit_mean)">--</div>
                            <div class="small text-secondary">Gas Used / Gas Limit</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(139, 92, 246, 0.1);">
                            <div class="small text-muted mb-1">Daily Gas Usage</div>
                            <div class="h5 mb-0 fw-bold" x-text="formatGasUsage(gasSummary?.latest?.gas_used_total)">--</div>
                            <div class="small" :class="getGasUsageChangeClass()" x-text="formatPercentage(gasSummary?.change_pct?.gas_used_total)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staking Row -->
        <div class="row g-3">
            <!-- Staking Deposits Chart -->
            <div class="col-lg-8">
                @include('components.onchain-ethereum.staking-deposits-chart')
            </div>

            <!-- Staking Statistics Panel -->
            <div class="col-lg-4">
                <div class="df-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">Staking Metrics</h5>
                            <small class="text-secondary">ETH 2.0 staking trends</small>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1);">
                            <div class="small text-muted mb-1">Latest Inflow</div>
                            <div class="h5 mb-0 fw-bold" x-text="formatETH(stakingSummary?.latest?.staking_inflow_total)">--</div>
                            <div class="small" :class="getStakingChangeClass()" x-text="formatPercentage(stakingSummary?.latest?.change_pct)"></div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1);">
                            <div class="small text-muted mb-1">7-Day Average</div>
                            <div class="h5 mb-0 fw-bold" x-text="formatETH(stakingSummary?.averages?.avg_7d)">--</div>
                            <div class="small text-secondary">Weekly trend</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(139, 92, 246, 0.1);">
                            <div class="small text-muted mb-1">Momentum</div>
                            <div class="h5 mb-0 fw-bold" :class="getMomentumClass()" x-text="formatPercentage(stakingSummary?.momentum_pct)">--</div>
                            <div class="small text-secondary">Staking acceleration</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trading Insights -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4">
                    <h5 class="mb-3">📚 Understanding Ethereum Network Metrics</h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                                <div class="fw-bold mb-2 text-success">🟩 Bullish Network Signals</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>High staking inflows → Supply reduction</li>
                                        <li>Stable gas prices → Network efficiency</li>
                                        <li>Increasing utilization → Growing adoption</li>
                                        <li>Positive staking momentum → Long-term confidence</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                                <div class="fw-bold mb-2 text-danger">🟥 Bearish Network Signals</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>Extremely high gas prices → Network congestion</li>
                                        <li>Declining staking inflows → Reduced confidence</li>
                                        <li>Low utilization → Decreased activity</li>
                                        <li>Negative staking momentum → Validator concerns</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                                <div class="fw-bold mb-2 text-primary">⚡ Key Concepts</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li><strong>Gas Price:</strong> Cost per unit of computation</li>
                                        <li><strong>Utilization:</strong> Network capacity usage</li>
                                        <li><strong>Staking Inflow:</strong> ETH locked in validators</li>
                                        <li><strong>Momentum:</strong> Rate of staking acceleration</li>
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
    
    <script src="{{ asset('js/onchain-ethereum-controller.js') }}?v={{ time() }}"></script>
    
    <!-- Debug script to verify controller is loaded -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Checking if onchainEthereumController is available:', typeof onchainEthereumController);
            if (typeof onchainEthereumController === 'undefined') {
                console.error('❌ onchainEthereumController is not defined! Check if the script loaded properly.');
            } else {
                console.log('✅ onchainEthereumController is available');
                // Test if the functions exist
                const controller = onchainEthereumController();
                console.log('🔍 formatPercentage function:', typeof controller.formatPercentage);
                console.log('🔍 getMomentumClass function:', typeof controller.getMomentumClass);
            }
        });
    </script>
@endsection