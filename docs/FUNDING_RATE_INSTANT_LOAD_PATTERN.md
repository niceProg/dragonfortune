# Funding Rate: Instant Load & Auto-Refresh Pattern

## 📋 Overview

Dokumentasi ini menjelaskan pattern optimisasi untuk **instant load** dan **auto-refresh** yang diimplementasikan di Funding Rate dashboard. Pattern ini dirancang untuk bisa di-reuse di page lain (Open Interest, Long-Short Ratio, Liquidations, Basis, Perp-Quarterly Spread).

## 🎯 Goals

1. **Instant Load**: Page load tanpa skeleton, data langsung muncul
2. **No Placeholder Flash**: Summary cards tidak pernah menampilkan "--" atau "Neutral"
3. **Silent Auto-Refresh**: Update data setiap 5 detik tanpa loading skeleton
4. **Smooth Transitions**: Switch filter tanpa delay atau flicker

## 🏗️ Architecture Pattern

### Core Principle: **Calculate First, Enhance Later**

**Key Insight**: Summary cards harus langsung terisi dari `rawData`, bukan menunggu analytics API.

```
┌─────────────────────────────────────────────────────────┐
│ 1. Fetch rawData dari API                              │
│    ↓                                                     │
│ 2. Calculate metrics IMMEDIATELY dari rawData          │
│    ↓ (instant, no delay)                                │
│ 3. Render chart + Update summary cards                  │
│    ↓ (all values populated, no "--" or null)           │
│ 4. Fetch analytics API (background, non-blocking)      │
│    ↓ (enhancement only, update if available)            │
└─────────────────────────────────────────────────────────┘
```

## 📝 Implementation Checklist

Gunakan checklist ini untuk implement di page lain:

### ✅ Phase 1: Initial Load (Instant)

#### 1.1 Controller Initialization
```javascript
async init() {
    // Initialize services
    this.apiService = new YourAPIService();
    this.chartManager = new ChartManager('yourChartId');
    
    // CRITICAL: Start with globalLoading = false (optimistic UI)
    this.globalLoading = false;
    this.analyticsLoading = false;
    
    // Step 1: Try load from cache (instant)
    const cacheLoaded = this.loadFromCache();
    if (cacheLoaded) {
        // Render chart with cached data
        if (this.chartManager && this.rawData.length > 0) {
            this.chartManager.renderChart(this.rawData, this.priceData);
        }
        // Background fetch fresh data
        this.loadData(true).catch(err => console.warn('Background fetch failed:', err));
    } else {
        // No cache: optimistic UI (no skeleton, show placeholders)
        await this.loadData(false);
    }
    
    // Step 2: Start auto-refresh AFTER initial load
    this.startAutoRefresh();
}
```

#### 1.2 Load Data Pattern
```javascript
async loadData(isAutoRefresh = false) {
    // Guard: Skip if already loading
    if (this.isLoading) return;
    this.isLoading = true;
    
    // Only show skeleton on initial load (hard refresh)
    const isInitialLoad = this.rawData.length === 0;
    const shouldShowLoading = isInitialLoad && !isAutoRefresh;
    
    if (shouldShowLoading) {
        this.globalLoading = true;
    }
    
    try {
        // Fetch data from API
        const data = await this.apiService.fetchHistory({...});
        
        if (data === null) return; // Request cancelled
        
        // CRITICAL: Set rawData first
        this.rawData = data;
        
        // Extract price data if needed
        this.priceData = data.filter(d => d.price).map(d => ({ date: d.date, price: d.price }));
        
        // ⚡ CRITICAL: Calculate metrics IMMEDIATELY from rawData
        // This ensures summary cards are NEVER empty (no "--" or null)
        if (this.rawData.length > 0) {
            this.calculateMetrics();
            console.log('✅ Metrics calculated from rawData (instant, no delay)');
        }
        
        this.dataLoaded = true;
        
        // Render chart IMMEDIATELY (before analytics fetch)
        if (this.chartManager && this.rawData.length > 0) {
            this.chartManager.renderChart(this.rawData, this.priceData);
        }
        
        // Fetch analytics in background (non-blocking, enhancement only)
        if (!isInitialLoad || !isAutoRefresh) {
            this.fetchAnalyticsData(isAutoRefresh).catch(err => {
                console.warn('⚠️ Analytics fetch failed:', err);
            });
        }
        
        // Save to cache
        this.saveToCache();
        
    } catch (error) {
        if (error.name === 'AbortError') return; // Expected during auto-refresh
        console.error('❌ Error loading data:', error);
    } finally {
        this.isLoading = false;
        if (shouldShowLoading) {
            this.globalLoading = false;
        }
    }
}
```

