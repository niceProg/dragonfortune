@extends('layouts.app')

@section('title', 'OnChain Mining & Price | DragonFortune')

@section('content')
    {{--
        Mining & Price Analytics Dashboard
        Think like a trader • Build like an engineer • Visualize like a designer

        Interpretasi Trading:
        - MPI > 2 → Miners distributing → Potential selling pressure
        - MPI < 0 → Miners accumulating → Bullish for price
        - High Z-score → Extreme miner behavior → Reversal signal
        - Price correlation → Miner sentiment alignment
    --}}

    <div class="d-flex flex-column h-100 gap-3" x-data="onchainMiningPriceController()">
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">Mining & Price Analytics</h1>
                        <span class="pulse-dot pulse-success"></span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Analyze miner behavior, position indices, and comprehensive price data across assets
                    </p>
                </div>

                <!-- Global Controls -->
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Asset Filter - Focused on Mining Assets Only -->
                    <select class="form-select" style="width: 120px;" x-model="selectedAsset" @change="handleLimitChange()">
                        <option value="BTC">Bitcoin</option>
                    </select>

                    <!-- Data Limit - Enhanced with more options -->
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
            @include('components.onchain-mining-price.mining-price-summary')
        </div>

        <!-- MPI Analysis Row -->
        <div class="row g-3">
            <!-- Miners MPI Chart -->
            <div class="col-lg-8">
                @include('components.onchain-mining-price.miners-mpi-chart')
            </div>

            <!-- MPI Statistics Panel -->
            <div class="col-lg-4">
                <div class="df-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">📊 MPI Analysis</h5>
                            <small class="text-secondary">Statistical insights</small>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1);">
                            <div class="small text-muted mb-1">Current MPI</div>
                            <div class="h5 mb-0 fw-bold" :class="getMPIClass()" x-text="formatMPI(mpiSummary?.latest?.mpi)">--</div>
                            <div class="small" :class="getMPIChangeClass()" x-text="formatPercentage(mpiSummary?.latest?.change_pct)"></div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1);">
                            <div class="small text-muted mb-1">Z-Score</div>
                            <div class="h5 mb-0 fw-bold" :class="getZScoreClass()" x-text="formatZScore(mpiSummary?.stats?.z_score)">--</div>
                            <div class="small text-secondary" x-text="getZScoreInterpretation()">--</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(139, 92, 246, 0.1);">
                            <div class="small text-muted mb-1">Miner Sentiment</div>
                            <div class="h5 mb-0 fw-bold" :class="getMinerSentimentClass()" x-text="getMinerSentiment()">--</div>
                            <div class="small text-secondary">Based on MPI value</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(255, 193, 7, 0.1);">
                            <div class="small text-muted mb-1">Statistical Range</div>
                            <div class="h6 mb-1">
                                <span class="text-success" x-text="formatMPI(mpiSummary?.stats?.min)">--</span>
                                <span class="text-muted mx-2">to</span>
                                <span class="text-danger" x-text="formatMPI(mpiSummary?.stats?.max)">--</span>
                            </div>
                            <div class="small text-secondary">Min/Max range</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Price Analysis Row -->
        <div class="row g-3">
            <!-- Price Charts -->
            <div class="col-lg-12">
                @include('components.onchain-mining-price.price-charts')
            </div>
        </div>

        <!-- Correlation Analysis -->
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="df-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">🔗 MPI-Price Correlation</h5>
                            <small class="text-secondary">Miner behavior vs price action</small>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1);">
                            <div class="small text-muted mb-1">Correlation Strength</div>
                            <div class="h5 mb-0 fw-bold" :class="getCorrelationClass()" x-text="formatCorrelation(priceCorrelation)">--</div>
                            <div class="small text-secondary" x-text="getCorrelationInterpretation()">--</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1);">
                            <div class="small text-muted mb-1">Signal Strength</div>
                            <div class="h5 mb-0 fw-bold" :class="getSignalStrengthClass()" x-text="getSignalStrength()">--</div>
                            <div class="small text-secondary">Trading signal reliability</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="df-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-1">📈 Price Performance</h5>
                            <small class="text-secondary">Recent price metrics</small>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1);">
                            <div class="small text-muted mb-1">Current Price</div>
                            <div class="h5 mb-0 fw-bold" x-text="formatPrice(currentPrice, selectedAsset)">--</div>
                            <div class="small" :class="getPriceChangeClass()" x-text="formatPriceChange()">--</div>
                        </div>

                        <div class="p-3 rounded" style="background: rgba(139, 92, 246, 0.1);">
                            <div class="small text-muted mb-1">24h Volume</div>
                            <div class="h5 mb-0 fw-bold" x-text="formatVolume(currentVolume, selectedAsset)">--</div>
                            <div class="small text-secondary">Trading activity</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trading Insights -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4">
                    <h5 class="mb-3">📚 Understanding Mining Position Index (MPI)</h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                                <div class="fw-bold mb-2 text-success">🟩 Bullish MPI Signals</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>MPI < 0 → Miners accumulating</li>
                                        <li>Negative Z-score → Below average selling</li>
                                        <li>Low correlation → Independent price action</li>
                                        <li>Declining MPI trend → Reduced selling pressure</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                                <div class="fw-bold mb-2 text-danger">🟥 Bearish MPI Signals</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>MPI > 2 → Heavy miner distribution</li>
                                        <li>High positive Z-score → Extreme selling</li>
                                        <li>Strong correlation → Price following miners</li>
                                        <li>Rising MPI trend → Increasing selling pressure</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                                <div class="fw-bold mb-2 text-primary">⚡ Key Concepts</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li><strong>MPI:</strong> Miners Position Index (selling behavior)</li>
                                        <li><strong>Z-Score:</strong> Statistical deviation from mean</li>
                                        <li><strong>Correlation:</strong> MPI-price relationship strength</li>
                                        <li><strong>Signal:</strong> Trading opportunity indicator</li>
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
    
    <script src="{{ asset('js/onchain-mining-price-controller.js') }}"></script>
@endsection