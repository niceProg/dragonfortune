/**
 * Shared State Management for On-Chain Metrics
 * Provides cross-page data sharing and navigation helpers
 */

window.OnChainSharedState = {
    // Shared filters
    filters: {
        selectedAsset: 'BTC',
        selectedWindow: 'day',
        selectedLimit: 200,
        selectedExchange: ''
    },
    
    // Event listeners for state changes
    listeners: new Map(),
    
    // Set shared filter value
    setFilter(key, value) {
        if (this.filters.hasOwnProperty(key)) {
            this.filters[key] = value;
            this.notifyListeners(key, value);
            this.saveToStorage();
        }
    },
    
    // Get shared filter value
    getFilter(key) {
        return this.filters[key];
    },
    
    // Get all filters
    getAllFilters() {
        return { ...this.filters };
    },
    
    // Subscribe to filter changes
    subscribe(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, new Set());
        }
        this.listeners.get(key).add(callback);
        
        // Return unsubscribe function
        return () => {
            this.listeners.get(key)?.delete(callback);
        };
    },
    
    // Notify listeners of changes
    notifyListeners(key, value) {
        const callbacks = this.listeners.get(key);
        if (callbacks) {
            callbacks.forEach(callback => {
                try {
                    callback(value, key);
                } catch (error) {
                    console.error('Error in shared state listener:', error);
                }
            });
        }
    },
    
    // Save to localStorage
    saveToStorage() {
        try {
            localStorage.setItem('onchain-shared-filters', JSON.stringify(this.filters));
        } catch (error) {
            console.warn('Failed to save shared state to localStorage:', error);
        }
    },
    
    // Load from localStorage
    loadFromStorage() {
        try {
            const stored = localStorage.getItem('onchain-shared-filters');
            if (stored) {
                const parsed = JSON.parse(stored);
                Object.assign(this.filters, parsed);
            }
        } catch (error) {
            console.warn('Failed to load shared state from localStorage:', error);
        }
    },
    
    // Initialize shared state
    init() {
        this.loadFromStorage();
        console.log('🔗 OnChain Shared State initialized:', this.filters);
    },
    
    // Navigation helpers
    navigation: {
        // Navigate to related page with current filters
        goToEthereum() {
            const params = new URLSearchParams({
                window: OnChainSharedState.filters.selectedWindow,
                limit: OnChainSharedState.filters.selectedLimit.toString()
            });
            window.location.href = `/onchain-ethereum?${params}`;
        },
        
        goToExchange() {
            const params = new URLSearchParams({
                asset: OnChainSharedState.filters.selectedAsset,
                window: OnChainSharedState.filters.selectedWindow,
                limit: OnChainSharedState.filters.selectedLimit.toString()
            });
            if (OnChainSharedState.filters.selectedExchange) {
                params.append('exchange', OnChainSharedState.filters.selectedExchange);
            }
            window.location.href = `/onchain-exchange?${params}`;
        },
        
        goToMiningPrice() {
            const params = new URLSearchParams({
                asset: OnChainSharedState.filters.selectedAsset,
                window: OnChainSharedState.filters.selectedWindow,
                limit: OnChainSharedState.filters.selectedLimit.toString()
            });
            window.location.href = `/onchain-mining-price?${params}`;
        },
        
        // Get navigation suggestions based on current page
        getSuggestions(currentPage) {
            const suggestions = [];
            
            if (currentPage !== 'ethereum') {
                suggestions.push({
                    title: 'Ethereum Network Metrics',
                    description: 'View gas fees and staking data',
                    action: () => this.goToEthereum(),
                    icon: '⚡'
                });
            }
            
            if (currentPage !== 'exchange') {
                suggestions.push({
                    title: 'Exchange Reserves',
                    description: `View ${OnChainSharedState.filters.selectedAsset} reserves and market indicators`,
                    action: () => this.goToExchange(),
                    icon: '🏦'
                });
            }
            
            if (currentPage !== 'mining-price') {
                suggestions.push({
                    title: 'Mining & Price Analytics',
                    description: `Analyze ${OnChainSharedState.filters.selectedAsset} MPI and price trends`,
                    action: () => this.goToMiningPrice(),
                    icon: '⛏️'
                });
            }
            
            return suggestions;
        }
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    OnChainSharedState.init();
});

// Alpine.js global helper
document.addEventListener('alpine:init', () => {
    Alpine.store('onchainShared', {
        // Get current filters
        get filters() {
            return OnChainSharedState.getAllFilters();
        },
        
        // Set filter and notify other pages
        setFilter(key, value) {
            OnChainSharedState.setFilter(key, value);
        },
        
        // Subscribe to changes
        subscribe(key, callback) {
            return OnChainSharedState.subscribe(key, callback);
        },
        
        // Navigation helpers
        navigation: OnChainSharedState.navigation
    });
});