#### 1.3 Calculate Metrics Pattern
```javascript
calculateMetrics() {
    if (this.rawData.length === 0) {
        console.warn('⚠️ No data for metrics calculation');
        return;
    }
    
    const sorted = [...this.rawData].sort((a, b) => 
        new Date(a.date) - new Date(b.date)
    );
    
    const values = sorted.map(d => parseFloat(d.value));
    
    // CRITICAL: Always calculate from rawData FIRST (instant, no delay)
    // Analytics API will update these later if available (non-blocking enhancement)
    // This ensures summary cards are NEVER empty (no "--" or null values)
    
    // Current metrics
    this.currentValue = values[values.length - 1] || 0;
    const previousValue = values[values.length - 2] || this.currentValue;
    this.change = (this.currentValue - previousValue) * 10000; // Basis points or appropriate unit
    
    // Statistical metrics (ALWAYS calculate, don't wait for analytics)
    this.avgValue = values.reduce((a, b) => a + b, 0) / values.length;
    this.maxValue = Math.max(...values);
    this.minValue = Math.min(...values);
    this.medianValue = YourUtils.calculateMedian(values);
    
    // Peak date
    const peakIndex = values.indexOf(this.maxValue);
    this.peakDate = YourUtils.formatDate(sorted[peakIndex]?.date || sorted[0].date);
    
    // Moving averages
    this.ma7 = YourUtils.calculateMA(values, 7);
    this.ma30 = YourUtils.calculateMA(values, 30);
    
    // Price metrics (if applicable)
    if (this.priceData.length > 0) {
        this.currentPrice = this.priceData[this.priceData.length - 1].price;
        const yesterdayPrice = this.priceData[this.priceData.length - 2]?.price || this.currentPrice;
        this.priceChange = ((this.currentPrice - yesterdayPrice) / yesterdayPrice) * 100;
    }
    
    // Volatility (fallback if analytics doesn't provide)
    if (values.length >= 2) {
        const stdDev = YourUtils.calculateStdDev(values);
        if (this.volatility === null || this.volatility === undefined) {
            this.volatility = stdDev; // Use stdDev as volatility fallback
        }
    }
    
    console.log('📊 Metrics calculated:', {
        current: this.currentValue,
        avg: this.avgValue,
        max: this.maxValue,
        min: this.minValue
    });
}
```

### ✅ Phase 1.5: Analytics-Only Pattern (Jika Analytics Hanya dari API)

**Use Case**: Jika analytics tidak bisa dihitung dari rawData (harus dari API), gunakan pattern ini untuk instant load.

