# Dokumentasi API Funding Rate - Backend API v2

## 1. Bias Funding Rate

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/funding-rate/bias
```

### Deskripsi Singkat
Mengambil data analisis bias pasar berdasarkan funding rate untuk menentukan arah sentimen pasar (bullish/bearish).

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |
| `with_price` | boolean | Tidak | true | Menyertakan data harga dalam response |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/funding-rate/bias?symbol=BTCUSDT&limit=100&with_price=true"
```

### Contoh Response Body
```json
{
  "bias": "long",
  "strength": 0.0125,
  "avg_funding_close": 0.000125,
  "direction": "bullish",
  "exchange": "Binance"
}
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan indikator bias pasar pada dashboard funding rate. Data ini membantu trader memahami sentimen pasar secara keseluruhan dan mengidentifikasi apakah posisi long atau short yang dominan di pasar.

---

## 2. Funding Rate per Exchange

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/funding-rate/exchanges
```

### Deskripsi Singkat
Mengambil data funding rate terkini dari berbagai exchange untuk perbandingan dan analisis arbitrase.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |
| `margin_type` | string | Tidak | cross | Tipe margin (cross, isolated) |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/funding-rate/exchanges?symbol=BTCUSDT&limit=50&margin_type=cross"
```

### Contoh Response Body
```json
[
  {
    "exchange": "Binance",
    "symbol": "BTCUSDT",
    "funding_rate": 0.0001,
    "next_funding_time": 1704096000000,
    "margin_type": "cross"
  },
  {
    "exchange": "Bybit",
    "symbol": "BTCUSDT",
    "funding_rate": 0.00015,
    "next_funding_time": 1704096000000,
    "margin_type": "cross"
  }
]
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan tabel perbandingan funding rate antar exchange pada halaman funding rate. Data ini memungkinkan trader untuk melihat perbedaan funding rate dan mengidentifikasi peluang arbitrase.

---

## 3. Analytics Funding Rate

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/funding-rate/analytics
```

### Deskripsi Singkat
Mengambil data analisis komprehensif funding rate termasuk statistik, volatilitas, dan insights untuk analisis mendalam.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `exchange` | string | Ya | Binance | Nama exchange (Binance, Bybit) |
| `interval` | string | Tidak | 8h | Interval funding rate (1h, 8h) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/funding-rate/analytics?symbol=BTCUSDT&exchange=Binance&interval=8h&limit=500"
```

### Contoh Response Body
```json
{
  "summary": {
    "average": 0.000125,
    "max": 0.0005,
    "min": -0.0002,
    "volatility": 0.00015,
    "data_points": 500,
    "margin_type": "cross"
  },
  "bias": {
    "direction": "long",
    "strength": 1.25
  },
  "latest_timestamp": 1704067200000
}
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan panel analytics dan insights pada dashboard funding rate. Data ini memberikan gambaran statistik lengkap tentang performa funding rate dan membantu dalam pengambilan keputusan trading.

---

## 4. Historical Funding Rate

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/funding-rate/history
```

### Deskripsi Singkat
Mengambil data historis funding rate untuk analisis tren dan pembuatan chart time series.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `exchange` | string | Ya | Binance | Nama exchange (Binance, Bybit) |
| `interval` | string | Tidak | 8h | Interval funding rate (1h, 8h) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/funding-rate/history?symbol=BTCUSDT&exchange=Binance&interval=8h&limit=200"
```

### Contoh Response Body
```json
[
  {
    "ts": 1704067200000,
    "exchange": "Binance",
    "pair": "BTCUSDT",
    "funding_rate": 0.0001,
    "interval": "8h",
    "symbol_price": 45000.0
  },
  {
    "ts": 1704038400000,
    "exchange": "Binance",
    "pair": "BTCUSDT",
    "funding_rate": 0.00015,
    "interval": "8h",
    "symbol_price": 44800.0
  }
]
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan chart historis funding rate pada dashboard. Data time series ini memungkinkan trader untuk melihat tren funding rate dari waktu ke waktu dan mengidentifikasi pola-pola tertentu.

---

## 5. Aggregated Funding Rate

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/funding-rate/aggregate
```

### Deskripsi Singkat
Mengambil data funding rate yang telah diagregasi dari multiple exchange dengan perhitungan weighted average dan statistik lanjutan.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `interval` | string | Tidak | 8h | Interval funding rate (1h, 8h) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/funding-rate/aggregate?symbol=BTCUSDT&interval=8h&limit=100"
```

### Contoh Response Body
```json
[
  {
    "symbol": "BTCUSDT",
    "interval": "8h",
    "ts": 1704067200000,
    "start_ts": 1704038400000,
    "end_ts": 1704067200000,
    "exchange_count": 5,
    "datapoints": 5,
    "simple_average": 0.000125,
    "weighted_average": 0.00013,
    "weight_basis": "volume",
    "weights_sum": 1.0,
    "median": 0.0001,
    "stdev": 0.00005,
    "min_rate": 0.00008,
    "max_rate": 0.00018,
    "positive_exchanges": 4,
    "negative_exchanges": 1,
    "positive_share": 0.8,
    "outlier_removed": false,
    "notes": "Normal funding cycle"
  }
]
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan data funding rate yang telah diagregasi dari semua exchange. Data ini memberikan gambaran funding rate pasar secara keseluruhan dan digunakan untuk analisis market-wide sentiment.

---

## 6. Funding Rate Heatmap

### Endpoint URL
```
GET https://test.dragonfortune.ai/api/funding-rate/heatmap
```

### Deskripsi Singkat
Mengambil data untuk membuat heatmap funding rate berdasarkan exchange dan waktu untuk visualisasi pola funding rate.

### Parameter Request
| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `symbol` | string | Ya | BTCUSDT | Pasangan trading (BTCUSDT, ETHUSDT, SOLUSDT, BNBUSDT) |
| `interval` | string | Tidak | 8h | Interval funding rate (1h, 8h) |
| `limit` | integer | Tidak | 1000 | Jumlah maksimal record yang dikembalikan |

### Contoh cURL Request
```bash
curl -X GET "https://test.dragonfortune.ai/api/funding-rate/heatmap?symbol=BTCUSDT&interval=8h&limit=500"
```

### Contoh Response Body
```json
{
  "heatmap_matrix": {
    "Binance": {
      "2024-01-01": {
        "00": {
          "avg_funding": 0.0001,
          "min_funding": 0.00008,
          "max_funding": 0.00012,
          "data_points": 3,
          "margin_type": "cross"
        },
        "08": {
          "avg_funding": 0.00015,
          "min_funding": 0.00012,
          "max_funding": 0.00018,
          "data_points": 3,
          "margin_type": "cross"
        }
      }
    }
  },
  "time_buckets": ["2024-01-01", "2024-01-02"],
  "exchange_buckets": ["Binance", "Bybit"],
  "margin_types": ["cross", "isolated"],
  "summary": {
    "total_exchanges": 2,
    "total_time_periods": 2,
    "total_data_points": 12
  }
}
```

### Kegunaan / Tujuan Endpoint
Endpoint ini digunakan untuk menampilkan heatmap funding rate pada dashboard yang menunjukkan pola funding rate berdasarkan exchange dan waktu. Visualisasi ini membantu trader mengidentifikasi pola temporal dan perbedaan antar exchange secara visual.