@props(['symbol' => 'BINANCE:BTCUSDT', 'height' => '520px'])

<div class="df-panel flex-grow-1 p-0 overflow-hidden">
    <div class="df-chart-container" id="tradingChart" style="min-height: {{ $height }};">
        <!-- Chart will be rendered here -->
    </div>
</div>

<script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        new TradingView.widget({
            "autosize": true,
            "symbol": "{{ $symbol }}",
            "interval": "D",
            "timezone": "Etc/UTC",
            "theme": "dark",
            "style": "1",
            "locale": "en",
            "toolbar_bg": "#1e293b",
            "enable_publishing": false,
            "withdateranges": true,
            "range": "1M",
            "hide_side_toolbar": false,
            "allow_symbol_change": true,
            "details": true,
            "hotlist": true,
            "calendar": false,
            "studies": [
                "RSI@tv-basicstudies",
                "MACD@tv-basicstudies"
            ],
            "container_id": "tradingChart"
        });
    });
</script>
