<!-- Orderbook Analysis Tab -->
<div class="d-flex flex-column gap-3">
    <!-- Sub-tabs for different orderbook endpoints -->
    <ul class="nav nav-pills" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#ob-ask-bids" type="button" role="tab">
                Ask/Bids History
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#ob-aggregated" type="button" role="tab">
                Aggregated History
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#ob-history" type="button" role="tab">
                Orderbook History
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#ob-large-orders" type="button" role="tab">
                Large Limit Orders
            </button>
        </li>
    </ul>

    <!-- Sub-tab Content -->
    <div class="tab-content">
        <!-- Ask/Bids History -->
        <div class="tab-pane fade show active" id="ob-ask-bids" role="tabpanel">
            <div class="d-flex flex-column gap-3">
                <!-- Filters -->
                <div class="df-panel p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Exchange <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="orderbook.askBids.exchange" placeholder="Binance">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Symbol <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="orderbook.askBids.symbol" placeholder="BTCUSDT">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Interval</label>
                            <select class="form-select" x-model="orderbook.askBids.interval">
                                <option value="1m">1 Minute</option>
                                <option value="5m">5 Minutes</option>
                                <option value="15m">15 Minutes</option>
                                <option value="1h">1 Hour</option>
                                <option value="4h">4 Hours</option>
                                <option value="1d" selected>1 Day</option>
                                <option value="1w">1 Week</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Range %</label>
                            <select class="form-select" x-model="orderbook.askBids.range">
                                <option value="0.25">0.25%</option>
                                <option value="0.5">0.5%</option>
                                <option value="0.75">0.75%</option>
                                <option value="1" selected>1%</option>
                                <option value="2">2%</option>
                                <option value="3">3%</option>
                                <option value="5">5%</option>
                                <option value="10">10%</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Limit</label>
                            <input type="number" class="form-control" x-model="orderbook.askBids.limit" value="100">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" @click="loadAskBidsHistory()" :disabled="isLoading">
                                <i class="fas fa-chart-bar"></i> Load
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="df-panel p-3" x-show="orderbook.askBids.data.length > 0">
                    <h5 class="mb-3">Ask/Bids Depth History</h5>
                    <div style="height: 400px; position: relative;">
                        <canvas id="askBidsChart"></canvas>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="df-panel" x-show="orderbook.askBids.data.length > 0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th class="text-end">Bid Depth (USD)</th>
                                    <th class="text-end">Bid Quantity</th>
                                    <th class="text-end">Ask Depth (USD)</th>
                                    <th class="text-end">Ask Quantity</th>
                                    <th class="text-end">Bid/Ask Ratio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in orderbook.askBids.data.slice(0, 20)" :key="item.time">
                                    <tr>
                                        <td x-text="new Date(item.time).toLocaleString()"></td>
                                        <td class="text-end text-success" x-text="'$' + (item.bids_usd / 1e6).toFixed(2) + 'M'"></td>
                                        <td class="text-end text-success" x-text="item.bids_quantity.toFixed(3)"></td>
                                        <td class="text-end text-danger" x-text="'$' + (item.asks_usd / 1e6).toFixed(2) + 'M'"></td>
                                        <td class="text-end text-danger" x-text="item.asks_quantity.toFixed(3)"></td>
                                        <td class="text-end" :class="(item.bids_usd / item.asks_usd) > 1 ? 'text-success' : 'text-danger'" x-text="(item.bids_usd / item.asks_usd).toFixed(2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aggregated History -->
        <div class="tab-pane fade" id="ob-aggregated" role="tabpanel">
            <div class="d-flex flex-column gap-3">
                <div class="df-panel p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Exchanges</label>
                            <input type="text" class="form-control" x-model="orderbook.aggregated.exchangeList" placeholder="Binance,OKX">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Symbol</label>
                            <input type="text" class="form-control" x-model="orderbook.aggregated.symbol" placeholder="BTC">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Interval</label>
                            <select class="form-select" x-model="orderbook.aggregated.interval">
                                <option value="1h" selected>1 Hour</option>
                                <option value="4h">4 Hours</option>
                                <option value="1d">1 Day</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Range %</label>
                            <select class="form-select" x-model="orderbook.aggregated.range">
                                <option value="1" selected>1%</option>
                                <option value="2">2%</option>
                                <option value="5">5%</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Limit</label>
                            <input type="number" class="form-control" x-model="orderbook.aggregated.limit" value="100">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" @click="loadAggregatedHistory()" :disabled="isLoading">
                                <i class="fas fa-chart-bar"></i> Load
                            </button>
                        </div>
                    </div>
                </div>

                <div class="df-panel p-3" x-show="orderbook.aggregated.data.length > 0">
                    <h5 class="mb-3">Aggregated Orderbook Depth</h5>
                    <div style="height: 400px; position: relative;">
                        <canvas id="aggregatedChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orderbook History -->
        <div class="tab-pane fade" id="ob-history" role="tabpanel">
            <div class="d-flex flex-column gap-3">
                <div class="df-panel p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Exchange</label>
                            <input type="text" class="form-control" x-model="orderbook.history.exchange" placeholder="Binance">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Symbol</label>
                            <input type="text" class="form-control" x-model="orderbook.history.symbol" placeholder="BTCUSDT">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Interval</label>
                            <select class="form-select" x-model="orderbook.history.interval">
                                <option value="1m">1 Minute</option>
                                <option value="5m">5 Minutes</option>
                                <option value="1h" selected>1 Hour</option>
                                <option value="1d">1 Day</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Limit</label>
                            <input type="number" class="form-control" x-model="orderbook.history.limit" value="100">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100" @click="loadOrderbookHistory()" :disabled="isLoading">
                                <i class="fas fa-chart-bar"></i> Load
                            </button>
                        </div>
                    </div>
                </div>

                <div class="df-panel" x-show="orderbook.history.data.length > 0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th class="text-end">Bids Count</th>
                                    <th class="text-end">Asks Count</th>
                                    <th class="text-end">Top Bid Price</th>
                                    <th class="text-end">Top Ask Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in orderbook.history.data.slice(0, 20)" :key="idx">
                                    <tr>
                                        <td x-text="new Date(item[0] * 1000).toLocaleString()"></td>
                                        <td class="text-end text-success" x-text="item[1]?.length || 0"></td>
                                        <td class="text-end text-danger" x-text="item[2]?.length || 0"></td>
                                        <td class="text-end text-success" x-text="item[1]?.[0]?.[0] ? '$' + item[1][0][0].toLocaleString() : 'N/A'"></td>
                                        <td class="text-end text-danger" x-text="item[2]?.[0]?.[0] ? '$' + item[2][0][0].toLocaleString() : 'N/A'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Large Limit Orders -->
        <div class="tab-pane fade" id="ob-large-orders" role="tabpanel">
            <div class="d-flex flex-column gap-3">
                <!-- Current Large Orders -->
                <div class="df-panel p-3">
                    <h5 class="mb-3">Current Large Limit Orders</h5>
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-secondary mb-1">Exchange</label>
                            <input type="text" class="form-control" x-model="orderbook.largeOrders.exchange" placeholder="Binance">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-secondary mb-1">Symbol</label>
                            <input type="text" class="form-control" x-model="orderbook.largeOrders.symbol" placeholder="BTCUSDT">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" @click="loadLargeLimitOrders()" :disabled="isLoading">
                                <i class="fas fa-search"></i> Load Current
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive" x-show="orderbook.largeOrders.current.length > 0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Exchange</th>
                                    <th>Symbol</th>
                                    <th class="text-end">Price</th>
                                    <th>Side</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Value (USD)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="order in orderbook.largeOrders.current.slice(0, 20)" :key="order.id">
                                    <tr>
                                        <td><span class="badge text-bg-secondary" x-text="order.exchange_name"></span></td>
                                        <td><strong x-text="order.symbol"></strong></td>
                                        <td class="text-end" x-text="'$' + order.price.toLocaleString()"></td>
                                        <td>
                                            <span class="badge" :class="order.order_side === 1 ? 'text-bg-success' : 'text-bg-danger'" x-text="order.order_side === 1 ? 'BUY' : 'SELL'"></span>
                                        </td>
                                        <td class="text-end" x-text="order.current_quantity.toFixed(4)"></td>
                                        <td class="text-end" x-text="'$' + (order.current_usd_value / 1e6).toFixed(2) + 'M'"></td>
                                        <td>
                                            <span class="badge" :class="order.order_state === 1 ? 'text-bg-warning' : 'text-bg-success'" x-text="order.order_state === 1 ? 'Active' : 'Completed'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Historical Large Orders -->
                <div class="df-panel p-3">
                    <h5 class="mb-3">Large Limit Order History</h5>
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Exchange</label>
                            <input type="text" class="form-control" x-model="orderbook.largeOrders.exchange" placeholder="Binance">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Symbol</label>
                            <input type="text" class="form-control" x-model="orderbook.largeOrders.symbol" placeholder="BTCUSDT">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">Time Range</label>
                            <select class="form-select" x-model="orderbook.largeOrders.timeRange">
                                <option value="1h">Last 1 Hour</option>
                                <option value="6h">Last 6 Hours</option>
                                <option value="24h" selected>Last 24 Hours</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary mb-1">State</label>
                            <select class="form-select" x-model="orderbook.largeOrders.state">
                                <option value="1" selected>In Progress</option>
                                <option value="2">Finished</option>
                                <option value="3">Revoked</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100" @click="loadLargeLimitOrderHistory()" :disabled="isLoading">
                                <i class="fas fa-history"></i> Load History
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive" x-show="orderbook.largeOrders.history.length > 0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Exchange</th>
                                    <th>Symbol</th>
                                    <th>Start Time</th>
                                    <th class="text-end">Price</th>
                                    <th>Side</th>
                                    <th class="text-end">Start Qty</th>
                                    <th class="text-end">Executed</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="order in orderbook.largeOrders.history.slice(0, 50)" :key="order.id">
                                    <tr>
                                        <td><span class="badge text-bg-secondary" x-text="order.exchange_name"></span></td>
                                        <td><strong x-text="order.symbol"></strong></td>
                                        <td x-text="new Date(order.start_time).toLocaleString()"></td>
                                        <td class="text-end" x-text="'$' + order.price.toLocaleString()"></td>
                                        <td>
                                            <span class="badge" :class="order.order_side === 1 ? 'text-bg-success' : 'text-bg-danger'" x-text="order.order_side === 1 ? 'BUY' : 'SELL'"></span>
                                        </td>
                                        <td class="text-end" x-text="order.start_quantity.toFixed(4)"></td>
                                        <td class="text-end" x-text="order.executed_volume.toFixed(4)"></td>
                                        <td>
                                            <span class="badge" :class="order.order_state === 1 ? 'text-bg-warning' : order.order_state === 2 ? 'text-bg-success' : 'text-bg-danger'" x-text="order.order_state === 1 ? 'Open' : order.order_state === 2 ? 'Filled' : 'Cancelled'"></span>
                                        </td>
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