#### 1.5.1 Parallel Fetch Pattern
```javascript
async loadData(isAutoRefresh = false) {
    if (this.isLoading) return;
    this.isLoading = true;
    
    const isInitialLoad = this.rawData.length === 0;
    const shouldShowLoading = isInitialLoad && !isAutoRefresh;
    
    if (shouldShowLoading) {
        this.globalLoading = true;
    }
    
    try {
        // ⚡ CRITICAL: Fetch history AND analytics PARALLEL (not sequential)
        // This ensures both finish faster, analytics appears as soon as API responds
        const [historyData, analyticsData] = await Promise.allSettled([
            this.apiService.fetchHistory({...}),
            this.apiService.fetchAnalytics({...}) // Fetch parallel, not sequential
        ]);
        
        // Handle history data
        if (historyData.status === 'fulfilled' && historyData.value) {
            this.rawData = historyData.value;
            this.dataLoaded = true;
            
            // Render chart immediately
            if (this.chartManager && this.rawData.length > 0) {
                this.chartManager.renderChart(this.rawData, this.priceData);
            }
        }
        
        // Handle analytics data (from API only, no calculation)
        if (analyticsData.status === 'fulfilled' && analyticsData.value) {
            this.analyticsData = analyticsData.value;
            this.mapAnalyticsToState(); // Map analytics to summary cards
            console.log('✅ Analytics loaded from API (parallel fetch)');
        } else {
            // Analytics failed, use defaults or cached values
            console.warn('⚠️ Analytics fetch failed, using defaults');
            this.setDefaultAnalytics();
        }
        
        // Save to cache (includes analytics)
        this.saveToCache();
        
    } catch (error) {
        if (error.name === 'AbortError') return;
        console.error('❌ Error loading data:', error);
    } finally {
        this.isLoading = false;
        if (shouldShowLoading) {
            this.globalLoading = false;
        }
    }
}
```

#### 1.5.2 Cache Analytics Pattern
```javascript
loadFromCache() {
    try {
        const cacheKey = this.getCacheKey();
        const cached = localStorage.getItem(cacheKey);
        if (cached) {
            const data = JSON.parse(cached);
            const cacheAge = Date.now() - data.timestamp;
            const maxAge = 5 * 60 * 1000; // 5 minutes
            
            if (cacheAge < maxAge && data.rawData && data.rawData.length > 0) {
                // Load raw data
                this.rawData = data.rawData;
                this.priceData = data.priceData || [];
                
                // ⚡ CRITICAL: Load analytics from cache (instant, no API call)
                this.analyticsData = data.analyticsData || null;
                
                if (this.analyticsData) {
                    // Map analytics to summary cards immediately
                    this.mapAnalyticsToState();
                    console.log('✅ Analytics loaded from cache (instant)');
                } else {
                    // No cached analytics, use defaults
                    this.setDefaultAnalytics();
                }
                
                this.dataLoaded = true;
                this.summaryDataLoaded = true;
                
                // IMPORTANT: Hide loading skeletons immediately after cache loaded
                this.globalLoading = false;
                this.analyticsLoading = false;
                
                console.log('✅ Cache loaded:', {
                    records: this.rawData.length,
                    analyticsCached: !!this.analyticsData,
                    age: Math.round(cacheAge / 1000) + 's'
                });
                return true;
            } else {
                localStorage.removeItem(cacheKey);
            }
        }
    } catch (error) {
        console.warn('⚠️ Cache load error:', error);
    }
    return false;
}

saveToCache() {
    try {
        const cacheKey = this.getCacheKey();
        const data = {
            timestamp: Date.now(),
            rawData: this.rawData,
            priceData: this.priceData,
            analyticsData: this.analyticsData, // ⚡ CRITICAL: Save analytics too
            // ... other summary card values if needed
        };
        localStorage.setItem(cacheKey, JSON.stringify(data));
        console.log('💾 Data saved to cache (including analytics):', cacheKey);
    } catch (error) {
        console.warn('⚠️ Cache save error:', error);
    }
}
```

#### 1.5.3 Default Values Pattern (Fallback)
```javascript
setDefaultAnalytics() {
    // Set safe defaults if analytics API fails or not available
    // This ensures summary cards never show "--" or null
    this.avgValue = 0;
    this.maxValue = 0;
    this.minValue = 0;
    this.volatility = 0;
    this.marketSignal = 'Neutral';
    this.signalStrength = 'Normal';
    
    // OR: Calculate from rawData if possible (fallback calculation)
    if (this.rawData.length > 0) {
        const values = this.rawData.map(d => parseFloat(d.value));
        this.avgValue = values.reduce((a, b) => a + b, 0) / values.length;
        this.maxValue = Math.max(...values);
        this.minValue = Math.min(...values);
        console.log('✅ Default values calculated from rawData (fallback)');
    }
}

mapAnalyticsToState() {
    if (!this.analyticsData) {
        this.setDefaultAnalytics();
        return;
    }
    
    // Map analytics API response to UI state
    this.avgValue = this.analyticsData.average ?? this.avgValue;
    this.maxValue = this.analyticsData.max ?? this.maxValue;
    this.minValue = this.analyticsData.min ?? this.minValue;
    this.volatility = this.analyticsData.volatility ?? this.volatility;
    this.marketSignal = this.analyticsData.bias ? 
        (this.analyticsData.bias === 'long_pays_short' ? 'Long' : 'Short') : 
        'Neutral';
    this.signalStrength = this.analyticsData.biasStrength ? 
        `${(this.analyticsData.biasStrength * 100).toFixed(2)}%` : 
        'Normal';
}
```

