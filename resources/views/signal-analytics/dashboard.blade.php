@extends('layouts.app')

@section('title', 'Signal & Analytics | DragonFortune')

@section('content')
<div class="d-flex flex-column gap-3" x-data="signalAnalytics()" x-init="init()" x-cloak>
    <div class="derivatives-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-3 mb-1">
                    <h1 class="mb-0">Signal & Analytics</h1>
                    <span class="badge rounded-pill" :class="signalBadgeClass()" x-text="signal?.signal ?? 'NEUTRAL'"></span>
                    <span class="pulse-dot pulse-success" x-show="!isLoading"></span>
                    <span class="spinner-border spinner-border-sm text-primary" style="width:16px;height:16px;" x-show="isLoading"></span>
                </div>
                <p class="mb-0 text-secondary">
                    Multi-factor signal engine untuk <strong x-text="symbol"></strong> (pair <strong x-text="pairLabel()"></strong>) yang menggabungkan Funding, OI, Whale, ETF, Sentimen, dan microstructure.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <select class="form-select" style="width:130px;" x-model="symbol" @change="onSymbolChange">
                    <template x-for="asset in symbols" :key="asset">
                        <option :value="asset" x-text="asset"></option>
                    </template>
                </select>
                <select class="form-select" style="width:110px;" x-model="interval" @change="fetchData">
                    <option value="1h">1H</option>
                    <option value="4h">4H</option>
                    <option value="1d">1D</option>
                </select>
                <button class="btn btn-outline-primary" @click="fetchData" :disabled="isLoading">
                    <i class="fas fa-sync-alt" :class="{'fa-spin': isLoading}"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4 col-lg-6">
            <div class="df-panel h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 text-uppercase text-muted small">Signal Quality</h5>
                    <span class="badge" :class="qualityBadgeClass()" x-text="signal?.quality?.status ?? 'N/A'"></span>
                </div>
                <div class="display-6 fw-semibold mb-1" x-text="qualityScoreLabel()"></div>
                <p class="text-muted small mb-3">Menggabungkan kelengkapan data, kondisi volatilitas, dan filter risiko.</p>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <template x-for="flag in (signal?.quality?.flags ?? [])" :key="flag.code">
                        <span class="badge rounded-pill" :class="flagBadgeClass(flag.severity)" x-text="flag.label"></span>
                    </template>
                    <span class="text-muted small" x-show="!(signal?.quality?.flags?.length)">Tidak ada filter aktif</span>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6">
            <div class="df-panel h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 text-uppercase text-muted small">Market Regime</h5>
                    <span class="badge" :class="regimeBadgeClass()" x-text="signal?.meta?.regime ?? '--'"></span>
                </div>
                <div class="h3 fw-semibold mb-1" x-text="features?.momentum?.regime_reason ?? 'Menunggu data'"></div>
                <div class="row mt-3 g-2">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="small text-muted text-uppercase">Trend Score</div>
                            <div class="fw-semibold" x-text="formatNumber(features?.momentum?.trend_score, 2)"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="small text-muted text-uppercase">Range Width</div>
                            <div class="fw-semibold" x-text="formatPercent(features?.momentum?.range?.width_pct)"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="small text-muted text-uppercase">Mom 24h</div>
                            <div class="fw-semibold" x-text="formatPercent(features?.momentum?.momentum_1d_pct)"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="small text-muted text-uppercase">Mom 7d</div>
                            <div class="fw-semibold" x-text="formatPercent(features?.momentum?.momentum_7d_pct)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="df-panel h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 text-uppercase text-muted small">Long / Short Sentiment</h5>
                    <span class="badge text-bg-warning-subtle text-warning" x-show="features?.long_short?.is_stale">Stale</span>
                </div>
                <div class="row small g-2 mb-2">
                    <div class="col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted text-uppercase">Global</div>
                            <div class="fw-semibold" x-text="formatPercent(features?.long_short?.global?.net_ratio ? features.long_short.global.net_ratio * 100 : null, 1)"></div>
                            <div class="text-muted" x-text="signal?.meta?.long_short_bias ?? '--'"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted text-uppercase">Top Traders</div>
                            <div class="fw-semibold" x-text="formatPercent(features?.long_short?.top?.net_ratio ? features.long_short.top.net_ratio * 100 : null, 1)"></div>
                            <div class="text-muted" x-text="signal?.meta?.top_trader_bias ?? '--'"></div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">Divergensi</span>
                    <span class="fw-semibold" x-text="formatPercent(features?.long_short?.divergence ? features.long_short.divergence * 100 : null, 2)"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="df-panel h-100 p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="mb-0 text-uppercase text-muted small">Primary Signal</h5>
                    <span class="badge text-bg-dark" x-text="formatTime(lastUpdated)"></span>
                </div>
                <div class="display-5 fw-semibold mb-2" x-text="signal?.signal ?? 'NEUTRAL'"></div>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Confidence</dt>
                    <dd class="col-7 fw-semibold" x-text="formatPercent(signal?.confidence ? signal.confidence * 100 : null)"></dd>
                    <dt class="col-5 text-muted">Score</dt>
                    <dd class="col-7 fw-semibold" x-text="signal?.score?.toFixed(2) ?? '--'"></dd>
                    <dt class="col-5 text-muted">Spot Price</dt>
                    <dd class="col-7 fw-semibold" x-text="formatUsd(features?.microstructure?.price?.last_close)"></dd>
                    <dt class="col-5 text-muted">AI Probability</dt>
                    <dd class="col-7 fw-semibold">
                        <span x-text="ai ? formatPercent(ai.probability * 100) : '--'"></span>
                        <span class="badge ms-2" :class="aiBadgeClass()" x-text="ai?.decision ?? 'N/A'"></span>
                        <div class="text-muted small">Edge <span x-text="ai ? formatPercent(ai.confidence * 100) : '--'"></span></div>
                    </dd>
                </dl>
                <div class="mt-3">
                    <h6 class="text-muted text-uppercase small mb-2">Reasons</h6>
                    <template x-if="!signal?.factors?.length">
                        <p class="text-muted small mb-0">Menunggu data ...</p>
                    </template>
                    <ul class="list-unstyled mb-0" x-show="signal?.factors?.length">
                        <template x-for="(reason, idx) in signal?.factors" :key="idx">
                            <li class="d-flex align-items-start gap-2 mb-2">
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                                    <span x-text="reason.weight > 0 ? '+' + reason.weight : reason.weight"></span>
                                </span>
                                <div>
                                    <div class="fw-semibold small" x-text="reason.reason"></div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="df-panel h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 text-uppercase text-muted small">Factor Monitor</h5>
                    <span class="badge rounded-pill text-bg-info">Auto refresh 5 menit</span>
                </div>
                <div class="row g-3">
                    <template x-for="card in factorCards" :key="card.key">
                        <div class="col-md-4">
                            <div class="p-3 rounded border h-100" :class="card.variant">
                                <div class="small text-muted text-uppercase" x-text="card.title"></div>
                                <div class="h4 fw-semibold my-1" x-text="card.value()"></div>
                                <div class="small text-muted" x-text="card.subtitle()"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="df-panel p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Backtest Overview</h5>
                <p class="text-muted small mb-0">Rule-based signal performance ({{ config('signal.label_horizon_hours',24) }}h horizon).</p>
            </div>
            <button class="btn btn-sm btn-outline-secondary" @click="fetchBacktest" :disabled="isBacktestLoading">
                <i class="fas fa-history me-1" :class="{'fa-spin': isBacktestLoading}"></i>
                Refresh
            </button>
        </div>
        <template x-if="!backtest">
            <p class="text-muted mb-0">Menunggu data backtest...</p>
        </template>
        <div class="row g-3" x-show="backtest">
            <div class="col-md-4 col-lg-3 col-xl-2">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted text-uppercase">Win Rate</div>
                    <div class="h4 mb-0" x-text="formatPercent(backtest?.metrics?.win_rate ? backtest.metrics.win_rate * 100 : null)"></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 col-xl-2">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted text-uppercase">Profit Factor</div>
                    <div class="h4 mb-0" x-text="formatNumber(backtest?.metrics?.profit_factor, 2)"></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 col-xl-2">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted text-uppercase">Trades (B/S)</div>
                    <div class="h5 mb-0" x-text="`${backtest?.metrics?.buy_trades ?? 0} / ${backtest?.metrics?.sell_trades ?? 0}`"></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 col-xl-2">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted text-uppercase">AI Alignment</div>
                    <div class="h4 mb-0" x-text="formatPercent(backtest?.metrics?.ai_alignment_rate ? backtest.metrics.ai_alignment_rate * 100 : null)"></div>
                    <div class="text-muted small">AI & rule searah</div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 col-xl-2">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted text-uppercase">Filtered Win Rate</div>
                    <div class="h4 mb-0" x-text="formatPercent(backtest?.metrics?.filtered_win_rate ? backtest.metrics.filtered_win_rate * 100 : null)"></div>
                    <div class="text-muted small" x-text="`${backtest?.metrics?.ai_filtered_trades ?? 0} trades`"></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 col-xl-2">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted text-uppercase">Avg / Max DD</div>
                    <div class="h5 mb-0" x-text="`${formatPercent(backtest?.metrics?.avg_return_all_pct)} / ${formatPercent(backtest?.metrics?.max_drawdown_pct, 2, true)}`"></div>
                </div>
            </div>
        </div>
        <div class="table-responsive mt-3" x-show="timelineRows().length">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Signal</th>
                        <th>Return</th>
                        <th>Cumulative</th>
                        <th>Drawdown</th>
                        <th>AI</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="entry in timelineRows()" :key="entry.generated_at">
                        <tr>
                            <td x-text="formatDateTime(entry.generated_at)"></td>
                            <td>
                                <span class="badge" :class="entry.signal === 'BUY' ? 'text-bg-success' : (entry.signal === 'SELL' ? 'text-bg-danger' : 'text-bg-secondary')" x-text="entry.signal"></span>
                            </td>
                            <td x-text="formatPercent(entry.return_pct)"></td>
                            <td x-text="formatPercent(entry.cumulative)"></td>
                            <td x-text="formatPercent(entry.drawdown, 2, true)"></td>
                            <td>
                                <span class="badge me-1" :class="entry.ai_decision === 'BUY' ? 'text-bg-success' : (entry.ai_decision === 'SELL' ? 'text-bg-danger' : 'text-bg-secondary')" x-text="entry.ai_decision ?? 'N/A'"></span>
                                <span class="text-muted small" x-text="entry.ai_probability ? formatPercent(entry.ai_probability * 100) : '--'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0 mt-2" x-show="!timelineRows().length">Belum ada histori backtest karena snapshot belum terkumpul.</p>
    </div>

    <div class="df-panel p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Indicator Breakdown</h5>
                <p class="text-muted small mb-0">Rangkuman faktor utama yang membentuk sinyal.</p>
            </div>
            <span class="badge text-bg-light" x-text="`Symbol ${symbol} • ${interval.toUpperCase()}`"></span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Faktor</th>
                        <th>Nilai</th>
                        <th>Interpretasi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Funding Heat</td>
                        <td x-text="formatNumber(features?.funding?.heat_score)"></td>
                        <td x-text="fundingInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>Funding Trend</td>
                        <td x-text="formatPercent(features?.funding?.trend_pct)"></td>
                        <td x-text="fundingTrendInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>Open Interest Δ 24h</td>
                        <td x-text="formatPercent(features?.open_interest?.pct_change_24h)"></td>
                        <td x-text="oiInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>Whale Pressure</td>
                        <td x-text="formatNumber(features?.whales?.pressure_score)"></td>
                        <td x-text="whaleInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>ETF Net Flow</td>
                        <td x-text="formatUsd(features?.etf?.latest_flow)"></td>
                        <td x-text="etfInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>ETF Streak</td>
                        <td x-text="formatStreak(features?.etf?.streak)"></td>
                        <td x-text="etfStreakInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>Sentiment (Fear & Greed)</td>
                        <td x-text="features?.sentiment?.value ?? '--'"></td>
                        <td x-text="sentimentInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>Long/Short Divergence</td>
                        <td x-text="formatPercent(features?.long_short?.divergence ? features.long_short.divergence * 100 : null, 2)"></td>
                        <td x-text="longShortInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>Trend Score</td>
                        <td x-text="formatNumber(features?.momentum?.trend_score, 2)"></td>
                        <td x-text="regimeInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>Microstructure (Taker Buy Ratio)</td>
                        <td x-text="formatPercent(features?.microstructure?.taker_flow?.buy_ratio ? features.microstructure.taker_flow.buy_ratio * 100 : null, 1)"></td>
                        <td x-text="microInterpretation()"></td>
                    </tr>
                    <tr>
                        <td>Volatility (24h)</td>
                        <td x-text="formatPercent(features?.microstructure?.price?.volatility_24h, 2, true)"></td>
                        <td x-text="volatilityInterpretation()"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="df-panel p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Whale Flow (24h)</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-light">USD</span>
                        <span class="badge text-bg-warning-subtle text-warning fw-semibold" x-show="features?.whales?.is_stale">Stale</span>
                    </div>
                </div>
                <dl class="row small mb-0">
                    <dt class="col-6 text-muted">Inflow ke CEX</dt>
                    <dd class="col-6 fw-semibold" x-text="formatUsd(features?.whales?.window_24h?.inflow_usd)"></dd>
                    <dt class="col-6 text-muted">Outflow dari CEX</dt>
                    <dd class="col-6 fw-semibold" x-text="formatUsd(features?.whales?.window_24h?.outflow_usd)"></dd>
                    <dt class="col-6 text-muted">Net</dt>
                    <dd class="col-6 fw-semibold" x-text="formatUsd(features?.whales?.window_24h?.net_usd)"></dd>
                </dl>
                <div class="small text-muted mt-3" x-text="whaleStatus()"></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="df-panel p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Liquidation Stress (24h)</h5>
                    <span class="badge text-bg-light">USD</span>
                </div>
                <dl class="row small mb-0">
                    <dt class="col-6 text-muted">Longs</dt>
                    <dd class="col-6 fw-semibold" x-text="formatUsd(features?.liquidations?.sum_24h?.longs)"></dd>
                    <dt class="col-6 text-muted">Shorts</dt>
                    <dd class="col-6 fw-semibold" x-text="formatUsd(features?.liquidations?.sum_24h?.shorts)"></dd>
                    <dt class="col-6 text-muted">Bias</dt>
                    <dd class="col-6 fw-semibold" x-text="liquidationBias()"></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="df-panel p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Signal History</h5>
                <p class="text-muted small mb-0">Log sinyal manual yang pernah dijalankan plus hasil akhirnya.</p>
            </div>
            <button class="btn btn-sm btn-outline-secondary" @click="fetchHistory">
                <i class="fas fa-list me-1"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Rule Signal</th>
                        <th>Score</th>
                        <th>AI Prob.</th>
                        <th>AI Edge</th>
                        <th>Outcome</th>
                        <th>Δ Price</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in historyRows()" :key="row.generated_at">
                        <tr>
                            <td x-text="formatDateTime(row.generated_at)"></td>
                            <td>
                                <span class="badge" :class="row.signal === 'BUY' ? 'text-bg-success' : (row.signal === 'SELL' ? 'text-bg-danger' : 'text-bg-secondary')" x-text="row.signal ?? 'N/A'"></span>
                            </td>
                            <td x-text="row.score ? row.score.toFixed(2) : '--'"></td>
                            <td>
                                <span x-text="row.ai_probability !== null ? formatPercent(row.ai_probability * 100) : '--'"></span>
                                <span class="badge ms-1" :class="row.ai_decision === 'BUY' ? 'text-bg-success' : (row.ai_decision === 'SELL' ? 'text-bg-danger' : 'text-bg-warning')" x-text="row.ai_decision ?? ''"></span>
                            </td>
                            <td x-text="row.ai_confidence !== null ? formatPercent(row.ai_confidence * 100) : '--'"></td>
                            <td>
                                <span class="badge" :class="row.label_direction === 'UP' ? 'text-bg-success' : (row.label_direction === 'DOWN' ? 'text-bg-danger' : 'text-bg-secondary')" x-text="row.label_direction ?? 'PENDING'"></span>
                            </td>
                            <td x-text="row.price_future && row.price_now ? formatPercent(((row.price_future - row.price_now) / row.price_now) * 100) : '--'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('signalAnalytics', () => ({
        isLoading: false,
        isBacktestLoading: false,
        signal: null,
        ai: null,
        features: null,
        history: [],
        lastUpdated: null,
        symbol: 'BTC',
        interval: '1h',
        pollTimer: null,
        pollMs: 5 * 60 * 1000,
        apiUrl: '{{ route('api.signal.analytics') }}',
        backtestApi: '{{ route('api.signal.backtest') }}',
        historyApi: '{{ route('api.signal.history') }}',
        backtest: null,
        symbols: @json(array_values(config('signal.symbols', ['BTC']))),
        init() {
            this.fetchData();
            this.fetchBacktest();
             this.fetchHistory();
            this.pollTimer = setInterval(() => this.fetchData(), this.pollMs);
            window.addEventListener('beforeunload', () => clearInterval(this.pollTimer));
        },
        onSymbolChange() {
            this.fetchData();
            this.fetchBacktest();
            this.fetchHistory();
        },
        async fetchData() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({ symbol: this.symbol, tf: this.interval });
                const response = await fetch(`${this.apiUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(`API error ${response.status}`);
                const data = await response.json();
                this.signal = data.signal;
                this.ai = data.ai;
                this.features = data.features;
                this.lastUpdated = data.generated_at;
            } catch (error) {
                console.error(error);
            } finally {
                this.isLoading = false;
            }
        },
        async fetchBacktest() {
            this.isBacktestLoading = true;
            try {
                const params = new URLSearchParams({ symbol: this.symbol, days: 30 });
                const response = await fetch(`${this.backtestApi}?${params.toString()}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(`Backtest API error ${response.status}`);
                const data = await response.json();
                this.backtest = data.data;
            } catch (error) {
                console.error(error);
            } finally {
                this.isBacktestLoading = false;
            }
        },
        async fetchHistory() {
            try {
                const params = new URLSearchParams({ symbol: this.symbol, limit: 50 });
                const response = await fetch(`${this.historyApi}?${params.toString()}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(`History API error ${response.status}`);
                const data = await response.json();
                this.history = data.history || [];
            } catch (error) {
                console.error(error);
            }
        },
        pairLabel() {
            return this.features?.pair ?? `${this.symbol}USDT`;
        },
        signalBadgeClass() {
            if (!this.signal) return 'text-bg-secondary';
            if (this.signal.signal === 'BUY') return 'text-bg-success';
            if (this.signal.signal === 'SELL') return 'text-bg-danger';
            return 'text-bg-secondary';
        },
        aiBadgeClass() {
            if (!this.ai || !this.ai.decision) return 'text-bg-secondary';
            if (this.ai.decision === 'BUY') return 'text-bg-success';
            if (this.ai.decision === 'SELL') return 'text-bg-danger';
            return 'text-bg-warning';
        },
        qualityBadgeClass() {
            const status = this.signal?.quality?.status;
            if (status === 'HIGH') return 'text-bg-success';
            if (status === 'MEDIUM') return 'text-bg-warning';
            if (status === 'LOW') return 'text-bg-danger';
            return 'text-bg-secondary';
        },
        regimeBadgeClass() {
            const regime = this.signal?.meta?.regime;
            if (regime === 'BULL TREND') return 'text-bg-success';
            if (regime === 'BEAR TREND') return 'text-bg-danger';
            if (regime === 'HIGH VOL CHOP') return 'text-bg-warning';
            return 'text-bg-secondary';
        },
        flagBadgeClass(severity) {
            if (severity === 'danger') return 'text-bg-danger';
            if (severity === 'warning') return 'text-bg-warning';
            if (severity === 'info') return 'text-bg-info';
            return 'text-bg-secondary';
        },
        qualityScoreLabel() {
            const score = this.signal?.quality?.score;
            if (score === null || score === undefined) {
                return '--';
            }
            return this.formatPercent(score * 100);
        },
        get factorCards() {
            return [
                {
                    key: 'funding',
                    title: 'Funding Heat',
                    value: () => this.formatNumber(this.features?.funding?.heat_score),
                    subtitle: () => this.fundingInterpretation(),
                    variant: 'bg-primary-subtle'
                },
                {
                    key: 'oi',
                    title: 'OI Δ24h',
                    value: () => this.formatPercent(this.features?.open_interest?.pct_change_24h),
                    subtitle: () => this.oiInterpretation(),
                    variant: 'bg-warning-subtle'
                },
                {
                    key: 'whale',
                    title: 'Whale Pressure',
                    value: () => this.formatNumber(this.features?.whales?.pressure_score),
                    subtitle: () => this.whaleInterpretation(),
                    variant: 'bg-info-subtle'
                },
                {
                    key: 'etf',
                    title: 'ETF Flow',
                    value: () => this.formatUsd(this.features?.etf?.latest_flow),
                    subtitle: () => this.etfInterpretation(),
                    variant: 'bg-success-subtle'
                },
                {
                    key: 'sentiment',
                    title: 'Fear & Greed',
                    value: () => this.features?.sentiment?.value ?? '--',
                    subtitle: () => this.sentimentInterpretation(),
                    variant: 'bg-light'
                },
                {
                    key: 'micro',
                    title: 'Taker Buy Ratio',
                    value: () => this.formatPercent(this.features?.microstructure?.taker_flow?.buy_ratio ? this.features.microstructure.taker_flow.buy_ratio * 100 : null, 1),
                    subtitle: () => this.microInterpretation(),
                    variant: 'bg-light-subtle'
                },
                {
                    key: 'longshort',
                    title: 'Long/Short Bias',
                    value: () => this.formatPercent(this.features?.long_short?.global?.net_ratio ? this.features.long_short.global.net_ratio * 100 : null, 1),
                    subtitle: () => this.longShortInterpretation(),
                    variant: 'bg-danger-subtle'
                },
                {
                    key: 'regime',
                    title: 'Regime State',
                    value: () => this.signal?.meta?.regime ?? '--',
                    subtitle: () => this.features?.momentum?.regime_reason ?? 'Menunggu data',
                    variant: 'bg-dark text-white'
                },
            ];
        },
        fundingInterpretation() {
            const heat = this.features?.funding?.heat_score;
            if (heat === null || heat === undefined) return 'Data belum tersedia';
            if (heat > 1.5) return 'Overheated (bearish risk)';
            if (heat < -1.5) return 'Discounted (bullish)';
            return 'Netral';
        },
        fundingTrendInterpretation() {
            const trend = this.features?.funding?.trend_pct;
            if (trend === null || trend === undefined) return 'Data belum tersedia';
            if (trend > 15) return 'Funding naik tajam';
            if (trend < -15) return 'Funding turun tajam';
            return 'Stabil';
        },
        oiInterpretation() {
            const pct = this.features?.open_interest?.pct_change_24h;
            if (pct === null || pct === undefined) return 'Data belum tersedia';
            if (pct > 2) return 'Leverage bertambah';
            if (pct < -2) return 'De-leverage berlangsung';
            return 'Stabil';
        },
        whaleInterpretation() {
            if (!this.features?.whales) return 'Data belum tersedia';
            if (this.features.whales.is_stale) return 'Data >7 hari (arsip)';
            const score = this.features.whales.pressure_score;
            const cexRatio = this.features.whales.cex_ratio;
            if (score === null || score === undefined) return 'Tidak ada aktivitas besar';
            if (score > 1.2) return cexRatio > 0.65 ? 'Inflow berat ke CEX' : 'Inflow berat (bearish)';
            if (score < -1.2) return cexRatio !== null && cexRatio < 0.4 ? 'Outflow kuat ke cold storage' : 'Outflow / akumulasi';
            return 'Netral';
        },
        whaleStatus() {
            if (!this.features?.whales) return 'Menunggu data whale tracking';
            if (this.features.whales.is_stale) return 'Belum ada catatan transfer baru >7 hari';
            const inflow = this.features.whales.window_24h?.count_inflow ?? 0;
            const outflow = this.features.whales.window_24h?.count_outflow ?? 0;
            if (inflow + outflow === 0) {
                return 'Tidak ada transfer besar 24 jam terakhir';
            }
            return `${inflow + outflow} transfer besar terdeteksi 24 jam terakhir`;
        },
        etfInterpretation() {
            const latest = this.features?.etf?.latest_flow;
            if (latest === null || latest === undefined) return 'Data belum tersedia';
            if (latest > 0) return 'Institusi net buy';
            if (latest < 0) return 'Institusi net sell';
            return 'Flat';
        },
        etfStreakInterpretation() {
            const streak = this.features?.etf?.streak;
            if (streak === null || streak === undefined) return 'Data belum tersedia';
            if (streak >= 3) return `Inflow ${streak} hari berturut`;
            if (streak <= -3) return `Outflow ${Math.abs(streak)} hari berturut`;
            return 'Tidak ada streak berarti';
        },
        sentimentInterpretation() {
            const value = this.features?.sentiment?.value;
            if (value === null || value === undefined) return 'Data belum tersedia';
            if (value >= 70) return 'Greed';
            if (value <= 30) return 'Fear';
            return 'Neutral';
        },
        microInterpretation() {
            const ratio = this.features?.microstructure?.taker_flow?.buy_ratio;
            if (ratio === null || ratio === undefined) return 'Data belum tersedia';
            if (ratio > 0.55) return 'Buyer aggression';
            if (ratio < 0.45) return 'Seller aggression';
            return 'Seimbang';
        },
        liquidationBias() {
            const longs = this.features?.liquidations?.sum_24h?.longs;
            const shorts = this.features?.liquidations?.sum_24h?.shorts;
            if (longs === undefined || shorts === undefined) return '--';
            if (longs > shorts * 1.3) return 'Long flush';
            if (shorts > longs * 1.3) return 'Short squeeze';
            return 'Balanced';
        },
        volatilityInterpretation() {
            const vol = this.features?.microstructure?.price?.volatility_24h;
            if (vol === null || vol === undefined) return 'Data belum tersedia';
            if (vol > 5) return 'Vol tinggi / regime agresif';
            if (vol < 1.5) return 'Vol rendah / market tenang';
            return 'Vol moderat';
        },
        longShortInterpretation() {
            const bias = this.signal?.meta?.long_short_bias;
            const divergence = this.features?.long_short?.divergence;
            if (!bias && (divergence === null || divergence === undefined)) {
                return 'Data belum tersedia';
            }
            if (bias === 'LONG HEAVY') {
                return 'Retail condong long';
            }
            if (bias === 'SHORT HEAVY') {
                return 'Retail condong short';
            }
            if (divergence !== null) {
                if (divergence > 0.05) return 'Top trader lebih agresif long';
                if (divergence < -0.05) return 'Top trader menekan short';
            }
            return 'Balanced';
        },
        regimeInterpretation() {
            return this.features?.momentum?.regime_reason ?? 'Data belum tersedia';
        },
        formatNumber(value, decimals = 2) {
            if (value === null || value === undefined) return '--';
            return Number(value).toFixed(decimals);
        },
        formatPercent(value, decimals = 2, keepSign = true) {
            if (value === null || value === undefined || isNaN(value)) return '--';
            const num = Number(value);
            const formatted = num.toFixed(decimals);
            if (keepSign) {
                return `${formatted}%`;
            }
            return `${Math.abs(num).toFixed(decimals)}%`;
        },
        formatUsd(value) {
            if (value === null || value === undefined) return '--';
            const abs = Math.abs(value);
            if (abs >= 1_000_000_000) return `$${(value / 1_000_000_000).toFixed(2)}B`;
            if (abs >= 1_000_000) return `$${(value / 1_000_000).toFixed(2)}M`;
            if (abs >= 1_000) return `$${(value / 1_000).toFixed(2)}K`;
            return `$${Number(value).toFixed(0)}`;
        },
        formatTime(value) {
            if (!value) return '--';
            return new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        formatDateTime(value) {
            if (!value) return '--';
            return new Date(value).toLocaleString([], { month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
        },
        timelineRows() {
            if (!this.backtest?.timeline) return [];
            const rows = this.backtest.timeline.slice(-15);
            return rows.reverse();
        },
        historyRows() {
            return this.history || [];
        },
        formatStreak(value) {
            if (value === null || value === undefined) return '--';
            if (value > 0) return `+${value}`;
            return `${value}`;
        },
    }));
});
</script>
<style>
    [x-cloak] { display: none !important; }
    .pulse-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        animation: pulse 2s ease-in-out infinite;
    }
    .pulse-success { background-color: #22c55e; }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        50% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
    }
    .df-panel {
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 16px;
    }
</style>
@endsection
