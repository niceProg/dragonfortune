/**
 * Open Interest Hybrid Controller
 * 
 * DUAL API APPROACH:
 * - TOP SECTION: Professional Chart (CryptoQuant API)
 * - BOTTOM SECTION: Data Tables (Internal API - test.dragonfortune.ai)
 * 
 * Think like a trader:
 * - Open Interest measures total outstanding contracts
 * - Rising OI + Rising Price = Strong bullish trend
 * - Rising OI + Falling Price = Strong bearish trend
 * - Falling OI = Trend weakening (profit taking)
 * 
 * Build like an engineer:
 * - Clean dual API integration
 * - Professional chart rendering
 * - Statistical analysis & detailed tables
 */

function basisTermStructureHybridController() {
    return {
        // Global state
        globalPeriod: '1m', // Changed from '30d' to match new time ranges
        globalLoading: false,
        selectedExchange: 'binance',
        selectedSymbol: 'btc_usdt', // Default to BTC/USDT

        // Enhanced chart controls with YTD (initialized in init method)
        timeRanges: [],
        scaleType: 'linear', // 'linear' or 'logarithmic'

        // Chart intervals
        chartIntervals: [
            { label: '1H', value: '1h' },
            { label: '4H', value: '4h' },
            { label: '1D', value: '1d' },
            { label: '1W', value: '1w' }
        ],
        selectedInterval: '1d',

        // Get YTD days
        getYTDDays() {
            const now = new Date();
            const startOfYear = new Date(now.getFullYear(), 0, 1);
            const diffTime = Math.abs(now - startOfYear);
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        },

        // Data
        rawData: [],
        priceData: [], // Bitcoin price data for overlay

        // Cache to prevent rate limiting
        dataCache: new Map(),
        priceCache: new Map(),

        // Summary metrics (Open Interest)
        currentOI: 0,
        oiChange: 0,
        avgOI: 0,
        medianOI: 0,
        maxOI: 0,
        minOI: 0,
        peakDate: '--',

        // Price metrics
        currentPrice: 0,
        priceChange: 0,

        // Analysis metrics (adapted for open interest)
        ma7: 0,
        ma30: 0,
        highOIEvents: 0,
        extremeOIEvents: 0,

        // Market signal
        marketSignal: 'Neutral',
        signalStrength: 'Normal',
        signalDescription: 'Loading...',

        // Chart state
        chartType: 'line',
        mainChart: null,
        distributionChart: null,
        maChart: null,

        // Initialize
        init() {
            console.log('🚀 Open Interest Hybrid Dashboard initialized');
            console.log('📊 Controller properties:', {
                chartType: this.chartType,
                scaleType: this.scaleType,
                chartIntervals: this.chartIntervals,
                selectedInterval: this.selectedInterval
            });

            // Initialize time ranges (removed 3M and 6M)
            this.timeRanges = [
                { label: '1D', value: '1d', days: 1 },
                { label: '7D', value: '7d', days: 7 },
                { label: '1M', value: '1m', days: 30 },
                { label: 'YTD', value: 'ytd', days: this.getYTDDays() },
                { label: '1Y', value: '1y', days: 365 },
                { label: 'ALL', value: 'all', days: 365 } // 3 years
            ];

            // Register Chart.js zoom plugin
            if (typeof Chart !== 'undefined' && Chart.register) {
                try {
                    // The zoom plugin should be automatically registered when loaded via CDN
                    console.log('✅ Chart.js zoom plugin should be available');
                } catch (error) {
                    console.warn('⚠️ Error with Chart.js zoom plugin:', error);
                }
            }

            // Wait for Chart.js to be ready
            if (typeof window.chartJsReady !== 'undefined') {
                window.chartJsReady.then(() => {
                    this.loadData();
                });
            } else {
                // Fallback: load data after a short delay
                setTimeout(() => this.loadData(), 500);
            }

            // Auto refresh every 5 minutes
            setInterval(() => this.loadData(), 5 * 60 * 1000);
        },

        // Update period filter
        updatePeriod() {
            console.log('🔄 Updating period to:', this.globalPeriod);
            this.loadData();
        },

        // Update exchange
        updateExchange() {
            console.log('🔄 Updating exchange to:', this.selectedExchange);
            this.loadData();
        },

        // Update symbol
        updateSymbol() {
            console.log('🔄 Updating symbol to:', this.selectedSymbol);
            this.loadData();
        },

        // Update interval
        updateInterval() {
            console.log('🔄 Updating interval to:', this.selectedInterval);
            this.loadData();
        },

        // Map chart interval to CryptoQuant window parameter
        getWindowParameter() {
            const intervalMap = {
                '1h': 'hour',
                '4h': 'hour',
                '1d': 'day',
                '1w': 'day'
            };
            return intervalMap[this.selectedInterval] || 'day';
        },

        // Refresh all data
        refreshAll() {
            this.globalLoading = true;
            this.loadData().finally(() => {
                this.globalLoading = false;
            });
        },

        // Set time range
        setTimeRange(range) {
            if (this.globalPeriod === range) return;

            console.log('🔄 Setting time range to:', range);
            this.globalPeriod = range;
            this.loadData();
        },

        // Set chart interval (renamed to avoid conflict with native setInterval)
        setChartInterval(interval) {
            if (this.selectedInterval === interval) return;

            console.log('🔄 Setting chart interval to:', interval);
            this.selectedInterval = interval;
            this.loadData(); // Reload data with new interval
        },

        // Set chart interval (renamed to avoid conflict with native setInterval)
        setChartInterval(interval) {
            if (this.selectedInterval === interval) return;

            console.log('🔄 Setting chart interval to:', interval);
            this.selectedInterval = interval;
            this.loadData(); // Reload data with new interval
        },

        // Toggle scale type (linear/logarithmic)
        toggleScale(type) {
            if (this.scaleType === type) return;

            console.log('🔄 Toggling scale to:', type);
            this.scaleType = type;
            this.renderChart(); // Re-render with new scale
        },

        // Toggle chart type (line/bar)
        toggleChartType(type) {
            if (this.chartType === type) return;

            console.log('🔄 Toggling chart type to:', type);
            this.chartType = type;
            this.renderChart(); // Re-render with new type
        },

        // Reset chart zoom
        resetZoom() {
            if (this.mainChart && this.mainChart.resetZoom) {
                console.log('🔄 Resetting chart zoom');
                this.mainChart.resetZoom();
            }
        },

        // Export chart with enhanced options
        exportChart(format = 'png') {
            if (!this.mainChart) {
                console.warn('⚠️ No chart available for export');
                return;
            }

            try {
                console.log(`📸 Exporting chart as ${format.toUpperCase()}`);

                const timestamp = new Date().toISOString().split('T')[0];
                const filename = `Funding_Rate_Chart_${this.selectedExchange}_${timestamp}`;

                if (format === 'png') {
                    const link = document.createElement('a');
                    link.download = `${filename}.png`;
                    link.href = this.mainChart.toBase64Image('image/png', 1.0);
                    link.click();
                } else if (format === 'svg') {
                    // For SVG export, we'd need additional library
                    console.warn('⚠️ SVG export requires additional implementation');
                    // Fallback to PNG
                    this.exportChart('png');
                }

                // Show success notification (could be enhanced with toast)
                console.log('✅ Chart exported successfully');

            } catch (error) {
                console.error('❌ Error exporting chart:', error);
            }
        },

        // Share chart functionality
        shareChart() {
            if (!this.mainChart) {
                console.warn('⚠️ No chart available for sharing');
                return;
            }

            try {
                const dataUrl = this.mainChart.toBase64Image('image/png', 0.8);

                // Create shareable content
                const shareData = {
                    title: `Bitcoin Funding Rate - ${this.selectedExchange}`,
                    text: `Current Funding Rate: ${this.formatFundingRate(this.currentFundingRate)} | Signal: ${this.marketSignal}`,
                    url: window.location.href
                };

                // Use Web Share API if available
                if (navigator.share) {
                    navigator.share(shareData).then(() => {
                        console.log('✅ Chart shared successfully');
                    }).catch((error) => {
                        console.log('⚠️ Share cancelled or failed:', error);
                        this.fallbackShare(shareData);
                    });
                } else {
                    this.fallbackShare(shareData);
                }

            } catch (error) {
                console.error('❌ Error sharing chart:', error);
            }
        },

        // Fallback share method
        fallbackShare(shareData) {
            // Copy URL to clipboard
            navigator.clipboard.writeText(shareData.url).then(() => {
                console.log('✅ Chart URL copied to clipboard');
                // Could show toast notification here
            }).catch(() => {
                console.warn('⚠️ Could not copy to clipboard');
            });
        },

        // Load data from API with optimization and rate limiting
        async loadData() {
            try {
                this.globalLoading = true;
                console.log('📡 Fetching Open Interest data...');

                // Calculate date range based on period
                const { startDate, endDate } = this.getDateRange();
                console.log(`📅 Date range: ${startDate} to ${endDate}`);

                // Add delay to prevent rate limiting
                await new Promise(resolve => setTimeout(resolve, 500));

                // Fetch Open Interest data and price data sequentially to avoid rate limits
                const [oiData, priceData] = await Promise.allSettled([
                    this.fetchOpenInterestData(startDate, endDate),
                    new Promise(resolve => setTimeout(resolve, 1000)).then(() => this.loadPriceData(startDate, endDate))
                ]);

                // Handle Open Interest data
                if (oiData.status === 'fulfilled') {
                    this.rawData = oiData.value;
                    console.log(`✅ Loaded ${this.rawData.length} Open Interest data points`);
                } else {
                    console.error('❌ Error loading Open Interest data:', oiData.reason);
                    this.rawData = [];
                    this.showError('CryptoQuant API rate limit reached. Please wait a moment and try again.');
                    return; // Stop execution if no data
                }

                // Calculate metrics
                this.calculateMetrics();

                // Render charts with small delay to ensure DOM is ready
                setTimeout(() => {
                    this.renderChart();
                    this.renderDistributionChart();
                    this.renderMAChart();
                }, 100);

            } catch (error) {
                console.error('❌ Error loading data:', error);
                this.showError(error.message);
            } finally {
                this.globalLoading = false;
            }
        },

        // Separate Open Interest data fetching with caching
        async fetchOpenInterestData(startDate, endDate) {
            const cacheKey = `${startDate}-${endDate}-${this.selectedExchange}-${this.selectedSymbol}`;

            // Check cache first
            if (this.dataCache.has(cacheKey)) {
                console.log('📦 Using cached Open Interest data');
                return this.dataCache.get(cacheKey);
            }

            // Fallback to basic parameters if advanced fails
            let url = `${window.location.origin}/api/cryptoquant/open-interest?start_date=${startDate}&end_date=${endDate}&exchange=${this.selectedExchange}`;

            // Only add symbol if not all_symbol (to avoid 403)
            if (this.selectedSymbol !== 'all_symbol') {
                url += `&symbol=${this.selectedSymbol}`;
            }

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (!data.success || !Array.isArray(data.data)) {
                throw new Error('Invalid data format');
            }

            // Cache the result for 5 minutes
            this.dataCache.set(cacheKey, data.data);
            setTimeout(() => this.dataCache.delete(cacheKey), 5 * 60 * 1000);

            return data.data;
        },
        // Load Bitcoin price data from CryptoQuant API only (NO DUMMY DATA)
        async loadPriceData(startDate, endDate) {
            try {
                console.log('📡 Fetching REAL Bitcoin price data from CryptoQuant...');
                await this.tryMultiplePriceSources(startDate, endDate);

                // Verify we have valid price data
                if (this.currentPrice > 0 && this.priceData.length > 0) {
                    console.log(`✅ CryptoQuant Bitcoin price loaded successfully: ${this.currentPrice.toLocaleString()}`);
                    console.log(`📊 Price data points: ${this.priceData.length}, 24h change: ${this.priceChange.toFixed(2)}%`);
                } else {
                    throw new Error('No valid CryptoQuant price data received');
                }

            } catch (error) {
                console.error('❌ Error loading CryptoQuant price data:', error);

                // NO DUMMY DATA - disable price overlay if CryptoQuant fails
                this.priceData = [];
                this.currentPrice = 0;
                this.priceChange = 0;

                console.warn('⚠️ Price overlay disabled - CryptoQuant API unavailable');
                console.warn('⚠️ Please check CryptoQuant API configuration and endpoints');
            }
        },

        // Get Bitcoin price from CryptoQuant API (REAL DATA - NO DUMMY)
        async tryMultiplePriceSources(startDate, endDate) {
            // Use the new CryptoQuant Bitcoin price endpoint
            try {
                const cryptoquantPrice = `${window.location.origin}/api/cryptoquant/btc-market-price?start_date=${startDate}&end_date=${endDate}`;
                console.log('📡 Fetching Bitcoin price from:', cryptoquantPrice);

                const response = await fetch(cryptoquantPrice);

                if (response.ok) {
                    const data = await response.json();
                    console.log('📊 CryptoQuant Bitcoin price response:', data);

                    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        // Transform price data
                        this.priceData = data.data.map(item => ({
                            date: item.date,
                            price: parseFloat(item.close || item.value) // Use close price or value
                        }));

                        // Calculate current price and change
                        const latest = this.priceData[this.priceData.length - 1];
                        const previous = this.priceData[this.priceData.length - 2];

                        this.currentPrice = latest.price;
                        this.priceChange = previous ? ((latest.price - previous.price) / previous.price) * 100 : 0;

                        console.log(`✅ Loaded ${this.priceData.length} REAL Bitcoin price points from CryptoQuant`);
                        console.log(`📊 Current BTC Price: ${this.currentPrice.toLocaleString()}, Change: ${this.priceChange.toFixed(2)}%`);
                        return;
                    } else {
                        console.warn('⚠️ CryptoQuant returned empty or invalid data:', data);
                    }
                } else {
                    console.warn('⚠️ CryptoQuant API response not OK:', response.status, response.statusText);
                }
            } catch (error) {
                console.warn('⚠️ CryptoQuant Bitcoin price endpoint failed:', error);
            }

            // If CryptoQuant endpoint fails, show error (NO DUMMY DATA)
            console.error('❌ CryptoQuant Bitcoin price endpoint failed. Please check API configuration.');
            throw new Error('No CryptoQuant Bitcoin price data available');
        },

        // Calculate price metrics
        calculatePriceMetrics() {
            if (this.priceData.length > 0) {
                this.currentPrice = this.priceData[this.priceData.length - 1].price;
                const yesterdayPrice = this.priceData[this.priceData.length - 2]?.price || this.currentPrice;
                this.priceChange = ((this.currentPrice - yesterdayPrice) / yesterdayPrice) * 100;
            }
        },

        // Get date range in days
        getDateRangeDays() {
            const selectedRange = this.timeRanges.find(r => r.value === this.globalPeriod);
            return selectedRange ? selectedRange.days : 30;
        },

        // Calculate all metrics (with safety checks for small datasets) - ADAPTED FOR FUNDING RATES
        calculateMetrics() {
            if (this.rawData.length === 0) {
                console.warn('⚠️ No data available for metrics calculation');
                return;
            }

            // Sort by date
            const sorted = [...this.rawData].sort((a, b) =>
                new Date(a.date) - new Date(b.date)
            );

            // Extract Open Interest values
            const oiValues = sorted.map(d => parseFloat(d.value));

            // Current metrics
            this.currentOI = oiValues[oiValues.length - 1] || 0;
            const previousOI = oiValues[oiValues.length - 2] || this.currentOI;

            // Calculate percentage change for open interest
            this.oiChange = previousOI !== 0 ?
                ((this.currentOI - previousOI) / previousOI) * 100 :
                0;

            // Statistical metrics
            this.avgOI = oiValues.length > 0 ? oiValues.reduce((a, b) => a + b, 0) / oiValues.length : 0;
            this.medianOI = oiValues.length > 0 ? this.calculateMedian(oiValues) : 0;
            this.maxOI = oiValues.length > 0 ? Math.max(...oiValues) : 0;
            this.minOI = oiValues.length > 0 ? Math.min(...oiValues) : 0;

            // Peak date (with safety check)
            if (oiValues.length > 0) {
                const peakIndex = oiValues.indexOf(this.maxOI);
                this.peakDate = this.formatDate(sorted[peakIndex]?.date || sorted[0].date);
            } else {
                this.peakDate = '--';
            }

            // Moving averages (flexible - use available data)
            this.ma7 = this.calculateMA(oiValues, 7);
            this.ma30 = this.calculateMA(oiValues, 30);

            // Outlier detection (flexible approach) - ADAPTED FOR OPEN INTEREST
            if (oiValues.length >= 2) {
                const stdDev = this.calculateStdDev(oiValues);
                const threshold2Sigma = this.avgOI + (2 * stdDev);
                const threshold3Sigma = this.avgOI + (3 * stdDev);

                this.highOIEvents = oiValues.filter(v => v > threshold2Sigma).length;
                this.extremeOIEvents = oiValues.filter(v => v > threshold3Sigma).length;

                // Market signal
                this.calculateMarketSignal(stdDev);
            } else if (oiValues.length === 1) {
                // Single data point
                this.highOIEvents = 0;
                this.extremeOIEvents = 0;
                this.marketSignal = 'Data Tunggal';
                this.signalStrength = 'Normal';
                this.signalDescription = 'Open Interest saat ini: ' + this.formatOI(this.currentOI);
            } else {
                // Not enough data for statistical analysis
                this.highOIEvents = 0;
                this.extremeOIEvents = 0;
                this.marketSignal = 'Data Tidak Cukup';
                this.signalStrength = 'N/A';
                this.signalDescription = 'Perlu lebih banyak data untuk analisis';
            }

            // Calculate Z-Score
            this.calculateCurrentZScore();

            console.log('📊 Metrics calculated:', {
                current: this.currentOI,
                avg: this.avgOI,
                max: this.maxOI,
                signal: this.marketSignal,
                zScore: this.currentZScore
            });
        },

        // Calculate market signal - ADAPTED FOR OPEN INTEREST
        calculateMarketSignal(stdDev) {
            const zScore = (this.currentOI - this.avgOI) / stdDev;

            if (zScore > 2) {
                this.marketSignal = 'OI Sangat Tinggi';
                this.signalStrength = 'Strong';
                this.signalDescription = 'Open Interest sangat tinggi - partisipasi pasar yang kuat';
            } else if (zScore > 1) {
                this.marketSignal = 'OI Tinggi';
                this.signalStrength = 'Moderate';
                this.signalDescription = 'Open Interest meningkat terdeteksi';
            } else if (zScore < -2) {
                this.marketSignal = 'OI Sangat Rendah';
                this.signalStrength = 'Strong';
                this.signalDescription = 'Open Interest sangat rendah - partisipasi pasar lemah';
            } else if (zScore < -1) {
                this.marketSignal = 'OI Rendah';
                this.signalStrength = 'Moderate';
                this.signalDescription = 'Open Interest di bawah rata-rata';
            } else {
                this.marketSignal = 'OI Normal';
                this.signalStrength = 'Normal';
                this.signalDescription = 'Level Open Interest normal';
            }
        },

        // Render main chart (CryptoQuant style with price overlay) - ADAPTED FOR OPEN INTEREST
        renderChart() {
            const canvas = document.getElementById('basisTermStructureMainChart');
            if (!canvas) {
                console.warn('⚠️ Canvas element not found: openInterestMainChart');
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('⚠️ Cannot get 2D context from canvas');
                return;
            }

            // Destroy existing chart safely
            if (this.mainChart) {
                try {
                    this.mainChart.destroy();
                } catch (error) {
                    console.warn('⚠️ Error destroying chart:', error);
                }
                this.mainChart = null;
            }

            // Check if we have data
            if (!this.rawData || this.rawData.length === 0) {
                console.warn('⚠️ No data available for chart rendering');
                return;
            }

            // Prepare Open Interest data
            const sorted = [...this.rawData].sort((a, b) =>
                new Date(a.date) - new Date(b.date)
            );

            const labels = sorted.map(d => d.date);
            const oiValues = sorted.map(d => parseFloat(d.value));

            // Prepare price data (align with funding rate dates)
            const priceMap = new Map(this.priceData.map(p => [p.date, p.price]));
            const alignedPrices = labels.map(date => priceMap.get(date) || null);

            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');

            // Build datasets
            const datasets = [];

            // Dataset 1: Open Interest (main data)
            if (this.chartType === 'bar') {
                // Bar chart for Open Interest
                datasets.push({
                    label: 'Open Interest',
                    data: oiValues,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    yAxisID: 'y',
                    order: 2
                });
            } else {
                // Line chart for Open Interest
                datasets.push({
                    label: 'Open Interest',
                    data: oiValues,
                    borderColor: '#3b82f6',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#3b82f6',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'y',
                    order: 2
                });
            }

            // Dataset 2: Bitcoin Price overlay (if available)
            if (this.priceData.length > 0) {
                datasets.push({
                    label: 'BTC Price',
                    data: alignedPrices,
                    borderColor: '#f59e0b',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    type: 'line',
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#f59e0b',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'y1',
                    order: 1
                });
            }

            // Create chart with dual Y-axis (CryptoQuant style)
            try {
                this.mainChart = new Chart(ctx, {
                    type: this.chartType,
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
                        // Enhanced plugins with zoom and pan (with safety checks)
                        plugins: {
                            zoom: {
                                pan: {
                                    enabled: true,
                                    mode: 'xy'
                                },
                                zoom: {
                                    wheel: {
                                        enabled: true
                                    },
                                    pinch: {
                                        enabled: true
                                    },
                                    mode: 'xy'
                                }
                            },
                            legend: {
                                display: this.priceData.length > 0,
                                position: 'top',
                                align: 'end',
                                labels: {
                                    color: '#64748b',
                                    font: { size: 11, weight: '500' },
                                    boxWidth: 12,
                                    boxHeight: 12,
                                    padding: 10,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(17, 24, 39, 0.95)',
                                titleColor: '#f3f4f6',
                                bodyColor: '#f3f4f6',
                                borderColor: 'rgba(59, 130, 246, 0.5)',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: true,
                                boxWidth: 8,
                                boxHeight: 8,
                                callbacks: {
                                    title: (items) => {
                                        const date = new Date(items[0].label);
                                        return date.toLocaleDateString('en-US', {
                                            weekday: 'short',
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric'
                                        });
                                    },
                                    label: (context) => {
                                        const datasetLabel = context.dataset.label;
                                        const value = context.parsed.y;

                                        if (datasetLabel === 'BTC Price') {
                                            return `  ${datasetLabel}: $${value.toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
                                        } else {
                                            return `  ${datasetLabel}: ${this.formatOI(value)}`;
                                        }
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#94a3b8',
                                    font: { size: 11 },
                                    maxRotation: 0,
                                    minRotation: 0,
                                    callback: function (value, index) {
                                        // Show every Nth label to avoid crowding
                                        const totalLabels = this.chart.data.labels.length;
                                        const showEvery = Math.max(1, Math.ceil(totalLabels / 12));
                                        if (index % showEvery === 0) {
                                            const date = this.chart.data.labels[index];
                                            return new Date(date).toLocaleDateString('en-US', {
                                                month: 'short',
                                                day: 'numeric'
                                            });
                                        }
                                        return '';
                                    }
                                },
                                grid: {
                                    display: true,
                                    color: 'rgba(148, 163, 184, 0.08)',
                                    drawBorder: false
                                }
                            },
                            y: {
                                type: 'linear',
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Open Interest (USD)',
                                    color: '#3b82f6',
                                    font: { size: 11, weight: '600' }
                                },
                                ticks: {
                                    color: '#3b82f6',
                                    font: { size: 11 },
                                    callback: (value) => this.formatOI(value)
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.08)',
                                    drawBorder: false
                                }
                            },
                            y1: {
                                type: this.scaleType, // Dynamic scale type
                                position: 'right',
                                display: this.priceData.length > 0,
                                title: {
                                    display: true,
                                    text: 'BTC Price (USD)',
                                    color: '#f59e0b',
                                    font: { size: 11, weight: '600' }
                                },
                                ticks: {
                                    color: '#f59e0b',
                                    font: { size: 11 },
                                    callback: (value) => '$' + value.toLocaleString('en-US', { maximumFractionDigits: 0 })
                                },
                                grid: {
                                    display: false,
                                    drawBorder: false
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('❌ Error creating chart:', error);
                this.mainChart = null;
            }
        },

        // Render distribution chart (histogram) - ADAPTED FOR OPEN INTEREST
        renderDistributionChart() {
            const canvas = document.getElementById('openInterestDistributionChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            if (this.distributionChart) {
                this.distributionChart.destroy();
            }

            // Create histogram bins with safety checks
            const values = this.rawData.map(d => parseFloat(d.value));

            // Always create histogram, adjust bin count based on data
            let binCount = Math.min(20, Math.max(1, values.length));
            if (values.length === 1) binCount = 1;
            else if (values.length === 2) binCount = 2;

            const bins = this.createHistogramBins(values, binCount);

            this.distributionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: bins.map(b => b.label),
                    datasets: [{
                        label: 'Frequency',
                        data: bins.map(b => b.count),
                        backgroundColor: 'rgba(139, 92, 246, 0.6)',
                        borderColor: '#8b5cf6',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#94a3b8', maxRotation: 45 },
                            grid: { display: false }
                        },
                        y: {
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(148, 163, 184, 0.1)' }
                        }
                    }
                }
            });
        },

        // Render moving average chart - ADAPTED FOR OPEN INTEREST
        renderMAChart() {
            const canvas = document.getElementById('openInterestMAChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            if (this.maChart) {
                this.maChart.destroy();
            }

            // Prepare data with safety checks
            const sorted = [...this.rawData].sort((a, b) =>
                new Date(a.date) - new Date(b.date)
            );

            // Always render MA chart, but adapt based on available data
            const labels = sorted.map(d => d.date);
            const values = sorted.map(d => parseFloat(d.value));

            // Calculate MA data (will return null for insufficient periods)
            const ma7Data = this.calculateMAArray(values, Math.min(7, values.length));
            const ma30Data = this.calculateMAArray(values, Math.min(30, values.length));

            this.maChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Open Interest',
                            data: values,
                            borderColor: '#94a3b8',
                            backgroundColor: 'transparent',
                            borderWidth: 1,
                            tension: 0.4
                        },
                        {
                            label: '7-Day MA',
                            data: ma7Data,
                            borderColor: '#22c55e',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            tension: 0.4
                        },
                        {
                            label: '30-Day MA',
                            data: ma30Data,
                            borderColor: '#ef4444',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: { color: '#94a3b8', boxWidth: 20 }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                maxRotation: 45,
                                minRotation: 45,
                                callback: function (value, index) {
                                    const totalLabels = this.chart.data.labels.length;
                                    const showEvery = Math.ceil(totalLabels / 8);
                                    if (index % showEvery === 0) {
                                        const date = this.chart.data.labels[index];
                                        return new Date(date).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric'
                                        });
                                    }
                                    return '';
                                }
                            },
                            grid: { display: false }
                        },
                        y: {
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(148, 163, 184, 0.1)' }
                        }
                    }
                }
            });
        },

        // Utility: Get date range based on period (exact copy from CDD)
        getDateRange() {
            const endDate = new Date();
            const startDate = new Date();

            // Handle different period types
            if (this.globalPeriod === 'ytd') {
                // Year to date
                startDate.setMonth(0, 1); // January 1st of current year
            } else if (this.globalPeriod === 'all') {
                // All available data (1 year max for API stability)
                startDate.setDate(endDate.getDate() - 365);
            } else {
                // Find the selected time range
                const selectedRange = this.timeRanges.find(r => r.value === this.globalPeriod);
                let days = selectedRange ? selectedRange.days : 30;

                // Handle special cases
                if (this.globalPeriod === 'all') {
                    days = 365; // 1 year max for API stability
                }

                // Set start date to X days ago
                startDate.setDate(endDate.getDate() - days);
            }

            // Ensure we don't go too far back (API limits)
            const maxDaysBack = 365; // 1 year max for stability
            const minStartDate = new Date();
            minStartDate.setDate(endDate.getDate() - maxDaysBack);

            if (startDate < minStartDate) {
                startDate.setTime(minStartDate.getTime());
            }

            // Format dates properly (YYYY-MM-DD)
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            return {
                startDate: formatDate(startDate),
                endDate: formatDate(endDate)
            };
        },

        // Utility: Get time unit for chart
        getTimeUnit() {
            const unitMap = {
                '7d': 'day',
                '30d': 'day',
                '90d': 'week',
                '180d': 'week',
                '1y': 'month'
            };
            return unitMap[this.globalPeriod] || 'day';
        },

        // Utility: Calculate median
        calculateMedian(values) {
            const sorted = [...values].sort((a, b) => a - b);
            const mid = Math.floor(sorted.length / 2);
            return sorted.length % 2 === 0
                ? (sorted[mid - 1] + sorted[mid]) / 2
                : sorted[mid];
        },

        // Utility: Calculate standard deviation (safe for small datasets)
        calculateStdDev(values) {
            if (values.length === 0) return 0;
            if (values.length === 1) return 0; // No deviation with single value

            const avg = values.reduce((a, b) => a + b, 0) / values.length;
            const squareDiffs = values.map(v => Math.pow(v - avg, 2));
            const avgSquareDiff = squareDiffs.reduce((a, b) => a + b, 0) / squareDiffs.length;
            return Math.sqrt(avgSquareDiff);
        },

        // Utility: Calculate moving average (last N values, flexible)
        calculateMA(values, period) {
            if (values.length === 0) return 0;

            // Use available data if less than period
            const effectivePeriod = Math.min(period, values.length);
            const slice = values.slice(-effectivePeriod);
            return slice.reduce((a, b) => a + b, 0) / slice.length;
        },

        // Utility: Calculate MA array for all points (flexible for small datasets)
        calculateMAArray(values, period) {
            if (values.length === 0) return [];

            // For very small datasets, use available data
            const effectivePeriod = Math.min(period, values.length);

            return values.map((_, i) => {
                if (i < effectivePeriod - 1) {
                    // For early points, use available data (expanding window)
                    const slice = values.slice(0, i + 1);
                    return slice.reduce((a, b) => a + b, 0) / slice.length;
                }
                const slice = values.slice(i - effectivePeriod + 1, i + 1);
                return slice.reduce((a, b) => a + b, 0) / slice.length;
            });
        },

        // Utility: Create histogram bins (with safety checks)
        createHistogramBins(values, binCount) {
            if (!values || values.length === 0) {
                console.warn('⚠️ No values provided for histogram bins');
                return [];
            }

            const min = Math.min(...values);
            const max = Math.max(...values);

            // Handle case where all values are the same (min = max)
            if (min === max) {
                console.warn('⚠️ All values are the same, creating single bin');
                return [{
                    min: min,
                    max: max,
                    count: values.length,
                    label: this.formatFundingRate(min)
                }];
            }

            const binSize = (max - min) / binCount;

            // Safety check for binSize
            if (binSize <= 0) {
                console.warn('⚠️ Invalid bin size, using fallback');
                return [{
                    min: min,
                    max: max,
                    count: values.length,
                    label: this.formatFundingRate(min)
                }];
            }

            const bins = Array.from({ length: binCount }, (_, i) => ({
                min: min + (i * binSize),
                max: min + ((i + 1) * binSize),
                count: 0,
                label: ''
            }));

            values.forEach(v => {
                const binIndex = Math.min(
                    Math.floor((v - min) / binSize),
                    binCount - 1
                );
                if (bins[binIndex]) {
                    bins[binIndex].count++;
                }
            });

            bins.forEach(bin => {
                if (bin) {
                    bin.label = this.formatOI(bin.min);
                }
            });

            return bins;
        },

        // Utility: Create gradient
        createGradient(ctx, color) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, color.replace(')', ', 0.3)').replace('rgb', 'rgba'));
            gradient.addColorStop(1, color.replace(')', ', 0)').replace('rgb', 'rgba'));
            return gradient;
        },

        // Utility: Format Open Interest value
        formatOI(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            if (num >= 1e9) return (num / 1e9).toFixed(2) + 'B';
            if (num >= 1e6) return (num / 1e6).toFixed(2) + 'M';
            if (num >= 1e3) return (num / 1e3).toFixed(2) + 'K';
            return num.toFixed(2);
        },

        // Utility: Format price
        formatPrice(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            return '$' + num.toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },

        // Utility: Format price with USD label
        formatPriceUSD(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            return '$' + num.toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },

        // Utility: Format change percentage
        formatChange(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const sign = value >= 0 ? '+' : '';
            return `${sign}${value.toFixed(2)}%`;
        },

        // Utility: Format date
        formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        },

        // Utility: Get trend class (for Open Interest - higher is generally bullish)
        getTrendClass(value) {
            if (value > 0) return 'text-success'; // Positive change = bullish
            if (value < 0) return 'text-danger';  // Negative change = bearish
            return 'text-secondary';
        },

        // Utility: Get price trend class (for price - higher is bullish)
        getPriceTrendClass(value) {
            if (value > 0) return 'text-success';
            if (value < 0) return 'text-danger';
            return 'text-secondary';
        },

        // Utility: Get signal badge class
        getSignalBadgeClass() {
            const strengthMap = {
                'Strong': 'text-bg-danger',
                'Moderate': 'text-bg-warning',
                'Weak': 'text-bg-info',
                'Normal': 'text-bg-secondary'
            };
            return strengthMap[this.signalStrength] || 'text-bg-secondary';
        },

        // Utility: Get signal color class
        getSignalColorClass() {
            const colorMap = {
                'OI Sangat Tinggi': 'text-success',
                'OI Tinggi': 'text-success',
                'OI Sangat Rendah': 'text-danger',
                'OI Rendah': 'text-danger',
                'OI Normal': 'text-secondary',
                'Data Tunggal': 'text-secondary',
                'Data Tidak Cukup': 'text-secondary'
            };
            return colorMap[this.marketSignal] || 'text-secondary';
        },

        // Z-Score calculation and display
        currentZScore: 0,

        // Calculate current Z-Score
        calculateCurrentZScore() {
            if (this.rawData.length < 2) {
                this.currentZScore = 0;
                return;
            }

            const values = this.rawData.map(d => parseFloat(d.value));
            const mean = values.reduce((a, b) => a + b, 0) / values.length;
            const stdDev = this.calculateStdDev(values);
            
            if (stdDev === 0) {
                this.currentZScore = 0;
                return;
            }

            this.currentZScore = (this.currentOI - mean) / stdDev;
        },

        // Format Z-Score for display
        formatZScore(value) {
            if (value === null || value === undefined || isNaN(value)) return 'N/A';
            const num = parseFloat(value);
            const sign = num >= 0 ? '+' : '';
            return `${sign}${num.toFixed(2)}σ`;
        },

        // Get Z-Score badge class based on value
        getZScoreBadgeClass(value) {
            if (value === null || value === undefined || isNaN(value)) return 'text-bg-secondary';
            
            const absValue = Math.abs(value);
            if (absValue >= 3) return 'text-bg-danger';      // Extreme (>3σ)
            if (absValue >= 2) return 'text-bg-warning';     // High (>2σ)
            if (absValue >= 1) return 'text-bg-info';        // Moderate (>1σ)
            return 'text-bg-success';                         // Normal (<1σ)
        },



        // Utility: Show error
        showError(message) {
            console.error('Error:', message);
            // Could add toast notification here
        }
    };
}

console.log('✅ Open Interest Hybrid Controller loaded');

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Make sure Alpine.js can access the controller
    if (typeof window.Alpine !== 'undefined') {
        console.log('🔗 Registering Open Interest Hybrid controller with Alpine.js');
    }
});

// Make controller available globally for Alpine.js
window.basisTermStructureHybridController = basisTermStructureHybridController;