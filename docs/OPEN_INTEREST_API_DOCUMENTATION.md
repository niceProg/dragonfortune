# Dokumentasi API Open Interest - Backend API v2

## 1. Analytics Open Interest

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/open-interest/analytics
```

### Deskripsi Singkat
Mengambil data analisis komprehensif Open Interest termasuk tren, volatilitas, dan insights untuk memahami perubahan posisi pasar.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `exchange` | string | Tidak | - | Nama exchange (Binance, Bybit, OKX, BitMEX, dll.) |
| `interval` | string | Tidak | 5m | Interval waktu (1m, 5m, 15m, 1h) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |
| `with_price` | boolean | Tidak | true | Menyertakan data harga dalam response |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/open-interest/analytics?symbol=BTCUSDT&exchange=Binance&interval=5m&limit=5&with_price=true"
```

### Contoh Response Body
```json
[
  {
    "current_price": "0E-8",
    "exchange": "Binance",
    "insights": {
      "data_points": 8200,
      "max_oi": "9059647758.00000000",
      "min_oi": "7915300148.00000000",
      "volatility_level": "low_volatility"
    },
    "open_interest": "8421994649.944155951220",
    "trend": "stable"
  },
  {
    "current_price": "0E-8",
    "exchange": "Bybit",
    "insights": {
      "data_points": 8202,
      "max_oi": "6304361492.00000000",
      "min_oi": "5354768394.00000000",
      "volatility_level": "low_volatility"
    },
    "open_interest": "5642343545.085366983663",
    "trend": "stable"
  },
  {
    "current_price": "0E-8",
    "exchange": "CoinEx",
    "insights": {
      "data_points": 3151,
      "max_oi": "153609475.00000000",
      "min_oi": "119715929.00000000",
      "volatility_level": "moderate_volatility"
    },
    "open_interest": "139299818.433536337671",
    "trend": "stable"
  }
]
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan panel analytics Open Interest pada dashboard. Data ini memberikan insights tentang tren OI, tingkat volatilitas, dan perubahan posisi pasar yang membantu trader memahami momentum dan kekuatan tren.

---

## 2. Open Interest per Exchange

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/open-interest/exchange
```

### Deskripsi Singkat
Mengambil data Open Interest terkini per exchange untuk perbandingan dominasi pasar dan analisis distribusi posisi.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTC | Simbol coin (BTC, ETH, SOL, BNB) |
| `exchange` | string | Ya | Binance | Nama exchange (Binance, Bybit, OKX, dll.) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |
| `pivot` | boolean | Tidak | false | Format data pivot |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/open-interest/exchange?symbol=BTC&exchange=Binance&limit=100&pivot=false"
```

### Contoh Response Body
```json
[
  {
    "ts": 1704067200000,
    "exchange": "Binance",
    "symbol_coin": "BTC",
    "oi_usd": 1500000000.0
  },
  {
    "ts": 1704063600000,
    "exchange": "Bybit",
    "symbol_coin": "BTC",
    "oi_usd": 800000000.0
  }
]
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan tabel perbandingan Open Interest antar exchange. Data ini memungkinkan trader melihat dominasi exchange dan distribusi posisi di pasar derivatives.

---

## 3. Historical Open Interest

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/open-interest/history
```

### Deskripsi Singkat
Mengambil data historis Open Interest untuk analisis tren dan pembuatan chart time series dengan overlay harga.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `exchange` | string | Ya | Binance | Nama exchange (Binance, Bybit) |
| `interval` | string | Tidak | 5m | Interval waktu (1m, 5m, 15m, 1h) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |
| `with_price` | boolean | Tidak | true | Menyertakan data harga dalam response |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/open-interest/history?symbol=BTCUSDT&exchange=Binance&interval=15m&limit=5&with_price=true"
```

### Contoh Response Body
```json
[
  {
    "exchange": "Binance",
    "oi_usd": "8230487962.44420000",
    "pair": "BTCUSDT",
    "price": "0E-8",
    "ts": 1762208100000
  },
  {
    "exchange": "Binance",
    "oi_usd": "8230487962.44420000",
    "pair": "BTCUSDT",
    "price": "0E-8",
    "ts": 1762208100000
  },
  {
    "exchange": "Binance",
    "oi_usd": "8214321984.00000000",
    "pair": "BTCUSDT",
    "price": "0E-8",
    "ts": 1762208040000
  },
  {
    "exchange": "Binance",
    "oi_usd": "8214321984.00000000",
    "pair": "BTCUSDT",
    "price": "0E-8",
    "ts": 1762207980000
  },
  {
    "exchange": "Binance",
    "oi_usd": "8214321984.00000000",
    "pair": "BTCUSDT",
    "price": "0E-8",
    "ts": 1762207920000
  }
]
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan chart historis Open Interest pada dashboard. Data time series ini memungkinkan trader melihat korelasi antara perubahan OI dan pergerakan harga untuk analisis momentum pasar.

---

## 4. Overview Open Interest

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/open-interest/overview
```

### Deskripsi Singkat
Mengambil ringkasan statistik Open Interest secara keseluruhan termasuk total OI, jumlah exchange, dan breakdown per exchange.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `unit` | string | Tidak | usd | Unit pengukuran (usd, btc) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/open-interest/overview?symbol=BTCUSDT&unit=usd&limit=500"
```

### Contoh Response Body
```json
{
  "summary": {
    "total_oi": 2500000000.0,
    "exchange_count": 5,
    "data_points": 500,
    "avg_oi_per_exchange": 500000000.0
  },
  "top_symbols": [
    {
      "exchange": "Binance",
      "avg_oi": 1500000000.0,
      "max_oi": 1800000000.0,
      "min_oi": 1200000000.0,
      "data_points": 100
    },
    {
      "exchange": "Bybit",
      "avg_oi": 800000000.0,
      "max_oi": 900000000.0,
      "min_oi": 700000000.0,
      "data_points": 100
    }
  ],
  "total_oi": 2500000000.0
}
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan summary card dan statistik overview pada dashboard Open Interest. Data ini memberikan gambaran keseluruhan pasar derivatives dan distribusi posisi antar exchange untuk analisis market structure.