### ✅ Phase 2: Auto-Refresh (Silent)

#### 2.1 Auto-Refresh Setup
```javascript
startAutoRefresh() {
    this.stopAutoRefresh(); // Clear any existing interval
    
    const intervalMs = 5000; // 5 seconds
    
    this.refreshInterval = setInterval(() => {
        // Safety checks
        if (document.hidden) return; // Don't refresh hidden tabs
        if (this.globalLoading) return; // Skip if showing skeleton
        if (this.isLoading) return; // Skip if already loading (prevent race condition)
        if (this.errorCount >= this.maxErrors) {
            console.error('❌ Too many errors, stopping auto refresh');
            this.stopAutoRefresh();
            return;
        }
        
        console.log('🔄 Auto-refresh triggered');
        
        // CRITICAL: Pass isAutoRefresh=true to prevent loading skeleton
        this.loadData(true).catch(err => {
            if (err.name !== 'AbortError') {
                console.warn('⚠️ Auto-refresh error:', err);
            }
        }); // Silent update - no skeleton shown
        
        // Also refresh analytics data independently (silent)
        if (!this.analyticsLoading) {
            this.fetchAnalyticsData(true).catch(err => {
                console.warn('⚠️ Analytics refresh failed:', err);
            });
        }
    }, intervalMs);
    
    console.log('✅ Auto-refresh started (5 second interval)');
}

stopAutoRefresh() {
    if (this.refreshInterval) {
        clearInterval(this.refreshInterval);
        this.refreshInterval = null;
    }
}
```

#### 2.2 Analytics Fetch Pattern (Background Enhancement)
```javascript
async fetchAnalyticsData(isAutoRefresh = false) {
    if (this.analyticsLoading) return;
    
    // CRITICAL: Don't show skeleton during auto-refresh
    if (isAutoRefresh) {
        this.analyticsLoading = false; // Don't show skeleton during auto-refresh
    } else if (this.rawData.length === 0) {
        this.analyticsLoading = true; // Only for initial load without data
    } else {
        this.analyticsLoading = false; // Data already exists, no skeleton needed
    }
    
    try {
        const analyticsData = await this.apiService.fetchAnalytics({
            symbol: this.selectedSymbol,
            exchange: this.selectedExchange,
            interval: this.selectedInterval,
            limit: 1000
        });
        
        if (analyticsData === null) return; // Request cancelled
        
        // Map analytics data to UI state
        // NOTE: Only update if analytics provides better/more accurate values
        // Don't overwrite calculated metrics unless analytics has better data
        
        if (analyticsData.average !== null && analyticsData.average !== undefined) {
            this.avgValue = analyticsData.average; // Override calculated value
        }
        if (analyticsData.max !== null && analyticsData.max !== undefined) {
            this.maxValue = analyticsData.max; // Override calculated value
        }
        if (analyticsData.min !== null && analyticsData.min !== undefined) {
            this.minValue = analyticsData.min; // Override calculated value
        }
        if (analyticsData.volatility !== null && analyticsData.volatility !== undefined) {
            this.volatility = analyticsData.volatility; // Override calculated value
        }
        
        // Update market signal (only from analytics, not calculated)
        if (analyticsData.bias) {
            this.marketSignal = analyticsData.bias === 'long_pays_short' ? 'Long' : 
                               analyticsData.bias === 'short_pays_long' ? 'Short' : 'Neutral';
        }
        if (analyticsData.biasStrength !== null && analyticsData.biasStrength !== undefined) {
            this.signalStrength = `${(analyticsData.biasStrength * 100).toFixed(2)}%`;
        }
        
        // Save to cache after analytics loaded (if not auto-refresh)
        if (!isAutoRefresh) {
            this.saveToCache();
        }
        
    } catch (error) {
        console.error('❌ Error loading analytics data:', error);
        // Don't reset values if analytics fails - keep calculated values
    } finally {
        this.analyticsLoading = false;
    }
}
```

