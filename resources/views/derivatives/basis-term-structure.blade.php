@extends('layouts.app')

@section('title', 'Basis & Term Structure | DragonFortune')

@push('head')
    <!-- Resource Hints for Faster API Loading (Critical for Hard Refresh) -->
    <link rel="dns-prefetch" href="{{ config('app.api_urls.internal') }}">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="{{ config('app.api_urls.internal') }}" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- Preload critical resources for faster initial load -->
    <link rel="preload" href="{{ asset('js/basis-term-structure-controller.js') }}" as="script" type="module">
    
    <!-- Prefetch API endpoints (will fetch in background during hard refresh) -->
    <link rel="prefetch" href="{{ config('app.api_urls.internal') }}/api/basis/history?exchange=Binance&spot_pair=BTC/USDT&futures_symbol=BTCUSDT&interval=1h&limit=100" as="fetch" crossorigin="anonymous">
    <link rel="prefetch" href="{{ config('app.api_urls.internal') }}/api/basis/analytics?exchange=Binance&spot_pair=BTC/USDT&futures_symbol=BTCUSDT&interval=1h&limit=100" as="fetch" crossorigin="anonymous">
@endpush

@section('content')
    {{--
        Basis & Term Structure Dashboard
        Think like a trader • Build like an engineer • Visualize like a designer
        
        Interpretasi Trading:
        - Basis = Futures Price - Spot Price
        - Positive basis (contango) = futures > spot (normal in bull markets)
        - Negative basis (backwardation) = futures < spot (stress signal)
        - Term structure shows yield curve across expiries for calendar spreads
    --}}

    <div class="d-flex flex-column h-100 gap-3" x-data="basisTermStructureController()" x-init="init()" x-cloak>
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">Basis & Term Structure</h1>
                        <span class="pulse-dot pulse-success" x-show="rawData.length > 0"></span>
                        <span class="spinner-border spinner-border-sm text-primary" style="width: 16px; height: 16px;" x-show="rawData.length === 0" x-cloak></span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Pantau basis (selisih harga futures vs spot) dan struktur term untuk mengidentifikasi peluang arbitrase dan memahami kondisi pasar futures.
                    </p>
                </div>

                <!-- Global Controls -->
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Exchange Selector -->
                    <select class="form-select" style="width: 160px;" x-model="selectedExchange" @change="updateExchange()">
                        <option value="Binance">Binance</option>
                        <option value="Bybit">Bybit</option>
                        <option value="MEXC">MEXC</option>
                        <option value="WhiteBIT">WhiteBIT</option>
                        <option value="Gate">Gate</option>
                        <option value="OKX">OKX</option>
                        <option value="Coinbase">Coinbase</option>
                        <option value="Bitget">Bitget</option>
                        <option value="Hyperliquid">Hyperliquid</option>
                        <option value="Bitunix">Bitunix</option>
                        <option value="BingX">BingX</option>
                        <option value="Crypto.com">Crypto.com</option>
                        <option value="Deribit">Deribit</option>
                        <option value="KuCoin">KuCoin</option>
                        <option value="HTX">HTX</option>
                        <option value="Bitmex">Bitmex</option>
                        <option value="Kraken">Kraken</option>
                        <option value="CoinEx">CoinEx</option>
                        <option value="Bitfinex">Bitfinex</option>
                        <option value="dYdX">dYdX</option>
                    </select>

                    <!-- Spot Pair Selector (for History & Analytics) -->
                    <select class="form-select" style="width: 140px;" x-model="selectedSpotPair" @change="updateSpotPair()">
                        <option value="BTC/USDT">BTC/USDT</option>
                        <option value="ETH/USDT">ETH/USDT</option>
                    </select>

                    <!-- Interval Selector -->
                    <select class="form-select" style="width: 120px;" x-model="selectedInterval" @change="updateInterval()">
                        <option value="5m">5 Minute</option>
                        <option value="15m">15 Minutes</option>
                        <option value="1h">1 Hour</option>
                        <option value="4h">4 Hours</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Summary Cards Row - Data from Analytics API -->
        <div class="row g-3">
            <!-- Current Basis (from History API - latest data point) -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Current Basis</span>
                        <span class="badge text-bg-primary" x-show="currentBasis !== null && currentBasis !== undefined">Latest</span>
                        <span class="badge text-bg-secondary" x-show="currentBasis === null || currentBasis === undefined">Loading...</span>
                    </div>
                    <div class="h3 mb-0" 
                         :class="currentBasis !== null && currentBasis !== undefined && currentBasis >= 0 ? 'text-success' : (currentBasis !== null && currentBasis !== undefined ? 'text-danger' : '')"
                         x-text="currentBasis !== null && currentBasis !== undefined ? formatBasis(currentBasis) : '--'"></div>
                </div>
            </div>

            <!-- Average Basis (from Analytics API) -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Avg Basis</span>
                        <span class="badge text-bg-info" x-show="avgBasis !== null && avgBasis !== undefined">Avg</span>
                        <span class="badge text-bg-secondary" x-show="avgBasis === null || avgBasis === undefined">Loading...</span>
                    </div>
                    <div class="h3 mb-0" x-text="avgBasis !== null && avgBasis !== undefined ? formatBasis(avgBasis) : '--'"></div>
                </div>
            </div>

            <!-- Basis Annualized (from Analytics API) -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Basis Annualized</span>
                        <span class="badge text-bg-info" x-show="basisAnnualized !== null && basisAnnualized !== undefined">Annual</span>
                        <span class="badge text-bg-secondary" x-show="basisAnnualized === null || basisAnnualized === undefined">Loading...</span>
                    </div>
                    <div class="h3 mb-0" x-text="basisAnnualized !== null && basisAnnualized !== undefined ? formatBasisAnnualized(basisAnnualized) : '--'"></div>
                </div>
            </div>

            <!-- Volatility (from Analytics API) -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Volatility</span>
                        <span class="badge text-bg-warning" x-show="basisVolatility !== null && basisVolatility !== undefined">Vol</span>
                        <span class="badge text-bg-secondary" x-show="basisVolatility === null || basisVolatility === undefined">Loading...</span>
                    </div>
                    <div class="h3 mb-0" x-text="basisVolatility !== null && basisVolatility !== undefined ? formatBasis(basisVolatility) : '--'"></div>
                </div>
            </div>

            <!-- Market Structure (from Analytics API) -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Market Structure</span>
                        <span class="badge" :class="getMarketStructureBadgeClass()" x-show="marketStructure !== null && marketStructure !== undefined" x-text="formatMarketStructure(marketStructure)"></span>
                        <span class="badge text-bg-secondary" x-show="marketStructure === null || marketStructure === undefined">Loading...</span>
                    </div>
                    <div class="h4 mb-0" x-text="marketStructure !== null && marketStructure !== undefined ? formatMarketStructure(marketStructure) : '--'"></div>
                    </div>
                </div>

            <!-- Trend (from Analytics API) -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Trend</span>
                        <span class="badge" :class="getTrendBadgeClass()" x-show="trend !== null && trend !== undefined" x-text="formatTrend(trend)"></span>
                        <span class="badge text-bg-secondary" x-show="trend === null || trend === undefined">Loading...</span>
                    </div>
                    <div class="h4 mb-0" :class="getTrendColorClass()" x-text="trend !== null && trend !== undefined ? formatTrend(trend) : '--'"></div>
                </div>
            </div>
        </div>

        <!-- Main Chart (Basis History) -->
        <div class="row g-3">
            <div class="col-12">
                <div class="tradingview-chart-container">
                    <div class="chart-header">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0">Basis History</h5>
                            <div class="chart-info">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="current-value" x-text="currentBasis !== null && currentBasis !== undefined ? formatBasis(currentBasis) : '--'"></span>
                                </div>
                            </div>
                        </div>
                        <div class="chart-controls">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <!-- Futures Symbol Selector (for Basis History Chart only) -->
                                <div class="d-flex align-items-center gap-2">
                                    <label class="small text-secondary mb-0" style="white-space: nowrap;">Futures Symbol:</label>
                                    <select class="form-select form-select-sm" style="width: 180px; min-width: 160px;" x-model="selectedFuturesSymbol" @change="updateFuturesSymbol()">
                                        <template x-for="symbol in getAvailableFuturesSymbols()" :key="symbol">
                                            <option :value="symbol" x-text="symbol"></option>
                                        </template>
                                    </select>
                                </div>

                            <!-- Time Range Buttons -->
                                <div class="time-range-selector">
                                <template x-for="range in timeRanges" :key="range.value">
                                    <button type="button" 
                                            class="btn btn-sm time-range-btn"
                                            :class="globalPeriod === range.value ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="setTimeRange(range.value)"
                                            x-text="range.label">
                                    </button>
                                </template>
                            </div>

                            <!-- Interval Dropdown -->
                                <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle interval-dropdown-btn" 
                                        type="button" 
                                        data-bs-toggle="dropdown" 
                                            :title="'Chart Interval: ' + (chartIntervals.find(i => i.value === selectedInterval)?.label || '1H')">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                        <span x-text="chartIntervals.find(i => i.value === selectedInterval)?.label || '1H'"></span>
                                </button>
                                    <ul class="dropdown-menu">
                                    <template x-for="interval in chartIntervals" :key="interval.value">
                                        <li>
                                            <a class="dropdown-item" 
                                               href="#" 
                                               @click.prevent="setChartInterval(interval.value)"
                                               :class="selectedInterval === interval.value ? 'active' : ''"
                                               x-text="interval.label">
                                            </a>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            </div>
                        </div>
                            </div>

                    <!-- Chart Canvas -->
                    <div class="chart-body">
                        <canvas id="basisMainChart"></canvas>
                                </div>
                                
                    <!-- Chart Footer Legend -->
                    <div class="chart-footer">
                        <div class="d-flex flex-wrap gap-3 justify-content-center small text-secondary">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 16px; height: 3px; background: linear-gradient(to right, rgba(239, 68, 68, 1), rgba(34, 197, 94, 1)); border-radius: 2px;"></div>
                                <span>Basis (USD) - Green (positive/contango), Red (negative/backwardation)</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 16px; height: 2px; background: #f59e0b;"></div>
                                <span>Spot Price</span>
                        </div>
                                                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 16px; height: 2px; background: #3b82f6;"></div>
                                <span>Futures Price</span>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Term Structure Chart -->
        <div class="row g-3">
            <div class="col-12">
                <div class="tradingview-chart-container">
                    <div class="chart-header">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0">Term Structure</h5>
                                </div>
                        <div class="chart-controls">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <!-- Symbol Selector for Term Structure (BTC, ETH) -->
                                <div class="d-flex align-items-center gap-2">
                                    <label class="small text-secondary mb-0" style="white-space: nowrap;">Symbol:</label>
                                    <select class="form-select form-select-sm" style="width: 100px; min-width: 80px;" x-model="termStructureSymbol" @change="loadTermStructure()">
                                    <option value="BTC">BTC</option>
                                    <option value="ETH">ETH</option>
                                </select>
                                </div>
                                <small class="text-secondary" style="white-space: nowrap;">Basis curve across expiries</small>
                            </div>
                        </div>
                    </div>

                    <!-- Term Structure Chart Canvas -->
                    <div class="chart-body">
                        <canvas id="basisTermStructureChart"></canvas>
                        </div>

                    <!-- Chart Footer Legend -->
                    <div class="chart-footer">
                        <div class="d-flex flex-wrap gap-3 justify-content-center small text-secondary">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 16px; height: 12px; background: rgba(34, 197, 94, 0.8); border-radius: 2px;"></div>
                                <span>Basis (USD) - Bars</span>
                                                </div>
                                                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 16px; height: 2px; background: #3b82f6;"></div>
                                <span>Basis Annualized (%) - Line</span>
                                                            </div>
                                                                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Chart.js with Date Adapter and Plugins - Load async for faster initial render -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js" defer></script>

    <!-- Initialize Chart.js ready promise immediately (non-blocking) -->
    <script>
        // Create promise immediately (non-blocking)
        window.chartJsReady = new Promise((resolve) => {
            // Check if Chart.js already loaded (from cache or previous load)
            if (typeof Chart !== 'undefined') {
                console.log('✅ Chart.js already loaded');
                resolve();
                return;
            }
            
            // Wait for Chart.js to load (with fallback timeout)
            let checkCount = 0;
            const checkInterval = setInterval(() => {
                checkCount++;
                if (typeof Chart !== 'undefined') {
                    console.log('✅ Chart.js loaded (after', checkCount * 50, 'ms)');
                    clearInterval(checkInterval);
                    resolve();
                } else if (checkCount > 40) {
                    // Timeout after 2 seconds - resolve anyway
                    console.warn('⚠️ Chart.js load timeout, resolving anyway');
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 50);
        });
    </script>

    <!-- Basis Term Structure Controller - Load with defer for non-blocking -->
    <script type="module" src="{{ asset('js/basis-term-structure-controller.js') }}" defer></script>

    <style>
        /* Skeleton placeholders */
        [x-cloak] { display: none !important; }
        .skeleton {
            position: relative;
            overflow: hidden;
            background: rgba(148, 163, 184, 0.15);
            border-radius: 6px;
        }
        .skeleton::after {
            content: '';
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.4) 50%,
                rgba(255,255,255,0) 100%);
            animation: skeleton-shimmer 1.2s infinite;
        }
        .skeleton-text { display: inline-block; }
        .skeleton-badge { display: inline-block; border-radius: 999px; }
        .skeleton-pill { display: inline-block; border-radius: 999px; }
        @keyframes skeleton-shimmer {
            100% { transform: translateX(100%); }
        }

        /* Use default .derivatives-header styles from app.css (matching funding-rate, open-interest, liquidations) */
        
        /* Light Theme Chart Container */
        .tradingview-chart-container {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            background: rgba(59, 130, 246, 0.03);
        }

        .chart-header h5 {
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .chart-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .current-value {
            color: #3b82f6;
            font-size: 20px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }

        .change-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .change-badge.positive {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }

        .change-badge.negative {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        /* Chart Controls - Responsive Layout */
        .chart-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
            padding: 12px 20px;
        }

        .chart-controls > div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-controls .btn-group {
            background: rgba(241, 245, 249, 0.8);
            border-radius: 6px;
            padding: 2px;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .chart-controls .btn {
            border: none;
            padding: 6px 12px;
            color: #64748b;
            background: transparent;
            transition: all 0.2s;
        }

        .chart-controls .btn:hover {
            color: #1e293b;
            background: rgba(241, 245, 249, 1);
        }

        .chart-controls .btn-primary,
        .chart-controls .btn.btn-primary {
            background: #3b82f6;
            color: #fff;
        }

        .chart-controls .btn-outline-secondary {
            color: #64748b;
            border-color: rgba(226, 232, 240, 0.8);
        }

        .chart-controls .btn-outline-secondary:hover {
            background: rgba(241, 245, 249, 1);
            color: #1e293b;
        }

        .chart-body {
            padding: 20px;
            height: 500px;
            position: relative;
            background: #ffffff;
        }

        .chart-footer {
            padding: 12px 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            background: rgba(59, 130, 246, 0.02);
        }

        .chart-footer small {
            color: #64748b !important;
            display: flex;
            align-items: center;
        }

        .chart-footer-text {
            color: #64748b !important;
        }

        /* Pulse animation for live indicator */
        .pulse-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #22c55e;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 0.7; }
            50% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0.8); opacity: 0.7; }
        }

        /* Time Range Selector */
        .time-range-selector {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .time-range-btn {
            padding: 6px 14px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chart-controls {
                padding: 10px 16px;
                gap: 0.75rem;
            }

            .chart-controls > div {
                width: 100%;
                justify-content: flex-start;
            }

            .time-range-selector {
            width: 100%;
                justify-content: flex-start;
            }

            .form-select.form-select-sm {
                width: 100% !important;
                min-width: unset !important;
            }
        }

        @media (max-width: 576px) {
            .chart-controls {
            flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .chart-controls > div {
                width: 100%;
            }

            .time-range-selector {
                width: 100%;
            justify-content: space-between;
            }

            .time-range-btn {
            flex: 1;
                min-width: 0;
            }
        }

        /* Dropdown Menu Styling */
        .dropdown-menu {
            background: #ffffff !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1) !important;
        }

        .dropdown-menu .dropdown-item {
            color: #1e293b !important;
            transition: all 0.2s ease !important;
            border-radius: 4px !important;
            margin: 0.125rem !important;
        }

        .dropdown-menu .dropdown-item:hover {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
        }

        .dropdown-menu .dropdown-item.active {
            background: #3b82f6 !important;
            color: #fff !important;
        }

        /* Interval Dropdown Styling */
        .interval-dropdown-btn {
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            padding: 0.5rem 0.75rem !important;
            min-width: 70px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                border: 1px solid rgba(59, 130, 246, 0.15) !important;
            background: rgba(241, 245, 249, 0.8) !important;
                color: #64748b !important;
            }

            .interval-dropdown-btn:hover {
                color: #1e293b !important;
                border-color: rgba(59, 130, 246, 0.3) !important;
            background: rgba(241, 245, 249, 1) !important;
            }

        .interval-dropdown-btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
        }
    </style>
@endsection
