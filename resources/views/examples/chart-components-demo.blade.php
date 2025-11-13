@extends('layouts.chart-layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1 text-gradient">Chart Components Demo</h1>
                    <p class="text-muted mb-0">Reusable chart components for trading dashboard</p>
                </div>
                
                <!-- Global Controls -->
                <div class="d-flex gap-3">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshAllCharts()">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Refresh All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Charts Grid -->
    <div class="chart-grid">
        
        <!-- Example 1: Basic Line Chart -->
        <div class="chart-card" 
             x-data="chartController({
                 containerId: 'demo-chart-1',
                 dataEndpoint: '/api/demo-data/line',
                 chartType: 'line',
                 defaultPeriod: '30d',
                 colors: { primary: '#3b82f6' }
             })">
            
            <!-- Chart Header -->
            <div class="chart-header">
                <h5 class="chart-title">Bitcoin Price Trend</h5>
                <p class="chart-subtitle">Professional line chart with time controls</p>
                
                <!-- Status Indicator -->
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="status-indicator status-success">
                        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                        Live Data
                    </span>
                    <small class="text-muted" x-text="formattedLastUpdate"></small>
                </div>
            </div>
            
            <!-- Chart Toolbar -->
            @include('components.chart-toolbar', [
                'showTimeRanges' => true,
                'showIntervals' => false,
                'showScaleToggle' => true,
                'showExport' => true
            ])
            
            <!-- Professional Chart Container -->
            @include('components.professional-chart', [
                'chartId' => 'demo-chart-1',
                'height' => '400px',
                'showMetrics' => true,
                'metrics' => [
                    ['label' => 'Current Price', 'value' => '$65,420.00', 'change' => '+2.5%', 'trend' => 'up'],
                    ['label' => '24h Volume', 'value' => '$28.5B', 'change' => '-1.2%', 'trend' => 'down'],
                    ['label' => 'Market Cap', 'value' => '$1.28T', 'change' => '+0.8%', 'trend' => 'up']
                ]
            ])
        </div>

        <!-- Example 2: Multi-Dataset Chart -->
        <div class="chart-card" 
             x-data="chartController({
                 containerId: 'demo-chart-2',
                 dataEndpoint: '/api/demo-data/multi',
                 chartType: 'line',
                 defaultPeriod: '7d',
                 colors: { 
                     primary: '#10b981', 
                     secondary: '#f59e0b',
                     danger: '#ef4444'
                 }
             })">
            
            <div class="chart-header">
                <h5 class="chart-title">On-Chain Metrics</h5>
                <p class="chart-subtitle">Multiple datasets with dual Y-axis</p>
                
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="status-indicator status-warning">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 0.5rem;"></i>
                        Delayed 15min
                    </span>
                    <small class="text-muted" x-text="formattedLastUpdate"></small>
                </div>
            </div>
            
            @include('components.chart-toolbar', [
                'showTimeRanges' => true,
                'showIntervals' => true,
                'showScaleToggle' => true,
                'showExport' => true
            ])
            
            @include('components.professional-chart', [
                'chartId' => 'demo-chart-2',
                'height' => '400px',
                'showMetrics' => true,
                'metrics' => [
                    ['label' => 'MVRV Ratio', 'value' => '2.15', 'change' => '+0.05', 'trend' => 'up'],
                    ['label' => 'Z-Score', 'value' => '1.42', 'change' => '-0.12', 'trend' => 'down'],
                    ['label' => 'SOPR', 'value' => '1.08', 'change' => '+0.02', 'trend' => 'up']
                ]
            ])
        </div>

        <!-- Example 3: Bar Chart -->
        <div class="chart-card" 
             x-data="chartController({
                 containerId: 'demo-chart-3',
                 dataEndpoint: '/api/demo-data/bar',
                 chartType: 'bar',
                 defaultPeriod: '30d',
                 colors: { primary: '#8b5cf6' }
             })">
            
            <div class="chart-header">
                <h5 class="chart-title">Exchange Netflows</h5>
                <p class="chart-subtitle">Daily inflow/outflow analysis</p>
                
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="status-indicator status-success">
                        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                        Real-time
                    </span>
                    <small class="text-muted" x-text="formattedLastUpdate"></small>
                </div>
            </div>
            
            @include('components.chart-toolbar', [
                'showTimeRanges' => true,
                'showIntervals' => false,
                'showScaleToggle' => false,
                'showExport' => true
            ])
            
            @include('components.professional-chart', [
                'chartId' => 'demo-chart-3',
                'height' => '350px',
                'showMetrics' => true,
                'metrics' => [
                    ['label' => 'Net Flow', 'value' => '-2.4%', 'change' => 'Outflow', 'trend' => 'down'],
                    ['label' => 'Total Inflow', 'value' => '48.2%', 'change' => '+1.2%', 'trend' => 'up'],
                    ['label' => 'Total Outflow', 'value' => '50.6%', 'change' => '+3.6%', 'trend' => 'up']
                ]
            ])
        </div>

        <!-- Example 4: Minimal Chart -->
        <div class="chart-card" 
             x-data="chartController({
                 containerId: 'demo-chart-4',
                 dataEndpoint: '/api/demo-data/minimal',
                 chartType: 'line',
                 defaultPeriod: '1d',
                 colors: { primary: '#ef4444' }
             })">
            
            <div class="chart-header">
                <h5 class="chart-title">Minimal Configuration</h5>
                <p class="chart-subtitle">Simple chart with basic controls</p>
            </div>
            
            @include('components.chart-toolbar', [
                'showTimeRanges' => true,
                'showIntervals' => false,
                'showScaleToggle' => false,
                'showExport' => false
            ])
            
            @include('components.professional-chart', [
                'chartId' => 'demo-chart-4',
                'height' => '300px',
                'showMetrics' => false
            ])
        </div>

    </div>

    <!-- Usage Documentation -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title">
                        <i class="bi bi-book me-2"></i>
                        Usage Documentation
                    </h5>
                    <p class="chart-subtitle">How to implement these reusable chart components</p>
                </div>
                
                <div class="p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">1. Basic Implementation</h6>
                            <pre class="bg-light p-3 rounded"><code>&lt;div x-data="chartController({
    containerId: 'my-chart',
    dataEndpoint: '/api/my-data',
    chartType: 'line',
    defaultPeriod: '30d'
})"&gt;
    @include('components.chart-toolbar')
    @include('components.professional-chart', [
        'chartId' => 'my-chart',
        'height' => '400px'
    ])