### ✅ Phase 3: Filter Changes (Smooth Transitions)

#### 3.1 Filter Change Pattern
```javascript
setTimeRange(range) {
    if (this.globalPeriod === range) return;
    console.log('🔄 Setting time range to:', range);
    this.globalPeriod = range;
    // CRITICAL: Pass true to prevent skeleton during filter change
    this.loadData(true).catch(err => console.warn('Load data failed:', err));
}

setChartInterval(interval) {
    if (this.selectedInterval === interval) return;
    console.log('🔄 Setting chart interval to:', interval);
    this.selectedInterval = interval;
    // CRITICAL: Pass true to prevent skeleton during filter change
    this.loadData(true).catch(err => console.warn('Load data failed:', err));
}

updateExchange() {
    console.log('🔄 Updating exchange to:', this.selectedExchange);
    // CRITICAL: Pass true to prevent skeleton during filter change
    this.loadData(true).catch(err => console.warn('Load data failed:', err));
}
```

**Key Point**: Semua filter change methods harus pass `isAutoRefresh = true` untuk silent update tanpa skeleton.

### ✅ Phase 4: Cache Strategy (Optional but Recommended)

#### 4.1 Simple Cache Pattern
```javascript
getCacheKey() {
    const exchange = YourUtils.capitalizeExchange(this.selectedExchange);
    return `your_dashboard_v2_${this.selectedSymbol}_${exchange}_${this.selectedInterval}_${this.globalPeriod}`;
}

loadFromCache() {
    try {
        const cacheKey = this.getCacheKey();
        const cached = localStorage.getItem(cacheKey);
        if (cached) {
            const data = JSON.parse(cached);
            const cacheAge = Date.now() - data.timestamp;
            const maxAge = 5 * 60 * 1000; // 5 minutes
            
            if (cacheAge < maxAge && data.rawData && data.rawData.length > 0) {
                this.rawData = data.rawData;
                this.priceData = data.priceData || [];
                
                // Load summary cards data (CRITICAL: all at once to prevent flash)
                this.currentValue = data.currentValue ?? null;
                this.avgValue = data.avgValue ?? null;
                this.maxValue = data.maxValue ?? null;
                this.minValue = data.minValue ?? null;
                this.marketSignal = data.marketSignal || 'Neutral';
                this.signalStrength = data.signalStrength || 'Normal';
                this.currentPrice = data.currentPrice ?? null;
                
                this.dataLoaded = true;
                this.summaryDataLoaded = true;
                
                // IMPORTANT: Hide loading skeletons immediately after cache loaded
                this.globalLoading = false;
                this.analyticsLoading = false;
                
                console.log('✅ Cache loaded:', {
                    records: this.rawData.length,
                    age: Math.round(cacheAge / 1000) + 's'
                });
                return true;
            } else {
                localStorage.removeItem(cacheKey);
            }
        }
    } catch (error) {
        console.warn('⚠️ Cache load error:', error);
    }
    return false;
}

saveToCache() {
    try {
        const cacheKey = this.getCacheKey();
        const data = {
            timestamp: Date.now(),
            rawData: this.rawData,
            priceData: this.priceData,
            currentValue: this.currentValue,
            avgValue: this.avgValue,
            maxValue: this.maxValue,
            minValue: this.minValue,
            volatility: this.volatility,
            marketSignal: this.marketSignal,
            signalStrength: this.signalStrength,
            currentPrice: this.currentPrice
        };
        localStorage.setItem(cacheKey, JSON.stringify(data));
        console.log('💾 Data saved to cache:', cacheKey);
    } catch (error) {
        console.warn('⚠️ Cache save error:', error);
    }
}
```

