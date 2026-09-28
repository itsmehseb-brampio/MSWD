<?php
/**
 * Shared visual-report block for disaster report pages.
 * Expects $dchart array:
 *   scope  => 'admin'|'barangay'
 *   months => array, monthly => array          (Reports Over Time line)
 *   barangays => array, barangay_reports => array  (Reports by Barangay line - admin only)
 *   status_by_brgy => ['labels'=>..,'pending'=>..,'approved'=>..,..]  (status lines per barangay - admin only)
 *   status => array keyed by pending/approved/declined/cancelled/reedit (status doughnut)
 *   damage => ['Totally'=>n,'Partially'=>n]    (damage doughnut)
 *
 * Layout contract (identical on every report page):
 *   - line charts sit in one grid row, pie/doughnut charts in the row below
 *   - every card is exactly the same height; nothing grows with title or data
 *   - the legend is rendered OUTSIDE the canvas in a fixed-height strip, so a
 *     pie with 5 slices gets the same plot area as one with 2
 *   - auto-fill (not auto-fit) keeps a lone chart in the last row from stretching
 */
if (!isset($dchart) || !is_array($dchart)) return;
$scope = $dchart['scope'] ?? 'admin';
$hasBarangay = $scope === 'admin' && !empty($dchart['barangays']);
$hasStatus = !empty($dchart['status']);
?>
<style>
/* ---- chart grid: uniform cards, fixed proportions ---- */
.dchart-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;margin-bottom:22px;align-items:start;}
.dchart-card{height:364px;display:flex;flex-direction:column;box-sizing:border-box;min-width:0;background:#fff;border:1px solid #e6ebf2;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);overflow:hidden;}
/* fixed-height header: a long title can never change the card height */
.dchart-head{flex:0 0 46px;box-sizing:border-box;display:flex;align-items:center;gap:8px;padding:0 16px;border-bottom:1px solid #eef2f7;font-weight:700;font-size:.85rem;white-space:nowrap;overflow:hidden;}
.dchart-head i{flex:0 0 auto;font-size:.95rem;}
.dchart-head span{overflow:hidden;text-overflow:ellipsis;}
/* the plot area: one height for every chart on the page */
.dchart-wrap{position:relative;height:280px;padding:14px 16px 8px;box-sizing:border-box;}
/* fixed-height legend strip, outside the canvas, so plot areas stay identical */
.dchart-legend{flex:0 0 38px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;gap:14px;padding:0 12px;border-top:1px solid #eef2f7;font-size:.72rem;color:#4a5562;overflow:hidden;}
.dchart-legend b{display:inline-flex;align-items:center;gap:5px;font-weight:600;white-space:nowrap;}
.dchart-legend .dot{width:9px;height:9px;border-radius:2px;flex:0 0 auto;}
/* empty state overlays the same fixed area, so no-data cards keep full height */
.dchart-empty{position:absolute;left:16px;right:16px;top:14px;bottom:8px;display:none;align-items:center;justify-content:center;text-align:center;color:#a5afbd;font-size:.8rem;font-weight:600;}
@media(max-width:1100px){.dchart-row{grid-template-columns:repeat(auto-fill,minmax(300px,1fr));}.dchart-wrap{height:260px;}.dchart-card{height:344px;}}
@media(max-width:640px){.dchart-row{grid-template-columns:1fr;}.dchart-wrap{height:240px;}.dchart-card{height:324px;}}
</style>

<!-- LINE CHARTS -->
<div class="dchart-row lines">
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-chart-line" style="color:#0072C6;"></i><span>Reports Over Time</span></div>
        <div class="dchart-wrap"><canvas id="dcTime"></canvas><div class="dchart-empty" id="dcTimeEmpty">No reports yet</div></div>
        <div class="dchart-legend" id="dcTimeLegend"></div>
    </div>
    <?php if ($hasBarangay): ?>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-chart-line" style="color:#fd7e14;"></i><span>Reports by Barangay</span></div>
        <div class="dchart-wrap"><canvas id="dcBarangay"></canvas><div class="dchart-empty" id="dcBarangayEmpty">No reports yet</div></div>
        <div class="dchart-legend" id="dcBarangayLegend"></div>
    </div>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-chart-line" style="color:#6f42c1;"></i><span>Status by Barangay</span></div>
        <div class="dchart-wrap"><canvas id="dcStatusBrgy"></canvas><div class="dchart-empty" id="dcStatusBrgyEmpty">No reports yet</div></div>
        <div class="dchart-legend" id="dcStatusBrgyLegend"></div>
    </div>
    <?php endif; ?>
</div>

<!-- PIE / DOUGHNUT CHARTS -->
<div class="dchart-row pies">
    <?php if ($hasStatus): ?>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-clipboard-check" style="color:#28a745;"></i><span>Reports by Status</span></div>
        <div class="dchart-wrap"><canvas id="dcStatus"></canvas><div class="dchart-empty" id="dcStatusEmpty">No reports yet</div></div>
        <div class="dchart-legend" id="dcStatusLegend"></div>
    </div>
    <?php endif; ?>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-house-damage" style="color:#dc3545;"></i><span>Damage Extent (Totally vs Partially)</span></div>
        <div class="dchart-wrap"><canvas id="dcDamage"></canvas><div class="dchart-empty" id="dcDamageEmpty">No damage data yet</div></div>
        <div class="dchart-legend" id="dcDamageLegend"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const D = <?php echo json_encode($dchart); ?>;

    /* ---- one shared palette for every chart on every report page ---- */
    const CHART_PALETTE = {
        pending:'#fd7e14', approved:'#28a745', declined:'#dc3545',
        cancelled:'#6c757d', reedit:'#6f42c1',
        damage:['#dc3545','#fd7e14'],
        series:['#0072C6','#7c5cff','#28a745','#fd7e14','#dc3545']
    };
    const STATUS_META = [
        ['pending','Processing'], ['approved','Approved'], ['declined','Declined'],
        ['cancelled','Cancelled'], ['reedit','Re-edit']
    ];

    /* ---- uniform canvas styling for every chart ---- */
    const GRID = '#eef2f7', TICK = '#6b7684';
    const BASE_SCALES = {
        x: { grid:{ color:GRID, drawBorder:false }, ticks:{ color:TICK, font:{ size:10 }, maxRotation:0, autoSkip:true, maxTicksLimit:8 } },
        y: { beginAtZero:true, grid:{ color:GRID, drawBorder:false }, ticks:{ color:TICK, font:{ size:10 }, precision:0 } }
    };

    function setState(cid, eid, lid, has){
        document.getElementById(cid).style.display = has ? '' : 'none';
        document.getElementById(eid).style.display = has ? 'none' : 'flex';
        if (!has) document.getElementById(lid).innerHTML = '';
    }

    /* Legend lives outside the canvas so the plot area is always the same size */
    function paintLegend(lid, labels, datasets, isPie){
        const el = document.getElementById(lid);
        if (!el) return;
        const items = [];
        if (isPie) {
            const c = datasets[0].backgroundColor;
            labels.forEach((l, i) => items.push({ label:l, color: Array.isArray(c) ? c[i] : c }));
        } else {
            datasets.forEach(ds => items.push({ label: ds.label, color: ds.borderColor }));
        }
        el.innerHTML = items.map(it =>
            '<b><span class="dot" style="background:' + it.color + '"></span>' + it.label + '</b>'
        ).join('');
    }

    function mount(cid, eid, lid, type, labels, datasets, tip){
        const isPie = (type === 'doughnut' || type === 'pie');
        const has = labels.length > 0 && datasets.some(ds => ds.data.some(v => v > 0));
        setState(cid, eid, lid, has);
        if (!has) return;
        paintLegend(lid, labels, datasets, isPie);
        new Chart(document.getElementById(cid), {
            type: type,
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                layout: { padding: 2 },
                plugins: {
                    /* legend is rendered in our own fixed-height strip */
                    legend: { display: false },
                    tooltip: { callbacks: { label: tip || ((ctx) => {
                        const v = ctx.parsed.y != null ? ctx.parsed.y : ctx.parsed;
                        const t = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const p = t ? (v / t * 100).toFixed(1) : 0;
                        return ' ' + ctx.label + ': ' + v + (ctx.dataset.label ? ' ' + ctx.dataset.label : '') + ' (' + p + '%)';
                    }) } }
                },
                scales: isPie ? undefined : BASE_SCALES
            }
        });
    }

    const countTip = (ctx) => ' ' + ctx.parsed.y + ' report' + (ctx.parsed.y === 1 ? '' : 's');

    mount('dcTime','dcTimeEmpty','dcTimeLegend','line', D.months, [
        { label:'Reports', data:D.monthly, borderColor:CHART_PALETTE.series[0], backgroundColor:'rgba(0,114,198,.12)', fill:true, tension:.3, pointRadius:4, borderWidth:2 }
    ], countTip);

    if (D.barangays && D.barangays.length) {
        mount('dcBarangay','dcBarangayEmpty','dcBarangayLegend','line', D.barangays, [
            { label:'Reports', data:D.barangay_reports, borderColor:CHART_PALETTE.series[1], backgroundColor:'rgba(124,92,255,.12)', fill:true, tension:.3, pointRadius:4, borderWidth:2 }
        ], countTip);
    }

    if (D.status_by_brgy && D.status_by_brgy.labels && D.status_by_brgy.labels.length) {
        const sbDs = STATUS_META.map((m, i) => ({
            label: m[1], data: D.status_by_brgy[m[0]],
            borderColor: CHART_PALETTE[m[0]], backgroundColor: 'transparent',
            fill: false, tension: .3, pointRadius: 4, borderWidth: 2
        }));
        mount('dcStatusBrgy','dcStatusBrgyEmpty','dcStatusBrgyLegend','line', D.status_by_brgy.labels, sbDs,
            (ctx) => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y);
    }

    if (D.status) {
        const lbl = [], dat = [], col = [];
        STATUS_META.forEach(m => { if (D.status[m[0]] > 0) { lbl.push(m[1]); dat.push(D.status[m[0]]); col.push(CHART_PALETTE[m[0]]); } });
        mount('dcStatus','dcStatusEmpty','dcStatusLegend','doughnut', lbl, [
            { data: dat, backgroundColor: col, borderWidth: 2, borderColor: '#fff' }
        ]);
    }

    mount('dcDamage','dcDamageEmpty','dcDamageLegend','doughnut', ['Totally','Partially'], [
        { data: [D.damage.Totally, D.damage.Partially], backgroundColor: CHART_PALETTE.damage, borderWidth: 2, borderColor: '#fff' }
    ]);
})();
</script>
