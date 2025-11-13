# Long Short Ratio - Tooltip Debugging Log

## Issue
Tooltip menampilkan "Invalid Date" ketika hover chart node.

## Root Cause Analysis

### Problem 1: Labels menggunakan `.toLocaleDateString()` (hanya tanggal, tanpa waktu)
**Before:**
```javascript
const labels = sorted.map(d => new Date(d.time || d.ts).toLocaleDateString());
// Result: ["11/2/2025", "11/2/2025", ...]
// Parsing: new Date("11/2/2025") → defaults to 00:00:00
```

**Fix:** Keep full timestamp (milliseconds)
```javascript
const labels = sorted.map(d => d.time || d.ts);
// Result: [1762051500000, 1762050600000, ...]
```

### Problem 2: Invalid Date parsing in tooltip
Chart.js tooltip menerima `items[0].label` yang mungkin dalam format string atau number, sehingga perlu handling yang robust.

## Solution Implemented

### 1. Chart Labels - Keep Full Timestamp
**Files Modified:**
- `public/js/long-short-ratio/chart-manager.js`

**Changes:**
```javascript
// Line 58 (renderMainChart)
const labels = sorted.map(d => d.time || d.ts);  // ✅ Full timestamp

// Line 195 (renderComparisonChart)
const labels = sorted.map(d => d.time || d.ts);  // ✅ Full timestamp

// Line 286 (renderNetPositionChart)
const labels = sorted.map(d => d.time || d.ts);  // ✅ Full timestamp
```

### 2. Tooltip Callback - Robust Parsing with Logging
**Main Chart Tooltip (lines 377-413):**
```javascript
callbacks: {
    title: (items) => {
        try {
            // Get raw label value (should be timestamp in milliseconds)
            const rawLabel = items[0].label;
            console.log('📅 [Main Chart Tooltip] Raw label:', rawLabel, 'Type:', typeof rawLabel);
            
            // Parse as timestamp (handle both number and string)
            const timestamp = typeof rawLabel === 'number' ? rawLabel : parseInt(rawLabel, 10);
            
            if (isNaN(timestamp)) {
                console.error('❌ Invalid timestamp:', rawLabel);
                return 'Invalid Date';
            }
            
            const date = new Date(timestamp);
            console.log('📅 Parsed date:', date.toISOString());
            
            // Format date and time in Jakarta timezone (UTC+7)
            const dateStr = date.toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                timeZone: 'Asia/Jakarta'
            });
            const timeStr = date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
                timeZone: 'Asia/Jakarta'
            });
            return `${dateStr}, ${timeStr} WIB`;
        } catch (error) {
            console.error('❌ Error formatting tooltip title:', error);
            return 'Error';
        }
    }
}
```

**Comparison Chart Tooltip (lines 529-565):**
- Same implementation with different log prefix: `[Comparison Chart Tooltip]`

### 3. X-Axis Display - Format on Render
**Main Chart X-Axis (lines 413-425):**
```javascript
x: {
    ticks: {
        callback: function (value, index) {
            const totalLabels = this.chart.data.labels.length;
            const showEvery = Math.max(1, Math.ceil(totalLabels / 10));
            if (index % showEvery === 0) {
                const date = new Date(this.chart.data.labels[index]);  // Convert on display
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            }
            return '';
        }
    }
}
```

## Expected Console Logs

### When hovering chart node:

**Successful Parse:**
```
📅 [Main Chart Tooltip] Raw label: 1762051500000 Type: number
📅 Parsed date: 2025-11-02T02:45:00.000Z
```

**If error occurs:**
```
❌ Invalid timestamp: undefined
```
or
```
❌ Error formatting tooltip title: TypeError: ...
```

## Expected Tooltip Output

**Format:**
```
Sat, Nov 2, 2025, 09:45 WIB
```

**Breakdown:**
- Date: `Sat, Nov 2, 2025`
- Time: `09:45` (24-hour format, converted to Jakarta/WIB timezone)
- Timezone: `WIB` (Waktu Indonesia Barat = UTC+7)

## Data Flow Verification

### 1. API Response
```json
{
  "ts": 1762051500000,
  "ls_ratio_accounts": "2.07000000"
}
```

### 2. Chart Labels Array
```javascript
labels: [1762051500000, 1762050600000, 1762049700000, ...]
```

### 3. Tooltip receives
```javascript
items[0].label: 1762051500000  // (number or string)
```

### 4. Parse and format
```javascript
new Date(1762051500000) → "2025-11-02T02:45:00.000Z" (UTC)
→ Convert to Jakarta time: 09:45 WIB
→ Display: "Sat, Nov 2, 2025, 09:45 WIB"
```

## Testing Checklist

- [ ] Hover over main chart node
- [ ] Check console for logs: `📅 [Main Chart Tooltip] Raw label: ...`
- [ ] Verify tooltip shows: `Sat, Nov 2, 2025, 09:45 WIB`
- [ ] Hover over comparison chart node
- [ ] Check console for logs: `📅 [Comparison Chart Tooltip] Raw label: ...`
- [ ] Verify tooltip shows correct time (not 00:00)
- [ ] Check different time ranges (1D, 7D, 1M)
- [ ] Verify no "Invalid Date" appears

## Copy This When Reporting

**Please paste the following from your browser console:**

1. When you hover a chart node, copy all logs that start with:
   - `📅 [Main Chart Tooltip]`
   - `📅 [Comparison Chart Tooltip]`
   - `❌` (any error logs)

2. Take a screenshot of:
   - The tooltip when hovering
   - The browser console showing the logs

3. Report:
   - What the tooltip displays (exact text)
   - Any error messages
   - Browser name and version

## Key Changes Summary

1. ✅ **Labels store full timestamps** (milliseconds) instead of date strings
2. ✅ **Tooltip parsing handles both number and string** types
3. ✅ **Added extensive error handling** with try-catch
4. ✅ **Added console logging** for debugging (rawLabel, type, parsed date)
5. ✅ **Jakarta timezone conversion** using `timeZone: 'Asia/Jakarta'`
6. ✅ **24-hour time format** using `hour12: false`
7. ✅ **X-axis still displays readable dates** via callback (not affected)

## Files Modified

- `public/js/long-short-ratio/chart-manager.js`
  - Line 58: Main chart labels (keep timestamp)
  - Line 195: Comparison chart labels (keep timestamp)
  - Line 286: Net position chart labels (keep timestamp)
  - Lines 377-413: Main chart tooltip callback (with logging)
  - Lines 529-565: Comparison chart tooltip callback (with logging)
  - Lines 413-425: Main chart X-axis callback (format on display)

