<!-- Volume Analysis Tab -->
<div class="d-flex flex-column gap-3">
    <!-- Sub-tabs -->
    <ul class="nav nav-pills" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#taker-volume" type="button" role="tab">
                Taker Buy/Sell Volume
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#aggregated-taker" type="button" role="tab">
                Aggregated Taker Volume
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#volume-footprint" type="button" role="tab">
                Volume Footprint
            </button>
        </li>
    </ul>

    <!-- Sub-tab Content -->
    <div class="tab-content">
        <!-- Taker Buy/Sell Volume -->
        <div class="tab-pane fade show active" id="taker-volume" role="tabpanel">
            <div class="d-flex flex-column gap-3">
                <div class="df-panel p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Exchange</label>
                            <input type="text" class="form-control" x-model="volumeAnalysis.taker.exchange" placeholder="Binance">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Symbol</label>
                            <input type="text" class="form-control" x-model="volumeAnalysis.taker.symbol" placeholder="BTCUSDT">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Interval</label>
                            <select class="form-select" x-model="volumeAnalysis.taker.interval">
                                <option value="1m">1 Minute</option>
                                <option value="5m">5 Minutes</option>
                                <option value="15m">15 Minutes</option>
                                <option value="1h" selected>1 Hour</option>
                                <option value="4h">4 Hours</option>
                                <option value="1d">1 Day</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Limit</label>
                            <input type="number" class="form-control" x-model="volumeAnalysis.taker.limit" value="100">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100" @click="loadTakerVolume()" :disabled="isLoading">
                                <i class="fas fa-chart-area"></i> Load
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="df-panel p-3" x-show="volumeAnalysis.taker.data.length > 0">
                    <h5 class="mb-3">Taker Buy vs Sell Volume</h5>
                    <div style="height: 400px; position: relative;">
                        <canvas id="takerVolumeChart"></canvas>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row g-3" x-show="volumeAnalysis.taker.data.length > 0">
                    <div class="col-md-4">
                        <div class="df-panel p-3">
                            <div class="small text-secondary mb-1">Total Buy Volume</div>
                            <div class="h4 mb-0 text-success" x-text="'$' + (volumeAnalysis.taker.totalBuy / 1e6).toFixed(2) + 'M'"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="df-panel p-3">
                            <div class="small text-secondary mb-1">Total Sell Volume</div>
                            <div class="h4 mb-0 text-danger" x-text="'$' + (volumeAnalysis.taker.totalSell / 1e6).toFixed(2) + 'M'"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="df-panel p-3">
                            <div class="small text-secondary mb-1">Buy/Sell Ratio</div>
                            <div class="h4 mb-0" :class="volumeAnalysis.taker.ratio > 1 ? 'text-success' : 'text-danger'" x-text="volumeAnalysis.taker.ratio.toFixed(2)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aggregated Taker Volume -->
        <div class="tab-pane fade" id="aggregated-taker" role="tabpanel">
            <div class="d-flex flex-column gap-3">
                <div class="df-panel p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Exchanges</label>
                            <input type="text" class="form-control" x-model="volumeAnalysis.aggregated.exchangeList" placeholder="Binance,OKX">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Symbol</label>
                            <input type="text" class="form-control" x-model="volumeAnalysis.aggregated.symbol" placeholder="BTC">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Interval</label>
                            <select class="form-select" x-model="volumeAnalysis.aggregated.interval">
                                <option value="1h" selected>1 Hour</option>
                                <option value="4h">4 Hours</option>
                                <option value="1d">1 Day</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Unit</label>
                            <select class="form-select" x-model="volumeAnalysis.aggregated.unit">
                                <option value="usd" selected>USD</option>
                                <option value="coin">Coin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Limit</label>
                            <input type="number" class="form-control" x-model="volumeAnalysis.aggregated.limit" value="100">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" @click="loadAggregatedTakerVolume()" :disabled="isLoading">
                                <i class="fas fa-chart-area"></i> Load
                            </button>
                        </div>
                    </div>
                </div>

                <div class="df-panel p-3" x-show="volumeAnalysis.aggregated.data.length > 0">
                    <h5 class="mb-3">Aggregated Taker Volume (Multi-Exchange)</h5>
                    <div style="height: 400px; position: relative;">
                        <canvas id="aggregatedTakerChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Volume Footprint -->
        <div class="tab-pane fade" id="volume-footprint" role="tabpanel">
            <div class="d-flex flex-column gap-3">
                <div class="df-panel p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Exchange</label>
                            <select class="form-select" x-model="volumeAnalysis.footprint.exchange">
                                <option value="Binance" selected>Binance</option>
                                <option value="OKX">OKX</option>
                                <option value="Bybit">Bybit</option>
                                <option value="Hyperliquid">Hyperliquid</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Symbol</label>
                            <input type="text" class="form-control" x-model="volumeAnalysis.footprint.symbol" placeholder="BTCUSDT">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Interval</label>
                            <select class="form-select" x-model="volumeAnalysis.footprint.interval">
                                <option value="1m">1 Minute</option>
                                <option value="5m">5 Minutes</option>
                                <option value="15m">15 Minutes</option>
                                <option value="1h" selected>1 Hour</option>
                                <option value="4h">4 Hours</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Limit</label>
                            <input type="number" class="form-control" x-model="volumeAnalysis.footprint.limit" value="50">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100" @click="loadVolumeFootprint()" :disabled="isLoading">
                                <i class="fas fa-chart-bar"></i> Load
                            </button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>📊 Volume Footprint:</strong> Shows detailed price-level volume distribution with buy/sell breakdown. Each price level shows taker buy volume (green) vs taker sell volume (red).
                </div>

                <div class="df-panel" x-show="volumeAnalysis.footprint.data.length > 0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th class="text-end">Price Range</th>
                                    <th class="text-end">Buy Volume</th>
                                    <th class="text-end">Sell Volume</th>
                                    <th class="text-end">Buy Value (USDT)</th>
                                    <th class="text-end">Sell Value (USDT)</th>
                                    <th class="text-end">Trade Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in volumeAnalysis.footprint.processed.slice(0, 50)" :key="idx">
                                    <tr>
                                        <td x-text="new Date(item.time * 1000).toLocaleString()"></td>
                                        <td class="text-end" x-text="'$' + item.priceStart.toLocaleString() + ' - $' + item.priceEnd.toLocaleString()"></td>
                                        <td class="text-end text-success" x-text="item.buyVolume.toFixed(3)"></td>
                                        <td class="text-end text-danger" x-text="item.sellVolume.toFixed(3)"></td>
                                        <td class="text-end text-success" x-text="'$' + (item.buyValueUSDT / 1e3).toFixed(2) + 'K'"></td>
                                        <td class="text-end text-danger" x-text="'$' + (item.sellValueUSDT / 1e3).toFixed(2) + 'K'"></td>
                                        <td class="text-end" x-text="item.buyTradeCount + ' / ' + item.sellTradeCount"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
