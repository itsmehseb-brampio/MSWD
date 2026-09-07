@extends('layouts.admin')
@section('title', ucfirst($status) . ' Disaster Reports')

@push('head')
<style>
.status-nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;background:white;padding:12px 16px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.status-btn{padding:8px 18px;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;transition:all 0.2s;border:2px solid transparent;}
.status-approved{background:#d4edda;color:#155724;}
.status-approved:hover,.status-approved.active{background:#28a745;color:white;}
.status-declined{background:#f8d7da;color:#721c24;}
.status-declined:hover,.status-declined.active{background:#dc3545;color:white;}
.status-processing{background:#fff3cd;color:#856404;}
.status-processing:hover,.status-processing.active{background:#fd7e14;color:white;}
.status-reedit{background:#e8daef;color:#6f42c1;}
.status-reedit:hover,.status-reedit.active{background:#6f42c1;color:white;}
.status-history{background:#d1ecf1;color:#0c5460;}
.status-history:hover,.status-history.active{background:#0072C6;color:white;}
.search-row{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.search-wrapper{position:relative;flex:1;max-width:400px;}
.search-wrapper i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#999;font-size:0.9rem;}
.search-wrapper input{width:100%;padding:9px 14px 9px 38px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.9rem;transition:border-color 0.3s;}
.search-wrapper input:focus{outline:none;border-color:#fd7e14;}
.page-card{background:white;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,0.08);overflow:hidden;}
.page-card h2{color:#fd7e14;padding:18px 25px;border-bottom:2px solid #f0f0f0;display:flex;align-items:center;gap:10px;font-size:1.1rem;margin:0;}
.badge{padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;background:#fff3cd;color:#856404;}
.badge-green{background:#d4edda;color:#155724;}
.badge-red{background:#f8d7da;color:#721c24;}
.badge-purple{background:#e8daef;color:#6f42c1;}
.badge-blue{background:#d1ecf1;color:#0c5460;}
.badge-gray{background:#e9ecef;color:#666;}
.action-cell{display:flex;gap:6px;flex-wrap:wrap;}
.btn-action{padding:5px 12px;border-radius:6px;font-size:0.8rem;font-weight:600;text-decoration:none;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all 0.2s;}
.btn-view{cursor:pointer;background:#e3f2fd;color:#1565c0;}
.btn-view:hover{background:#bbdefb;}
.btn-approve{background:#d4edda;color:#155724;}
.btn-approve:hover{background:#c3e6cb;}
.btn-decline{background:#f8d7da;color:#721c24;}
.btn-decline:hover{background:#f5c6cb;}
.btn-reedit{background:#e8daef;color:#6f42c1;}
.btn-reedit:hover{background:#ddd0ec;}
.empty-msg{text-align:center;color:#999;padding:50px;}
.empty-msg i{font-size:2.5rem;margin-bottom:10px;display:block;color:#ddd;}

/* Bond paper */
.bond-paper{width:100%;max-width:210mm;margin:0 auto;background:white;padding:40px 50px 35px;box-shadow:0 4px 20px rgba(0,0,0,0.15);}
.bond-header{text-align:center;border-bottom:3px double #333;padding-bottom:20px;margin-bottom:25px;}
.bond-header .repub{font-size:11px;text-transform:uppercase;letter-spacing:2px;color:#555;}
.bond-header h1{font-size:18px;text-transform:uppercase;margin:5px 0;letter-spacing:1px;}
.bond-header h2{font-size:14px;font-weight:400;margin:3px 0;}
.bond-header .dept{font-size:11px;color:#666;}
.bond-title-bar{background:#f5f5f5;border:1px solid #ddd;padding:12px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;border-radius:4px;flex-wrap:wrap;gap:10px;}
.bond-title-bar .ref{font-size:13px;color:#555;}
.bond-title-bar .ref strong{color:#333;}
.bond-title-bar .sbadge{padding:4px 14px;border-radius:12px;font-size:11px;font-weight:700;}
.sbadge-green{background:#d4edda;color:#155724;}
.sbadge-red{background:#f8d7da;color:#721c24;}
.sbadge-orange{background:#fff3cd;color:#856404;}
.sbadge-purple{background:#e8daef;color:#6f42c1;}
.bond-table{width:100%;border-collapse:collapse;margin-bottom:20px;}
.bond-table td{padding:10px 12px;border:1px solid #ccc;font-size:13px;vertical-align:top;}
.bond-table td:first-child{font-weight:600;width:190px;color:#444;background:#fafafa;}
.bond-desc{border:1px solid #ccc;border-radius:4px;margin-bottom:20px;}
.bond-desc h4{padding:8px 12px;background:#f5f5f5;border-bottom:1px solid #ccc;font-size:12px;text-transform:uppercase;color:#555;margin:0;}
.bond-desc p{padding:12px;font-size:13px;color:#333;min-height:40px;line-height:1.5;margin:0;}
.decline-box{background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;padding:12px 16px;margin-bottom:20px;}
.decline-box strong{color:#721c24;}
.decline-box p{color:#721c24;margin-top:5px;font-size:13px;}

.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:2000;justify-content:center;align-items:center;}
.modal-overlay.show{display:flex;}
.modal{background:white;border-radius:12px;padding:30px;width:90%;max-width:500px;box-shadow:0 10px 40px rgba(0,0,0,0.3);}
.modal h3{margin-bottom:15px;font-size:1.1rem;}
.modal textarea{width:100%;height:100px;padding:12px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.9rem;resize:vertical;font-family:inherit;}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:15px;}
.modal-actions button{padding:8px 20px;border-radius:6px;font-size:0.85rem;font-weight:600;cursor:pointer;border:none;}
.modal-cancel{background:#e0e0e0;color:#333;}
.modal-confirm-red{background:#dc3545;color:white;}
.modal-confirm-purple{background:#6f42c1;color:white;}
.bond-photo-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.bond-photo-item{text-align:center;}
.bond-photo-item img{width:100%;border:1px solid #ddd;border-radius:4px;}
.bond-photo-item .pl{font-size:11px;color:#666;margin-top:6px;font-weight:600;}
.bond-photo-placeholder{border:1px dashed #ccc;border-radius:4px;padding:40px 20px;color:#ccc;font-size:12px;}
.bond-b2b{text-align:center;margin-top:15px;}
.bond-b2b img{max-width:280px;border:1px solid #ddd;border-radius:4px;}
.bond-b2b-placeholder{border:2px dashed #ccc;border-radius:8px;padding:40px;display:inline-block;color:#ccc;font-size:14px;}
.v-actions{display:flex;gap:8px;justify-content:flex-end;margin-bottom:10px;flex-wrap:wrap;}
/* Visual reports */
.dchart-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:22px;}
.dchart-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;}
.dchart-card .dchart-head{padding:12px 18px;border-bottom:2px solid #f0f0f0;font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:8px;}
.dchart-wrap{position:relative;height:240px;padding:12px 14px 6px;}
.dchart-empty{display:none;text-align:center;color:#bbb;padding:55px 10px;font-size:0.8rem;}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="status-nav">
    <a href="{{ route('admin.disaster.approved') }}" class="status-btn status-approved {{ $status==='approved'?'active':'' }}"><i class="fas fa-check-circle"></i> Approved ({{ $counts['approved'] }})</a>
    <a href="{{ route('admin.disaster.declined') }}" class="status-btn status-declined {{ $status==='declined'?'active':'' }}"><i class="fas fa-times-circle"></i> Declined ({{ $counts['declined'] }})</a>
    <a href="{{ route('admin.disaster.pending') }}" class="status-btn status-processing {{ $status==='pending'?'active':'' }}"><i class="fas fa-clock"></i> Processing ({{ $counts['pending'] }})</a>
    <a href="{{ route('admin.disaster.reedit') }}" class="status-btn status-reedit {{ $status==='reedit'?'active':'' }}"><i class="fas fa-redo"></i> Re-edit ({{ $counts['reedit'] }})</a>
    <a href="{{ route('admin.disaster.history') }}" class="status-btn status-history {{ $status==='history'?'active':'' }}"><i class="fas fa-history"></i> History ({{ $counts['history'] }})</a>
</div>

<div class="search-row">
    <form method="GET" style="display:flex;align-items:center;gap:10px;width:100%;">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by format, barangay, household..." value="{{ $search ?? '' }}">
        </div>
        <button type="submit" class="btn btn-orange"><i class="fas fa-search"></i></button>
        @if ($search)
            <a href="{{ request()->url() }}" class="btn btn-gray"><i class="fas fa-times"></i></a>
        @endif
    </form>
</div>

<div class="dchart-row">
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-chart-line" style="color:#0072C6;"></i> Reports Over Time</div>
        <div class="dchart-wrap"><canvas id="dcTime"></canvas></div>
        <div class="dchart-empty" id="dcTimeEmpty">No reports yet</div>
    </div>
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
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-clipboard-check" style="color:#28a745;"></i> Reports by Status</div>
        <div class="dchart-wrap"><canvas id="dcStatus"></canvas></div>
        <div class="dchart-empty" id="dcStatusEmpty">No reports yet</div>
    </div>
    <div class="dchart-card">
        <div class="dchart-head"><i class="fas fa-house-damage" style="color:#dc3545;"></i> Damage Extent (Totally vs Partially)</div>
        <div class="dchart-wrap"><canvas id="dcDamage"></canvas></div>
        <div class="dchart-empty" id="dcDamageEmpty">No damage data yet</div>
    </div>
</div>

<div class="page-card">
    <h2><i class="fas fa-clipboard-list"></i> {{ ucfirst($status) }} Reports <span style="margin-left:auto;font-size:0.8rem;color:#999;">{{ count($reports) }} result(s)</span></h2>
    @if (count($reports) > 0)
        <table>
            <thead><tr>
                <th>Format No.</th><th>Barangay</th><th>Household Name</th><th>Disaster Type</th><th>Damage</th><th>Date</th><th>Status</th><th>Action</th>
            </tr></thead>
            <tbody>
                @foreach ($reports as $row)
                <tr>
                    <td>{{ $row->format_no ?? 'N/A' }}</td>
                    <td>{{ $row->barangay->barangay_name ?? 'N/A' }}</td>
                    <td>{{ $row->household_head ?? 'N/A' }}</td>
                    <td>{{ $row->disaster_type ?? 'N/A' }}</td>
                    <td>{{ $row->damage_extent ?? 'N/A' }}</td>
                    <td>{{ optional($row->created_at)->format('M d, Y') }}</td>
                    <td>
                        @if ($row->status === 'approved')<span class="badge badge-green">Approved</span>
                        @elseif ($row->status === 'declined')<span class="badge badge-red">Declined</span>
                        @elseif ($row->status === 'cancelled')<span class="badge badge-gray">Cancelled</span>
                        @elseif ($row->status === 'reedit')<span class="badge badge-purple">Re-edit</span>
                        @else<span class="badge">Pending</span>@endif
                    </td>
                    <td><div class="action-cell"><button class="btn-action btn-view" onclick="openReportModal(@json($row))"><i class="fas fa-eye"></i> View</button></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-msg"><i class="fas fa-inbox"></i><p>No {{ $status }} reports found.</p></div>
    @endif
</div>

<div class="modal-overlay" id="declineModal">
    <div class="modal">
        <h3 style="color:#dc3545;"><i class="fas fa-exclamation-triangle"></i> Decline Report</h3>
        <p style="font-size:0.85rem;color:#666;margin-bottom:12px;">Please provide a reason so the barangay can revise and resubmit.</p>
        <textarea id="declineReason" placeholder="Enter decline reason / instructions for revision..."></textarea>
        <div class="modal-actions">
            <button class="modal-cancel" onclick="closeDeclineModal()">Cancel</button>
            <button class="modal-confirm-red" onclick="submitAction('declined')"><i class="fas fa-times"></i> Decline</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="reeditModal">
    <div class="modal">
        <h3 style="color:#6f42c1;"><i class="fas fa-redo"></i> Send Back for Re-edit</h3>
        <p style="font-size:0.85rem;color:#666;margin-bottom:12px;">Provide instructions for the barangay to revise this report.</p>
        <textarea id="reeditReason" placeholder="Enter instructions for revision..."></textarea>
        <div class="modal-actions">
            <button class="modal-cancel" onclick="closeReeditModal()">Cancel</button>
            <button class="modal-confirm-purple" onclick="submitAction('reedit')"><i class="fas fa-redo"></i> Send for Re-edit</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="reportModal">
    <div class="modal" style="max-width:820px;max-height:90vh;overflow-y:auto;padding:0;">
        <div id="reportModalBody"></div>
    </div>
</div>

<form id="actionForm" method="POST" action="{{ route('admin.disaster.review') }}">
    @csrf
    <input type="hidden" name="id" id="actionFormId">
    <input type="hidden" name="action" id="actionFormAction">
    <input type="hidden" name="decline_reason" id="actionFormReason">
</form>
@endsection

@push('scripts')
<script>
(function(){
    const D = @json($dchart);
    function dcSetState(cid, eid, has){
        const c=document.getElementById(cid), e=document.getElementById(eid);
        if(!c||!e)return;
        c.style.display = has ? '' : 'none';
        e.style.display = has ? 'none' : 'block';
    }
    function dcMount(cid, eid, type, labels, datasets, opts){
        const has = labels.length > 0 && datasets.some(ds => (ds.data||[]).some(v => v > 0));
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
                        const v = ctx.parsed.y || ctx.parsed || 0;
                        const t = ctx.dataset.data.reduce((a,b)=>a+b,0);
                        const p = t ? (v / t * 100).toFixed(1) : 0;
                        return ' ' + ctx.label + ': ' + v + (ctx.dataset.label ? ' ' + ctx.dataset.label : '') + ' (' + p + '%)';
                    } } }
                }
            }, opts || {})
        });
    }
    dcMount('dcTime','dcTimeEmpty','line', D.months||[], [{ label: 'Reports', data: D.monthly||[], borderColor: '#0072C6', backgroundColor: 'rgba(0,114,198,0.15)', fill: true, tension: 0.3, pointRadius: 4, borderWidth: 2 }], { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed.y + ' report' + (ctx.parsed.y === 1 ? '' : 's') } } } });
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
let currentId = null;

function openReportModal(r) {
    currentId = r.report_id;
    document.getElementById('reportModalBody').innerHTML = makeBondHTML(r);
    document.getElementById('reportModal').classList.add('show');
}

function closeReportModal() {
    document.getElementById('reportModal').classList.remove('show');
}
document.getElementById('reportModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});

function openDeclineModal(id) { currentId = id; document.getElementById('declineReason').value=''; document.getElementById('declineModal').classList.add('show'); }
function closeDeclineModal() { document.getElementById('declineModal').classList.remove('show'); }
function openReeditModal(id) { currentId = id; document.getElementById('reeditReason').value=''; document.getElementById('reeditModal').classList.add('show'); }
function closeReeditModal() { document.getElementById('reeditModal').classList.remove('show'); }
document.getElementById('declineModal').addEventListener('click', function(e){ if(e.target===this) this.classList.remove('show'); });
document.getElementById('reeditModal').addEventListener('click', function(e){ if(e.target===this) this.classList.remove('show'); });

function submitAction(action) {
    if (!currentId) return;
    document.getElementById('actionFormId').value = currentId;
    document.getElementById('actionFormAction').value = action;
    document.getElementById('actionFormReason').value = action === 'approved' ? '' : (action === 'declined' ? document.getElementById('declineReason').value : document.getElementById('reeditReason').value);
    document.getElementById('actionForm').submit();
}

function approveReport(id) {
    if (!confirm('Are you sure you want to approve this report?')) return;
    currentId = id;
    submitAction('approved');
}

function esc(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : 'N/A'; }

function makeBondHTML(r) {
    const statusClass = r.status==='approved'?'sbadge-green':(r.status==='declined'||r.status==='cancelled'?'sbadge-red':(r.status==='reedit'?'sbadge-purple':'sbadge-orange'));
    const declineReason = r.decline_reason ? '<div class="decline-box"><strong><i class="fas fa-exclamation-circle"></i> Decline Reason:</strong><p>'+esc(r.decline_reason)+'</p></div>' : '';
    const photos = [1,2,3,4].map(i => {
        const p = r['pic'+i];
        return '<div class="bond-photo-item">' + (p ? '<img src="{{ asset('uploads') }}/' + esc(p) + '">' : '<div class="bond-photo-placeholder">No Photo</div>') + '<div class="pl">Photo '+i+'</div></div>';
    }).join('');
    const b2b = r.b2b_id ? '<img src="{{ asset('uploads') }}/'+esc(r.b2b_id)+'">' : '<div class="bond-b2b-placeholder"><i class="fas fa-id-card" style="font-size:40px;display:block;margin-bottom:10px;"></i>No B2B ID uploaded</div>';

    let actions = '';
    if (@json(in_array($status, ['pending']))) {
        actions = '<button class="btn btn-green" onclick="approveReport('+r.report_id+')"><i class="fas fa-check"></i> Approve</button><button class="btn btn-purple" onclick="event.stopPropagation();openReeditModal('+r.report_id+')"><i class="fas fa-redo"></i> Re-edit</button><button class="btn btn-red" onclick="event.stopPropagation();openDeclineModal('+r.report_id+')"><i class="fas fa-times"></i> Decline</button>';
    } else if (@json(in_array($status, ['declined']))) {
        actions = '<button class="btn btn-green" onclick="approveReport('+r.report_id+')"><i class="fas fa-check"></i> Approve</button>';
    }

    return '<div class="bond-paper" style="box-shadow:none;">' +
        '<div class="v-actions">' + actions +
        '<button class="btn btn-blue" onclick="window.print()"><i class="fas fa-print"></i> Print</button>' +
        '<button class="btn btn-gray" onclick="closeReportModal()"><i class="fas fa-times"></i> Close</button></div>' +
        '<div class="bond-header"><div class="repub">Republic of the Philippines</div><h1>Province of Albay</h1><h2>Municipality of Malilipot</h2><div class="dept">Disaster Risk Reduction Management Office</div></div>' +
        '<div class="bond-title-bar"><div class="ref"><strong>Report No.:</strong> '+esc(r.format_no)+'</div><div class="ref"><strong>Barangay:</strong> '+esc(r.barangay ? r.barangay.barangay_name : 'N/A')+'</div><span class="sbadge '+statusClass+'">'+String(r.status).toUpperCase()+'</span></div>' +
        declineReason +
        '<table class="bond-table"><tr><td>Report Title</td><td>'+esc(r.title)+'</td></tr><tr><td>Type of Disaster</td><td>'+esc(r.disaster_type)+'</td></tr><tr><td>Name of Household Head</td><td>'+esc(r.household_head)+'</td></tr><tr><td>Number of Family Members</td><td>'+esc(r.family_members)+'</td></tr><tr><td>Full Address</td><td>'+esc(r.full_address)+'</td></tr><tr><td>Housing Type</td><td>'+esc(r.housing_type)+'</td></tr><tr><td>Extent of Damage</td><td>'+esc(r.damage_extent)+'</td></tr><tr><td>Date Reported</td><td>'+(r.created_at?new Date(r.created_at).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'}):'N/A')+'</td></tr></table>' +
        '<div class="bond-desc"><h4>Description / Remarks</h4><p>'+(esc(r.description)||'No remarks provided.')+'</p></div>' +
        '<div class="bond-annex" style="text-align:center;border-bottom:1px solid #ddd;padding-bottom:12px;margin-bottom:20px;"><div class="atitle" style="font-size:11px;color:#555;text-transform:uppercase;">Annex A</div><h2 style="font-size:16px;">Photographs of Damage</h2><div style="font-size:11px;color:#888;">'+esc(r.format_no)+' — '+esc(r.barangay ? r.barangay.barangay_name : '')+'</div></div>' +
        '<div class="bond-photo-grid">'+photos+'</div>' +
        '<div class="bond-annex" style="text-align:center;border-bottom:1px solid #ddd;padding-bottom:12px;margin:20px 0;"><div class="atitle" style="font-size:11px;color:#555;text-transform:uppercase;">Annex B</div><h2 style="font-size:16px;">B2B ID / Valid Identification</h2></div>' +
        '<div class="bond-b2b">'+b2b+'</div>' +
        '<div style="text-align:center;margin-top:25px;font-size:11px;color:#888;font-style:italic;">This document serves as the official disaster report submitted to the Municipal DSWD Office.</div>' +
        '</div>';
}
</script>
@endpush

