# Verifikasi Data Basis & Term Structure Dashboard

## 1. Basis History Chart - Response JSON Structure

### API Endpoint
```
GET /api/basis/history?exchange=Binance&spot_pair=BTC/USDT&futures_symbol=BTCUSDT&interval=1h&limit=5000
```

### Response JSON Structure (dari dokumentasi)
```json
[
  {
    "ts": 1704067200000,           // Timestamp dalam milliseconds
    "basis_abs": 125.50,           // Basis absolute (USD)
    "basis_annualized": 0.0285,    // Basis annualized (%)
    "spot_price": 45000.0,         // Spot price (USD)
    "futures_price": 45125.50      // Futures price (USD)
  }
]
```

### Field Mapping di Code
✅ **Sudah Sesuai:**
- `ts` → `ts` (dengan auto-detect seconds/milliseconds conversion)
- `basis_abs` → `basisAbs`
- `basis_annualized` → `basisAnnualized`
- `spot_price` → `spotPrice`
- `futures_price` → `futuresPrice`

### Field yang Digunakan untuk Chart
- **Basis Line Chart:** `basisAbs` (dari field `basis_abs`)
- **Spot Price Line:** `spotPrice` (dari field `spot_price`)
- **Futures Price Line:** `futuresPrice` (dari field `futures_price`)

**File:** `public/js/basis/chart-manager.js` line 81-84:
```javascript
const spotPrices = sorted.map(d => parseFloat(d.spotPrice || 0));
const futuresPrices = sorted.map(d => parseFloat(d.futuresPrice || 0));
const basisValues = sorted.map(d => parseFloat(d.basisAbs || 0));
```

## 2. Date Range & Interval Filtering

### Date Range Filtering
✅ **Sudah Diimplementasikan - Client-Side Filtering**
- Filter dilakukan di `api-service.js` method `filterByDateRange()`
- Menggunakan timestamp `ts` untuk compare dengan `startDate` dan `endDate`
- **File:** `public/js/basis/api-service.js` line 206-213

```javascript
filterByDateRange(data, startDate, endDate) {
    const startTs = startDate.getTime();
    const endTs = endDate.getTime();
    return data.filter(item => {
        const itemTs = item.ts;
        return itemTs >= startTs && itemTs <= endTs;
    });
}
```

### Interval Filtering
⚠️ **TIDAK Ada Client-Side Interval Filtering**
- Backend API menerima parameter `interval` (5m, 15m, 1h, 4h)
- Response JSON **tidak memiliki field** yang menunjukkan interval dari record
- Berbeda dengan funding-rate yang punya field `margin_type` untuk client-side filtering
- **Asumsi:** Backend sudah melakukan interval filtering dengan benar

**Rekomendasi:** Jika backend terkadang mengembalikan mixed intervals (seperti funding-rate), perlu tambahkan client-side filtering. Tapi karena response tidak punya field interval, perlu verifikasi dengan curl test terlebih dahulu.

**File:** `public/js/basis/api-service.js` line 68-131

## 3. Term Structure Chart - Response JSON Structure

### API Endpoint
```
GET /api/basis/term-structure?symbol=BTC&exchange=Binance&limit=1000
```

### Response JSON Structure (dari dokumentasi)
```json
{
  "expiries": ["perpetual", "weekly", "monthly", "quarterly"],
  "basis_curve": [
    {
      "expiry": "perpetual",
      "basis": 0.0,                // Basis (USD)
      "basis_annualized": 0.0,     // Basis annualized (%)
      "volatility": 0.0025
    },
    {
      "expiry": "weekly",
      "basis": 25.50,
      "basis_annualized": 0.0285,
      "volatility": 0.0045
    }
  ],
  "term_structure": {
    "perpetual": {
      "basis": 0.0,
      "basis_annualized": 0.0,
      "data_points": 100
    }
  }
}
```

### Field Mapping di Code
✅ **Sudah Sesuai:**
- `basis_curve` → Array of objects
- `basis_curve[].expiry` → Labels untuk chart
- `basis_curve[].basis` → Bar chart data (USD)
- `basis_curve[].basis_annualized` → Line chart data (%)

### Field yang Digunakan untuk Chart
- **Bar Chart:** `basis` (dari `basis_curve[].basis`)
- **Line Chart:** `basis_annualized` (dari `basis_curve[].basis_annualized`)
- **Labels:** `expiry` (dari `basis_curve[].expiry`)

**File:** `public/js/basis/chart-manager.js` line 309-312:
```javascript
const basisCurve = termStructureData.basis_curve || [];
const labels = basisCurve.map(item => item.expiry || 'Unknown');
const basisValues = basisCurve.map(item => parseFloat(item.basis || 0));
const basisAnnualizedValues = basisCurve.map(item => parseFloat(item.basis_annualized || 0));
```

## 4. Summary - Status Implementasi

### ✅ Sudah Benar:
1. **Field Mapping:** Semua field JSON response sudah di-map dengan benar
2. **Date Range Filtering:** Client-side filtering berdasarkan timestamp
3. **Timestamp Handling:** Auto-detect seconds/milliseconds conversion
4. **Term Structure Data:** Semua field dari `basis_curve` digunakan dengan benar

### ⚠️ Perlu Verifikasi:
1. **Interval Filtering:** 
   - Backend seharusnya sudah filter berdasarkan parameter `interval`
   - Jika backend mengembalikan mixed intervals (seperti funding-rate), perlu tambahkan client-side filtering
   - **Action Required:** Test dengan curl untuk verify apakah interval filtering sudah benar

2. **Data Consistency:**
   - Pastikan backend mengembalikan data sesuai dengan interval yang diminta
   - Jika tidak, perlu tambahkan logic client-side filtering (tapi response tidak punya field untuk filtering ini)

## 5. Recommended Verification Steps

### Test 1: Verify Interval Filtering
```bash
# Test dengan interval berbeda
curl "https://test.dragonfortune.ai/api/basis/history?exchange=Binance&spot_pair=BTC/USDT&futures_symbol=BTCUSDT&interval=1h&limit=100"
curl "https://test.dragonfortune.ai/api/basis/history?exchange=Binance&spot_pair=BTC/USDT&futures_symbol=BTCUSDT&interval=4h&limit=100"

# Compare hasil - apakah data points berbeda? Apakah timestamp spacing sesuai interval?
```

### Test 2: Verify Date Range Filtering
```bash
# Test dengan limit besar dan date range filter di frontend
# Frontend akan filter client-side berdasarkan timestamp
```

### Test 3: Verify Term Structure
```bash
curl "https://test.dragonfortune.ai/api/basis/term-structure?symbol=BTC&exchange=Binance&limit=100"
# Verify response structure sesuai dokumentasi
```

## 6. Kesimpulan

**Basis History:**
- ✅ Field mapping sudah benar sesuai dokumentasi
- ✅ Date range filtering sudah diimplementasikan (client-side)
- ⚠️ Interval filtering bergantung pada backend (tidak ada client-side filtering karena response tidak punya field interval)

**Term Structure:**
- ✅ Field mapping sudah benar sesuai dokumentasi
- ✅ Semua field yang diperlukan sudah digunakan
- ✅ Tidak ada filtering yang diperlukan (snapshot data)

**Rekomendasi:**
- Verifikasi dengan curl test apakah interval filtering di backend sudah benar
- Jika backend tidak konsisten, pertimbangkan untuk request semua data dengan limit besar dan filter client-side berdasarkan timestamp spacing (tetapi ini lebih kompleks karena perlu detect interval dari spacing timestamp)

