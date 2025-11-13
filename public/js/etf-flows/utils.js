/**
 * ETF Flows Utilities
 * Helper functions for data processing and formatting
 */

export const EtfFlowsUtils = {

    /**
     * Format flow value for display (in millions/billions)
     */
    formatFlow(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const num = parseFloat(value);
        const absNum = Math.abs(num);
        
        let formatted;
        if (absNum >= 1e9) {
            formatted = '$' + (num / 1e9).toFixed(2) + 'B';
        } else if (absNum >= 1e6) {
            formatted = '$' + (num / 1e6).toFixed(2) + 'M';
        } else if (absNum >= 1e3) {
            formatted = '$' + (num / 1e3).toFixed(2) + 'K';
        } else {
            formatted = '$' + num.toLocaleString('en-US', { 
                minimumFractionDigits: 0, 
                maximumFractionDigits: 0 
            });
        }
        
        return formatted;
    },

    /**
     * Format percentage change
     */
    formatPercentage(value) {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        const sign = value >= 0 ? '+' : '';
        return `${sign}${value.toFixed(2)}%`;
    },

    /**
     * Get color based on flow direction
     */
    getFlowColor(value) {
        if (value > 0) return '#10b981'; // green for inflow
        if (value < 0) return '#ef4444'; // red for outflow
        return '#6b7280'; // gray for neutral
    },

    /**
     * Format date for display
     */
    formatDate(timestamp) {
        if (!timestamp) return 'N/A';
        
        const date = new Date(timestamp);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    /**
     * Format datetime for display
     */
    formatDateTime(timestamp) {
        if (!timestamp) return 'N/A';
        
        const date = new Date(timestamp);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Calculate total inflows
     */
    calculateTotalInflows(flowData) {
        if (!flowData || !Array.isArray(flowData)) return 0;
        
        return flowData.reduce((total, item) => {
            const flow = item.flow_usd || 0;
            return total + (flow > 0 ? flow : 0);
        }, 0);
    },

    /**
     * Calculate total outflows
     */
    calculateTotalOutflows(flowData) {
        if (!flowData || !Array.isArray(flowData)) return 0;
        
        return flowData.reduce((total, item) => {
            const flow = item.flow_usd || 0;
            return total + (flow < 0 ? Math.abs(flow) : 0);
        }, 0);
    },

    /**
     * Calculate net flow
     */
    calculateNetFlow(flowData) {
        if (!flowData || !Array.isArray(flowData)) return 0;
        
        return flowData.reduce((total, item) => {
            return total + (item.flow_usd || 0);
        }, 0);
    },

    /**
     * Get recent trend (last 7 days)
     */
    getRecentTrend(flowData, days = 7) {
        if (!flowData || flowData.length < days) return 0;
        
        const recentData = flowData.slice(-days);
        const totalFlow = recentData.reduce((sum, item) => sum + (item.flow_usd || 0), 0);
        
        return totalFlow / days;
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};