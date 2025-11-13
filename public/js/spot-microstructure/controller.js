/**
 * Spot Microstructure Controller
 * Professional Trading Dashboard
 */

export function spotMicrostructure() {
    return {
        isLoading: false,
        activeTab: 'coins-markets',
        
        // Supported data
        supportedCoins: [],
        supportedExchangePairs: {},
        
        // Coins Markets
        coinsMarkets: {
            raw: [],
            processed: [],
            filtered: [],
            timeInterval: '24h',
            sortBy: 'volume_change_percent',
            sortDirection: 'desc',
            search: '',
            perPage: 100,
            page: 1,
            get avgPriceChange() {
                if (this.filtered.length === 0) return 0;
                const sum = this.filtered.reduce((acc, c) => acc + c.price_change, 0);
                return sum / this.filtered.length;
            },
            get totalVolume() {
                return this.filtered.reduce((acc, c) => acc + c.volume_usd, 0);
            },
            get totalNetFlow() {
                return this.filtered.reduce((acc, c) => acc + c.volume_flow_usd, 0);
            }
        },
        
        // Pairs Markets
        pairsMarkets: {
            raw: [],
            processed: [],
            filtered: [],
            symbol: 'BTC',
            exchanges: [],
            timeInterval: '24h',
            exchange: '',
            sortBy: 'volume_usd',
            search: '',
            get totalVolume() {
                return this.filtered.reduce((acc, p) => acc + p.volume_usd, 0);
            },
            get totalNetFlow() {
                return this.filtered.reduce((acc, p) => acc + p.net_flows_usd, 0);
            }
        },
        
        // Price History
        priceHistory: {
            exchange: 'Binance',
            symbol: 'BTCUSDT',
            interval: '1h',
            limit: 100,
            data: [],
            chart: null
        },

        async init() {
            console.log('🚀 Spot Microstructure initialized');
            
            // Load initial data
            await Promise.all([
                this.loadSupportedCoins(),
                this.loadSupportedExchangePairs(),
                this.loadCoinsMarkets()
            ]);
            
            console.log('✅ Ready');
        },

        async loadSupportedCoins() {
            try {
                const response = await fetch('/api/spot-microstructure/supported-coins');
                const result = await response.json();
                if (result.success) {
                    this.supportedCoins = result.data;
                }
            } catch (error) {
                console.error('❌ Supported coins error:', error);
            }
        },

        async loadSupportedExchangePairs() {
            try {
                const response = await fetch('/api/spot-microstructure/supported-exchange-pairs');
                const result = await response.json();
                if (result.success) {
                    this.supportedExchangePairs = result.data;
                }
            } catch (error) {
                console.error('❌ Supported pairs error:', error);
            }
        },

        async loadCoinsMarkets() {
            this.isLoading = true;
            try {
                const url = `/api/spot-microstructure/coins-markets?per_page=${this.coinsMarkets.perPage}&page=${this.coinsMarkets.page}`;
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    this.coinsMarkets.raw = result.data;
                    this.processCoinsMarkets();
                }
            } catch (error) {
                console.error('❌ Coins markets error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        processCoinsMarkets() {
            const interval = this.coinsMarkets.timeInterval;
            
            this.coinsMarkets.processed = this.coinsMarkets.raw.map(coin => ({
                symbol: coin.symbol,
                current_price: coin.current_price || 0,
                market_cap: coin.market_cap || 0,
                price_change: coin[`price_change_percent_${interval}`] || 0,
                volume_usd: coin[`volume_usd_${interval}`] || 0,
                volume_change_percent: coin[`volume_change_percent_${interval}`] || 0,
                buy_volume_usd: coin[`buy_volume_usd_${interval}`] || 0,
                sell_volume_usd: coin[`sell_volume_usd_${interval}`] || 0,
                volume_flow_usd: coin[`volume_flow_usd_${interval}`] || 0
            }));
            
            this.applyCoinsMarketsFilters();
        },

        applyCoinsMarketsFilters() {
            let filtered = [...this.coinsMarkets.processed];
            
            if (this.coinsMarkets.search) {
                const search = this.coinsMarkets.search.toLowerCase();
                filtered = filtered.filter(c => c.symbol.toLowerCase().includes(search));
            }
            
            const sortBy = this.coinsMarkets.sortBy;
            const dir = this.coinsMarkets.sortDirection === 'desc' ? -1 : 1;
            filtered.sort((a, b) => ((b[sortBy] || 0) - (a[sortBy] || 0)) * dir);
            
            this.coinsMarkets.filtered = filtered;
        },

        async loadPairsMarkets() {
            this.isLoading = true;
            try {
                const url = `/api/spot-microstructure/pairs-markets?symbol=${this.pairsMarkets.symbol}`;
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    this.pairsMarkets.raw = result.data;
                    this.pairsMarkets.exchanges = [...new Set(result.data.map(p => p.exchange_name))].sort();
                    this.processPairsMarkets();
                }
            } catch (error) {
                console.error('❌ Pairs markets error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        processPairsMarkets() {
            const interval = this.pairsMarkets.timeInterval;
            
            this.pairsMarkets.processed = this.pairsMarkets.raw.map(pair => ({
                symbol: pair.symbol,
                exchange_name: pair.exchange_name,
                current_price: pair.current_price || 0,
                price_change_percent: pair[`price_change_percent_${interval}`] || 0,
                volume_usd: pair[`volume_usd_${interval}`] || 0,
                buy_volume_usd: pair[`buy_volume_usd_${interval}`] || 0,
                sell_volume_usd: pair[`sell_volume_usd_${interval}`] || 0,
                net_flows_usd: pair[`net_flows_usd_${interval}`] || 0
            }));
            
            this.applyPairsMarketsFilters();
        },

        applyPairsMarketsFilters() {
            let filtered = [...this.pairsMarkets.processed];
            
            if (this.pairsMarkets.exchange) {
                filtered = filtered.filter(p => p.exchange_name === this.pairsMarkets.exchange);
            }
            
            if (this.pairsMarkets.search) {
                const search = this.pairsMarkets.search.toLowerCase();
                filtered = filtered.filter(p => p.symbol.toLowerCase().includes(search));
            }
            
            const sortBy = this.pairsMarkets.sortBy;
            filtered.sort((a, b) => (b[sortBy] || 0) - (a[sortBy] || 0));
            
            this.pairsMarkets.filtered = filtered;
        },

        async loadPriceHistory() {
            this.isLoading = true;
            
            try {
                const url = `/api/spot-microstructure/price-history?exchange=${this.priceHistory.exchange}&symbol=${this.priceHistory.symbol}&interval=${this.priceHistory.interval}&limit=${this.priceHistory.limit}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    this.priceHistory.data = result.data;
                    this.renderPriceChart();
                }
            } catch (error) {
                console.error('❌ Price history error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        renderPriceChart() {
            const canvas = document.getElementById('priceHistoryChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            
            if (this.priceHistory.chart) {
                this.priceHistory.chart.destroy();
            }
            
            const labels = this.priceHistory.data.map(d => new Date(d.time).toLocaleString());
            const closes = this.priceHistory.data.map(d => d.close);
            const volumes = this.priceHistory.data.map(d => d.volume_usd);
            
            this.priceHistory.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Price',
                        data: closes,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1,
                        fill: true,
                        yAxisID: 'y'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    return 'Price: $' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            ticks: {
                                callback: (value) => '$' + value.toLocaleString()
                            }
                        }
                    }
                }
            });
        },

        // Orderbook Analysis
        orderbook: {
            askBids: {
                exchange: 'Binance',
                symbol: 'BTCUSDT',
                interval: '1d',
                range: '1',
                limit: 100,
                data: [],
                chart: null
            },
            aggregated: {
                exchangeList: 'Binance',
                symbol: 'BTC',
                interval: '1h',
                range: '1',
                limit: 100,
                data: [],
                chart: null
            },
            history: {
                exchange: 'Binance',
                symbol: 'BTCUSDT',
                interval: '1h',
                limit: 100,
                data: []
            },
            largeOrders: {
                exchange: 'Binance',
                symbol: 'BTCUSDT',
                timeRange: '24h',
                state: '1',
                current: [],
                history: []
            }
        },

        async loadAskBidsHistory() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    exchange: this.orderbook.askBids.exchange,
                    symbol: this.orderbook.askBids.symbol,
                    interval: this.orderbook.askBids.interval,
                    range: this.orderbook.askBids.range,
                    limit: this.orderbook.askBids.limit
                });
                
                const response = await fetch(`/api/spot-microstructure/orderbook/ask-bids-history?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.orderbook.askBids.data = result.data;
                    this.renderAskBidsChart();
                }
            } catch (error) {
                console.error('❌ Ask/Bids error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        renderAskBidsChart() {
            const canvas = document.getElementById('askBidsChart');
            if (!canvas) return;
            
            if (this.orderbook.askBids.chart) {
                this.orderbook.askBids.chart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            const labels = this.orderbook.askBids.data.map(d => new Date(d.time).toLocaleString());
            const bids = this.orderbook.askBids.data.map(d => d.bids_usd);
            const asks = this.orderbook.askBids.data.map(d => d.asks_usd);
            
            this.orderbook.askBids.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Bid Depth (USD)',
                            data: bids,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Ask Depth (USD)',
                            data: asks,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => '$' + (value / 1e6).toFixed(2) + 'M'
                            }
                        }
                    }
                }
            });
        },

        async loadAggregatedHistory() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    exchange_list: this.orderbook.aggregated.exchangeList,
                    symbol: this.orderbook.aggregated.symbol,
                    interval: this.orderbook.aggregated.interval,
                    range: this.orderbook.aggregated.range,
                    limit: this.orderbook.aggregated.limit
                });
                
                const response = await fetch(`/api/spot-microstructure/orderbook/aggregated-history?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.orderbook.aggregated.data = result.data;
                    this.renderAggregatedChart();
                }
            } catch (error) {
                console.error('❌ Aggregated error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        renderAggregatedChart() {
            const canvas = document.getElementById('aggregatedChart');
            if (!canvas) return;
            
            if (this.orderbook.aggregated.chart) {
                this.orderbook.aggregated.chart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            const labels = this.orderbook.aggregated.data.map(d => new Date(d.time).toLocaleString());
            const bids = this.orderbook.aggregated.data.map(d => d.aggregated_bids_usd);
            const asks = this.orderbook.aggregated.data.map(d => d.aggregated_asks_usd);
            
            this.orderbook.aggregated.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Aggregated Bid Depth',
                            data: bids,
                            backgroundColor: 'rgba(34, 197, 94, 0.7)'
                        },
                        {
                            label: 'Aggregated Ask Depth',
                            data: asks,
                            backgroundColor: 'rgba(239, 68, 68, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => '$' + (value / 1e6).toFixed(2) + 'M'
                            }
                        }
                    }
                }
            });
        },

        async loadOrderbookHistory() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    exchange: this.orderbook.history.exchange,
                    symbol: this.orderbook.history.symbol,
                    interval: this.orderbook.history.interval,
                    limit: this.orderbook.history.limit
                });
                
                const response = await fetch(`/api/spot-microstructure/orderbook/history?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.orderbook.history.data = result.data;
                }
            } catch (error) {
                console.error('❌ Orderbook history error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async loadLargeLimitOrders() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    exchange: this.orderbook.largeOrders.exchange,
                    symbol: this.orderbook.largeOrders.symbol
                });
                
                const response = await fetch(`/api/spot-microstructure/orderbook/large-limit-order?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.orderbook.largeOrders.current = result.data;
                }
            } catch (error) {
                console.error('❌ Large orders error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async loadLargeLimitOrderHistory() {
            this.isLoading = true;
            try {
                const now = Date.now();
                const ranges = {
                    '1h': 60 * 60 * 1000,
                    '6h': 6 * 60 * 60 * 1000,
                    '24h': 24 * 60 * 60 * 1000
                };
                
                const startTime = now - ranges[this.orderbook.largeOrders.timeRange];
                
                const params = new URLSearchParams({
                    exchange: this.orderbook.largeOrders.exchange,
                    symbol: this.orderbook.largeOrders.symbol,
                    start_time: startTime,
                    end_time: now,
                    state: this.orderbook.largeOrders.state
                });
                
                const response = await fetch(`/api/spot-microstructure/orderbook/large-limit-order-history?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.orderbook.largeOrders.history = result.data;
                }
            } catch (error) {
                console.error('❌ Large order history error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        // Volume Analysis
        volumeAnalysis: {
            taker: {
                exchange: 'Binance',
                symbol: 'BTCUSDT',
                interval: '1h',
                limit: 100,
                data: [],
                chart: null,
                get totalBuy() {
                    return this.data.reduce((sum, d) => sum + parseFloat(d.taker_buy_volume_usd), 0);
                },
                get totalSell() {
                    return this.data.reduce((sum, d) => sum + parseFloat(d.taker_sell_volume_usd), 0);
                },
                get ratio() {
                    return this.totalSell > 0 ? this.totalBuy / this.totalSell : 0;
                }
            },
            aggregated: {
                exchangeList: 'Binance',
                symbol: 'BTC',
                interval: '1h',
                unit: 'usd',
                limit: 100,
                data: [],
                chart: null
            },
            footprint: {
                exchange: 'Binance',
                symbol: 'BTCUSDT',
                interval: '1h',
                limit: 50,
                data: [],
                processed: []
            }
        },

        async loadTakerVolume() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    exchange: this.volumeAnalysis.taker.exchange,
                    symbol: this.volumeAnalysis.taker.symbol,
                    interval: this.volumeAnalysis.taker.interval,
                    limit: this.volumeAnalysis.taker.limit
                });
                
                const response = await fetch(`/api/spot-microstructure/taker-volume/history?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.volumeAnalysis.taker.data = result.data;
                    this.renderTakerVolumeChart();
                }
            } catch (error) {
                console.error('❌ Taker volume error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        renderTakerVolumeChart() {
            const canvas = document.getElementById('takerVolumeChart');
            if (!canvas) return;
            
            if (this.volumeAnalysis.taker.chart) {
                this.volumeAnalysis.taker.chart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            const labels = this.volumeAnalysis.taker.data.map(d => new Date(d.time).toLocaleString());
            const buyVolume = this.volumeAnalysis.taker.data.map(d => parseFloat(d.taker_buy_volume_usd));
            const sellVolume = this.volumeAnalysis.taker.data.map(d => parseFloat(d.taker_sell_volume_usd));
            
            this.volumeAnalysis.taker.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Taker Buy Volume',
                            data: buyVolume,
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1
                        },
                        {
                            label: 'Taker Sell Volume',
                            data: sellVolume,
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderColor: 'rgb(239, 68, 68)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => '$' + (value / 1e3).toFixed(0) + 'K'
                            }
                        }
                    }
                }
            });
        },

        async loadAggregatedTakerVolume() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    exchange_list: this.volumeAnalysis.aggregated.exchangeList,
                    symbol: this.volumeAnalysis.aggregated.symbol,
                    interval: this.volumeAnalysis.aggregated.interval,
                    unit: this.volumeAnalysis.aggregated.unit,
                    limit: this.volumeAnalysis.aggregated.limit
                });
                
                const response = await fetch(`/api/spot-microstructure/taker-volume/aggregated-history?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.volumeAnalysis.aggregated.data = result.data;
                    this.renderAggregatedTakerChart();
                }
            } catch (error) {
                console.error('❌ Aggregated taker error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        renderAggregatedTakerChart() {
            const canvas = document.getElementById('aggregatedTakerChart');
            if (!canvas) return;
            
            if (this.volumeAnalysis.aggregated.chart) {
                this.volumeAnalysis.aggregated.chart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            const labels = this.volumeAnalysis.aggregated.data.map(d => new Date(d.time).toLocaleString());
            const buyVolume = this.volumeAnalysis.aggregated.data.map(d => d.aggregated_buy_volume_usd);
            const sellVolume = this.volumeAnalysis.aggregated.data.map(d => d.aggregated_sell_volume_usd);
            
            this.volumeAnalysis.aggregated.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Aggregated Buy Volume',
                            data: buyVolume,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Aggregated Sell Volume',
                            data: sellVolume,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => '$' + (value / 1e6).toFixed(0) + 'M'
                            }
                        }
                    }
                }
            });
        },

        async loadVolumeFootprint() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    exchange: this.volumeAnalysis.footprint.exchange,
                    symbol: this.volumeAnalysis.footprint.symbol,
                    interval: this.volumeAnalysis.footprint.interval,
                    limit: this.volumeAnalysis.footprint.limit
                });
                
                const response = await fetch(`/api/spot-microstructure/volume-footprint/history?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.volumeAnalysis.footprint.data = result.data;
                    this.processFootprintData();
                }
            } catch (error) {
                console.error('❌ Footprint error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        processFootprintData() {
            const processed = [];
            
            this.volumeAnalysis.footprint.data.forEach(item => {
                const timestamp = item[0];
                const priceData = item[1];
                
                if (Array.isArray(priceData)) {
                    priceData.forEach(level => {
                        processed.push({
                            time: timestamp,
                            priceStart: level[0],
                            priceEnd: level[1],
                            buyVolume: level[2],
                            sellVolume: level[3],
                            buyValueQuote: level[4],
                            sellValueQuote: level[5],
                            buyValueUSDT: level[6],
                            sellValueUSDT: level[7],
                            buyTradeCount: level[8],
                            sellTradeCount: level[9]
                        });
                    });
                }
            });
            
            this.volumeAnalysis.footprint.processed = processed;
        },

        async refresh() {
            if (this.activeTab === 'coins-markets') {
                await this.loadCoinsMarkets();
            } else if (this.activeTab === 'pairs-markets') {
                await this.loadPairsMarkets();
            }
        }
    };
}

window.spotMicrostructure = spotMicrostructure;
