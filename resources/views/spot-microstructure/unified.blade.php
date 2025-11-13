@extends('layouts.app')

@section('title', 'Spot Microstructure | DragonFortune')

@push('head')
    <link rel="dns-prefetch" href="https://open-api-v4.coinglass.com">
    <link rel="preconnect" href="https://open-api-v4.coinglass.com" crossorigin>
@endpush

@section('content')
    <div class="d-flex flex-column h-100 gap-3" x-data="spotMicrostructure()">
        <!-- Page Header -->
        <div class="derivatives-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h1 class="mb-0">Spot Microstructure</h1>
                        <span class="pulse-dot pulse-success" x-show="!isLoading"></span>
                        <span class="spinner-border spinner-border-sm text-primary" style="width: 16px; height: 16px;" x-show="isLoading" x-cloak></span>
                        <span class="badge text-bg-info">
                            <i class="fas fa-link"></i> Coinglass
                        </span>
                    </div>
                    <p class="mb-0 text-secondary">
                        Real-time spot market microstructure analysis
                    </p>
                </div>

                <!-- Refresh Button -->
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-outline-primary" @click="refresh()" :disabled="isLoading">
                        <i class="fas fa-sync-alt" :class="{'fa-spin': isLoading}"></i>
                        <span x-text="isLoading ? 'Loading...' : 'Refresh'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#coins-markets" type="button" role="tab">
                    Coins Markets
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pairs-markets" type="button" role="tab">
                    Pairs Markets
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#price-history" type="button" role="tab">
                    Price History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#orderbook" type="button" role="tab">
                    Orderbook Analysis
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#volume-analysis" type="button" role="tab">
                    Volume Analysis
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#supported-data" type="button" role="tab">
                    Supported Data
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Coins Markets Tab -->
            <div class="tab-pane fade show active" id="coins-markets" role="tabpanel">
                @include('spot-microstructure.tabs.coins-markets')
            </div>

            <!-- Pairs Markets Tab -->
            <div class="tab-pane fade" id="pairs-markets" role="tabpanel">
                @include('spot-microstructure.tabs.pairs-markets')
            </div>

            <!-- Price History Tab -->
            <div class="tab-pane fade" id="price-history" role="tabpanel">
                @include('spot-microstructure.tabs.price-history')
            </div>

            <!-- Orderbook Analysis Tab -->
            <div class="tab-pane fade" id="orderbook" role="tabpanel">
                @include('spot-microstructure.tabs.orderbook')
            </div>

            <!-- Volume Analysis Tab -->
            <div class="tab-pane fade" id="volume-analysis" role="tabpanel">
                @include('spot-microstructure.tabs.volume-analysis')
            </div>

            <!-- Supported Data Tab -->
            <div class="tab-pane fade" id="supported-data" role="tabpanel">
                @include('spot-microstructure.tabs.supported-data')
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    <script type="module" src="{{ asset('js/spot-microstructure/controller.js') }}" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        
        .df-panel {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .df-panel:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .pulse-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        .pulse-success {
            background-color: #22c55e;
            box-shadow: 0 0 0 rgba(34, 197, 94, 0.7);
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
            }
        }

        .table th {
            font-weight: 600;
            font-size: 0.875rem;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }

        .table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        .nav-tabs .nav-link {
            color: #64748b;
            border: none;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e2e8f0;
        }

        .nav-tabs .nav-link.active {
            color: #3b82f6;
            border-color: #3b82f6;
            background: transparent;
        }
    </style>
@endsection