&lt;/div&gt;</code></pre>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">2. Configuration Options</h6>
                            <ul class="list-unstyled">
                                <li><strong>containerId:</strong> Unique chart container ID</li>
                                <li><strong>dataEndpoint:</strong> API endpoint for data</li>
                                <li><strong>chartType:</strong> line, bar, area, etc.</li>
                                <li><strong>defaultPeriod:</strong> 1d, 7d, 30d, 90d, 1y</li>
                                <li><strong>colors:</strong> Custom color scheme</li>
                                <li><strong>showLegend:</strong> Show/hide legend</li>
                                <li><strong>responsive:</strong> Responsive behavior</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="fw-bold mb-3">3. Component Features</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="glass-effect p-3 rounded">
                                        <h6 class="text-primary">Chart Toolbar</h6>
                                        <ul class="small mb-0">
                                            <li>Time range selector</li>
                                            <li>Interval controls</li>
                                            <li>Scale toggle (Linear/Log)</li>
                                            <li>Export functionality</li>
                                            <li>Reset zoom</li>
                                            <li>Share options</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="glass-effect p-3 rounded">
                                        <h6 class="text-success">Professional Chart</h6>
                                        <ul class="small mb-0">
                                            <li>Responsive canvas</li>
                                            <li>Loading states</li>
                                            <li>Error handling</li>
                                            <li>Metrics display</li>
                                            <li>Professional styling</li>
                                            <li>Dark mode support</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="glass-effect p-3 rounded">
                                        <h6 class="text-warning">Base Controller</h6>
                                        <ul class="small mb-0">
                                            <li>Data management</li>
                                            <li>State synchronization</li>
                                            <li>Event handling</li>
                                            <li>API integration</li>
                                            <li>Chart lifecycle</li>
                                            <li>Utility functions</li>
                                        </ul>
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
// Demo data endpoints (mock)
function refreshAllCharts() {
    // Trigger refresh for all chart controllers
    document.querySelectorAll('[x-data*="chartController"]').forEach(el => {
        if (el._x_dataStack && el._x_dataStack[0].refreshData) {
            el._x_dataStack[0].refreshData();
        }
    });
}

// Mock API endpoints for demo
if (!window.mockApiSetup) {
    window.mockApiSetup = true;
    
    // Intercept fetch requests for demo endpoints
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        if (url.includes('/api/demo-data/')) {
            return new Promise(resolve => {
                setTimeout(() => {
                    const mockData = generateMockData(url);
                    resolve({
                        ok: true,
                        json: () => Promise.resolve(mockData)
                    });
                }, 500); // Simulate network delay
            });
        }
        return originalFetch.apply(this, arguments);
    };
}

function generateMockData(endpoint) {
    const days = 30;
    const labels = Array.from({length: days}, (_, i) => {
        const date = new Date();
        date.setDate(date.getDate() - (days - 1 - i));
        return date.toISOString().split('T')[0];
    });
    
    if (endpoint.includes('line')) {
        return {
            labels,
            datasets: [{
                label: 'Bitcoin Price',
                data: Array.from({length: days}, () => 
                    65000 + (Math.random() - 0.5) * 10000
                ),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true
            }]
        };
    }
    
    if (endpoint.includes('multi')) {
        return {
            labels,
            datasets: [
                {
                    label: 'MVRV',
                    data: Array.from({length: days}, () => 2 + Math.random()),
                    borderColor: '#10b981',
                    yAxisID: 'y'
                },
                {
                    label: 'Z-Score',
                    data: Array.from({length: days}, () => 1 + Math.random()),
                    borderColor: '#f59e0b',
                    yAxisID: 'y1'
                }
            ]
        };
    }
    
    if (endpoint.includes('bar')) {
        return {
            labels,
            datasets: [{
                label: 'Net Flow',
                data: Array.from({length: days}, () => (Math.random() - 0.5) * 10),
                backgroundColor: function(context) {
                    const value = context.parsed.y;
                    return value >= 0 ? 'rgba(239, 68, 68, 0.7)' : 'rgba(16, 185, 129, 0.7)';
                }
            }]
        };
    }
    
    // Default minimal data
    return {
        labels: labels.slice(-7),
        datasets: [{
            label: 'Value',
            data: Array.from({length: 7}, () => Math.random() * 100),
            borderColor: '#ef4444'
        }]
    };
}
</script>
@endsection