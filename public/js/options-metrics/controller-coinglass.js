/**
 * Options Metrics Controller (Minimal)
 * Blueprint - No auto-refresh or chart rendering
 * Sections use iframe embeds or will be customized later
 */

export function createOpenInterestController() {
    return {
        initialized: false,

        // State (for UI compatibility)
        selectedSymbol: 'BTC',
        selectedUnit: 'usd',
        selectedInterval: '1h',
        selectedTimeRange: '1d',

        // Supported symbols
        supportedSymbols: ['BTC', 'ETH', 'SOL', 'XRP', 'HYPE', 'BNB', 'DOGE'],

        // Time ranges
        timeRanges: [
            { label: '1D', value: '1d', days: 1 },
            { label: '1W', value: '1w', days: 7 },
            { label: '1M', value: '1m', days: 30 },
            { label: '3M', value: '3m', days: 90 },
            { label: '1Y', value: '1y', days: 365 },
            { label: 'ALL', value: 'all', days: 1095 }
        ],

        // Chart intervals
        chartIntervals: [
            { label: '1M', value: '1m' },
            { label: '3M', value: '3m' },
            { label: '5M', value: '5m' },
            { label: '15M', value: '15m' },
            { label: '30M', value: '30m' },
            { label: '1H', value: '1h' },
            { label: '4H', value: '4h' },
            { label: '6H', value: '6h' },
            { label: '8H', value: '8h' },
            { label: '12H', value: '12h' },
            { label: '1D', value: '1d' },
            { label: '1W', value: '1w' }
        ],

        // Dummy state for UI compatibility
        isLoading: false,
        refreshEnabled: false, // Disabled - no auto-refresh
        rawData: [],
        currentOI: null,
        minOI: null,
        maxOI: null,
        avgOI: null,
        oiChange: null,
        oiVolatility: null,
        momentum: null,

        // Minimal init - no API calls, no chart rendering
        async init() {
            if (this.initialized) return;
            this.initialized = true;
            console.log('✅ Options Metrics Controller initialized (minimal mode)');
        },

        // Stub methods - no API calls, no chart rendering
        async loadData() {
            // No-op: Options Metrics uses iframe embeds
        },

        startAutoRefresh() {
            // No-op: Auto-refresh disabled
        },

        stopAutoRefresh() {
            // No-op: No refresh interval to stop
        },

        renderChart() {
            // No-op: Charts use iframe embeds
        },

        updateSymbol(value) {
            this.selectedSymbol = value;
        },

        updateUnit(value) {
            this.selectedUnit = value;
        },

        updateInterval(value) {
            this.selectedInterval = value;
        },

        updateTimeRange(value) {
            this.selectedTimeRange = value;
        },

        setChartInterval(value) {
            this.selectedInterval = value;
        },

        setTimeRange(value) {
            this.selectedTimeRange = value;
        },

        instantLoadData() {
            // No-op: No data to load
        },

        formatOI(value) {
            if (!value) return '0';
            if (value >= 1e12) return (value / 1e12).toFixed(2) + 'T';
            if (value >= 1e9) return (value / 1e9).toFixed(2) + 'B';
            if (value >= 1e6) return (value / 1e6).toFixed(2) + 'M';
            if (value >= 1e3) return (value / 1e3).toFixed(2) + 'K';
            return value.toFixed(2);
        },

        formatChange(value) {
            if (value === null || value === undefined) return '';
            const sign = value >= 0 ? '+' : '';
            return `${sign}${value.toFixed(2)}%`;
        },

        formatPercentage(value) {
            if (value === null || value === undefined) return '';
            return `${value.toFixed(2)}%`;
        }
    };
}
