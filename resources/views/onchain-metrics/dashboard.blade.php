@extends('layouts.app')

@section('title', 'On-Chain Metrics | DragonFortune')

@section('content')
<div class="d-flex flex-column h-100 gap-3" x-data="onChainMetrics()">
    <!-- Header -->
    <div class="derivatives-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h1 class="mb-0">On-Chain Metrics</h1>
                    <span class="pulse-dot pulse-success" x-show="!isLoading"></span>
                    <span class="spinner-border spinner-border-sm text-primary" style="width: 16px; height: 16px;" x-show="isLoading" x-cloak></span>
                    <span class="badge text-bg-info">
                        <i class="fas fa-link"></i> Coinglass
                    </span>
                </div>
                <p class="mb-0 text-secondary">
                    Exchange balances, chain transactions, and whale activity monitoring
                </p>
            </div>

            <button class="btn btn-outline-primary" @click="refresh()" :disabled="isLoading">
                <i class="fas fa-sync-alt" :class="{'fa-spin': isLoading}"></i>
                <span x-text="isLoading ? 'Loading...' : 'Refresh'"></span>
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#exchange-assets" type="button" role="tab">
                Exchange Assets
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#exchange-balances" type="button" role="tab">
                Exchange Balances
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#chain-transactions" type="button" role="tab">
                Chain Transactions
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#whale-transfers" type="button" role="tab">
                Whale Transfers
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        @include('onchain-metrics.tabs.exchange-assets')
        @include('onchain-metrics.tabs.exchange-balances')
        @include('onchain-metrics.tabs.chain-transactions')
        @include('onchain-metrics.tabs.whale-transfers')
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/onchain-metrics/controller.js') }}"></script>

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
    }

    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        50% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
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
