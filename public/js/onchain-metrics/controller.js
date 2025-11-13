/**
 * On-Chain Metrics Controller
 * Exchange Assets, Balances, Chain Transactions, Whale Transfers
 */

function onChainMetrics() {
    return {
        isLoading: false,
        activeTab: 'exchange-assets',
        
        // Exchange Assets
        exchangeAssets: {
            exchange: 'Binance',
            perPage: 50,
            page: 1,
            data: [],
            get totalBalanceUSD() {
                return this.data.reduce((sum, asset) => sum + (asset.balance_usd || 0), 0);
            },
            get uniqueAssets() {
                return new Set(this.data.map(a => a.symbol)).size;
            }
        },
        
        // Exchange Balances
        exchangeBalances: {
            symbol: 'BTC',
            list: [],
            chart: {
                data: null,
                instance: null
            }
        },
        
        // Chain Transactions
        chainTx: {
            symbol: 'USDT',
            minUsd: 10000,
            perPage: 50,
            page: 1,
            data: [],
            get inflowCount() {
                return this.data.filter(tx => tx.transfer_type === 1).length;
            },
            get outflowCount() {
                return this.data.filter(tx => tx.transfer_type === 2).length;
            },
            get totalVolume() {
                return this.data.reduce((sum, tx) => sum + (tx.amount_usd || 0), 0);
            }
        },
        
        // Whale Transfers
        whaleTransfers: {
            symbol: 'BTC',
            data: [],
            get totalVolume() {
                return this.data.reduce((sum, tx) => sum + parseFloat(tx.amount_usd || 0), 0);
            },
            get avgSize() {
                return this.data.length > 0 ? this.totalVolume / this.data.length : 0;
            }
        },

        init() {
            console.log('🚀 On-Chain Metrics initialized');
            console.log('Exchange Assets config:', this.exchangeAssets);
        },

        async loadExchangeAssets() {
            this.isLoading = true;
            try {
                const url = `/api/onchain/exchange/assets?exchange=${this.exchangeAssets.exchange}&per_page=${this.exchangeAssets.perPage}&page=${this.exchangeAssets.page}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    this.exchangeAssets.data = result.data;
                    console.log('✅ Exchange Assets loaded:', result.count);
                }
            } catch (error) {
                console.error('❌ Exchange Assets error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async loadExchangeBalances() {
            this.isLoading = true;
            try {
                // Load balance list
                const listUrl = `/api/onchain/exchange/balance/list?symbol=${this.exchangeBalances.symbol}`;
                const listResponse = await fetch(listUrl);
                const listResult = await listResponse.json();
                
                if (listResult.success) {
                    this.exchangeBalances.list = listResult.data;
                }
                
                // Load balance chart
                const chartUrl = `/api/onchain/exchange/balance/chart?symbol=${this.exchangeBalances.symbol}`;
                const chartResponse = await fetch(chartUrl);
                const chartResult = await chartResponse.json();
                
                if (chartResult.success) {
                    this.exchangeBalances.chart.data = chartResult.data;
                    this.renderBalanceChart();
                }
                
                console.log('✅ Exchange Balances loaded');
            } catch (error) {
                console.error('❌ Exchange Balances error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        renderBalanceChart() {
            const canvas = document.getElementById('balanceChart');
            if (!canvas) return;
            
            if (this.exchangeBalances.chart.instance) {
                this.exchangeBalances.chart.instance.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            const data = this.exchangeBalances.chart.data;
            
            if (!data || !data.date_list || !data.data_map) {
                console.warn('No chart data available');
                return;
            }
            
            const labels = data.date_list.map(timestamp => 
                new Date(timestamp * 1000).toLocaleDateString()
            );
            
            const datasets = Object.entries(data.data_map).map((([exchange, values], index) => {
                const colors = [
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)',
                    'rgb(251, 191, 36)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)',
                    'rgb(14, 165, 233)',
                    'rgb(132, 204, 22)'
                ];
                const color = colors[index % colors.length];
                
                return {
                    label: exchange,
                    data: values,
                    borderColor: color,
                    backgroundColor: color.replace('rgb', 'rgba').replace(')', ', 0.1)'),
                    tension: 0.1,
                    fill: false
                };
            }));
            
            this.exchangeBalances.chart.instance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: (value) => value.toLocaleString()
                            }
                        }
                    }
                }
            });
        },

        async loadChainTransactions() {
            this.isLoading = true;
            try {
                const url = `/api/onchain/chain/transactions?symbol=${this.chainTx.symbol}&min_usd=${this.chainTx.minUsd}&per_page=${this.chainTx.perPage}&page=${this.chainTx.page}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    this.chainTx.data = result.data;
                    console.log('✅ Chain Transactions loaded:', result.count);
                }
            } catch (error) {
                console.error('❌ Chain Transactions error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async loadWhaleTransfers() {
            this.isLoading = true;
            try {
                const url = `/api/onchain/whale-transfers?symbol=${this.whaleTransfers.symbol}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    this.whaleTransfers.data = result.data;
                    console.log('✅ Whale Transfers loaded:', result.count);
                }
            } catch (error) {
                console.error('❌ Whale Transfers error:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async refresh() {
            if (this.activeTab === 'exchange-assets') {
                await this.loadExchangeAssets();
            } else if (this.activeTab === 'exchange-balances') {
                await this.loadExchangeBalances();
            } else if (this.activeTab === 'chain-transactions') {
                await this.loadChainTransactions();
            } else if (this.activeTab === 'whale-transfers') {
                await this.loadWhaleTransfers();
            }
        }
    };
}

// Register with Alpine.js
if (window.Alpine) {
    window.Alpine.data('onChainMetrics', onChainMetrics);
} else {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('onChainMetrics', onChainMetrics);
    });
}

// Also expose globally for backward compatibility
window.onChainMetrics = onChainMetrics;
