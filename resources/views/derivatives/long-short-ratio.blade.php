@extends('layouts.app')

@section('title', 'Long Short Ratio | DragonFortune')

@push('head')
    <!-- Resource Hints for Faster API Loading (Critical for Hard Refresh) -->
    <link rel="dns-prefetch" href="{{ config('app.api_urls.internal') }}">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="{{ config('app.api_urls.internal') }}" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- Preload critical resources for faster initial load -->
    <link rel="preload" href="{{ asset('js/long-short-ratio-controller.js') }}" as="script" type="module">
    
    <!-- Prefetch API endpoints (will fetch in background during hard refresh) -->
    <link rel="prefetch" href="{{ config('app.api_urls.internal') }}/api/long-short-ratio/top-accounts?symbol=BTCUSDT&exchange=Binance&interval=1h&limit=100" as="fetch" crossorigin="anonymous">
    <link rel="prefetch" href="{{ config('app.api_urls.internal') }}/api/long-short-ratio/analytics?symbol=BTCUSDT&exchange=Binance&interval=1h&ratio_type=accounts&limit=100" as="fetch" crossorigin="anonymous">
@endpush

@section('content')
    {{--
        Bitcoin: Long/Short Ratio Dashboard (HYBRID)
        Think like a trader • Build like an engineer • Visualize like a designer

        HYBRID API APPROACH:
        - MAIN DATA: Coinglass API for Long/Short Ratio data
        - PRICE OVERLAY: CryptoQuant API for Bitcoin price (as reference)
        
        Interpretasi Trading:
        - Long/Short Ratio menunjukkan sentimen dan positioning pasar
        - Ratio > 2.0: Long crowded → Potensi koreksi atau short squeeze
        - Ratio < 0.5: Short crowded → Potensi rally atau long squeeze  
        - Ratio 0.8-1.2: Pasar seimbang → Kelanjutan trend yang sehat
    --}}

    <div class="d-flex flex-column h-100 gap-3" x-data="longShortRatioController()">
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">Long-Short Ratio</h1>
                        <span class="pulse-dot pulse-success" x-show="topAccountData.length > 0"></span>
                        <span class="spinner-border spinner-border-sm text-primary" style="width: 16px; height: 16px;" x-show="topAccountData.length === 0" x-cloak></span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Analisis sentimen pasar melalui rasio posisi long vs short untuk mengidentifikasi crowding dan peluang contrarian.
                    </p>
                </div>

                <!-- Global Controls -->
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Exchange Selector -->
                    <select class="form-select" style="width: 160px;" x-model="selectedExchange" @change="updateExchange()">
                        <option value="Binance">Binance</option>
                        <option value="Bybit">Bybit</option>
                        <!-- <option value="CoinEx">CoinEx</option> -->
                    </select>

                    <!-- Symbol/Pair Selector -->
                    <select class="form-select" style="width: 140px;" x-model="selectedSymbol" @change="updateSymbol()">
                        <option value="BTCUSDT">BTC/USDT</option>
                        <!-- <option value="ETHUSDT">ETH/USDT</option>
                        <option value="BNBUSDT">BNB/USDT</option>
                        <option value="SOLUSDT">SOL/USDT</option> -->
                    </select>

                    <!-- Interval Selector -->
                    <select class="form-select" style="width: 100px;" x-model="selectedInterval" @change="updateInterval()">
                        <template x-for="interval in chartIntervals" :key="interval.value">
                            <option :value="interval.value" x-text="interval.label"></option>
                        </template>
                    </select>

                </div>
            </div>
        </div>

        <!-- Summary Cards Row (from Analytics API) -->
        <div class="row g-3">
            <!-- Average Ratio -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Avg Ratio</span>
                        <span class="badge text-bg-primary" x-show="analyticsData?.ratio_stats">Analytics</span>
                    </div>
                    <div class="h3 mb-1" x-text="analyticsData?.ratio_stats?.avg_ratio ? parseFloat(analyticsData.ratio_stats.avg_ratio).toFixed(2) : '--'"></div>
                    <div class="small text-secondary">Average</div>
                </div>
            </div>

            <!-- Max Ratio -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="small text-secondary mb-2">Max Ratio</div>
                    <div class="h3 mb-1 text-danger" x-text="analyticsData?.ratio_stats?.max_ratio ? parseFloat(analyticsData.ratio_stats.max_ratio).toFixed(2) : '--'"></div>
                    <div class="small text-secondary">Peak</div>
                </div>
            </div>

            <!-- Min Ratio -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="small text-secondary mb-2">Min Ratio</div>
                    <div class="h3 mb-1 text-success" x-text="analyticsData?.ratio_stats?.min_ratio ? parseFloat(analyticsData.ratio_stats.min_ratio).toFixed(2) : '--'"></div>
                    <div class="small text-secondary">Low</div>
                </div>
            </div>

            <!-- Volatility -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="small text-secondary mb-2">Volatility</div>
                    <div class="h3 mb-1 text-warning" x-text="analyticsData?.ratio_stats?.volatility ? (analyticsData.ratio_stats.volatility * 100).toFixed(2) + '%' : '--'"></div>
                    <div class="small text-secondary">Stability</div>
                </div>
            </div>

            <!-- Positioning -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="small text-secondary mb-2">Positioning</div>
                    <div class="h5 mb-0 text-break" x-text="analyticsData?.positioning || '--'"></div>
                </div>
            </div>

            <!-- Trend -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="small text-secondary mb-2">Trend</div>
                    <div class="h5 mb-0 text-break" x-text="analyticsData?.trend || '--'"></div>
                </div>
            </div>
        </div>

        <!-- Main Chart (TradingView Style) -->
        <div class="row g-3">
            <div class="col-12">
                <div class="tradingview-chart-container">
                    <div class="chart-header">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0">Top Accounts Ratio & Distribution</h5>
                        </div>
                        <div class="chart-controls">
                            <!-- Time Range Buttons -->
                            <div class="time-range-selector me-3">
                                <template x-for="range in timeRanges" :key="range.value">
                                    <button type="button" 
                                            class="btn btn-sm time-range-btn"
                                            :class="globalPeriod === range.value ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="setTimeRange(range.value)"
                                            x-text="range.label">
                                    </button>
                                </template>
                            </div>

                            <!-- Chart Type Toggle (hidden) -->
                            <div class="btn-group btn-group-sm me-3" role="group" style="display: none;">
                                <button type="button" class="btn" :class="chartType === 'line' ? 'btn-primary' : 'btn-outline-secondary'" @click="toggleChartType('line')">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                        <path d="M2 12l3-3 3 3 6-6"/>
                                    </svg>
                                </button>
                                <button type="button" class="btn" :class="chartType === 'bar' ? 'btn-primary' : 'btn-outline-secondary'" @click="toggleChartType('bar')">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                        <rect x="2" y="6" width="3" height="8"/>
                                        <rect x="6" y="4" width="3" height="10"/>
                                        <rect x="10" y="8" width="3" height="6"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Interval Dropdown -->
                            <div class="dropdown me-3">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle interval-dropdown-btn" 
                                        type="button" 
                                        data-bs-toggle="dropdown" 
                                        :title="'Chart Interval: ' + (chartIntervals.find(i => i.value === selectedInterval)?.label || '1D')">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    <span x-text="chartIntervals.find(i => i.value === selectedInterval)?.label || '1D'"></span>
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

                            <!-- Scale Toggle (hidden) -->
                            <div class="btn-group btn-group-sm me-3" role="group" style="display: none;">
                                <button type="button" 
                                        class="btn scale-toggle-btn"
                                        :class="scaleType === 'linear' ? 'btn-primary' : 'btn-outline-secondary'"
                                        @click="toggleScale('linear')"
                                        title="Linear Scale - Equal intervals, good for absolute changes">
                                    Linear
                                </button>
                                <button type="button" 
                                        class="btn scale-toggle-btn"
                                        :class="scaleType === 'logarithmic' ? 'btn-primary' : 'btn-outline-secondary'"
                                        @click="toggleScale('logarithmic')"
                                        title="Logarithmic Scale - Exponential intervals, good for percentage changes">
                                    Log
                                </button>
                            </div>

                            <!-- Chart Tools (hidden) -->
                            <div class="btn-group btn-group-sm chart-tools" role="group" style="display: none;">
                                <button type="button" class="btn btn-outline-secondary chart-tool-btn" @click="resetZoom()" title="Reset Zoom">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                        <path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                                        <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="longShortRatioMainChart"></canvas>
                    </div>
                    <div class="chart-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="chart-footer-text">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" style="margin-right: 4px;">
                                    <circle cx="6" cy="6" r="5" fill="none" stroke="currentColor" stroke-width="1"/>
                                    <path d="M6 3v3l2 2" stroke="currentColor" stroke-width="1" fill="none"/>
                                </svg>
                                Ratio > 2.0 menunjukkan long crowded, ratio < 0.5 menunjukkan short crowded - peluang contrarian
                            </small>
                            <small class="text-muted">
                                <span class="badge text-bg-primary">Internal API v2</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Positions Ratio & Distribution Chart -->
        <div class="row g-3">
            <div class="col-12">
                <div class="tradingview-chart-container">
                    <div class="chart-header">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0">Top Positions Ratio & Distribution</h5>
                            <!-- <div class="chart-info">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="current-value" x-text="currentTopPositionRatio !== null && currentTopPositionRatio !== undefined ? formatRatio(currentTopPositionRatio) : '--'"></span>
                                    <span class="change-badge" :class="topPositionRatioChange >= 0 ? 'positive' : 'negative'" x-text="currentTopPositionRatio !== null && currentTopPositionRatio !== undefined ? formatChange(topPositionRatioChange) : '--'"></span>
                                </div>
                            </div> -->
                        </div>
                        <div class="chart-controls">
                            <!-- Time Range Buttons -->
                            <div class="time-range-selector me-3">
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
                            <div class="dropdown me-3">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle interval-dropdown-btn" 
                                        type="button" 
                                        data-bs-toggle="dropdown" 
                                            :title="'Chart Interval: ' + (chartIntervals.find(i => i.value === selectedInterval)?.label || '1D')">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                        <span x-text="chartIntervals.find(i => i.value === selectedInterval)?.label || '1D'"></span>
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
                    <div class="chart-body">
                        <canvas id="longShortRatioPositionsChart"></canvas>
                    </div>
                    <div class="chart-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="chart-footer-text">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" style="margin-right: 4px;">
                                    <circle cx="6" cy="6" r="5" fill="none" stroke="currentColor" stroke-width="1"/>
                                    <path d="M6 3v3l2 2" stroke="currentColor" stroke-width="1" fill="none"/>
                                </svg>
                                Ratio menunjukkan distribusi posisi long vs short berdasarkan ukuran modal (USD)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RATIO COMPARISON CHART SECTION -->
        <div class="row g-3">
            <div class="col-12">
                <div class="tradingview-chart-container">
                    <div class="chart-header">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0">Ratio Comparison Chart</h5>
                            <div class="chart-info">
                                <span class="small text-secondary">Top Account vs Top Position</span>
                            </div>
                        </div>
                        
                    </div>
                    <div class="chart-body">
                        <canvas id="longShortRatioComparisonChart"></canvas>
                    </div>
                    <div class="chart-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="chart-footer-text">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" style="margin-right: 4px;">
                                    <path d="M6 2L3 6h2v4h2V6h2L6 2z" fill="currentColor"/>
                                </svg>
                                Divergensi antara Top Account, dan Top Position ratio dapat mengindikasikan perubahan sentimen
                            </small>
                            <small class="text-muted">
                                <span class="badge text-bg-info">Multi-Ratio Analysis</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Long-Short Ratio Interpretation -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4">
                    <h5 class="mb-3">📚 Memahami Long/Short Ratio</h5>

                    <div class="alert alert-info mb-0">
                        <strong>💡 Tips Pro:</strong> Long/Short Ratio adalah indikator contrarian yang kuat. Ketika rasio berada pada kondisi ekstrem, pasar cenderung overcrowded dan berpotensi mengalami reversal. Gunakan bersama analisis teknikal untuk timing entry/exit yang optimal.
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

    <!-- Long/Short Ratio Controller - Load with defer for non-blocking -->
    <script type="module" src="{{ asset('js/long-short-ratio-controller.js') }}" defer></script>

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
        /* Light Theme Chart Container */
        .tradingview-chart-container {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
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

        .chart-controls .btn-group {
            background: rgba(241, 245, 249, 0.8);
            border-radius: 6px;
            padding: 2px;
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
            background: rgba(241, 245, 249, 0.8);
        }

        .chart-controls .btn-primary {
            background: #3b82f6;
            color: #ffffff;
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
            color: #64748b;
            display: flex;
            align-items: center;
        }

        /* Pulse animation for live indicator */
        .pulse-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        .pulse-success {
            background-color: #22c55e;
            box-shadow: 0 0 0 rgba(34, 197, 94, 0.7);
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
            }
        }

        /* Enhanced Summary Cards */
        .df-panel {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
            border: 1px solid rgba(59, 130, 246, 0.1);
            transition: all 0.3s ease;
        }

        .df-panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }

        /* Professional Time Range Controls */
        .time-range-selector {
            display: flex;
            gap: 6px;
            background: rgba(241, 245, 249, 0.8);
            border-radius: 8px;
            padding: 0.25rem;
        }

        .time-range-btn {
            padding: 6px 14px !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            border: none !important;
            border-radius: 6px !important;
            transition: all 0.2s ease !important;
            min-width: 44px;
            color: #64748b !important;
            background: transparent !important;
        }

        .time-range-btn:hover {
            color: #1e293b !important;
            background: rgba(241, 245, 249, 0.5) !important;
        }

        .time-range-btn.btn-primary {
            background: #3b82f6 !important;
            color: #ffffff !important;
        }

        .time-range-btn.btn-primary:hover {
            background: #2563eb !important;
        }

        .scale-toggle-btn {
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            padding: 0.375rem 0.75rem !important;
            min-width: 50px;
        }

        .chart-controls {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .chart-controls .btn-group {
            background: rgba(241, 245, 249, 0.8);
            border-radius: 6px;
            padding: 2px;
        }

        .chart-controls .btn-outline-secondary {
            border-color: rgba(226, 232, 240, 0.8) !important;
            color: #64748b !important;
            background: rgba(241, 245, 249, 0.5) !important;
        }

        .chart-controls .btn-outline-secondary:hover {
            background: rgba(59, 130, 246, 0.1) !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
            color: #3b82f6 !important;
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

        .chart-header {
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.08) 0%, 
                rgba(139, 92, 246, 0.06) 100%);
            border-bottom: 1px solid rgba(59, 130, 246, 0.25);
            position: relative;
            z-index: 2;
        }

        .chart-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(59, 130, 246, 0.3) 50%, 
                transparent 100%);
        }


        .chart-footer {
            padding: 12px 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            background: rgba(59, 130, 246, 0.02);
        }

        .chart-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(59, 130, 246, 0.2) 50%, 
                transparent 100%);
        }

        /* Professional Animations */
        @keyframes chartLoad {
            0% {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
            }
        }

        .tradingview-chart-container {
            animation: chartLoad 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pulse-dot.pulse-success {
            animation: pulse 2s ease-in-out infinite, pulseGlow 2s ease-in-out infinite;
        }

        /* Loading States */
        .chart-loading {
            position: relative;
            overflow: hidden;
        }

        .chart-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(59, 130, 246, 0.1) 50%, 
                transparent 100%);
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Enhanced Hover Effects */
        .df-panel {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .df-panel:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 
                0 12px 32px rgba(59, 130, 246, 0.2),
                0 4px 16px rgba(59, 130, 246, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .derivatives-header h1 {
                font-size: 1.5rem;
            }
            
            .chart-body {
                height: 350px;
                padding: 12px;
            }
            
            .chart-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            
            .current-value {
                font-size: 16px;
            }

            .chart-controls {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
                gap: 0.75rem;
            }

            .time-range-selector {
                justify-content: center;
                flex-wrap: wrap;
            }

            .time-range-btn {
                flex: 1;
                min-width: 35px;
            }

            .chart-tools {
                justify-content: center;
            }

            .df-panel:hover {
                transform: translateY(-2px) scale(1.01);
            }
        }

        /* Light Mode Support */
        .chart-footer-text {
            color: var(--bs-body-color, #6c757d);
            transition: color 0.3s ease;
        }

        /* Light mode chart styling */
        @media (prefers-color-scheme: light) {
            .tradingview-chart-container {
                background: linear-gradient(135deg, 
                    rgba(248, 250, 252, 0.98) 0%, 
                    rgba(241, 245, 249, 0.98) 50%,
                    rgba(248, 250, 252, 0.98) 100%);
                border: 1px solid rgba(59, 130, 246, 0.2);
                box-shadow: 
                    0 10px 40px rgba(0, 0, 0, 0.1),
                    0 4px 16px rgba(59, 130, 246, 0.05),
                    inset 0 1px 0 rgba(255, 255, 255, 0.8);
            }

            .chart-header {
                background: linear-gradient(135deg, 
                    rgba(59, 130, 246, 0.05) 0%, 
                    rgba(139, 92, 246, 0.03) 100%);
                border-bottom: 1px solid rgba(59, 130, 246, 0.15);
            }

            .chart-header h5 {
                color: #1e293b;
                text-shadow: none;
            }

            .current-value {
                color: #2563eb;
                text-shadow: none;
            }

            .chart-body {
                background: linear-gradient(135deg, 
                    rgba(248, 250, 252, 0.9) 0%, 
                    rgba(241, 245, 249, 0.85) 50%,
                    rgba(248, 250, 252, 0.9) 100%);
            }

            .chart-footer {
                background: linear-gradient(135deg, 
                    rgba(59, 130, 246, 0.03) 0%, 
                    rgba(139, 92, 246, 0.02) 100%);
                border-top: 1px solid rgba(59, 130, 246, 0.15);
            }

            .chart-footer-text {
                color: #64748b !important;
            }

            .time-range-selector {
                background: linear-gradient(135deg, 
                    rgba(241, 245, 249, 0.8) 0%, 
                    rgba(226, 232, 240, 0.8) 100%);
                border: 1px solid rgba(59, 130, 246, 0.15);
            }

            .time-range-btn {
                color: #64748b !important;
            }

            .time-range-btn:hover {
                color: #1e293b !important;
            }

            .chart-tools {
                background: linear-gradient(135deg, 
                    rgba(241, 245, 249, 0.6) 0%, 
                    rgba(226, 232, 240, 0.6) 100%);
                border: 1px solid rgba(59, 130, 246, 0.1);
            }

            .chart-tool-btn {
                color: #64748b !important;
            }

            .chart-tool-btn:hover {
                color: #1e293b !important;
            }
        }

        /* Light theme only - no dark mode */

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