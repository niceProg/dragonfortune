/**
 * OnChain Cache Helper
 * Provides caching and debouncing for API requests
 */

window.OnChainCacheHelper = {
    // Cache storage
    cache: new Map(),
    
    // Cache TTL (5 minutes)
    cacheTTL: 5 * 60 * 1000,
    
    // Debounce timers
    debounceTimers: new Map(),
    
    // Generate cache key
    generateCacheKey(endpoint, params) {
        const paramString = params ? new URLSearchParams(params).toString() : '';
        return `${endpoint}?${paramString}`;
    },
    
    // Check if cache entry is valid
    isCacheValid(entry) {
        return entry && (Date.now() - entry.timestamp) < this.cacheTTL;
    },
    
    // Get from cache
    getFromCache(endpoint, params) {
        const key = this.generateCacheKey(endpoint, params);
        const entry = this.cache.get(key);
        
        if (this.isCacheValid(entry)) {
            console.log(`📦 Cache hit for: ${key}`);
            return entry.data;
        }
        
        return null;
    },
    
    // Set to cache
    setToCache(endpoint, params, data) {
        const key = this.generateCacheKey(endpoint, params);
        this.cache.set(key, {
            data: data,
            timestamp: Date.now()
        });
        
        console.log(`💾 Cached: ${key}`);
        
        // Clean old entries periodically
        this.cleanOldEntries();
    },
    
    // Clean old cache entries
    cleanOldEntries() {
        const now = Date.now();
        for (const [key, entry] of this.cache.entries()) {
            if ((now - entry.timestamp) > this.cacheTTL) {
                this.cache.delete(key);
                console.log(`🗑️ Removed expired cache: ${key}`);
            }
        }
    },
    
    // Clear all cache
    clearCache() {
        this.cache.clear();
        console.log('🧹 Cache cleared');
    },
    
    // Debounced fetch
    debouncedFetch(key, fetchFunction, delay = 300) {
        return new Promise((resolve, reject) => {
            // Clear existing timer
            if (this.debounceTimers.has(key)) {
                clearTimeout(this.debounceTimers.get(key));
            }
            
            // Set new timer
            const timer = setTimeout(async () => {
                try {
                    const result = await fetchFunction();
                    resolve(result);
                } catch (error) {
                    reject(error);
                } finally {
                    this.debounceTimers.delete(key);
                }
            }, delay);
            
            this.debounceTimers.set(key, timer);
        });
    },
    
    // Enhanced fetch with caching and debouncing
    async cachedFetch(endpoint, params, fetchFunction, options = {}) {
        const {
            useCache = true,
            useDebounce = true,
            debounceDelay = 300,
            forceRefresh = false
        } = options;
        
        // Check cache first (unless force refresh)
        if (useCache && !forceRefresh) {
            const cached = this.getFromCache(endpoint, params);
            if (cached) {
                return cached;
            }
        }
        
        // Create debounce key
        const debounceKey = this.generateCacheKey(endpoint, params);
        
        // Use debounced fetch if enabled
        const fetchPromise = useDebounce 
            ? this.debouncedFetch(debounceKey, fetchFunction, debounceDelay)
            : fetchFunction();
        
        try {
            const result = await fetchPromise;
            
            // Cache the result
            if (useCache) {
                this.setToCache(endpoint, params, result);
            }
            
            return result;
        } catch (error) {
            console.error(`❌ Cached fetch error for ${endpoint}:`, error);
            throw error;
        }
    },
    
    // Batch fetch multiple endpoints
    async batchFetch(requests, options = {}) {
        const {
            useCache = true,
            maxConcurrent = 5
        } = options;
        
        // Process requests in batches
        const results = [];
        for (let i = 0; i < requests.length; i += maxConcurrent) {
            const batch = requests.slice(i, i + maxConcurrent);
            const batchPromises = batch.map(async (request) => {
                const { endpoint, params, fetchFunction } = request;
                
                try {
                    return await this.cachedFetch(endpoint, params, fetchFunction, {
                        useCache,
                        useDebounce: false // Disable debounce for batch requests
                    });
                } catch (error) {
                    console.error(`❌ Batch fetch error for ${endpoint}:`, error);
                    return null;
                }
            });
            
            const batchResults = await Promise.all(batchPromises);
            results.push(...batchResults);
        }
        
        return results;
    },
    
    // Preload data for better UX
    async preloadData(endpoint, params, fetchFunction) {
        try {
            await this.cachedFetch(endpoint, params, fetchFunction, {
                useCache: true,
                useDebounce: false
            });
            console.log(`🚀 Preloaded: ${endpoint}`);
        } catch (error) {
            console.warn(`⚠️ Preload failed for ${endpoint}:`, error);
        }
    },
    
    // Get cache statistics
    getCacheStats() {
        const now = Date.now();
        let validEntries = 0;
        let expiredEntries = 0;
        
        for (const [key, entry] of this.cache.entries()) {
            if (this.isCacheValid(entry)) {
                validEntries++;
            } else {
                expiredEntries++;
            }
        }
        
        return {
            totalEntries: this.cache.size,
            validEntries,
            expiredEntries,
            hitRate: this.hitCount / (this.hitCount + this.missCount) || 0
        };
    },
    
    // Initialize cache helper
    init() {
        // Clean cache periodically (every 10 minutes)
        setInterval(() => {
            this.cleanOldEntries();
        }, 10 * 60 * 1000);
        
        console.log('💾 OnChain Cache Helper initialized');
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    OnChainCacheHelper.init();
});