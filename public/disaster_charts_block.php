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
 */
if (!isset($dchart) || !is_array($dchart)) return;
$scope = $dchart['scope'] ?? 'admin';
$hasBarangay = $scope === 'admin' && !empty($dchart['barangays']);
$hasStatus = !empty($dchart['status']);
?>
<style>
.dchart-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:22px;}
.dchart-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;}
.dchart-card .dchart-head{padding:12px 18px;border-bottom:2px solid #f0f0f0;font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:8px;}
.dchart-wrap{position:relative;height:240px;padding:12px 14px 6px;}
.dchart-empty{display:none;text-align:center;color:#bbb;padding:55px 10px;font-size:0.8rem;}
</style>
<div class="dchart-row">
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-chart-line" style="color:#0072C6;"></i> Reports Over Time</div>
        <div class="dchart-wrap"><canvas id="dcTime"></canvas></div>
        <div class="dchart-empty" id="dcTimeEmpty">No reports yet</div>
    </div>
    <?php if ($hasBarangay): ?>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-chart-line" style="color:#fd7e14;"></i> Reports by Barangay</div>
        <div class="dchart-wrap"><canvas id="dcBarangay"></canvas></div>
        <div class="dchart-empty" id="dcBarangayEmpty">No reports yet</div>
    </div>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-chart-line" style="color:#6f42c1;"></i> Status by Barangay</div>
        <div class="dchart-wrap"><canvas id="dcStatusBrgy"></canvas></div>
        <div class="dchart-empty" id="dcStatusBrgyEmpty">No reports yet</div>
    </div>
    <?php endif; ?>
    <?php if ($hasStatus): ?>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-clipboard-check" style="color:#28a745;"></i> Reports by Status</div>
        <div class="dchart-wrap"><canvas id="dcStatus"></canvas></div>
        <div class="dchart-empty" id="dcStatusEmpty">No reports yet</div>
    </div>
    <?php endif; ?>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-house-damage" style="color:#dc3545;"></i> Damage Extent (Totally vs Partially)</div>
        <div class="dchart-wrap"><canvas id="dcDamage"></canvas></div>
        <div class="dchart-empty" id="dcDamageEmpty">No damage data yet</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const D = <?php echo json_encode($dchart); ?>;
    function dcSetState(cid, eid, has){
        document.getElementById(cid).style.display = has ? '' : 'none';
        document.getElementById(eid).style.display = has ? 'none' : 'block';
    }
    function dcMount(cid, eid, type, labels, datasets, opts){
        const has = labels.length > 0 && datasets.some(ds => ds.data.some(v => v > 0));
        dcSetState(cid, eid, has);
        if (!has) return;
        new Chart(document.getElementById(cid), {
            type: type,
            data: { labels: labels, datasets: datasets },
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                    tooltip: { callbacks: { label: (ctx) => {
                        const v = ctx.parsed.y || ctx.parsed;
                        const t = ctx.dataset.data.reduce((a,b)=>a+b,0);
                        const p = t ? (v / t * 100).toFixed(1) : 0;
                        return ' ' + ctx.label + ': ' + v + (ctx.dataset.label ? ' ' + ctx.dataset.label : '') + ' (' + p + '%)';
                    } } }
                }
            }, opts || {})
        });
    }
    dcMount('dcTime','dcTimeEmpty','line', D.months, [{ label: 'Reports', data: D.monthly, borderColor: '#0072C6', backgroundColor: 'rgba(0,114,198,0.15)', fill: true, tension: 0.3, pointRadius: 4, borderWidth: 2 }], { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed.y + ' report' + (ctx.parsed.y === 1 ? '' : 's') } } } });
    if (D.barangays && D.barangays.length) {
        dcMount('dcBarangay','dcBarangayEmpty','line', D.barangays, [{ label: 'Reports', data: D.barangay_reports, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.15)', fill: true, tension: 0.3, pointRadius: 4, borderWidth: 2 }], { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed.y + ' report' + (ctx.parsed.y === 1 ? '' : 's') } } } });
    }
    if (D.status_by_brgy && D.status_by_brgy.labels && D.status_by_brgy.labels.length) {
        const sbMeta = [['pending','Processing','#fd7e14'],['approved','Approved','#28a745'],['declined','Declined','#dc3545'],['cancelled','Cancelled','#6c757d'],['reedit','Re-edit','#6f42c1']];
        const sbDs = sbMeta.map(m => ({ label: m[1], data: D.status_by_brgy[m[0]], borderColor: m[2], backgroundColor: m[2] + '22', fill: false, tension: 0.3, pointRadius: 4, borderWidth: 2 }));
        dcMount('dcStatusBrgy','dcStatusBrgyEmpty','line', D.status_by_brgy.labels, sbDs, { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { tooltip: { callbacks: { label: (ctx) => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y } } } });
    }
    if (D.status) {
        const meta = [['pending','Processing','#fd7e14'],['approved','Approved','#28a745'],['declined','Declined','#dc3545'],['cancelled','Cancelled','#6c757d'],['reedit','Re-edit','#6f42c1']];
        const lbl = [], dat = [], col = [];
        meta.forEach(m => { if (D.status[m[0]] > 0) { lbl.push(m[1]); dat.push(D.status[m[0]]); col.push(m[2]); } });
        dcMount('dcStatus','dcStatusEmpty','doughnut', lbl, [{ data: dat, backgroundColor: col, borderWidth: 2, borderColor: '#fff' }]);
    }
    dcMount('dcDamage','dcDamageEmpty','doughnut', ['Totally','Partially'], [{ data: [D.damage.Totally, D.damage.Partially], backgroundColor: ['#dc3545','#fd7e14'], borderWidth: 2, borderColor: '#fff' }]);
})();
</script>
