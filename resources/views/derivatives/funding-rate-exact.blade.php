@extends('layouts.app')

@section('title', 'Funding Rate | DragonFortune')

@push('head')
    <!-- Resource Hints for Faster API Loading (Critical for Hard Refresh) -->
    <link rel="dns-prefetch" href="{{ config('app.api_urls.internal') }}">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="{{ config('app.api_urls.internal') }}" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- Preload critical resources for faster initial load -->
    <link rel="preload" href="{{ asset('js/funding-rate-exact-controller.js') }}" as="script" type="module">
    
    <!-- Prefetch API endpoints (will fetch in background during hard refresh) -->
    <link rel="prefetch" href="{{ config('app.api_urls.internal') }}/api/funding-rate/history?symbol=BTCUSDT&exchange=Binance&interval=8h&limit=100" as="fetch" crossorigin="anonymous">
    <link rel="prefetch" href="{{ config('app.api_urls.internal') }}/api/funding-rate/analytics?symbol=BTCUSDT&exchange=Binance&interval=8h&limit=100" as="fetch" crossorigin="anonymous">
@endpush

@section('content')
    {{--
        Bitcoin: Funding Rate Dashboard
        Think like a trader • Build like an engineer • Visualize like a designer

        Interpretasi Trading:
        - Funding Rate mengukur premium/discount perpetual futures
        - Positive funding = longs pay shorts (bullish sentiment)
        - Negative funding = shorts pay longs (bearish sentiment)
        - Extreme funding rates often signal market tops/bottoms
    --}}

    <div class="d-flex flex-column h-100 gap-3" x-data="fundingRateController()">
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">Funding Rate</h1>
                        <span class="pulse-dot pulse-success" x-show="rawData.length > 0"></span>
                        <span class="spinner-border spinner-border-sm text-primary" style="width: 16px; height: 16px;" x-show="rawData.length === 0" x-cloak></span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Pantau funding rate dari kontrak perpetual (perpetual futures) untuk melihat arah sentimen pasar dan mengidentifikasi potensi pembalikan tren.
                    </p>
                </div>

                <!-- Global Controls -->
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Symbol Selector (20 Verified Symbols) -->
                    <select class="form-select" style="width: 140px;" x-model="selectedSymbol" @change="updateSymbol()">
                        <option value="BTCUSDT">BTC/USDT</option>
                        <option value="ETHUSDT">ETH/USDT</option>
                        <option value="SOLUSDT">SOL/USDT</option>
                        <option value="XRPUSDT">XRP/USDT</option>
                        <option value="HYPEUSDT">HYPE/USDT</option>
                        <option value="DOGEUSDT">DOGE/USDT</option>
                        <option value="BNBUSDT">BNB/USDT</option>
                        <option value="ZECUSDT">ZEC/USDT</option>
                        <option value="SUIUSDT">SUI/USDT</option>
                        <option value="ADAUSDT">ADA/USDT</option>
                        <option value="LINKUSDT">LINK/USDT</option>
                        <option value="ASTERUSDT">ASTER/USDT</option>
                        <option value="AVAXUSDT">AVAX/USDT</option>
                        <option value="ENAUSDT">ENA/USDT</option>
                        <option value="LTCUSDT">LTC/USDT</option>
                        <option value="PUMPUSDT">PUMP/USDT</option>
                        <option value="XPLUSDT">XPL/USDT</option>
                        <option value="BCHUSDT">BCH/USDT</option>
                        <option value="AAVEUSDT">AAVE/USDT</option>
                        <option value="TRUMPUSDT">TRUMP/USDT</option>
                    </select>

                    <!-- Exchange Selector -->
                    <select class="form-select" style="width: 160px;" x-model="selectedExchange" @change="updateExchange()">
                        <option value="Binance">Binance</option>
                        <option value="CoinEx">CoinEx</option>
                        <option value="Bybit">Bybit</option>
                        <!-- <option value="OKX">OKX</option>
                        <option value="HTX">HTX</option>
                        <option value="Bitmex">Bitmex</option>
                        <option value="Bitfinex">Bitfinex</option>
                        <option value="Deribit">Deribit</option>
                        <option value="Gate">Gate</option>
                        <option value="Kraken">Kraken</option>
                        <option value="KuCoin">KuCoin</option>
                        <option value="CME">CME</option>
                        <option value="Bitget">Bitget</option>
                        <option value="dYdX">dYdX</option>
                        <option value="BingX">BingX</option>
                        <option value="Coinbase">Coinbase</option>
                        <option value="Gemini">Gemini</option>
                        <option value="Crypto.com">Crypto.com</option>
                        <option value="Hyperliquid">Hyperliquid</option>
                        <option value="Bitunix">Bitunix</option>
                        <option value="MEXC">MEXC</option>
                        <option value="WhiteBIT">WhiteBIT</option>
                        <option value="Aster">Aster</option>
                        <option value="Lighter">Lighter</option>
                        <option value="EdgeX">EdgeX</option>
                        <option value="Drift">Drift</option>
                        <option value="Paradex">Paradex</option>
                        <option value="Extended">Extended</option>
                        <option value="ApeX Omni">ApeX Omni</option> -->
                    </select>

                    <!-- Interval Selector -->
                    <select class="form-select" style="width: 120px;" x-model="selectedInterval" @change="updateInterval()">
                        <option value="1m">1 Minute</option>
                        <option value="5m">5 Minutes</option>
                        <option value="15m">15 Minutes</option>
                        <option value="1h">1 Hour</option>
                        <option value="4h">4 Hours</option>
                        <option value="8h">8 Hours</option>
                        <option value="1w">1 Week</option>
                    </select>

                </div>
            </div>
        </div>

        <!-- Summary Cards Row -->
        <div class="row g-3">
            <!-- Current Funding Rate -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Current Rate</span>
                        <span class="badge text-bg-primary" x-show="currentFundingRate !== null && currentFundingRate !== undefined">Latest</span>
                        <!-- No loading badge for optimistic UI -->
                    </div>
                    <div>
                        <div class="h3 mb-1" x-text="currentFundingRate !== null && currentFundingRate !== undefined ? formatFundingRate(currentFundingRate) : '--'"></div>
                    </div>
                </div>
            </div>

            <!-- Average Funding Rate -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Period Avg</span>
                        <span class="badge text-bg-info" x-show="avgFundingRate !== null && avgFundingRate !== undefined">Avg</span>
                        <!-- No loading badge for optimistic UI -->
                    </div>
                    <div>
                        <div class="h3 mb-1" x-text="avgFundingRate !== null && avgFundingRate !== undefined ? formatFundingRate(avgFundingRate) : '--'"></div>
                    </div>
                </div>
            </div>

            <!-- Peak Funding Rate -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Peak Rate</span>
                        <span class="badge text-bg-danger" x-show="maxFundingRate !== null && maxFundingRate !== undefined">Max</span>
                        <!-- No loading badge for optimistic UI -->
                    </div>
                    <div>
                        <div class="h3 mb-1 text-danger" x-text="maxFundingRate !== null && maxFundingRate !== undefined ? formatFundingRate(maxFundingRate) : '--'"></div>
                        <!-- <div class="small text-secondary" x-text="peakDate || '--'"></div> -->
                    </div>
                </div>
            </div>

            <!-- Volatility -->
            <div class="col-md-2">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Volatility</span>
                        <span class="badge text-bg-warning" x-show="fundingVolatility !== null && fundingVolatility !== undefined">Vol</span>
                        <!-- No loading badge for optimistic UI -->
                    </div>
                    <div>
                        <div class="h3 mb-1" x-text="fundingVolatility !== null && fundingVolatility !== undefined ? formatFundingRate(fundingVolatility) : '--'"></div>
                    </div>
                </div>
            </div>

            <!-- Market Signal -->
            <div class="col-md-4">
                <div class="df-panel p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-secondary">Market Signal</span>
                        <span class="badge" :class="getSignalBadgeClass()" x-show="marketSignal !== null && marketSignal !== undefined && marketSignal !== 'Neutral'" x-text="signalStrength"></span>
                        <!-- No loading badge for optimistic UI -->
                    </div>
                    <div>
                        <div class="h4 mb-1" :class="getSignalColorClass()" x-text="marketSignal !== null && marketSignal !== undefined ? marketSignal : '--'"></div>
                        <div class="small text-secondary" x-text="signalDescription || ''"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Chart (TradingView Style) -->
        <div class="row g-3">
            <div class="col-12">
                <div class="tradingview-chart-container">
                    <div class="chart-header">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0">Funding Rate</h5>
                            <div class="chart-info">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="current-value" x-text="currentFundingRate !== null && currentFundingRate !== undefined ? formatFundingRate(currentFundingRate) : '--'"></span>
                                    <!-- <span class="change-badge" :class="fundingChange >= 0 ? 'positive' : 'negative'" x-text="formatChange(fundingChange)"></span> -->
                                </div>
                            </div>
                        </div>
                        <div class="chart-controls">
                            <div class="d-flex flex-wrap align-items-center gap-3">
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

                                <!-- Chart Type Toggle -->
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" 
                                            class="btn" 
                                            :class="chartType === 'line' ? 'btn-primary' : 'btn-outline-secondary'" 
                                            @click="toggleChartType('line')"
                                            title="Line Chart - Mudah Dibaca">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 4px;">
                                            <path d="M2 12l3-3 3 3 6-6"/>
                                        </svg>
                                        Line
                                    </button>
                                    <button type="button" 
                                            class="btn" 
                                            :class="chartType === 'candlestick' ? 'btn-primary' : 'btn-outline-secondary'" 
                                            @click="toggleChartType('candlestick')"
                                            title="Candlestick - OHLC Detail">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 4px;">
                                            <path d="M3 2h10v12H3V2zm1 1v10h8V3H4zm2 2h4v1H6V5zm0 3h2v1H6V8zm0 3h4v1H6v-1z"/>
                                        </svg>
                                        OHLC
                                    </button>
                                </div>

                                <!-- Interval Dropdown -->
                                <div class="dropdown">
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
                                
                                <!-- Export Dropdown -->
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle chart-tool-btn" data-bs-toggle="dropdown" title="Export Chart">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                            <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" @click.prevent="exportChart('png')">
                                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-2">
                                                <path d="M4.502 9a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM4 10.5a.5.5 0 1 1 1 0 .5.5 0 0 1-1 0z"/>
                                                <path d="M14 2H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zM2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2H2z"/>
                                                <path d="M10.648 7.646a.5.5 0 0 1 .577-.093L15.002 9.5V13h-14v-1l2.646-2.354a.5.5 0 0 1 .63-.062l2.66 1.773 3.71-3.71z"/>
                                            </svg>
                                            Export as PNG
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="exportChart('svg')">
                                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-2">
                                                <path d="M8.5 2a.5.5 0 0 0-1 0v5.793L5.354 5.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 7.793V2z"/>
                                                <path d="M3 9.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5z"/>
                                            </svg>
                                            Export as SVG
                                        </a></li>
                                    </ul>
                                </div>
                                
                                <button type="button" class="btn btn-outline-secondary chart-tool-btn" @click="shareChart()" title="Share Chart">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                        <path d="M11 2.5a2.5 2.5 0 1 1 .603 1.628l-6.718 3.12a2.499 2.499 0 0 1 0 1.504l6.718 3.12a2.5 2.5 0 1 1-.488.876l-6.718-3.12a2.5 2.5 0 1 1 0-3.256l6.718-3.12A2.5 2.5 0 0 1 11 2.5z"/>
                                    </svg>
                                </button>
                            </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="fundingRateMainChart"></canvas>
                    </div>
                    <div class="chart-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <template x-if="chartType === 'candlestick'">
                                <div class="d-flex align-items-center gap-3">
                                    <small class="chart-footer-text">
                                        <span style="display: inline-block; width: 10px; height: 10px; background: rgba(34, 197, 94, 0.8); border-radius: 2px; margin-right: 4px;"></span>
                                        🟢 Bullish (Longs pay Shorts)
                                    </small>
                                    <small class="chart-footer-text">
                                        <span style="display: inline-block; width: 10px; height: 10px; background: rgba(239, 68, 68, 0.8); border-radius: 2px; margin-right: 4px;"></span>
                                        🔴 Bearish (Shorts pay Longs)
                                    </small>
                                </div>
                            </template>
                            <template x-if="chartType === 'line'">
                                <div class="d-flex align-items-center gap-3">
                                    <small class="chart-footer-text text-secondary">
                                        📈 Line Chart - Menampilkan funding rate sebagai garis, mudah dibaca untuk melihat tren
                                    </small>
                                </div>
                            </template>
                            <small class="text-muted">
                                <span class="badge text-bg-success">Internal API v2</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exchange Comparison Table -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4" style="background: #ffffff; border: 1px solid #e5e7eb;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0" style="color: #1f2937;">📊 Perbandingan Exchange</h5>
                        <small class="text-secondary">
                            <span class="badge text-bg-success">Internal API v2</span>
                        </small>
                    </div>

                    <template x-if="exchangesData.length === 0">
                        <div class="text-center py-4 text-secondary">
                            <small>Tidak ada data exchange tersedia</small>
                        </div>
                    </template>

                    <template x-if="exchangesData.length > 0">
                        <div>
                            <!-- Arbitrage Info -->
                            <template x-if="calculateArbitrage()">
                                <div class="alert alert-info mb-3" role="alert" style="background-color: #dbeafe; border-color: #93c5fd; color: #1e40af;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <strong>💰 Peluang Arbitrase Ditemukan:</strong>
                                        <span class="badge text-bg-success" x-text="'Selisih: ' + (calculateArbitrage().spread * 100).toFixed(4) + '%'"></span>
                                    </div>
                                    <small>
                                        Tertinggi: <strong x-text="calculateArbitrage().maxExchange"></strong> 
                                        (<span x-text="formatFundingRate(calculateArbitrage().maxRate)"></span>) 
                                        vs 
                                        Terendah: <strong x-text="calculateArbitrage().minExchange"></strong> 
                                        (<span x-text="formatFundingRate(calculateArbitrage().minRate)"></span>)
                                    </small>
                                </div>
                            </template>

                            <!-- Exchange Table -->
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" style="background: #ffffff; color: #1f2937;">
                                    <thead style="background-color: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                        <tr>
                                            <th style="color: #374151; font-weight: 600; padding: 12px;">Exchange</th>
                                            <th class="text-end" style="color: #374151; font-weight: 600; padding: 12px;">Funding Rate</th>
                                            <th class="text-end" style="color: #374151; font-weight: 600; padding: 12px;">Next Funding</th>
                                            <th class="text-center" style="color: #374151; font-weight: 600; padding: 12px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="exchange in exchangesData" :key="exchange.exchange">
                                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                                <td style="padding: 12px;">
                                                    <strong style="color: #1f2937;" x-text="exchange.exchange"></strong>
                                                </td>
                                                <td class="text-end" style="padding: 12px;">
                                                    <span :class="exchange.funding_rate >= 0 ? 'text-success' : 'text-danger'" 
                                                          style="font-weight: 600; font-size: 0.95rem;"
                                                          x-text="formatFundingRate(exchange.funding_rate)"></span>
                                                </td>
                                                <td class="text-end" style="padding: 12px;">
                                                    <small class="text-secondary" style="font-size: 0.875rem;" x-text="formatNextFundingTime(exchange.next_funding_time)"></small>
                                                </td>
                                                <td class="text-center" style="padding: 12px;">
                                                    <span class="badge" 
                                                          :class="exchange.funding_rate >= 0 ? 'text-bg-success' : 'text-bg-danger'"
                                                          x-text="exchange.funding_rate >= 0 ? 'Long Pay' : 'Short Pay'">
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Explanation in Indonesian -->
                            <!-- <div class="mt-4 p-3 rounded" style="background-color: #f9fafb; border-left: 4px solid #3b82f6;">
                                <h6 style="color: #1f2937; margin-bottom: 12px; font-weight: 600;">📖 Penjelasan Tabel Perbandingan Exchange</h6>
                                <div class="small" style="color: #4b5563; line-height: 1.6;">
                                    <p class="mb-2">
                                        <strong>Funding Rate:</strong> Tingkat biaya yang dibayarkan atau diterima oleh trader berdasarkan posisi long/short mereka. 
                                        Rate positif berarti trader dengan posisi long membayar trader dengan posisi short (sentimen bullish), 
                                        sedangkan rate negatif berarti short membayar long (sentimen bearish).
                                    </p>
                                    <p class="mb-2">
                                        <strong>Next Funding:</strong> Waktu hingga pembayaran funding rate berikutnya. 
                                        Funding rate biasanya dibayarkan setiap 8 jam (pada pukul 00:00, 08:00, dan 16:00 UTC).
                                    </p>
                                    <p class="mb-0">
                                        <strong>Peluang Arbitrase:</strong> Selisih funding rate antar exchange dapat menciptakan peluang arbitrase. 
                                        Trader dapat memanfaatkan perbedaan ini dengan membuka posisi long di exchange dengan rate lebih rendah 
                                        dan short di exchange dengan rate lebih tinggi, menghasilkan keuntungan dari selisih rate.
                                    </p>
                                </div>
                            </div> -->
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Trading Interpretation -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-4">
                    <h5 class="mb-3">📚 Memahami Funding Rate</h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e;">
                                <div class="fw-bold mb-2 text-success">🟢 Positive Funding (Bullish)</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>Longs pay shorts (bullish sentiment)</li>
                                        <li>Perpetual futures diperdagangkan di harga premium</li>
                                        <li>Demand tinggi untuk posisi long</li>
                                        <li>Strategi: Waspadai kondisi pasar yang overheated</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                                <div class="fw-bold mb-2 text-danger">🔴 Negative Funding (Bearish)</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>Shorts pay longs (bearish sentiment)</li>
                                        <li>Perpetual futures diperdagangkan di bawah harga spot</li>
                                        <li>Demand tinggi untuk posisi short</li>
                                        <li>Strategi: Cari peluang pantulan saat kondisi oversold</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6;">
                                <div class="fw-bold mb-2 text-primary">⚡ Extreme Funding</div>
                                <div class="small text-secondary">
                                    <ul class="mb-0 ps-3">
                                        <li>Funding rate sangat tinggi atau sangat rendah</li>
                                        <li>Sering kali menandakan tops/bottoms pasar</li>
                                        <li>Indikator kontrarian untuk potensi pembalikan arah</li>
                                        <li>Strategi: Bersiap untuk perubahan tren</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <strong>💡 Pro Tip:</strong> Funding rate ekstrem (>0.1% atau <-0.1%) sering bertepatan dengan puncak atau dasar pasar. Gunakan sebagai indikator kontrarian bersama analisis pergerakan harga.
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

    <!-- Funding Rate Controller - Load with defer for non-blocking -->
    <script type="module" src="{{ asset('js/funding-rate-exact-controller.js') }}" defer></script>

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

        .chart-footer-text {
            color: #64748b !important;
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
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            padding: 2px;
        }

        .chart-controls .btn-outline-secondary {
            border-color: rgba(148, 163, 184, 0.3) !important;
            color: #94a3b8 !important;
        }

        .chart-controls .btn-outline-secondary:hover {
            background: rgba(59, 130, 246, 0.1) !important;
            border-color: rgba(59, 130, 246, 0.4) !important;
            color: #3b82f6 !important;
        }

        /* Enhanced Chart Tools */
        .chart-tools {
            background: linear-gradient(135deg, 
                rgba(30, 41, 59, 0.6) 0%, 
                rgba(51, 65, 85, 0.6) 100%);
            border-radius: 8px;
            padding: 0.25rem;
            border: 1px solid rgba(59, 130, 246, 0.15);
        }

        .chart-tool-btn {
            border: none !important;
            background: transparent !important;
            color: #94a3b8 !important;
            padding: 0.5rem 0.75rem !important;
            border-radius: 6px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative;
            overflow: hidden;
        }

        .chart-tool-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.1) 0%, 
                rgba(139, 92, 246, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .chart-tool-btn:hover {
            color: #e2e8f0 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2) !important;
        }

        .chart-tool-btn:hover::before {
            opacity: 1;
        }

        .chart-tool-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3) !important;
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

    </style>
@endsection