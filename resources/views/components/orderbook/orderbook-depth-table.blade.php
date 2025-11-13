{{-- Orderbook Depth Table Component --}}
<div class="df-panel p-3" x-data="orderbookDepthTable()" x-init="init()">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📋 Orderbook Depth Details</h5>
        <span class="badge bg-secondary" x-show="loading">Loading...</span>
    </div>

    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-sm table-hover">
            <thead class="sticky-top bg-dark">
                <tr>
                    <th>Level</th>
                    <th>Bid Price</th>
                    <th>Bid Qty</th>
                    <th>Bid Total</th>
                    <th>Ask Price</th>
                    <th>Ask Qty</th>
                    <th>Ask Total</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="!loading && depths.length > 0">
                    <template x-for="depth in depths" :key="depth.level">
                        <tr>
                            <td class="small" x-text="depth.level"></td>
                            <td class="small text-success" x-text="formatPrice(depth.bid_price)"></td>
                            <td class="small" x-text="formatQuantity(depth.bid_quantity)"></td>
                            <td class="small text-success" x-text="formatTotal(depth.bid_total)"></td>
                            <td class="small text-danger" x-text="formatPrice(depth.ask_price)"></td>
                            <td class="small" x-text="formatQuantity(depth.ask_quantity)"></td>
                            <td class="small text-danger" x-text="formatTotal(depth.ask_total)"></td>
                        </tr>
                    </template>
                </template>
                <template x-if="!loading && depths.length === 0">
                    <tr>
                        <td colspan="7" class="text-center text-secondary">No orderbook depth data available</td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

