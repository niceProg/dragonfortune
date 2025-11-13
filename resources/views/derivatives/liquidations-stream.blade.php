@extends('layouts.app')

@section('title', 'Liquidation Order Stream | DragonFortune')

@push('head')
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="{{ asset('js/liquidations-stream-controller.js') }}" as="script" crossorigin="anonymous">
@endpush

@section('content')
    {{--
        Liquidation Order Stream (Real-Time)
        Think like a trader • Build like an engineer • Visualize like a designer

        Real-time liquidation orders dari Coinglass WebSocket
        - Long liquidations = Bearish pressure (longs forced out)
        - Short liquidations = Bullish pressure (shorts forced out)
        - Large liquidations = Market volatility signals
    --}}

    <div class="d-flex flex-column h-100 gap-3" x-data="liquidationsStreamController()">
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">Liquidation Order Stream</h1>
                        <span class="pulse-dot" :class="isConnected ? 'pulse-success' : 'pulse-danger'"></span>
                        <span class="badge" :class="isConnected ? 'text-bg-success' : 'text-bg-danger'">
                            <i class="fas" :class="isConnected ? 'fa-wifi' : 'fa-wifi-slash'"></i>
                            <span x-text="isConnected ? 'LIVE' : 'DISCONNECTED'"></span>
                        </span>
                        <span class="badge text-bg-info" x-show="ordersCount > 0" x-text="ordersCount + ' orders'"></span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Monitor liquidation orders secara real-time. Large liquidations sering jadi sinyal volatilitas pasar.
                    </p>
                </div>

                <!-- Connection Controls -->
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <button type="button" 
                            class="btn btn-sm" 
                            :class="isConnected ? 'btn-danger' : 'btn-success'"
                            @click="isConnected ? disconnect() : connect()">
                        <i class="fas" :class="isConnected ? 'fa-stop' : 'fa-play'"></i>
                        <span x-text="isConnected ? 'Stop' : 'Start'"></span>
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" @click="clearOrders()">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                    <button type="button" 
                            class="btn btn-sm" 
                            :class="soundEnabled ? 'btn-info' : 'btn-secondary'"
                            @click="toggleSound()">
                        <i class="fas" :class="soundEnabled ? 'fa-volume-up' : 'fa-volume-mute'"></i>
                        <span x-text="soundEnabled ? 'Sound On' : 'Sound Off'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="row g-3">
            <div class="col-12">
                <div class="df-panel p-3">
                    <div class="row g-3 align-items-end">
                        <!-- Exchange Filter -->
                        <div class="col-md-3">
                            <label class="form-label small text-secondary mb-1">Exchange</label>
                            <select class="form-select form-select-sm" x-model="filters.exchange">
                                <option value="">All Exchanges</option>
                                <option value="Binance">Binance</option>
                                <option value="OKX">OKX</option>
                                <option value="Bybit">Bybit</option>
                                <option value="Bitget">Bitget</option>
                                <option value="dYdX">dYdX</option>
                                <option value="Kraken">Kraken</option>
                            </select>
                        </div>

                        <!-- Coin Filter -->
                        <div class="col-md-3">
                            <label class="form-label small text-secondary mb-1">Coin</label>
                            <select class="form-select form-select-sm" x-model="filters.coin">
                                <option value="">All Coins</option>
                                <option value="BTC">BTC</option>
                                <option value="ETH">ETH</option>
                                <option value="SOL">SOL</option>
                                <option value="BNB">BNB</option>
                                <option value="XRP">XRP</option>
                                <option value="ADA">ADA</option>
                                <option value="DOGE">DOGE</option>
                            </select>
                        </div>

                        <!-- Min USD Filter -->
                        <div class="col-md-3">
                            <label class="form-label small text-secondary mb-1">Minimum USD</label>
                            <select class="form-select form-select-sm" x-model="filters.minUsd">
                                <option value="0">All Sizes</option>
                                <option value="1000">$1K+</option>
                                <option value="10000">$10K+</option>
                                <option value="50000">$50K+</option>
                                <option value="100000">$100K+</option>
                                <option value="500000">$500K+</option>
                                <option value="1000000">$1M+</option>
                            </select>
                        </div>

                        <!-- Side Filter -->
                        <div class="col-md-3">
                            <label class="form-label small text-secondary mb-1">Side</label>
                            <select class="form-select form-select-sm" x-model="filters.side">
                                <option value="">All Sides</option>
                                <option value="1">Long Liquidations</option>
                                <option value="2">Short Liquidations</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3">
            <div class="col-md-3">
                <div class="df-panel p-3">
                    <div class="small text-secondary mb-1">Total Liquidations</div>
                    <div class="h4 mb-0" x-text="formatCurrency(stats.totalUsd)"></div>
                    <small class="text-muted" x-text="stats.count + ' orders'"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="df-panel p-3">
                    <div class="small text-danger mb-1">Long Liquidations</div>
                    <div class="h4 mb-0 text-danger" x-text="formatCurrency(stats.longUsd)"></div>
                    <small class="text-muted" x-text="stats.longCount + ' orders'"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="df-panel p-3">
                    <div class="small text-success mb-1">Short Liquidations</div>
                    <div class="h4 mb-0 text-success" x-text="formatCurrency(stats.shortUsd)"></div>
                    <small class="text-muted" x-text="stats.shortCount + ' orders'"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="df-panel p-3">
                    <div class="small text-warning mb-1">Largest Order</div>
                    <div class="h4 mb-0 text-warning" x-text="formatCurrency(stats.maxUsd)"></div>
                    <small class="text-muted" x-show="stats.maxOrder" x-text="stats.maxOrder ? stats.maxOrder.baseAsset + ' @ ' + stats.maxOrder.exName : ''"></small>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="row g-3 flex-fill">
            <div class="col-12">
                <div class="df-panel p-0 h-100 d-flex flex-column">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">Live Orders</h5>
                    </div>
                    <div class="flex-fill overflow-auto" style="max-height: 600px;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="sticky-top bg-dark">
                                <tr>
                                    <th class="text-secondary small">Time</th>
                                    <th class="text-secondary small">Exchange</th>
                                    <th class="text-secondary small">Symbol</th>
                                    <th class="text-secondary small">Side</th>
                                    <th class="text-secondary small text-end">Price</th>
                                    <th class="text-secondary small text-end">Amount (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="order in filteredOrders" :key="order.id">
                                    <tr class="order-row" :class="order.side === 1 ? 'long-liq' : 'short-liq'">
                                        <td class="small" x-text="formatTime(order.time)"></td>
                                        <td class="small">
                                            <span class="badge badge-sm text-bg-secondary" x-text="order.exName"></span>
                                        </td>
                                        <td class="small fw-bold" x-text="order.baseAsset"></td>
                                        <td class="small">
                                            <span class="badge badge-sm" 
                                                  :class="order.side === 1 ? 'text-bg-danger' : 'text-bg-success'"
                                                  x-text="order.side === 1 ? 'LONG' : 'SHORT'"></span>
                                        </td>
                                        <td class="small text-end" x-text="formatPrice(order.price)"></td>
                                        <td class="small text-end fw-bold" 
                                            :class="order.volUsd >= 100000 ? 'text-warning' : ''"
                                            x-text="formatCurrency(order.volUsd)"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredOrders.length === 0">
                                    <td colspan="6" class="text-center text-secondary py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <div>No liquidation orders yet. Waiting for stream...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Liquidations Stream Controller -->
    <script type="module" src="{{ asset('js/liquidations-stream-controller.js') }}"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Pulse animation */
        .pulse-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        .pulse-success {
            background-color: #22c55e;
        }

        .pulse-danger {
            background-color: #ef4444;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            50% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
        }

        /* Table styling */
        .table thead {
            background: rgba(0, 0, 0, 0.5);
        }

        .order-row {
            transition: background-color 0.3s ease;
        }

        .order-row.long-liq {
            border-left: 3px solid #ef4444;
        }

        .order-row.short-liq {
            border-left: 3px solid #22c55e;
        }

        .order-row:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        /* Sticky header */
        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Panel styling */
        .df-panel {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
            border: 1px solid rgba(59, 130, 246, 0.1);
            border-radius: 8px;
        }

        /* Badge sizing */
        .badge-sm {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
@endsection
