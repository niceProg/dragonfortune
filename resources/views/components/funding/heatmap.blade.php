{{--
    Komponen: Funding Heatmap (Exchange × Time)
    Mendengarkan event "funding-overview-ready" dan merender grid heatmap

    Props:
    - $title: string
--}}

<div class="df-panel p-3" x-data="fundingHeatmap()" x-init="init()">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">{{ $title ?? 'Funding Heatmap' }}</h5>
        <small class="text-secondary" x-text="lastUpdated ? 'Updated ' + timeAgo(lastUpdated) : 'Waiting data...'">Waiting data...</small>
    </div>

    <div class="table-responsive" x-show="rows.length > 0">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-secondary small" style="white-space: nowrap;">Exchange</th>
                    <template x-for="(tsLabel, idx) in columns" :key="idx">
                        <th class="text-secondary small text-center" x-text="tsLabel"></th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in rows" :key="row.exchange">
                    <tr>
                        <td class="small fw-semibold" x-text="row.exchange"></td>
                        <template x-for="cell in row.cells" :key="cell.ts">
                            <td class="text-center" :title="formatTitle(cell)" :style="cellStyle(cell)">
                                <span class="small fw-semibold" x-text="formatCell(cell)"></span>
                            </td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="text-center py-4 text-secondary" x-show="rows.length === 0">
        No data yet. Change filters or refresh.
    </div>
</div>

<script>
function fundingHeatmap() {
    return {
        rows: [],
        columns: [],
        lastUpdated: null,

        init() {
            window.addEventListener('funding-overview-ready', (e) => {
                const overview = e.detail || {};
                this.lastUpdated = overview?.meta?.last_updated || Date.now();
                this.buildGrid(overview?.timeseries_by_exchange || {});
            });
        },

        buildGrid(byExchange) {
            // Collect column timestamps across exchanges
            const tsSet = new Set();
            Object.values(byExchange).forEach(arr => arr.forEach(p => tsSet.add(p.ts)));
            const tsList = Array.from(tsSet).sort((a,b)=>a-b);
            this.columns = tsList.map(ts => new Date(ts).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false}));

            // Build rows
            this.rows = Object.entries(byExchange).map(([exchange, arr]) => {
                const map = new Map(arr.map(p => [p.ts, p.funding_rate]));
                const cells = tsList.map(ts => ({ ts, rate: map.get(ts) ?? null }));
                return { exchange, cells };
            });
        },

        // Styling based on funding rate sign and magnitude
        cellStyle(cell) {
            if (cell.rate === null || cell.rate === undefined) return 'background: transparent;';
            const r = parseFloat(cell.rate);
            const mag = Math.min(Math.abs(r) / 0.02, 1); // cap at 2%
            const alpha = 0.15 + 0.35 * mag;
            const color = r >= 0 ? `rgba(34, 197, 94, ${alpha})` : `rgba(239, 68, 68, ${alpha})`;
            return `background:${color};`;
        },

        formatCell(cell) {
            if (cell.rate === null || cell.rate === undefined) return '—';
            const percent = (parseFloat(cell.rate) * 100).toFixed(3);
            return `${percent}%`;
        },

        formatTitle(cell) {
            const ts = new Date(cell.ts).toISOString().replace('T',' ').slice(0,16) + ' UTC';
            const val = this.formatCell(cell);
            return `${ts} • ${val}`;
        },

        timeAgo(ts) {
            const diff = Math.max(0, Date.now() - ts);
            const m = Math.floor(diff / 60000);
            if (m < 1) return 'just now';
            if (m < 60) return `${m}m ago`;
            const h = Math.floor(m / 60);
            return `${h}h ago`;
        }
    };
}
</script>

<style>
table.table td {
    transition: background-color 0.2s ease;
}
</style>


