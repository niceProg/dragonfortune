(() => {
    const page = document.querySelector('.spot-microstructure-page');
    if (!page) return;

    const metaValue = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content')?.trim();
    const base = (metaValue('spot-microstructure-api') || metaValue('api-base-url') || 'https://test.dragonfortune.ai').replace(/\/+$/, '');
    const el = (id) => document.getElementById(id);

    const normalizeSymbol = (value) => {
        if (!value) return 'BTC/USDT';
        const raw = value.toString().trim();
        if (!raw) return 'BTC/USDT';
        if (raw.includes('/')) return raw.toUpperCase();

        const cleaned = raw.replace(/[^A-Za-z]/g, '').toUpperCase();
        if (cleaned.length >= 6) {
            const base = cleaned.slice(0, cleaned.length - 4);
            const quote = cleaned.slice(-4);
            return `${base}/${quote}`;
        }
        return cleaned || 'BTC/USDT';
    };

    const TRADE_SUMMARY_INTERVAL = '5m';
    const TRADE_SUMMARY_INTERVAL_MINUTES = 5;
    const TRADE_SUMMARY_LIMIT = 60;
    const TRADE_FETCH_LIMIT = 200;

    const state = { loading: false, timer: null, trades: [] };

    const refs = {
        symbol: el('spotSymbolSelect'),
        exchange: el('spotExchangeSelect'),
        button: el('spotRefreshButton'),
        lastPrice: el('spotLastPrice'),
        lastPriceTime: el('spotLastPriceTime'),
        tradeBias: el('spotTradeBias'),
        biasStrength: el('spotBiasStrength'),
        cvdDelta: el('spotCvdDelta'),
        cvdPoints: el('spotCvdPoints'),
        vwapSignal: el('spotVwapSignal'),
        vwapPosition: el('spotVwapPosition'),
        vwapCount: el('spotVwapCount'),
        spread: el('spotSpread'),
        depth: el('spotOrderbookDepth'),
        bookTs: el('spotOrderbookTimestamp'),
        pressureRatio: el('spotPressureRatio'),
        pressureImbalance: el('spotPressureImbalance'),
        pressureCount: el('spotBookPressureCount'),
        summaryCount: el('spotTradeSummaryCount'),
        tradesBody: el('spotTradesBody'),
        bidsBody: el('spotBidsBody'),
        asksBody: el('spotAsksBody'),
        volumeBody: el('spotVolumeProfileBody'),
        pressureBody: el('spotBookPressureBody')
    };

    const chartIf = (id, config) => {
        const ctx = el(id);
        return ctx && window.Chart ? new Chart(ctx, config) : null;
    };

    const charts = {
        cvd: chartIf('spotCvdChart', {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'CVD',
                    data: [],
                    borderColor: '#60a5fa',
                    backgroundColor: 'rgba(96, 165, 250, 0.15)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: { responsive: true, animation: false, plugins: { legend: { display: false } } }
        }),
        vwap: chartIf('spotVwapChart', {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Price', data: [], borderColor: '#facc15', tension: 0.25, pointRadius: 0, borderWidth: 2 },
                    { label: 'VWAP', data: [], borderColor: '#3b82f6', tension: 0.25, pointRadius: 0, borderWidth: 2 },
                    { label: 'Upper', data: [], borderColor: '#22c55e', borderDash: [6, 4], pointRadius: 0, borderWidth: 1.5 },
                    { label: 'Lower', data: [], borderColor: '#ef4444', borderDash: [6, 4], pointRadius: 0, borderWidth: 1.5 }
                ]
            },
            options: { responsive: true, animation: false, plugins: { legend: { position: 'bottom' } } }
        }),
        pressure: chartIf('spotBookPressureChart', {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    { label: 'Bid Pressure', data: [], backgroundColor: 'rgba(34, 197, 94, 0.75)', borderRadius: 4 },
                    { label: 'Ask Pressure', data: [], backgroundColor: 'rgba(239, 68, 68, 0.75)', borderRadius: 4 }
                ]
            },
            options: { responsive: true, animation: false, plugins: { legend: { position: 'bottom' } } }
        }),
        summary: chartIf('spotTradeSummaryChart', {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    { label: 'Buy Volume', data: [], backgroundColor: 'rgba(34, 197, 94, 0.75)', borderRadius: 4, stack: 'vol' },
                    { label: 'Sell Volume', data: [], backgroundColor: 'rgba(239, 68, 68, 0.75)', borderRadius: 4, stack: 'vol' }
                ]
            },
            options: {
                responsive: true,
                animation: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { x: { stacked: true }, y: { stacked: true } }
            }
        })
    };

    const toNum = (value) => {
        const number = Number(value);
        return Number.isFinite(number) ? number : NaN;
    };

    const fmtNum = (value, decimals = 2) => {
        const number = toNum(value);
        return Number.isFinite(number)
            ? number.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
            : '-';
    };

    const fmtCur = (value) => {
        const number = toNum(value);
        return Number.isFinite(number)
            ? '$' + number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '-';
    };

    const fmtPct = (value, decimals = 2) => {
        const number = toNum(value);
        return Number.isFinite(number)
            ? `${number >= 0 ? '' : '-'}${Math.abs(number).toFixed(decimals)}%`
            : '-';
    };

    const parseTs = (value) => {
        if (value == null) return null;
        if (value instanceof Date && !Number.isNaN(value.getTime())) return value;
        if (typeof value === 'number') return new Date(value > 1e12 ? value : value * 1000);
        if (typeof value === 'string' && value.trim()) {
            const numeric = Number(value);
            if (!Number.isNaN(numeric)) return new Date(numeric > 1e12 ? numeric : numeric * 1000);
            const parsed = new Date(value);
            if (!Number.isNaN(parsed.getTime())) return parsed;
        }
        return null;
    };

    const fmtDt = (date) => (date ? date.toISOString().replace('T', ' ').slice(0, 19) : '-');
    const fmtHm = (date) => (date ? `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}` : '');

    const fmtAgo = (date) => {
        if (!date) return '-';
        const diffSeconds = Math.floor((Date.now() - date.getTime()) / 1000);
        if (diffSeconds < 0) return fmtDt(date);
        if (diffSeconds < 60) return `${diffSeconds}s ago`;
        const diffMinutes = Math.floor(diffSeconds / 60);
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        const diffHours = Math.floor(diffMinutes / 60);
        if (diffHours < 24) return `${diffHours}h ago`;
        return `${Math.floor(diffHours / 24)}d ago`;
    };

    const setText = (node, text) => { if (node) node.textContent = text; };
    const setNotice = (id, show) => {
        const node = el(id);
        if (!node) return;
        node.classList.toggle('d-none', !show);
    };

    const buildUrl = (path, params = {}) => {
        const url = new URL(path.startsWith('/') ? path : `/${path}`, `${base}/`);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });
        return url.toString();
    };

    const request = async (path, params = {}) => {
        try {
            const response = await fetch(buildUrl(path, {
                ...params,
                symbol: Object.prototype.hasOwnProperty.call(params, 'symbol')
                    ? normalizeSymbol(params.symbol)
                    : undefined
            }), {
                headers: { Accept: 'application/json' },
                cache: 'no-cache'
            });
            if (!response.ok) return null;
            return await response.json();
        } catch {
            return null;
        }
    };

    const setLoading = (on) => {
        state.loading = on;
        if (!refs.button) return;
        if (on) {
            refs.button.disabled = true;
            refs.button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Loading';
        } else {
            refs.button.disabled = false;
            refs.button.innerHTML = 'Refresh';
        }
    };

    const updateTrades = (payload) => {
        const rows = Array.isArray(payload?.data) ? payload.data : [];
        state.trades = rows;
        if (!rows.length) {
            refs.tradesBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No trades available.</td></tr>';
        } else {
            refs.tradesBody.innerHTML = rows.slice(0, 25).map((trade) => {
                const side = (trade.side || '').toString().toLowerCase();
                const badge = side === 'buy' ? 'bg-success' : 'bg-danger';
                const timestamp = fmtDt(parseTs(trade.timestamp));
                return `<tr><td>${timestamp}</td><td><span class="badge ${badge}">${side ? side.toUpperCase() : 'N/A'}</span></td><td>${fmtCur(trade.price)}</td><td>${fmtNum(trade.quantity, 4)}</td></tr>`;
            }).join('');
        }

        const latest = rows[0];
        setText(refs.lastPrice, latest ? fmtCur(latest.price) : '-');
        setText(refs.lastPriceTime, latest ? fmtAgo(parseTs(latest.timestamp)) : '-');
    };

    const buildFallbackSummary = (trades, intervalMinutes, maxBuckets) => {
        if (!Array.isArray(trades) || trades.length === 0) return [];
        const bucketMs = Math.max(intervalMinutes, 1) * 60 * 1000;
        const bucketMap = new Map();

        trades.forEach((trade) => {
            const ts = parseTs(trade.timestamp);
            const price = toNum(trade.price);
            const quantity = toNum(trade.quantity);
            if (!ts || !Number.isFinite(price) || !Number.isFinite(quantity)) return;

            const key = Math.floor(ts.getTime() / bucketMs) * bucketMs;
            const side = (trade.side || '').toString().toLowerCase();
            const bucket = bucketMap.get(key) || {
                timestamp: new Date(key),
                buy_volume_quote: 0,
                sell_volume_quote: 0,
                trades_count: 0
            };

            const notional = price * quantity;
            if (side === 'buy') bucket.buy_volume_quote += notional;
            else if (side === 'sell') bucket.sell_volume_quote += notional;
            else {
                bucket.buy_volume_quote += notional / 2;
                bucket.sell_volume_quote += notional / 2;
            }
            bucket.trades_count += 1;

            bucketMap.set(key, bucket);
        });

        const buckets = Array.from(bucketMap.values())
            .sort((a, b) => a.timestamp - b.timestamp)
            .slice(-maxBuckets);

        return buckets.map((bucket) => ({
            ...bucket,
            total_volume: bucket.buy_volume_quote + bucket.sell_volume_quote
        }));
    };

    const updateTradeSummary = (payload) => {
        let buckets = Array.isArray(payload?.data) ? payload.data : [];
        if (buckets.length <= 1) {
            const fallback = buildFallbackSummary(state.trades, TRADE_SUMMARY_INTERVAL_MINUTES, TRADE_SUMMARY_LIMIT);
            if (fallback.length > buckets.length) {
                buckets = fallback;
            }
        }

        const bucketCount = Math.max(payload?.count ?? 0, buckets.length);
        setText(refs.summaryCount, `${bucketCount} bucket${bucketCount === 1 ? '' : 's'}`);

        const extractVolume = (item, keys) => {
            for (const key of keys) {
                if (item && item[key] !== undefined && item[key] !== null) {
                    return toNum(item[key]);
                }
            }
            return NaN;
        };

        const labels = buckets.map((item) => {
            const rawTs = item.timestamp || item.bucket_time;
            const ts = parseTs(rawTs);
            return ts ? fmtHm(ts) : (rawTs || '-');
        });

        const buyVolumes = buckets.map((item) =>
            extractVolume(item, ['buy_volume', 'buy_volume_quote', 'buy_volume_base', 'buy_volume_value'])
        );
        const sellVolumes = buckets.map((item) =>
            extractVolume(item, ['sell_volume', 'sell_volume_quote', 'sell_volume_base', 'sell_volume_value'])
        );

        if (charts.summary) {
            charts.summary.data.labels = labels;
            charts.summary.data.datasets[0].data = buyVolumes;
            charts.summary.data.datasets[1].data = sellVolumes;
            charts.summary.update('none');
        }

        const hasData = buckets.length > 0 && (buyVolumes.some(Number.isFinite) || sellVolumes.some(Number.isFinite));
        setNotice('spotTradeSummaryNotice', !hasData);
    };

    const updateCvd = (payload) => {
        const series = Array.isArray(payload?.data) ? payload.data : [];
        if (!charts.cvd) return;

        const labels = series.map((item) => fmtHm(parseTs(item.timestamp)));
        const values = series.map((item) => toNum(item.cumulative_cvd ?? item.cvd));

        charts.cvd.data.labels = labels;
        charts.cvd.data.datasets[0].data = values;
        charts.cvd.update('none');

        const hasData = series.length > 0;
        setText(refs.cvdPoints, `${labels.length} pts`);
        setNotice('spotCvdNotice', !hasData);
        setNotice('spotCvdCardNotice', !hasData);

        if (!hasData) {
            setText(refs.cvdDelta, '-');
            return;
        }

        const first = values[values.length - 1];
        const latest = values[0];
        const delta = Number.isFinite(first) && Number.isFinite(latest) ? latest - first : 0;
        setText(refs.cvdDelta, fmtNum(delta, 2));
        if (refs.cvdDelta) {
            refs.cvdDelta.classList.remove('text-success', 'text-danger');
            if (delta > 0) refs.cvdDelta.classList.add('text-success');
            else if (delta < 0) refs.cvdDelta.classList.add('text-danger');
        }
    };

    const updateBias = (payload) => {
        const bias = (payload?.bias || 'neutral').toString();
        const strength = payload?.strength;

        setText(refs.tradeBias, bias.charAt(0).toUpperCase() + bias.slice(1));

        if (refs.biasStrength) {
            refs.biasStrength.className = 'badge';
            const value = toNum(strength);
            if (Number.isFinite(value)) {
                refs.biasStrength.textContent = fmtNum(value, 2);
                refs.biasStrength.classList.add(
                    bias === 'buy' ? 'text-bg-success' : bias === 'sell' ? 'text-bg-danger' : 'text-bg-secondary'
                );
            } else {
                refs.biasStrength.textContent = 'No data';
                refs.biasStrength.classList.add('text-bg-secondary');
            }
        }

        const hasData = Boolean(payload) && ((typeof payload.n === 'number' && payload.n > 0) || Number.isFinite(toNum(strength)));
        setNotice('spotBiasCardNotice', !hasData);
    };

    const updateOrderbook = (payload) => {
        const bids = Array.isArray(payload?.bids) ? payload.bids : [];
        const asks = Array.isArray(payload?.asks) ? payload.asks : [];

        refs.bidsBody.innerHTML = bids.length
            ? bids.slice(0, 10).map((level) => `<tr><td class="text-success">${fmtCur(level.price)}</td><td>${fmtNum(level.quantity, 4)}</td></tr>`).join('')
            : '<tr><td colspan="2" class="text-center text-muted py-3">No bids available.</td></tr>';

        refs.asksBody.innerHTML = asks.length
            ? asks.slice(0, 10).map((level) => `<tr><td class="text-danger">${fmtCur(level.price)}</td><td>${fmtNum(level.quantity, 4)}</td></tr>`).join('')
            : '<tr><td colspan="2" class="text-center text-muted py-3">No asks available.</td></tr>';

        setText(refs.spread, fmtPct(payload?.spread_pct, 3));
        setText(refs.depth, bids.length ? `${bids.length} / ${asks.length}` : '-');
        setText(refs.bookTs, fmtDt(parseTs(payload?.timestamp)));

        const hasData = bids.length > 0 || asks.length > 0;
        setNotice('spotOrderbookCardNotice', !hasData);
    };

    const updatePressure = (payload) => {
        const rows = Array.isArray(payload?.data) ? payload.data : [];
        setText(refs.pressureCount, `${rows.length} pts`);

        if (charts.pressure) {
            charts.pressure.data.labels = rows.map((item) => fmtHm(parseTs(item.timestamp)));
            charts.pressure.data.datasets[0].data = rows.map((item) => toNum(item.bid_pressure));
            charts.pressure.data.datasets[1].data = rows.map((item) => toNum(item.ask_pressure));
            charts.pressure.update('none');
        }

        const latest = rows[rows.length - 1];
        const ratio = toNum(latest?.pressure_ratio);
        setText(refs.pressureRatio, Number.isFinite(ratio) ? fmtNum(ratio, 2) : '-');
        setText(refs.pressureImbalance, latest?.pressure_direction || latest?.imbalance || '-');

        refs.pressureBody.innerHTML = rows.length
            ? rows.slice(-15).reverse().map((item) => `
                <tr>
                    <td>${fmtDt(parseTs(item.timestamp))}</td>
                    <td class="text-success">${fmtNum(item.bid_pressure, 2)}</td>
                    <td class="text-danger">${fmtNum(item.ask_pressure, 2)}</td>
                    <td>${fmtNum(item.pressure_ratio, 2)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="4" class="text-center text-muted py-4">No book pressure data.</td></tr>';

        const hasData = rows.length > 0;
        setNotice('spotBookPressureNotice', !hasData);
        setNotice('spotPressureCardNotice', !hasData);
    };

    const updateVolumeProfile = (payload) => {
        if (!refs.volumeBody) return;

        if (!payload || Object.keys(payload).length === 0) {
            refs.volumeBody.innerHTML = '<tr><td class="text-center text-muted py-4">No volume profile data.</td></tr>';
            return;
        }

        const rows = [
            ['Period', `${fmtDt(parseTs(payload.period_start))} -> ${fmtDt(parseTs(payload.period_end))}`],
            ['Total Trades', fmtNum(payload.total_trades, 0)],
            ['Buy Trades', fmtNum(payload.total_buy_trades, 0)],
            ['Sell Trades', fmtNum(payload.total_sell_trades, 0)],
            ['Buy/Sell Ratio', fmtNum(payload.buy_sell_ratio, 2)],
            ['Avg Trade Size', fmtNum(payload.avg_trade_size, 3)],
            ['Max Trade Size', fmtNum(payload.max_trade_size, 3)]
        ].filter(([, value]) => value && value !== 'NaN' && value !== '- -> -');

        refs.volumeBody.innerHTML = rows.length
            ? rows.map(([label, value]) => `<tr><th class="text-secondary fw-normal" style="width:45%;">${label}</th><td>${value}</td></tr>`).join('')
            : '<tr><td class="text-center text-muted py-4">No volume profile data.</td></tr>';
    };

    const updateVwap = (series, latestPayload) => {
        const rows = Array.isArray(series?.data) ? series.data : [];
        setText(refs.vwapCount, `${rows.length} pts`);

        if (charts.vwap) {
            charts.vwap.data.labels = rows.map((item) => fmtHm(parseTs(item.timestamp)));
            charts.vwap.data.datasets[0].data = rows.map((item) => toNum(item.price ?? item.current_price));
            charts.vwap.data.datasets[1].data = rows.map((item) => toNum(item.vwap));
            charts.vwap.data.datasets[2].data = rows.map((item) => toNum(item.upper_band));
            charts.vwap.data.datasets[3].data = rows.map((item) => toNum(item.lower_band));
            charts.vwap.update('none');
        }

        const snapshot = latestPayload || rows[rows.length - 1];
        const signalRaw = snapshot?.signal || snapshot?.trading_signal || null;
        const signal = signalRaw ? signalRaw.toString().toLowerCase() : null;
        const position = snapshot?.price_position ? snapshot.price_position.toString() : null;

        setText(refs.vwapSignal, signal ? signal.charAt(0).toUpperCase() + signal.slice(1) : '-');
        setText(refs.vwapPosition, position ? position.charAt(0).toUpperCase() + position.slice(1) : '-');

        if (refs.vwapSignal) {
            refs.vwapSignal.classList.remove('text-success', 'text-danger', 'text-warning');
            if (signal === 'overbought') refs.vwapSignal.classList.add('text-danger');
            else if (signal === 'oversold') refs.vwapSignal.classList.add('text-success');
            else if (signal) refs.vwapSignal.classList.add('text-warning');
        }

        const hasSeries = rows.length > 0;
        const hasSnapshot = Boolean(snapshot);
        setNotice('spotVwapNotice', !hasSeries);
        setNotice('spotVwapCardNotice', !(hasSeries || hasSnapshot));
    };

    const fetchAll = () => Promise.all([
        request('/api/spot-microstructure/trades', { symbol: refs.symbol?.value || 'BTC/USDT', exchange: refs.exchange?.value || 'binance', limit: TRADE_FETCH_LIMIT }),
        request('/api/spot-microstructure/trades/summary', { symbol: refs.symbol?.value || 'BTC/USDT', interval: TRADE_SUMMARY_INTERVAL, limit: TRADE_SUMMARY_LIMIT }),
        request('/api/spot-microstructure/cvd', { symbol: refs.symbol?.value || 'BTC/USDT', exchange: refs.exchange?.value || 'binance', limit: 120 }),
        request('/api/spot-microstructure/trade-bias', { symbol: refs.symbol?.value || 'BTC/USDT', limit: 1000 }),
        request('/api/spot-microstructure/orderbook/snapshot', { symbol: refs.symbol?.value || 'BTC/USDT', exchange: refs.exchange?.value || 'binance', depth: 15 }),
        request('/api/spot-microstructure/book-pressure', { symbol: refs.symbol?.value || 'BTC/USDT', exchange: refs.exchange?.value || 'binance', limit: 60 }),
        request('/api/spot-microstructure/volume-profile', { symbol: refs.symbol?.value || 'BTC/USDT', limit: 720 }),
        request('/api/spot-microstructure/vwap', { symbol: refs.symbol?.value || 'BTC/USDT', exchange: refs.exchange?.value || 'binance', timeframe: '5min', limit: 180 }),
        request('/api/spot-microstructure/vwap/latest', { symbol: refs.symbol?.value || 'BTC/USDT', exchange: refs.exchange?.value || 'binance', timeframe: '5min' })
    ]);

    const refresh = async (force = false) => {
        if (state.loading && !force) return;
        setLoading(true);
        try {
            const [trades, summary, cvd, bias, orderbook, pressure, profile, vwapSeries, vwapLatest] = await fetchAll();
            updateTrades(trades);
            updateTradeSummary(summary);
            updateCvd(cvd);
            updateBias(bias);
            updateOrderbook(orderbook);
            updatePressure(pressure);
            updateVolumeProfile(profile);
            updateVwap(vwapSeries, vwapLatest);
        } finally {
            setLoading(false);
        }
    };

    refs.symbol?.addEventListener('change', () => refresh(true));
    refs.exchange?.addEventListener('change', () => refresh(true));
    refs.button?.addEventListener('click', () => refresh(true));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(false); });

    refresh(true);
    state.timer = setInterval(() => refresh(false), 20000);
    window.addEventListener('beforeunload', () => clearInterval(state.timer));
})();
