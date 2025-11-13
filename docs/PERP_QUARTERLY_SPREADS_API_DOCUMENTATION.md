# Dokumentasi API Perpetual-Quarterly Spreads - Backend API v2

## 1. Analytics Perp-Quarterly Spreads

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/perp-quarterly/analytics
```

### Deskripsi Singkat
Mengambil data analisis komprehensif spread antara kontrak perpetual dan quarterly futures termasuk volatilitas spread, level arbitrase, dan insights untuk trading opportunities.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTC | Simbol base trading (BTC, ETH) |
| `exchange` | string | Ya | Bybit | Nama exchange (Bybit, Deribit) |
| `interval` | string | Tidak | 5m | Interval waktu (5m, 15m, 1h, 4h) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/perp-quarterly/analytics?symbol=BTC&exchange=Bybit&interval=5m&limit=500"
```

### Contoh Response Body
```json
[
  {
    "spread_analysis": {
      "avg_spread": 25.50,
      "min_spread": -15.20,
      "max_spread": 85.30,
      "spread_volatility": 12.45,
      "avg_spread_bps": 0.57,
      "spread_level": "moderate"
    },
    "trend": "widening",
    "insights": {
      "data_points": 500,
      "base_symbol": "BTC"
    },
    "arbitrage_opportunities": []
  }
]
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan panel analytics spread pada dashboard perp-quarterly. Data ini memberikan insights tentang peluang arbitrase, volatilitas spread, dan kondisi pasar futures untuk strategi trading calendar spreads.

---

## 2. Historical Perp-Quarterly Spreads

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/perp-quarterly/history
```

### Deskripsi Singkat
Mengambil data historis spread antara kontrak perpetual dan quarterly futures untuk analisis tren dan pembuatan chart time series.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTC | Simbol base trading (BTC, ETH) |
| `exchange` | string | Ya | Bybit | Nama exchange (Bybit, Deribit) |
| `interval` | string | Tidak | 5m | Interval waktu (5m, 15m, 1h, 4h) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/perp-quarterly/history?symbol=BTC&exchange=Bybit&interval=15m&limit=200"
```

### Contoh Response Body
```json
[
  {
    "ts": 1704067200000,
    "perp_price": 45000.0,
    "quarterly_price": 45025.50,
    "spread": 25.50,
    "spread_bps": 0.57
  },
  {
    "ts": 1704066300000,
    "perp_price": 44980.0,
    "quarterly_price": 44995.20,
    "spread": 15.20,
    "spread_bps": 0.34
  }
]
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan chart historis spread perp-quarterly pada dashboard. Data time series ini memungkinkan trader melihat evolusi spread dari waktu ke waktu dan mengidentifikasi pola-pola arbitrase yang berulang.