## 🔑 Key Principles

### 1. **Calculate First, Enhance Later**
- ✅ Always calculate metrics from `rawData` FIRST
- ✅ Analytics API is enhancement only (non-blocking)
- ✅ Summary cards NEVER wait for analytics API

### 2. **Silent Updates**
- ✅ Auto-refresh: `isAutoRefresh = true` → no skeleton
- ✅ Filter changes: `isAutoRefresh = true` → no skeleton
- ✅ Only show skeleton on initial hard refresh

### 3. **Instant Metrics**
- ✅ `calculateMetrics()` called IMMEDIATELY after `rawData` is set
- ✅ All summary cards populated from calculated values
- ✅ Analytics API updates values later if available

### 4. **Error Handling**
- ✅ AbortError is expected (don't log as error)
- ✅ Analytics failure doesn't break UI (keep calculated values)
- ✅ Auto-refresh stops after 3 errors (prevent spam)

## 📊 Performance Targets

### Initial Load
- **Target**: < 500ms to first render
- **Method**: Cache load (instant) or progressive loading (limit 100)

### Filter Switch
- **Target**: < 200ms to chart render
- **Method**: Silent update (no skeleton), instant metrics calculation

### Auto-Refresh
- **Interval**: 5 seconds
- **Method**: Silent update (no skeleton), background fetch

## 🚀 Implementation for Other Pages

### Checklist for Open Interest / Long-Short / Liquidations / Basis / Perp

#### Option A: Analytics Dapat Dihitung dari RawData (seperti Funding Rate)
1. ✅ **Controller Structure**
   - [ ] Add `isLoading` flag
   - [ ] Add `refreshInterval` for auto-refresh
   - [ ] Add `errorCount` and `maxErrors` for error handling

2. ✅ **Initial Load**
   - [ ] `globalLoading = false` initially (optimistic UI)
   - [ ] Try `loadFromCache()` first
   - [ ] Call `calculateMetrics()` IMMEDIATELY after `rawData` set
   - [ ] Render chart before analytics fetch

3. ✅ **Metrics Calculation**
   - [ ] Always calculate from `rawData` (don't wait for analytics)
   - [ ] Calculate avg, max, min, median from rawData
   - [ ] Analytics API only for enhancement (market signal, bias)

4. ✅ **Auto-Refresh**
   - [ ] Implement `startAutoRefresh()` with 5s interval
   - [ ] Pass `isAutoRefresh = true` to `loadData()`
   - [ ] Safety checks (hidden tab, isLoading, errorCount)

5. ✅ **Filter Changes**
   - [ ] All filter methods pass `isAutoRefresh = true`
   - [ ] No skeleton during filter changes
   - [ ] Instant metrics calculation after filter change

6. ✅ **Cache**
   - [ ] Implement `loadFromCache()` and `saveToCache()`
   - [ ] Cache key includes all filter parameters
   - [ ] Cache age check (5 minutes max)

#### Option B: Analytics Hanya dari API (seperti Open Interest)
1. ✅ **Controller Structure**
   - [ ] Add `isLoading` flag
   - [ ] Add `refreshInterval` for auto-refresh
   - [ ] Add `analyticsData` state variable
   - [ ] Add `setDefaultAnalytics()` method

2. ✅ **Initial Load**
   - [ ] `globalLoading = false` initially (optimistic UI)
   - [ ] Try `loadFromCache()` first (includes analytics)
   - [ ] **Fetch history AND analytics PARALLEL** (Promise.allSettled)
   - [ ] Render chart immediately after history loads
   - [ ] Map analytics to state when API responds

3. ✅ **Parallel Fetch Pattern**
   - [ ] Use `Promise.allSettled()` for history + analytics
   - [ ] Don't await analytics - render chart first
   - [ ] Analytics updates summary cards when ready

4. ✅ **Cache Analytics**
   - [ ] Save `analyticsData` to cache
   - [ ] Load `analyticsData` from cache (instant)
   - [ ] Map analytics to state from cache immediately

5. ✅ **Default Values**
   - [ ] Implement `setDefaultAnalytics()` for fallback
   - [ ] Use defaults if analytics API fails
   - [ ] OR: Calculate from rawData if possible (fallback)

6. ✅ **Auto-Refresh**
   - [ ] Fetch analytics parallel with history refresh
   - [ ] Pass `isAutoRefresh = true` (silent update)
   - [ ] Safety checks (hidden tab, isLoading, errorCount)

7. ✅ **Filter Changes**
   - [ ] All filter methods pass `isAutoRefresh = true`
   - [ ] Fetch analytics parallel with history
   - [ ] No skeleton during filter changes

## 🎯 Expected Results

### Before (Without Pattern)
- ❌ Skeleton loading on initial load
- ❌ "--" or "Neutral" flash on filter change
- ❌ Summary cards wait for analytics API
- ❌ No auto-refresh

### After (With Pattern)
- ✅ Instant load (cache or progressive loading)
- ✅ No placeholder flash (instant metrics calculation)
- ✅ Summary cards always populated
- ✅ Silent auto-refresh every 5 seconds
- ✅ Smooth filter transitions

## 📝 Example: Open Interest Implementation

```javascript
// In open-interest/controller.js

async loadData(isAutoRefresh = false) {
    if (this.isLoading) return;
    this.isLoading = true;
    
    const isInitialLoad = this.rawData.length === 0;
    const shouldShowLoading = isInitialLoad && !isAutoRefresh;
    
    if (shouldShowLoading) {
        this.globalLoading = true;
    }
    
    try {
        const data = await this.apiService.fetchHistory({...});
        if (data === null) return;
        
        this.rawData = data;
        
        // ⚡ CRITICAL: Calculate metrics IMMEDIATELY
        if (this.rawData.length > 0) {
            this.calculateMetrics(); // Instant, no delay
        }
        
        // Render chart
        if (this.chartManager && this.rawData.length > 0) {
            this.chartManager.renderChart(this.rawData, this.priceData);
        }
        
        // Analytics in background (non-blocking)
        this.fetchAnalyticsData(isAutoRefresh).catch(err => {
            console.warn('⚠️ Analytics fetch failed:', err);
        });
        
    } catch (error) {
        if (error.name === 'AbortError') return;
        console.error('❌ Error loading data:', error);
    } finally {
        this.isLoading = false;
        if (shouldShowLoading) {
            this.globalLoading = false;
        }
    }
}

calculateMetrics() {
    if (this.rawData.length === 0) return;
    
    const values = this.rawData.map(d => parseFloat(d.value));
    
    // ALWAYS calculate from rawData (instant)
    this.currentOpenInterest = values[values.length - 1] || 0;
    this.avgOpenInterest = values.reduce((a, b) => a + b, 0) / values.length;
    this.maxOpenInterest = Math.max(...values);
    this.minOpenInterest = Math.min(...values);
    
    // ... other metrics
}
```

## 🔍 Testing Checklist

- [ ] Initial load: No skeleton, data appears instantly
- [ ] Filter change: No "--" or null flash, smooth transition
- [ ] Auto-refresh: Data updates silently every 5 seconds
- [ ] Analytics failure: Summary cards still populated (calculated values)
- [ ] Tab hidden: Auto-refresh stops
- [ ] Tab visible: Auto-refresh restarts
- [ ] Error handling: Auto-refresh stops after 3 errors

## 📚 Related Files

- `public/js/funding-rate/controller.js` - Reference implementation
- `public/js/funding-rate/api-service.js` - API service pattern
- `public/js/funding-rate/chart-manager.js` - Chart rendering pattern

## 🎓 Summary

Pattern ini memastikan:
1. **Instant Load**: Data muncul segera tanpa skeleton
2. **No Placeholder Flash**: Summary cards selalu terisi (calculated from rawData)
3. **Silent Auto-Refresh**: Update otomatis tanpa loading indicator
4. **Smooth Transitions**: Filter changes tanpa delay

**Key Takeaway**: Always calculate metrics from `rawData` FIRST, then enhance with analytics API if available. Never wait for analytics API to populate summary cards.

