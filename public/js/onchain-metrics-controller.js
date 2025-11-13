/**
 * On-Chain Metrics Dashboard Controller (Alpine.js)
 * Consumes the Flask on-chain API endpoints and renders valuation,
 * flow, network, and market data in a single consolidated view.
 */
function onchainMetricsController() {
    return {
        apiBase: window.location.origin,

        selectedLimit: 60,
        loading: false,
        error: null,
        lastUpdated: null,

        summaryCards: [],
        insights: [],
        exchangeSummary: [],

        stats: {
            mvrv: null,
            mvrvDelta: null,
            sopr: null,
            soprDelta: null,
            aSopr: null,
            lthSopr: null,
            sthSopr: null,
            netflow: null,
            netflowDelta: null,
            netflowDate: null,
            dominantExchange: null,
            dominantExchangeValue: null,
            transactions: null,
            transactionsDelta: null,
            transactionsPct: null,
            transactionsMean: null,
            transactionsDeltaText: "—",
            transactionsDeltaClass: "text-muted",
            priceClose: null,
            priceVolume: null,
            priceDelta: null,
            pricePct: null,
            priceDeltaText: "—",
            priceDeltaClass: "text-muted",
        },

        loadingStates: {
            mvrv: false,
            sopr: false,
            exchangeFlows: false,
            transactions: false,
            price: false,
        },

        charts: {
            mvrv: null,
            sopr: null,
            exchangeFlows: null,
            transactions: null,
            price: null,
        },

        init() {
            this.refreshAll();
        },

        async refreshAll() {
            this.loading = true;
            this.error = null;
            this.summaryCards = [];
            this.insights = [];

            this.loadingStates = {
                mvrv: true,
                sopr: true,
                exchangeFlows: true,
                transactions: true,
                price: true,
            };

            const loaders = [
                this.loadValuationMetrics(),
                this.loadExchangeFlows(),
                this.loadTransactions(),
                this.loadPriceSeries(),
            ];

            const results = await Promise.allSettled(loaders);

            const rejected = results.filter((result) => result.status === 'rejected');
            if (rejected.length) {
                console.warn('On-chain refresh completed with failures', rejected);
                if (!this.error) {
                    this.error = 'One or more data sources failed to load. Try refreshing.';
                }
            }

            this.buildSummaryCards();
            this.buildInsights();
            this.lastUpdated = new Date().toLocaleString();
            this.loading = false;
        },

        async loadValuationMetrics() {
            this.loadingStates.mvrv = true;
            this.loadingStates.sopr = true;

            try {
                const [mvrvResponse, soprResponse] = await Promise.all([
                    this.fetchEndpoint("/api/onchain/metrics", {
                        asset: "btc",
                        metric_type: "mvrv",
                        limit: this.selectedLimit,
                    }),
                    this.fetchEndpoint("/api/onchain/metrics", {
                        asset: "btc",
                        metric_type: "sopr",
                        limit: this.selectedLimit,
                    }),
                ]);

                const mvrvSeries = this.normaliseMetricSeries(mvrvResponse?.data, "mvrv");
                const soprSeries = this.normaliseSoprSeries(soprResponse?.data);

                this.stats.mvrv = mvrvSeries.latestValue;
                this.stats.mvrvDelta = this.computeDelta(
                    mvrvSeries.latestValue,
                    mvrvSeries.previousValue
                );

                this.stats.sopr = soprSeries.latest?.sopr ?? null;
                this.stats.soprDelta = this.computeDelta(
                    soprSeries.latest?.sopr,
                    soprSeries.previous?.sopr
                );
                this.stats.aSopr = soprSeries.latest?.a_sopr ?? null;
                this.stats.lthSopr = soprSeries.latest?.lth_sopr ?? null;
                this.stats.sthSopr = soprSeries.latest?.sth_sopr ?? null;

                this.renderMvrvChart(mvrvSeries.chartData);
                this.renderSoprChart(soprSeries.chartData);
            } catch (error) {
                console.error("Failed loading valuation metrics", error);
                this.setError("Failed to load valuation metrics.", error);
                this.renderMvrvChart({ labels: [], data: [] });
                this.renderSoprChart({ labels: [], datasets: [] });
            } finally {
                this.loadingStates.mvrv = false;
                this.loadingStates.sopr = false;
            }
        },

        async loadExchangeFlows() {
            this.loadingStates.exchangeFlows = true;

            try {
                const response = await this.fetchEndpoint("/api/onchain/exchange-flows", {
                    asset: "btc",
                    metric_type: "netflow",
                    limit: this.selectedLimit,
                });

                const transformed = this.normaliseExchangeFlows(response?.data);
                this.stats.netflow = transformed.totalNetflow;
                this.stats.netflowDelta = this.computeDelta(
                    transformed.latestAggregate?.value,
                    transformed.previousAggregate?.value
                );
                this.stats.netflowDate = transformed.latestAggregate?.date ?? null;
                this.stats.dominantExchange = transformed.dominant?.exchange ?? null;
                this.stats.dominantExchangeValue = transformed.dominant?.total ?? null;

                this.exchangeSummary = transformed.summary;
                this.renderExchangeFlowChart(transformed.labels, transformed.datasets);
            } catch (error) {
                console.error("Failed loading exchange flows", error);
                this.setError("Failed to load exchange flow data.", error);
                this.exchangeSummary = [];
                this.renderExchangeFlowChart([], []);
            } finally {
                this.loadingStates.exchangeFlows = false;
            }
        },

        async loadTransactions() {
            this.loadingStates.transactions = true;

            try {
                const response = await this.fetchEndpoint("/api/onchain/network-activity", {
                    asset: "btc",
                    metric_type: "transactions-count",
                    limit: this.selectedLimit,
                });

                const series = this.normaliseTransactions(response?.data);
                this.stats.transactions = series.latest?.transactions_count_total ?? null;
                this.stats.transactionsMean = series.latest?.transactions_count_mean ?? null;
                this.stats.transactionsDelta = this.computeDelta(
                    series.latest?.transactions_count_total,
                    series.previous?.transactions_count_total
                );
                this.stats.transactionsPct = this.computePercent(
                    series.latest?.transactions_count_total,
                    series.previous?.transactions_count_total
                );

                this.renderTransactionsChart(series.chartData);
            } catch (error) {
                console.error("Failed loading network activity", error);
                this.setError("Failed to load network activity data.", error);
                this.renderTransactionsChart({ labels: [], data: [] });
            } finally {
                this.loadingStates.transactions = false;
            }
        },

        async loadPriceSeries() {
            this.loadingStates.price = true;

            try {
                const response = await this.fetchEndpoint("/api/onchain/market-data", {
                    asset: "btc",
                    metric_type: "price-ohlcv",
                    limit: this.selectedLimit,
                });

                const series = this.normalisePriceSeries(response?.data);
                this.stats.priceClose = series.latest?.close ?? null;
                this.stats.priceVolume = series.latest?.volume ?? null;
                this.stats.priceDelta = this.computeDelta(
                    series.latest?.close,
                    series.previous?.close
                );
                this.stats.pricePct = this.computePercent(
                    series.latest?.close,
                    series.previous?.close
                );

                this.renderPriceChart(series.chartData);
            } catch (error) {
                console.error("Failed loading price series", error);
                this.setError("Failed to load price data.", error);
                this.renderPriceChart({ labels: [], data: [] });
            } finally {
                this.loadingStates.price = false;
            }
        },

        async fetchEndpoint(endpoint, params = {}) {
            const url = this.buildUrl(endpoint, params);
            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (!response.ok) {
                    const message = `Request failed: ${response.status} ${response.statusText}`;
                    throw new Error(message);
                }

                return await response.json();
            } catch (error) {
                console.error('Onchain fetch error', url.toString(), error);
                throw error;
            }
        },

        buildUrl(endpoint, params) {
            const base = this.apiBase || window.location.origin;
            const url = new URL(endpoint, base.endsWith('/') ? base : `${base}/`);
            Object.entries(params).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== "") {
                    url.searchParams.set(key, value);
                }
            });
            return url;
        },

        buildSummaryCards() {
            const cards = [];

            const mvrvDeltaText = this.formatDirectionalDelta(this.stats.mvrvDelta, {
                digits: 3,
                invert: true,
            });
            const mvrvDeltaClass = this.deltaClass(this.stats.mvrvDelta, { invert: true });

            cards.push({
                title: "MVRV Ratio",
                icon: "📊",
                value: this.formatNumber(this.stats.mvrv, 3),
                delta: mvrvDeltaText,
                deltaClass: mvrvDeltaClass,
                deltaNote: "vs previous day",
                footer: this.stats.mvrv
                    ? `${this.getMvrvLabel(this.stats.mvrv)} · Fair value band 0.7 – 3.7`
                    : "Waiting for valuation data",
                background: "background: linear-gradient(135deg, rgba(37,99,235,0.15), rgba(37,99,235,0.05));",
            });

            const soprDeltaText = this.formatDirectionalDelta(this.stats.soprDelta, {
                digits: 3,
            });
            const soprDeltaClass = this.deltaClass(this.stats.soprDelta);

            cards.push({
                title: "SOPR",
                icon: "💰",
                value: this.formatNumber(this.stats.sopr, 3),
                delta: soprDeltaText,
                deltaClass: soprDeltaClass,
                deltaNote: "vs previous day",
                footer:
                    this.stats.aSopr || this.stats.lthSopr || this.stats.sthSopr
                        ? `aSOPR ${this.formatNumber(this.stats.aSopr, 3)} · LTH ${this.formatNumber(
                              this.stats.lthSopr,
                              3
                          )} · STH ${this.formatNumber(this.stats.sthSopr, 3)}`
                        : "Holder profitability mix pending",
                background: "background: linear-gradient(135deg, rgba(16,185,129,0.16), rgba(16,185,129,0.04));",
            });

            const netflowDeltaText = this.formatDirectionalDelta(this.stats.netflowDelta, {
                digits: 0,
                suffix: " BTC",
                invert: true,
            });
            const netflowDeltaClass = this.deltaClass(this.stats.netflowDelta, { invert: true });

            cards.push({
                title: "Exchange Netflow",
                icon: "🏦",
                value: this.stats.netflow !== null ? `${this.formatNumber(this.stats.netflow, 0)} BTC` : "--",
                delta: netflowDeltaText,
                deltaClass: netflowDeltaClass,
                deltaNote: "vs previous day",
                footer: this.stats.dominantExchange
                    ? `${this.stats.dominantExchange} ${
                          (this.stats.dominantExchangeValue ?? 0) < 0 ? "outflow" : "inflow"
                      } ${this.formatNumber(Math.abs(this.stats.dominantExchangeValue ?? 0), 0)} BTC`
                    : "Awaiting exchange breakdown",
                background: "background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(59,130,246,0.04));",
            });

            const transactionsDeltaText = this.formatDirectionalDelta(this.stats.transactionsDelta, {
                digits: 0,
                suffix: " tx",
            });
            const transactionsDeltaClass = this.deltaClass(this.stats.transactionsDelta);

            cards.push({
                title: "Network Transactions",
                icon: "🔗",
                value: this.formatNumber(this.stats.transactions, 0),
                delta: transactionsDeltaText,
                deltaClass: transactionsDeltaClass,
                deltaNote: this.stats.transactionsPct !== null
                    ? `${this.formatPercent(this.stats.transactionsPct)} vs previous`
                    : "vs previous day",
                footer: this.stats.transactionsMean
                    ? `Mean ${this.formatNumber(this.stats.transactionsMean, 0)} tx per block`
                    : "Throughput loading",
                background: "background: linear-gradient(135deg, rgba(14,165,233,0.16), rgba(14,165,233,0.04));",
            });

            const priceDeltaBase = this.formatDirectionalDelta(this.stats.priceDelta, {
                digits: 0,
                suffix: " USD",
            });
            const pricePctText =
                this.stats.pricePct !== null && !Number.isNaN(this.stats.pricePct)
                    ? ` (${this.formatPercent(this.stats.pricePct)})`
                    : "";
            const priceDeltaText =
                priceDeltaBase === "—" && pricePctText
                    ? pricePctText.trim()
                    : `${priceDeltaBase}${pricePctText}`.trim();
            const priceDeltaClass = this.deltaClass(this.stats.priceDelta);

            cards.push({
                title: "BTC Price",
                icon: "💹",
                value: this.formatCurrency(this.stats.priceClose),
                delta: priceDeltaText,
                deltaClass: priceDeltaClass,
                deltaNote: "vs previous close",
                footer: this.stats.priceVolume !== null
                    ? `Volume ${this.formatNumber(this.stats.priceVolume, 0)}`
                    : "Awaiting volume data",
                background: "background: linear-gradient(135deg, rgba(34,197,94,0.16), rgba(34,197,94,0.04));",
            });

            this.summaryCards = cards;

            this.stats.transactionsDeltaText = transactionsDeltaText;
            this.stats.transactionsDeltaClass = transactionsDeltaClass;
            this.stats.priceDeltaText = priceDeltaText || "—";
            this.stats.priceDeltaClass = priceDeltaClass;
        },

        buildInsights() {
            const insights = [];

            if (this.stats.mvrv !== null) {
                const label = this.getMvrvLabel(this.stats.mvrv);
                insights.push({
                    icon: this.stats.mvrv >= 3.7 ? "⚠️" : this.stats.mvrv < 1 ? "✅" : "📊",
                    title: `Valuation ${label}`,
                    body: `MVRV sits at ${this.formatNumber(this.stats.mvrv, 3)}, suggesting ${
                        label === "Distribution" ? "heightened distribution risk" : label === "Accumulation" ? "favorable accumulation conditions" : "neutral pricing"
                    }.`,
                });
            }

            if (this.stats.sopr !== null) {
                insights.push({
                    icon: this.stats.sopr > 1 ? "💵" : "🧊",
                    title: "Holder Profitability",
                    body: `SOPR prints ${this.formatNumber(this.stats.sopr, 3)} with LTH ${this.formatNumber(
                        this.stats.lthSopr,
                        3
                    )} and STH ${this.formatNumber(this.stats.sthSopr, 3)}, indicating ${
                        this.stats.sopr > 1 ? "realized profits dominating" : "loss-taking or neutrality"
                    }.`,
                });
            }

            if (this.stats.netflow !== null) {
                insights.push({
                    icon: this.stats.netflow < 0 ? "⬇️" : "⬆️",
                    title: "Exchange Flow Pressure",
                    body: this.stats.netflow < 0
                        ? `${this.formatNumber(Math.abs(this.stats.netflow), 0)} BTC left exchanges in the latest print, supporting accumulation narratives.`
                        : `${this.formatNumber(this.stats.netflow, 0)} BTC entered exchanges, pointing to potential distribution.`,
                });
            }

            if (this.stats.transactions !== null) {
                    const direction = this.stats.transactionsDelta && this.stats.transactionsDelta > 0 ? "rising" : this.stats.transactionsDelta && this.stats.transactionsDelta < 0 ? "cooling" : "stable";
                insights.push({
                    icon: "🔗",
                    title: "Network Throughput",
                    body: `Daily transactions ${direction} at ${this.formatNumber(
                        this.stats.transactions,
                        0
                    )} tx, a ${
                        this.stats.transactionsPct !== null
                            ? this.formatPercent(this.stats.transactionsPct)
                            : "neutral"
                    } move versus yesterday.`,
                });
            }

            if (this.stats.priceClose !== null) {
                insights.push({
                    icon: this.stats.priceDelta && this.stats.priceDelta > 0 ? "🚀" : this.stats.priceDelta && this.stats.priceDelta < 0 ? "📉" : "〽️",
                    title: "Market Structure",
                    body: `BTC closed at ${this.formatCurrency(this.stats.priceClose)} with a ${
                        this.stats.pricePct !== null ? this.formatPercent(this.stats.pricePct) : "flat"
                    } move day-over-day.`,
                });
            }

            this.insights = insights.slice(0, 4);
        },

        normaliseMetricSeries(data = [], valueKey) {
            const series = Array.isArray(data)
                ? data.map((row) => ({
                      date: row.date,
                      value: Number(row.values?.[valueKey] ?? row[valueKey] ?? 0),
                  }))
                : [];

            series.sort((a, b) => new Date(a.date) - new Date(b.date));

            const latest = series.length ? series[series.length - 1] : null;
            const previous = series.length > 1 ? series[series.length - 2] : null;

            return {
                latestValue: latest?.value ?? null,
                previousValue: previous?.value ?? null,
                chartData: {
                    labels: series.map((item) => item.date),
                    data: series.map((item) => item.value),
                },
            };
        },

        normaliseSoprSeries(data = []) {
            const series = Array.isArray(data)
                ? data.map((row) => ({
                      date: row.date,
                      sopr: Number(row.values?.sopr ?? row.sopr ?? 0),
                      a_sopr: Number(row.values?.a_sopr ?? row.a_sopr ?? 0),
                      lth_sopr: Number(row.values?.lth_sopr ?? row.lth_sopr ?? 0),
                      sth_sopr: Number(row.values?.sth_sopr ?? row.sth_sopr ?? 0),
                  }))
                : [];

            series.sort((a, b) => new Date(a.date) - new Date(b.date));

            const latest = series.length ? series[series.length - 1] : null;
            const previous = series.length > 1 ? series[series.length - 2] : null;

            const chartData = {
                labels: series.map((item) => item.date),
                datasets: [
                    {
                        label: "SOPR",
                        data: series.map((item) => item.sopr),
                        borderColor: "#2563eb",
                        backgroundColor: "rgba(37, 99, 235, 0.10)",
                        tension: 0.25,
                        borderWidth: 2,
                        fill: true,
                    },
                    {
                        label: "aSOPR",
                        data: series.map((item) => item.a_sopr),
                        borderColor: "#10b981",
                        backgroundColor: "rgba(16, 185, 129, 0.08)",
                        tension: 0.2,
                        borderWidth: 1.5,
                        fill: false,
                    },
                    {
                        label: "LTH SOPR",
                        data: series.map((item) => item.lth_sopr),
                        borderColor: "#f97316",
                        backgroundColor: "rgba(249, 115, 22, 0.08)",
                        tension: 0.2,
                        borderWidth: 1.5,
                        fill: false,
                    },
                    {
                        label: "STH SOPR",
                        data: series.map((item) => item.sth_sopr),
                        borderColor: "#ef4444",
                        backgroundColor: "rgba(239, 68, 68, 0.08)",
                        tension: 0.2,
                        borderWidth: 1.5,
                        fill: false,
                    },
                ],
            };

            return { latest, previous, chartData };
        },

        normaliseExchangeFlows(data = []) {
            const groupedByDate = {};
            const exchanges = new Set();

            (data || []).forEach((row) => {
                const date = row.date;
                const exchange = row.exchange || "Unknown";
                const value = Number(row.values?.netflow_total ?? row.netflow_total ?? row.primary_value ?? 0);
                if (!groupedByDate[date]) {
                    groupedByDate[date] = {};
                }
                groupedByDate[date][exchange] = value;
                exchanges.add(exchange);
            });

            const labels = Object.keys(groupedByDate).sort((a, b) => new Date(a) - new Date(b));
            const exchangeList = Array.from(exchanges).sort();

            const aggregateSeries = labels.map((date) => ({
                date,
                value: exchangeList.reduce(
                    (sum, exchange) => sum + Number(groupedByDate[date]?.[exchange] ?? 0),
                    0
                ),
            }));

            const datasets = exchangeList.map((exchange, index) => ({
                label: exchange,
                data: labels.map((date) => groupedByDate[date]?.[exchange] ?? 0),
                backgroundColor: this.getPaletteColor(index, 0.75),
                borderColor: this.getPaletteColor(index, 1),
                borderWidth: 1,
            }));

            const latestAggregate = aggregateSeries.length ? aggregateSeries[aggregateSeries.length - 1] : null;
            const previousAggregate = aggregateSeries.length > 1 ? aggregateSeries[aggregateSeries.length - 2] : null;

            const latestDate = latestAggregate?.date ?? null;
            const latestSnapshot = latestDate ? groupedByDate[latestDate] || {} : {};

            const summary = exchangeList
                .map((exchange) => ({
                    exchange,
                    total: Number(latestSnapshot[exchange] ?? 0),
                }))
                .sort((a, b) => Math.abs(b.total) - Math.abs(a.total));

            const dominant = summary.length ? summary[0] : null;

            const totalNetflow = latestAggregate?.value ?? null;

            return {
                labels,
                datasets,
                summary,
                dominant,
                aggregateSeries,
                latestAggregate,
                previousAggregate,
                totalNetflow,
            };
        },

        normaliseTransactions(data = []) {
            const series = (data || []).map((row) => ({
                date: row.date,
                transactions_count_total: Number(
                    row.values?.transactions_count_total ?? row.transactions_count_total ?? 0
                ),
                transactions_count_mean: Number(
                    row.values?.transactions_count_mean ?? row.transactions_count_mean ?? 0
                ),
            }));

            series.sort((a, b) => new Date(a.date) - new Date(b.date));

            const latest = series.length ? series[series.length - 1] : null;
            const previous = series.length > 1 ? series[series.length - 2] : null;

            return {
                latest,
                previous,
                chartData: {
                    labels: series.map((item) => item.date),
                    data: series.map((item) => item.transactions_count_total),
                },
            };
        },

        normalisePriceSeries(data = []) {
            const byDate = new Map();

            (data || []).forEach((row) => {
                const dateKey = row.date;
                const close = Number(row.values?.close ?? row.close ?? 0);
                const volume = Number(row.values?.volume ?? row.volume ?? 0);
                const fetchTimestamp = new Date(row.fetch_timestamp || 0).getTime();

                if (!byDate.has(dateKey)) {
                    byDate.set(dateKey, { date: dateKey, close, volume, fetchTimestamp });
                    return;
                }

                const current = byDate.get(dateKey);
                if (fetchTimestamp > current.fetchTimestamp) {
                    byDate.set(dateKey, { date: dateKey, close, volume, fetchTimestamp });
                }
            });

            const series = Array.from(byDate.values())
                .sort((a, b) => new Date(a.date) - new Date(b.date))
                .map(({ date, close, volume }) => ({ date, close, volume }));

            const latest = series.length ? series[series.length - 1] : null;
            const previous = series.length > 1 ? series[series.length - 2] : null;

            return {
                latest,
                previous,
                chartData: {
                    labels: series.map((item) => item.date),
                    data: series.map((item) => item.close),
                },
            };
        },

        renderMvrvChart(dataset) {
            const ctx = this.$refs.mvrvChart?.getContext("2d");
            if (!ctx) return;
            if (!dataset?.labels?.length || !dataset?.data?.length) {
                this.destroyChart("mvrv");
                return;
            }
            this.destroyChart("mvrv");

            this.charts.mvrv = new Chart(ctx, {
                type: "line",
                data: {
                    labels: dataset.labels ?? [],
                    datasets: [
                        {
                            label: "MVRV",
                            data: dataset.data ?? [],
                            borderColor: "#2563eb",
                            backgroundColor: "rgba(37, 99, 235, 0.12)",
                            borderWidth: 2.5,
                            tension: 0.25,
                            fill: true,
                        },
                    ],
                },
                options: this.defaultLineOptions(),
            });
        },

        renderSoprChart(chartData) {
            const ctx = this.$refs.soprChart?.getContext("2d");
            if (!ctx) return;
            if (!chartData?.labels?.length || !chartData?.datasets?.length) {
                this.destroyChart("sopr");
                return;
            }
            this.destroyChart("sopr");

            this.charts.sopr = new Chart(ctx, {
                type: "line",
                data: {
                    labels: chartData.labels ?? [],
                    datasets: chartData.datasets ?? [],
                },
                options: {
                    ...this.defaultLineOptions(),
                    plugins: {
                        legend: {
                            position: "bottom",
                        },
                    },
                },
            });
        },

        renderExchangeFlowChart(labels, datasets) {
            const ctx = this.$refs.exchangeFlowChart?.getContext("2d");
            if (!ctx) return;
            if (!labels?.length || !datasets?.length) {
                this.destroyChart("exchangeFlows");
                return;
            }
            this.destroyChart("exchangeFlows");

            this.charts.exchangeFlows = new Chart(ctx, {
                type: "bar",
                data: {
                    labels,
                    datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                            ticks: { autoSkip: true, maxRotation: 0 },
                            grid: { display: false },
                        },
                        y: {
                            stacked: true,
                            grid: { color: "rgba(148, 163, 184, 0.25)" },
                            ticks: {
                                callback: (value) => this.formatNumber(value, 0),
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            position: "bottom",
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const value = context.parsed.y;
                                    return `${context.dataset.label}: ${this.formatNumber(value, 0)} BTC`;
                                },
                            },
                        },
                    },
                },
            });
        },

        renderTransactionsChart(dataset) {
            const ctx = this.$refs.transactionsChart?.getContext("2d");
            if (!ctx) return;
            if (!dataset?.labels?.length || !dataset?.data?.length) {
                this.destroyChart("transactions");
                return;
            }
            this.destroyChart("transactions");

            this.charts.transactions = new Chart(ctx, {
                type: "line",
                data: {
                    labels: dataset.labels ?? [],
                    datasets: [
                        {
                            label: "Transactions",
                            data: dataset.data ?? [],
                            borderColor: "#0ea5e9",
                            backgroundColor: "rgba(14, 165, 233, 0.15)",
                            tension: 0.25,
                            borderWidth: 2,
                            fill: true,
                        },
                    ],
                },
                options: this.defaultLineOptions(),
            });
        },

        renderPriceChart(dataset) {
            const ctx = this.$refs.priceChart?.getContext("2d");
            if (!ctx) return;
            if (!dataset?.labels?.length || !dataset?.data?.length) {
                this.destroyChart("price");
                return;
            }
            this.destroyChart("price");

            this.charts.price = new Chart(ctx, {
                type: "line",
                data: {
                    labels: dataset.labels ?? [],
                    datasets: [
                        {
                            label: "Close",
                            data: dataset.data ?? [],
                            borderColor: "#22c55e",
                            backgroundColor: "rgba(34, 197, 94, 0.18)",
                            tension: 0.25,
                            borderWidth: 2,
                            fill: true,
                        },
                    ],
                },
                options: this.defaultLineOptions({
                    ticksFormatter: (value) => `$${this.formatNumber(value, 0)}`,
                }),
            });
        },

        destroyChart(key) {
            if (this.charts[key]) {
                try {
                    this.charts[key].destroy();
                } catch (error) {
                    console.warn(`Failed destroying chart ${key}`, error);
                }
                this.charts[key] = null;
            }
        },

        defaultLineOptions({ ticksFormatter } = {}) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: "category",
                        ticks: {
                            maxRotation: 0,
                            callback: (value, index, ticks) => {
                                const label = ticks?.[index]?.label ?? value;
                                return this.formatDateLabel(label);
                            },
                        },
                        grid: { display: false },
                    },
                    y: {
                        grid: { color: "rgba(148, 163, 184, 0.25)" },
                        ticks: {
                            callback:
                                typeof ticksFormatter === "function"
                                    ? ticksFormatter
                                    : (value) => this.formatNumber(value, 2),
                        },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: "index", intersect: false },
                },
                interaction: { mode: "index", intersect: false },
            };
        },

        getPaletteColor(index, alpha = 1) {
            const palette = [
                "#2563eb",
                "#10b981",
                "#f97316",
                "#ef4444",
                "#a855f7",
                "#14b8a6",
                "#f59e0b",
            ];
            const base = palette[index % palette.length];
            if (alpha === 1) return base;
            const hex = base.replace("#", "");
            const bigint = parseInt(hex, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },

        formatDateLabel(label) {
            if (label === undefined || label === null) {
                return "";
            }
            const raw = Array.isArray(label) ? label[0] : label;
            if (typeof raw === "string") {
                const parsed = new Date(raw);
                if (!Number.isNaN(parsed.getTime())) {
                    return parsed.toLocaleDateString(undefined, {
                        month: "short",
                        day: "numeric",
                    });
                }
                return raw;
            }
            if (typeof raw === "number") {
                return String(raw);
            }
            return String(raw ?? "");
        },

        computeDelta(current, previous) {
            if (
                current === null ||
                current === undefined ||
                previous === null ||
                previous === undefined ||
                Number.isNaN(current) ||
                Number.isNaN(previous)
            ) {
                return null;
            }
            return Number(current) - Number(previous);
        },

        computePercent(current, previous) {
            if (
                current === null ||
                current === undefined ||
                previous === null ||
                previous === undefined ||
                Number.isNaN(current) ||
                Number.isNaN(previous) ||
                previous === 0
            ) {
                return null;
            }
            return ((Number(current) - Number(previous)) / Math.abs(Number(previous))) * 100;
        },

        deltaClass(value, { invert = false } = {}) {
            if (value === null || value === undefined || Number.isNaN(value)) {
                return "text-muted";
            }
            const effective = invert ? -value : value;
            if (Math.abs(effective) < 1e-6) {
                return "text-muted";
            }
            return effective > 0 ? "text-success" : "text-danger";
        },

        formatDirectionalDelta(value, { digits = 2, suffix = "", invert = false } = {}) {
            if (value === null || value === undefined || Number.isNaN(value)) {
                return "—";
            }
            const effective = invert ? -value : value;
            let arrow = "→";
            if (effective > 1e-6) arrow = "▲";
            else if (effective < -1e-6) arrow = "▼";
            const magnitude = Math.abs(Number(value));
            return `${arrow} ${this.formatNumber(magnitude, digits)}${suffix}`;
        },

        formatNumber(value, digits = 2) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return "--";
            }
            const number = Number(value);
            return number.toLocaleString("en-US", {
                maximumFractionDigits: digits,
                minimumFractionDigits: number >= 1000 || digits === 0 ? 0 : Math.min(digits, 2),
            });
        },

        formatCurrency(value) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return "--";
            }
            return `$${Number(value).toLocaleString("en-US", {
                maximumFractionDigits: 0,
                minimumFractionDigits: 0,
            })}`;
        },

        formatPercent(value, digits = 1) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return "0.0%";
            }
            return `${Number(value).toFixed(digits)}%`;
        },

        getMvrvLabel(value) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return "Neutral";
            }
            if (value >= 3.7) return "Distribution";
            if (value < 1) return "Accumulation";
            return "Neutral";
        },

        setError(message, detail) {
            const detailMessage = detail?.message ?? detail ?? "";
            const composed = detailMessage ? `${message} (${detailMessage})` : message;
            this.error = this.error ? `${this.error}
${composed}` : composed;
        },
    };
}
