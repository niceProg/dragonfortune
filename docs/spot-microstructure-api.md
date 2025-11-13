# Spot Microstructure API (External)

Reference for the external Spot Microstructure service hosted at `https://test.dragonfortune.ai`. Keep this file handy so the frontend can integrate with the new backend without visiting the public Swagger UI.

## Base URL

- **Production/Test:** `https://test.dragonfortune.ai`
- All endpoints documented below live under `/api/spot-microstructure`.

> **Authentication:** The Swagger spec does not list auth requirements and the endpoints respond to unsigned `GET` requests in the test environment. If authentication is added later, update this doc accordingly.

## Quick Start

```bash
curl 'https://test.dragonfortune.ai/api/spot-microstructure/trades?symbol=BTCUSDT&exchange=binance&limit=50'
```

## Endpoint Matrix

| Endpoint | Summary | Required Query Params | Optional Query Params |
| --- | --- | --- | --- |
| `/trades` | Recent trades feed | `symbol` | `exchange=binance`, `limit=50` |
| `/trades/summary` | Bucketed OHLC + buy/sell volumes | `symbol` | `interval=5m`, `limit=100` |
| `/cvd` | Cumulative Volume Delta series | `symbol` | `exchange=binance`, `limit=100` |
| `/trade-bias` | Market bias strength | `symbol` | `limit=1000` (minutes) |
| `/orderbook/snapshot` | Top-of-book snapshot | `symbol` | `exchange=binance`, `depth=15` |
| `/book-pressure` | Bid/ask pressure ratios | `symbol` | `exchange=binance`, `limit=100` |
| `/volume-profile` | Aggregated trade statistics | `symbol` | `limit=1000` (hours) |
| `/vwap` | Historical VWAP bands | `symbol` | `exchange=binance`, `timeframe=5min`, `limit=2000` |
| `/vwap/latest` | Latest VWAP bands | `symbol` | `exchange=binance`, `timeframe=5min` |

## Endpoint Details

### `GET /trades`
Recent executed trades for the requested spot pair.

- **Query**: `symbol` (required), `exchange` (default `binance`), `limit` (default `50`).
- **Response fields** (per trade):
  - `timestamp` (ISO string)
  - `symbol`, `exchange`
  - `price`, `quantity`
  - `side` (`buy` or `sell`)
- **Meta**: `count`, `symbol`, `exchange` accompany the `data` array.

### `GET /trades/summary`
Bucketed OHLC candles derived from trades plus buy/sell volume aggregates.

- **Query**: `symbol` (required), `interval` (`1m`, `5m`, `15m`, `1h`; default `5m`), `limit` (default `100` buckets).
- **Response fields** (per bucket):
  - `timestamp`, `open`, `high`, `low`, `close`
  - `volume`, `buy_volume`, `sell_volume`
- **Meta**: `count`, `interval`, `symbol`.

### `GET /cvd`
Cumulative Volume Delta time series.

- **Query**: `symbol` (required), `exchange` (default `binance`), `limit` (default `100` points).
- **Response fields** (per row):
  - `timestamp`, `symbol`, `exchange`
  - `buy_volume`, `sell_volume`, `total_volume`
  - `cvd`, `cumulative_cvd`
- **Meta**: `count`, `symbol`, `exchange`.

### `GET /trade-bias`
Directional bias computed over rolling buckets.

- **Query**: `symbol` (required), `limit` (default `1000` minutes of history).
- **Response fields**:
  - `bias` (`buy`, `sell`, `neutral`)
  - `strength` (stringified score)
  - `avg_buyer_ratio`, `avg_seller_ratio`
  - `n` (number of data points considered)

### `GET /orderbook/snapshot`
Latest orderbook snapshot for the symbol.

- **Query**: `symbol` (required), `exchange` (default `binance`), `depth` (default `15` levels).
- **Response fields**:
  - `bids` / `asks`: arrays of `{ price, quantity }`
  - `spread_pct`: spread in percent
  - `timestamp`: ISO string
- **Errors**: `404` when no snapshot is available.

### `GET /book-pressure`
Bid/ask pressure ratios over recent observations.

- **Query**: `symbol` (required), `exchange` (default `binance`), `limit` (default `100` rows).
- **Response fields** (per row):
  - `timestamp`, `symbol`
  - `bid_pressure`, `ask_pressure`
  - `pressure_ratio`
  - `imbalance` (string label)
- **Meta**: `count`, `symbol`, `exchange`.

### `GET /volume-profile`
Aggregated trade statistics for the lookback window.

- **Query**: `symbol` (required), `limit` (default `1000` hours sampled).
- **Response fields**:
  - `period_start`, `period_end`
  - `total_trades`, `total_buy_trades`, `total_sell_trades`
  - `avg_trade_size`, `max_trade_size`
  - `buy_sell_ratio`

### `GET /vwap`
Historical VWAP with upper/lower bands and signals.

- **Query**: `symbol` (required), `exchange` (default `binance`), `timeframe` (`1min`, `5min`, `15min`, `30min`, `1h`, `4h`; default `5min`), `limit` (default `2000` rows).
- **Response fields** (per row):
  - `timestamp`, `symbol`, `timeframe`, `exchange`
  - `vwap`, `current_price`
  - `upper_band`, `lower_band`
  - `price_position` (`above`, `below`, `at`)
  - `signal` (`overbought`, `oversold`, `neutral`)
  - `volume`
- **Meta**: `count`, `symbol`, `exchange`, `timeframe`.

### `GET /vwap/latest`
Single-row VWAP snapshot for the latest interval.

- **Query**: `symbol` (required), `exchange` (default `binance`), `timeframe` (`1min`, `5min`, `15min`, `30min`, `1h`, `4h`; default `5min`).
- **Response fields**:
  - `timestamp`, `symbol`
  - `vwap`, `current_price`
  - `upper_band`, `lower_band`
  - `price_position`, `signal`

## Sample Response Shapes

```json
// GET /api/spot-microstructure/trades?symbol=BTCUSDT&exchange=binance&limit=2
{
  "symbol": "BTCUSDT",
  "exchange": "binance",
  "count": 2,
  "data": [
    {
      "timestamp": "2025-03-03T15:00:00Z",
      "symbol": "BTCUSDT",
      "exchange": "binance",
      "side": "buy",
      "price": 63000.12,
      "quantity": 0.25
    }
  ]
}
```

```json
// GET /api/spot-microstructure/vwap/latest?symbol=BTCUSDT&exchange=binance&timeframe=5min
{
  "symbol": "BTCUSDT",
  "timestamp": "2025-03-03T15:00:00Z",
  "vwap": 62950.5,
  "current_price": 63010.0,
  "upper_band": 63120.0,
  "lower_band": 62780.0,
  "price_position": "above",
  "signal": "overbought"
}
```

## Handy Notes

- All timestamps are delivered as strings (ISO8601 in the test responses).
- Numeric values may arrive as strings in some endpoints (`cvd`, `trade-bias`). Cast as needed on the frontend.
- Always send `symbol` without slash (e.g., `BTCUSDT`), matching the Swagger examples.
- If upstream introduces pagination or auth, revise this guide immediately to avoid breaking the frontend integration.

