@extends('layouts.app')

@section('title', 'ATR Detector | DragonFortune')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1 fw-semibold">ATR (Average True Range)</h2>
                    <p class="text-muted mb-0">Stop & Position Sizing adaptif — atur stop loss & ukuran posisi sesuai volatilitas</p>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" style="width: auto;">
                        <option value="24h">24 Hours</option>
                        <option value="7d" selected>7 Days</option>
                        <option value="30d">30 Days</option>
                        <option value="90d">90 Days</option>
                    </select>
                    <button class="btn btn-outline-secondary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-circle p-2">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-success">
                                    <path d="M3 3v18h18"/>
                                    <path d="M7 12l3-3 3 3 5-5"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Current ATR</h6>
                            <h4 class="mb-0 text-success">$2,847</h4>
                            <small class="text-muted">14-period</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-circle p-2">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-info">
                                    <path d="M3 3v18h18"/>
                                    <path d="M7 12l3-3 3 3 5-5"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">ATR %</h6>
                            <h4 class="mb-0 text-info">2.85%</h4>
                            <small class="text-muted">Of price</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-2">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-warning">
                                    <path d="M3 3v18h18"/>
                                    <path d="M7 12l3-3 3 3 5-5"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Stop Loss</h6>
                            <h4 class="mb-0 text-warning">$8,541</h4>
                            <small class="text-muted">3x ATR</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                                    <path d="M3 3v18h18"/>
                                    <path d="M7 12l3-3 3 3 5-5"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Position Size</h6>
                            <h4 class="mb-0 text-primary">0.35 BTC</h4>
                            <small class="text-muted">Risk-adjusted</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Chart Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">ATR Analysis</h5>
                        <div class="d-flex gap-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="showATR" checked>
                                <label class="form-check-label" for="showATR">ATR</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="showStopLoss" checked>
                                <label class="form-check-label" for="showStopLoss">Stop Loss</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="showPositionSize" checked>
                                <label class="form-check-label" for="showPositionSize">Position Size</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="atrChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ATR Settings -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">ATR Settings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th class="text-end">Value</th>
                                    <th class="text-end">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>ATR Period</td>
                                    <td class="text-end">14</td>
                                    <td class="text-end">Standard period</td>
                                </tr>
                                <tr>
                                    <td>Stop Loss Multiplier</td>
                                    <td class="text-end">3.0x</td>
                                    <td class="text-end">Risk tolerance</td>
                                </tr>
                                <tr>
                                    <td>Risk per Trade</td>
                                    <td class="text-end">2.0%</td>
                                    <td class="text-end">Portfolio risk</td>
                                </tr>
                                <tr>
                                    <td>Max Position Size</td>
                                    <td class="text-end">1.0 BTC</td>
                                    <td class="text-end">Position limit</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Risk Management</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="position-relative d-inline-block">
                            <svg width="120" height="120" viewBox="0 0 120 120" class="position-relative">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#e9ecef" stroke-width="8"/>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#28a745" stroke-width="8"
                                        stroke-dasharray="314" stroke-dashoffset="125.6" transform="rotate(-90 60 60)"/>
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <h3 class="mb-0 text-success">60%</h3>
                                <small class="text-muted">Risk Level</small>
                            </div>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h6 class="text-success mb-1">Low Risk</h6>
                                <small class="text-muted">60%</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h6 class="text-warning mb-1">Medium Risk</h6>
                                <small class="text-muted">30%</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h6 class="text-danger mb-1">High Risk</h6>
                            <small class="text-muted">10%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Position Sizing Calculator -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Position Sizing Calculator</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-success mb-1">Account Balance</h6>
                                <h4 class="mb-1">$100,000</h4>
                                <small class="text-muted">Total capital</small>
                                <div class="mt-2">
                                    <span class="badge bg-success">Available</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-info mb-1">Risk per Trade</h6>
                                <h4 class="mb-1">2.0%</h4>
                                <small class="text-muted">Portfolio risk</small>
                                <div class="mt-2">
                                    <span class="badge bg-info">$2,000</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-warning mb-1">Stop Loss Distance</h6>
                                <h4 class="mb-1">$8,541</h4>
                                <small class="text-muted">3x ATR</small>
                                <div class="mt-2">
                                    <span class="badge bg-warning">2.85%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-primary mb-1">Position Size</h6>
                                <h4 class="mb-1">0.35 BTC</h4>
                                <small class="text-muted">Risk-adjusted</small>
                                <div class="mt-2">
                                    <span class="badge bg-primary">$35,000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ATR Analysis -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">ATR Analysis</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Timeframe</th>
                                    <th class="text-end">ATR</th>
                                    <th class="text-end">ATR %</th>
                                    <th class="text-end">Volatility</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1 Hour</td>
                                    <td class="text-end">$1,234</td>
                                    <td class="text-end">1.23%</td>
                                    <td class="text-end text-success">Low</td>
                                </tr>
                                <tr>
                                    <td>4 Hours</td>
                                    <td class="text-end">$1,890</td>
                                    <td class="text-end">1.89%</td>
                                    <td class="text-end text-info">Medium</td>
                                </tr>
                                <tr>
                                    <td>24 Hours</td>
                                    <td class="text-end">$2,847</td>
                                    <td class="text-end">2.85%</td>
                                    <td class="text-end text-warning">High</td>
                                </tr>
                                <tr>
                                    <td>7 Days</td>
                                    <td class="text-end">$3,456</td>
                                    <td class="text-end">3.46%</td>
                                    <td class="text-end text-danger">Very High</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Stop Loss Levels</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h6 class="text-success mb-1">Conservative</h6>
                                <h4 class="mb-1">2x ATR</h4>
                                <small class="text-muted">$5,694</small>
                                <div class="mt-2">
                                    <span class="badge bg-success">Tight</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h6 class="text-info mb-1">Standard</h6>
                                <h4 class="mb-1">3x ATR</h4>
                                <small class="text-muted">$8,541</small>
                                <div class="mt-2">
                                    <span class="badge bg-info">Balanced</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h6 class="text-warning mb-1">Aggressive</h6>
                                <h4 class="mb-1">4x ATR</h4>
                                <small class="text-muted">$11,388</small>
                                <div class="mt-2">
                                    <span class="badge bg-warning">Wide</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h6 class="text-primary mb-1">Dynamic</h6>
                                <h4 class="mb-1">2.5x ATR</h4>
                                <small class="text-muted">$7,118</small>
                                <div class="mt-2">
                                    <span class="badge bg-primary">Adaptive</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Management Rules -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Risk Management Rules</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <div class="mb-3">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-success">
                                        <path d="M3 3v18h18"/>
                                        <path d="M7 12l3-3 3 3 5-5"/>
                                    </svg>
                                </div>
                                <h6 class="text-success">Adaptive Stop Loss</h6>
                                <p class="small text-muted mb-0">Stop loss disesuaikan dengan volatilitas pasar menggunakan ATR multiplier.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <div class="mb-3">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-info">
                                        <path d="M3 3v18h18"/>
                                        <path d="M7 12l3-3 3 3 5-5"/>
                                    </svg>
                                </div>
                                <h6 class="text-info">Position Sizing</h6>
                                <p class="small text-muted mb-0">Ukuran posisi dihitung berdasarkan risiko per trade dan jarak stop loss.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <div class="mb-3">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-warning">
                                        <path d="M3 3v18h18"/>
                                        <path d="M7 12l3-3 3 3 5-5"/>
                                    </svg>
                                </div>
                                <h6 class="text-warning">Risk Control</h6>
                                <p class="small text-muted mb-0">Kontrol risiko dengan membatasi ukuran posisi maksimal dan risiko per trade.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights Section -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Trading Insights</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="alert alert-success border-0">
                                <div class="d-flex align-items-start">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-success me-2 mt-1">
                                        <path d="M9 12l2 2 4-4"/>
                                        <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
                                    </svg>
                                    <div>
                                        <h6 class="alert-heading">Adaptive Risk Management</h6>
                                        <p class="mb-0 small">ATR 2.85% mengindikasikan volatilitas tinggi. Stop loss disesuaikan dengan 3x ATR.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-info border-0">
                                <div class="d-flex align-items-start">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-info me-2 mt-1">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    <div>
                                        <h6 class="alert-heading">Position Sizing</h6>
                                        <p class="mb-0 small">Dengan risiko 2% per trade, ukuran posisi optimal adalah 0.35 BTC.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning border-0">
                                <div class="d-flex align-items-start">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-warning me-2 mt-1">
                                        <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                    </svg>
                                    <div>
                                        <h6 class="alert-heading">Volatility Alert</h6>
                                        <p class="mb-0 small">ATR tinggi mengindikasikan volatilitas tinggi. Pertimbangkan mengurangi ukuran posisi.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('ATR Detector chart initialized');
});
</script>
@endsection
