/**
 * Laevitas-Style Professional Heatmap
 * 
 * Professional grid-based heatmap like Laevitas/CME
 * Features:
 * - Grid-based cells with market share percentages
 * - Professional color gradient (green to red)
 * - Hover tooltips with detailed info
 * - Responsive design
 * - Trading terminal aesthetic
 */

function exchangeDominanceHeatmap() {
    return {
        loading: false,
        selectedSymbol: 'BTC',
        selectedTimeRange: '1m',
        
        timeRanges: [
            { label: '1D', value: '1d', days: 1 },
            { label: '7D', value: '7d', days: 7 },
            { label: '1M', value: '1m', days: 30 },
            { label: '3M', value: '3m', days: 90 }
        ],

        rawData: [],
        heatmapMatrix: [],
        topExchanges: [],
        marketInsights: [],
        totalMarketOI: 0,

        // Heatmap grid data
        gridData: [],
        exchanges: [],
        dates: [],

        async init() {
            console.log('🔥 Laevitas-Style Heatmap initialized');
            
            // Load data and render
            await this.loadData();
        },

        async loadData() {
            try {
                this.loading = true;
                console.log('📡 Loading exchange dominance data...');

                const baseMeta = document.querySelector('meta[name="api-base-url"]');
                const apiBase = baseMeta?.content?.trim() || 'https://test.dragonfortune.ai';
                
                const days = this.timeRanges.find(r => r.value === this.selectedTimeRange)?.days || 30;
                const limit = Math.max(days * 10, 100);
                
                const url = `${apiBase}/api/open-interest/exchange?symbol=${this.selectedSymbol}&limit=${limit}`;
                console.log('📡 Fetching from:', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                
                if (!data.data || !Array.isArray(data.data)) {
                    throw new Error('Invalid data format');
                }

                this.rawData = data.data.map(item => ({
                    ...item,
                    date: new Date(parseInt(item.ts)).toISOString().split('T')[0],
                    oi_usd: parseFloat(item.oi_usd) || 0
                }));

                console.log(`✅ Loaded ${this.rawData.length} data points`);

                this.processData();
                this.calculateRankings();
                this.generateInsights();
                this.renderLaevitasGrid();

            } catch (error) {
                console.error('❌ Error loading data:', error);
            } finally {
                this.loading = false;
            }
        },

        processData() {
            if (!this.rawData.length) return;

            // Group data by date and exchange
            const dataByDate = {};
            
            this.rawData.forEach(item => {
                const date = item.date;
                const exchange = item.exchange;
                
                if (!dataByDate[date]) {
                    dataByDate[date] = {};
                }
                
                if (!dataByDate[date][exchange]) {
                    dataByDate[date][exchange] = 0;
                }
                dataByDate[date][exchange] += item.oi_usd;
            });

            // Get unique exchanges and dates
            this.exchanges = [...new Set(this.rawData.map(item => item.exchange))].sort();
            this.dates = Object.keys(dataByDate).sort();
            
            // Limit to recent dates based on time range
            const maxDays = this.timeRanges.find(r => r.value === this.selectedTimeRange)?.days || 30;
            this.dates = this.dates.slice(-Math.min(maxDays, 15)); // Max 15 columns for readability
            
            this.heatmapMatrix = [];
            this.gridData = [];
            
            this.dates.forEach(date => {
                const dayData = dataByDate[date];
                const totalOI = Object.values(dayData).reduce((sum, oi) => sum + oi, 0);
                
                if (totalOI > 0) {
                    const row = {
                        date: date,
                        exchanges: {},
                        totalOI: totalOI
                    };
                    
                    this.exchanges.forEach(exchange => {
                        const oi = dayData[exchange] || 0;
                        const marketShare = (oi / totalOI) * 100;
                        row.exchanges[exchange] = {
                            oi: oi,
                            marketShare: marketShare
                        };
                    });
                    
                    this.heatmapMatrix.push(row);
                }
            });

            // Create grid data for rendering
            this.createGridData();

            console.log(`📊 Processed ${this.heatmapMatrix.length} days, ${this.exchanges.length} exchanges`);
        },

        createGridData() {
            this.gridData = [];
            
            this.exchanges.forEach(exchange => {
                const row = {
                    exchange: exchange,
                    cells: [],
                    total: 0,
                    avgShare: 0
                };
                
                let totalShare = 0;
                let validDays = 0;
                
                this.dates.forEach(date => {
                    const dayData = this.heatmapMatrix.find(d => d.date === date);
                    if (dayData && dayData.exchanges[exchange]) {
                        const share = dayData.exchanges[exchange].marketShare;
                        row.cells.push({
                            date: date,
                            value: share,
                            oi: dayData.exchanges[exchange].oi,
                            color: this.getHeatmapColor(share)
                        });
                        totalShare += share;
                        validDays++;
                    } else {
                        row.cells.push({
                            date: date,
                            value: 0,
                            oi: 0,
                            color: this.getHeatmapColor(0)
                        });
                    }
                });
                
                row.avgShare = validDays > 0 ? totalShare / validDays : 0;
                this.gridData.push(row);
            });
            
            // Sort by average share
            this.gridData.sort((a, b) => b.avgShare - a.avgShare);
            
            // Limit to top 8 exchanges for readability
            this.gridData = this.gridData.slice(0, 8);
        },

        renderLaevitasGrid() {
            // CoinGlass-style table is now rendered via Alpine.js in the template
            // No need to render grid anymore, just ensure data is ready
            console.log('✅ CoinGlass-style table data ready');
        },

        createGridHTML() {
            const formatDate = (dateStr) => {
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            };

            // Limit exchanges to top 6 and dates to recent 8-10 for optimal rectangle
            const topExchanges = this.gridData.slice(0, 6);
            const recentDates = this.dates.slice(-8); // Last 8 dates for better rectangle shape

            let html = `
                <div class="laevitas-grid optimal">
                    <div class="grid-header">
                        <div class="header-cell exchange-header">Exchange</div>
            `;

            // Date headers (back to top)
            recentDates.forEach(date => {
                html += `<div class="header-cell date-header">${formatDate(date)}</div>`;
            });
            
            html += `</div>`;

            // Exchange rows (back to left side)
            topExchanges.forEach((row, rowIndex) => {
                html += `<div class="grid-row" data-exchange="${row.exchange}">`;
                
                // Exchange name cell
                html += `
                    <div class="grid-cell exchange-cell">
                        <div class="exchange-name">${row.exchange}</div>
                    </div>
                `;
                
                // Data cells for each date
                recentDates.forEach((date, dateIndex) => {
                    const cell = row.cells.find(c => c.date === date);
                    if (cell) {
                        const displayValue = cell.value > 0 ? cell.value.toFixed(1) : '-';
                        html += `
                            <div class="grid-cell data-cell" 
                                 style="background-color: ${cell.color};"
                                 data-exchange="${row.exchange}"
                                 data-date="${cell.date}"
                                 data-value="${cell.value}"
                                 data-oi="${cell.oi}">
                                <span class="cell-value">${displayValue}</span>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="grid-cell data-cell" 
                                 style="background-color: #374151;">
                                <span class="cell-value">-</span>
                            </div>
                        `;
                    }
                });
                
                html += `</div>`;
            });

            html += `</div>`;
            
            return html;
        },

        setupGridEventListeners() {
            const dataCells = document.querySelectorAll('.data-cell');
            
            dataCells.forEach(cell => {
                cell.addEventListener('mouseenter', (e) => this.showGridTooltip(e));
                cell.addEventListener('mouseleave', () => this.hideGridTooltip());
            });
        },

        showGridTooltip(e) {
            const cell = e.target.closest('.data-cell');
            if (!cell) return;
            
            const exchange = cell.dataset.exchange;
            const date = cell.dataset.date;
            const value = parseFloat(cell.dataset.value);
            const oi = parseFloat(cell.dataset.oi);
            
            let tooltip = document.getElementById('grid-tooltip');
            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.id = 'grid-tooltip';
                tooltip.className = 'laevitas-tooltip';
                document.body.appendChild(tooltip);
            }
            
            tooltip.innerHTML = `
                <div class="tooltip-header">${exchange}</div>
                <div class="tooltip-row">
                    <span>Date:</span>
                    <span>${new Date(date).toLocaleDateString()}</span>
                </div>
                <div class="tooltip-row">
                    <span>Market Share:</span>
                    <span class="highlight">${value.toFixed(2)}%</span>
                </div>
                <div class="tooltip-row">
                    <span>Open Interest:</span>
                    <span class="highlight">${this.formatOI(oi)}</span>
                </div>
            `;
            
            const rect = cell.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2) + 'px';
            tooltip.style.top = (rect.top - 10) + 'px';
            tooltip.style.display = 'block';
        },

        hideGridTooltip() {
            const tooltip = document.getElementById('grid-tooltip');
            if (tooltip) {
                tooltip.style.display = 'none';
            }
        },

        getHeatmapColor(marketShare) {
            // Laevitas-style color scheme
            if (marketShare >= 40) return '#22c55e';      // High - Green
            if (marketShare >= 25) return '#84cc16';      // Medium-High - Light Green
            if (marketShare >= 15) return '#eab308';      // Medium - Yellow
            if (marketShare >= 5) return '#f97316';       // Low - Orange
            if (marketShare > 0) return '#ef4444';        // Very Low - Red
            return '#374151';                             // No data - Gray
        },

        calculateRankings() {
            if (!this.heatmapMatrix.length) return;

            const exchangeTotals = {};
            let totalMarketOI = 0;

            this.heatmapMatrix.forEach(row => {
                totalMarketOI += row.totalOI || 0;
                
                Object.entries(row.exchanges).forEach(([exchange, data]) => {
                    if (!exchangeTotals[exchange]) {
                        exchangeTotals[exchange] = {
                            name: exchange,
                            totalOI: 0,
                            avgMarketShare: 0,
                            dataPoints: 0
                        };
                    }
                    
                    exchangeTotals[exchange].totalOI += data.oi;
                    exchangeTotals[exchange].avgMarketShare += data.marketShare;
                    exchangeTotals[exchange].dataPoints++;
                });
            });

            this.topExchanges = Object.values(exchangeTotals)
                .filter(exchange => exchange.dataPoints > 0)
                .map(exchange => ({
                    ...exchange,
                    avgMarketShare: exchange.avgMarketShare / exchange.dataPoints,
                    openInterest: exchange.totalOI,
                    marketShare: ((exchange.totalOI / totalMarketOI) * 100).toFixed(1),
                    change24h: this.calculateChange24h(exchange.name),
                    trend: this.calculateTrend(exchange.name)
                }))
                .sort((a, b) => b.avgMarketShare - a.avgMarketShare)
                .slice(0, 10);

            this.totalMarketOI = totalMarketOI;
        },

        calculateChange24h(exchange) {
            if (this.heatmapMatrix.length < 2) return 0;
            
            const latest = this.heatmapMatrix[this.heatmapMatrix.length - 1];
            const previous = this.heatmapMatrix[this.heatmapMatrix.length - 2];
            
            const latestShare = latest.exchanges[exchange]?.marketShare || 0;
            const previousShare = previous.exchanges[exchange]?.marketShare || 0;
            
            if (previousShare === 0) return 0;
            
            return ((latestShare - previousShare) / previousShare) * 100;
        },

        calculateTrend(exchange) {
            if (this.heatmapMatrix.length < 3) return 'neutral';
            
            const recent = this.heatmapMatrix.slice(-3);
            const shares = recent.map(row => row.exchanges[exchange]?.marketShare || 0);
            
            const trend1 = shares[1] - shares[0];
            const trend2 = shares[2] - shares[1];
            
            if (trend1 > 0 && trend2 > 0) return 'bullish';
            if (trend1 < 0 && trend2 < 0) return 'bearish';
            return 'neutral';
        },

        generateInsights() {
            this.marketInsights = [];
            
            if (!this.topExchanges.length) return;

            const leader = this.topExchanges[0];
            if (leader.marketShare > 40) {
                this.marketInsights.push({
                    type: 'warning',
                    title: 'Market Concentration',
                    description: `${leader.name} dominates with ${leader.marketShare}% market share`
                });
            }

            const fastGrowing = this.topExchanges.find(ex => ex.change24h > 5);
            if (fastGrowing) {
                this.marketInsights.push({
                    type: 'bullish',
                    title: 'Rapid Growth',
                    description: `${fastGrowing.name} gaining ${fastGrowing.change24h.toFixed(1)}% market share`
                });
            }

            const declining = this.topExchanges.find(ex => ex.change24h < -5);
            if (declining) {
                this.marketInsights.push({
                    type: 'bearish',
                    title: 'Market Share Loss',
                    description: `${declining.name} losing ${Math.abs(declining.change24h).toFixed(1)}% market share`
                });
            }

            const top3Total = this.topExchanges.slice(0, 3).reduce((sum, ex) => sum + parseFloat(ex.marketShare), 0);
            if (top3Total < 70) {
                this.marketInsights.push({
                    type: 'neutral',
                    title: 'Healthy Competition',
                    description: 'Market share well distributed among top exchanges'
                });
            }
        },

        // Event handlers
        async updateSymbol() {
            console.log('🔄 Updating symbol to:', this.selectedSymbol);
            await this.loadData();
        },

        async setTimeRange(range) {
            if (this.selectedTimeRange === range) return;
            
            console.log('🔄 Setting time range to:', range);
            this.selectedTimeRange = range;
            await this.loadData();
        },

        async refreshHeatmap() {
            console.log('🔄 Refreshing heatmap data...');
            await this.loadData();
        },

        // Utility functions
        formatOI(value) {
            if (!value || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            if (num >= 1e9) return '$' + (num / 1e9).toFixed(2) + 'B';
            if (num >= 1e6) return '$' + (num / 1e6).toFixed(2) + 'M';
            if (num >= 1e3) return '$' + (num / 1e3).toFixed(2) + 'K';
            return '$' + num.toFixed(2);
        },

        formatBTC(value) {
            if (!value || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            if (num >= 1e6) return (num / 1e6).toFixed(2) + 'M BTC';
            if (num >= 1e3) return (num / 1e3).toFixed(2) + 'K BTC';
            return num.toFixed(2) + ' BTC';
        },

        formatChange(value) {
            if (!value || isNaN(value)) return 'N/A';
            const sign = value >= 0 ? '+' : '';
            return `${sign}${value.toFixed(2)}%`;
        },

        getChangeClass(value) {
            if (value > 0) return 'text-success';
            if (value < 0) return 'text-danger';
            return 'text-secondary';
        },

        getRankBadgeClass(rank) {
            if (rank === 1) return 'rank-1';
            if (rank === 2) return 'rank-2';
            if (rank === 3) return 'rank-3';
            return 'rank-other';
        },

        getExchangeColor(exchange) {
            const colors = {
                'BINANCE': '#f0b90b',
                'CME': '#0052ff',
                'BYBIT': '#f7931a',
                'OKX': '#0052ff',
                'GATE': '#64748b'
            };
            return colors[exchange] || '#6b7280';
        },

        getTrendIcon(trend) {
            switch (trend) {
                case 'bullish': return '📈';
                case 'bearish': return '📉';
                default: return '➡️';
            }
        },

        getInsightClass(type) {
            return `insight-${type}`;
        },

        getInsightIcon(type) {
            switch (type) {
                case 'bullish': return '🚀';
                case 'bearish': return '⚠️';
                case 'warning': return '🔥';
                default: return '💡';
            }
        }
    };
}

window.exchangeDominanceHeatmap = exchangeDominanceHeatmap;
console.log('✅ Laevitas-Style Heatmap loaded');