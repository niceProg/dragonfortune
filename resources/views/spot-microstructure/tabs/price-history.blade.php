<!-- Price History Tab -->
<div class="d-flex flex-column gap-3">
    <!-- Filters -->
    <div class="df-panel p-3">
        <div class="row g-3 align-items-end">
            <!-- Exchange -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Exchange <span class="text-danger">*</span></label>
                <input type="text" class="form-control" x-model="priceHistory.exchange" placeholder="Binance">
            </div>

            <!-- Symbol -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Symbol <span class="text-danger">*</span></label>
                <input type="text" class="form-control" x-model="priceHistory.symbol" placeholder="BTCUSDT">
            </div>

            <!-- Interval -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Interval <span class="text-danger">*</span></label>
                <select class="form-select" x-model="priceHistory.interval">
                    <option value="1m">1 Minute</option>
                    <option value="3m">3 Minutes</option>
                    <option value="5m">5 Minutes</option>
                    <option value="15m">15 Minutes</option>
                    <option value="30m">30 Minutes</option>
                    <option value="1h" selected>1 Hour</option>
                    <option value="4h">4 Hours</option>
                    <option value="6h">6 Hours</option>
                    <option value="8h">8 Hours</option>
                    <option value="12h">12 Hours</option>
                    <option value="1d">1 Day</option>
                    <option value="1w">1 Week</option>
                </select>
            </div>

            <!-- Limit -->
            <div class="col-md-2">
                <label class="form-label small text-secondary mb-1">Limit</label>
                <select class="form-select" x-model="priceHistory.limit">
                    <option value="50">50 Candles</option>
                    <option value="100" selected>100 Candles</option>
                    <option value="200">200 Candles</option>
                    <option value="500">500 Candles</option>
                    <option value="1000">1000 Candles</option>
                </select>
            </div>

            <!-- Load Button -->
            <div class="col-md-4">
                <button class="btn btn-primary w-100" @click="loadPriceHistory()" :disabled="isLoading">
                    <i class="fas fa-chart-line"></i> Load Chart
                </button>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="df-panel">
        <div class="p-3">
            <h5 class="mb-3">
                <span x-text="priceHistory.symbol"></span> Price Chart 
                <span class="badge text-bg-secondary" x-text="priceHistory.exchange"></span>
            </h5>
            <div style="height: 400px; position: relative;">
                <canvas id="priceHistoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- OHLCV Table -->
    <div class="df-panel" x-show="priceHistory.data.length > 0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th class="text-end">Open</th>
                        <th class="text-end">High</th>
                        <th class="text-end">Low</th>
                        <th class="text-end">Close</th>
                        <th class="text-end">Volume</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="candle in priceHistory.data.slice(0, 20)" :key="candle.time">
                        <tr>
                            <td x-text="new Date(candle.time).toLocaleString()"></td>
                            <td class="text-end" x-text="'$' + candle.open.toLocaleString()"></td>
                            <td class="text-end text-success" x-text="'$' + candle.high.toLocaleString()"></td>
                            <td class="text-end text-danger" x-text="'$' + candle.low.toLocaleString()"></td>
                            <td class="text-end" x-text="'$' + candle.close.toLocaleString()"></td>
                            <td class="text-end" x-text="'$' + (candle.volume_usd / 1e6).toFixed(2) + 'M'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="p-3 text-center text-secondary small" x-show="priceHistory.data.length > 20">
            Showing latest 20 of <span x-text="priceHistory.data.length"></span> candles
        </div>
    </div>
</div>
