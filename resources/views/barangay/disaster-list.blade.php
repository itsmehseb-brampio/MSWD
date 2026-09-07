@extends('layouts.barangay')

@section('title', $pageTitle)
@section('headerTitle', $pageTitle)

@section('head')
<style>
.page-card {
    background: #fff;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.page-head { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
.page-ico {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.page-head h3 { font-size: 1.12rem; color: #222; }
.page-head .sub { font-size: 0.8rem; color: #999; }
.status-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.status-nav a {
    padding: 8px 17px;
    border-radius: 24px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #555;
    background: #fff;
    border: 1.5px solid #e2e8e4;
    transition: all 0.15s;
}
.status-nav a.status-approved { border-color: #28a745; color: #28a745; }
.status-nav a.status-declined { border-color: #dc3545; color: #dc3545; }
.status-nav a.status-processing { border-color: #fd7e14; color: #fd7e14; }
.status-nav a.status-reedit { border-color: #6f42c1; color: #6f42c1; }
.status-nav a.status-history { border-color: #0072C6; color: #0072C6; }
.status-nav a:hover { transform: translateY(-1px); }
.status-nav a.active { color: #fff !important; box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
.status-nav a.status-approved.active { background: #28a745; }
.status-nav a.status-declined.active { background: #dc3545; }
.status-nav a.status-processing.active { background: #fd7e14; }
.status-nav a.status-reedit.active { background: #6f42c1; }
.status-nav a.status-history.active { background: #0072C6; }

.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
.toolbar .card-title { font-size: 1rem; color: #333; font-weight: 700; }
.search-form { display: flex; gap: 8px; }
.search-form input {
    padding: 9px 14px;
    border: 1.5px solid #e2e8e4;
    border-radius: 9px;
    font-size: 0.86rem;
    min-width: 240px;
}
.search-form input:focus { outline: none; border-color: #11998e; }
.search-form button {
    padding: 9px 18px;
    border: none;
    border-radius: 9px;
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
}
.table-wrap { overflow-x: auto; }
table.list-table { width: 100%; border-collapse: collapse; min-width: 720px; }
.list-table thead th {
    text-align: left;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #888;
    border-bottom: 2px solid #eef2ef;
    padding: 10px 12px;
    white-space: nowrap;
}
.list-table tbody td {
    padding: 13px 12px;
    border-bottom: 1px solid #f2f5f3;
    font-size: 0.88rem;
    color: #444;
    vertical-align: middle;
}
.list-table tbody tr.clickable { cursor: pointer; }
.list-table tbody tr.clickable:hover { background: #f7fbf9; }
.list-table tbody tr.expanded { background: #f0fbf7; }
.fmt-no { font-weight: 700; color: #222; }
.badge { padding: 4px 13px; border-radius: 18px; font-size: 0.72rem; font-weight: 700; color: #fff; white-space: nowrap; }
.badge-approved { background: #28a745; }
.badge-pending { background: #fd7e14; }
.badge-declined { background: #dc3545; }
.badge-reedit { background: #6f42c1; }
.badge-cancelled { background: #6c757d; }
.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 13px;
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
}
.action-btn.blue { background: #0072C6; }
.action-btn.purple { background: #6f42c1; }
.expand-col { text-align: center; }
.detail-row td { background: #fbfdfc; padding: 18px 20px; }
.detail-inner { display: grid; gap: 14px; }
.detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
.dg-item { background: #fff; border: 1px solid #eef2ef; border-radius: 9px; padding: 11px 13px; }
.dg-item .dl { font-size: 0.7rem; color: #999; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; }
.dg-item .dv { font-size: 0.9rem; color: #222; font-weight: 600; word-break: break-word; }
.reason-box {
    background: #fdf0f0;
    border: 1px solid #f2c6c6;
    color: #9c1c1c;
    border-radius: 9px;
    padding: 12px 15px;
    font-size: 0.86rem;
}
.photos-row { display: flex; gap: 10px; flex-wrap: wrap; }
.photos-row img { width: 96px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e9e6; }
.sig-row { display: flex; gap: 26px; justify-content: center; flex-wrap: wrap; padding-top: 12px; border-top: 1px dashed #e2e8e4; }
.sig-box { text-align: center; min-width: 190px; }
.sig-line { border-bottom: 1.5px solid #999; margin-bottom: 8px; }
.sig-label { font-size: 0.84rem; font-weight: 700; color: #333; }
.sig-sub { font-size: 0.73rem; color: #888; margin-top: 2px; }
.empty-state { text-align: center; padding: 40px 0; color: #aaa; font-size: 0.9rem; }
.count-note { font-size: 0.8rem; color: #999; }
</style>
@endsection

@section('content')

@php
    $routeFor = [
        'approved' => route('barangay.disaster.approved'),
        'pending' => route('barangay.disaster.pending'),
        'declined' => route('barangay.disaster.declined'),
        'reedit' => route('barangay.disaster.reedit'),
        'history' => route('barangay.disaster.history'),
    ];
@endphp

<div class="page-card">
    <div class="page-head">
        <div class="page-ico" style="background:{{ $accent }};"><i class="fas {{ $pageIcon }}"></i></div>
        <div>
            <h3>{{ $cardTitle }}</h3>
            <div class="sub">{{ $barangay->barangay_name }}</div>
        </div>
    </div>

    <div class="status-nav">
        @foreach ($statusNav as $nav)
            <a class="status-{{ $nav['key'] }} {{ $nav['active'] ? 'active' : '' }}" href="{{ $routeFor[$nav['key']] }}">
                {{ $nav['label'] }}
            </a>
        @endforeach
    </div>

    <div class="toolbar">
        <span class="card-title"><i class="fas fa-list-alt" style="color:{{ $accent }};"></i> {{ $cardTitle }}</span>
        <form class="search-form" method="GET" action="{{ $routeFor[$pageKey] }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by report no., name, type...">
            <button type="submit" style="background:{{ $accent }};"><i class="fas fa-search"></i></button>
        </form>
    </div>

    @if ($reports->isNotEmpty())
        <div class="count-note">Showing <b>{{ $reports->count() }}</b> report(s)</div>
    @endif

    <div class="table-wrap">
        <table class="list-table">
            <thead>
                <tr>
                    <th>Format No</th>
                    <th>Household Name</th>
                    <th>Disaster Type</th>
                    <th>Damage</th>
                    @if ($showReason)
                        <th>DSWD Instructions</th>
                    @endif
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $r)
                    <tr class="clickable" onclick="toggleDetail(this)">
                        <td class="fmt-no">{{ $r->format_no }}</td>
                        <td>{{ $r->household_head }}</td>
                        <td>{{ $r->disaster_type }}</td>
                        <td>{{ $r->damage_extent }}</td>
                        @if ($showReason)
                            <td style="max-width:180px;">
                                <span style="font-size:0.85rem;color:#666;">{{ \Illuminate\Support\Str::limit($r->decline_reason ?? 'No instructions', 60) }}</span>
                            </td>
                        @endif
                        <td>{{ $r->date_formatted }}<br><small style="color:#aaa;">{{ $r->time_formatted }}</small></td>
                        <td>
                            @if ($badgeText)
                                <span class="badge" style="background:{{ $accent }};">{{ $badgeText }}</span>
                            @else
                                <span class="badge badge-{{ strtolower($r->status ?? 'pending') }}">{{ ucfirst($r->status ?? 'pending') }}</span>
                            @endif
                        </td>
                        <td>
                            <a class="action-btn {{ $isReedit ? 'purple' : 'blue' }}"
                               href="{{ $isReedit ? route('barangay.disaster.edit', ['report' => $r->report_id]) . '?reedit=1' : route('barangay.disaster.edit', ['report' => $r->report_id]) }}">
                                <i class="fas fa-edit"></i> {{ $isReedit ? 'Edit & Resubmit' : 'Edit' }}
                            </a>
                        </td>
                        <td class="expand-col"><i class="fas fa-chevron-down" style="color:#bbb;"></i></td>
                    </tr>
                    <tr class="detail-row" style="display:none;">
                        <td colspan="{{ $showReason ? 9 : 8 }}">
                            <div class="detail-inner">
                                @if ($showReason && !empty($r->decline_reason))
                                    <div class="reason-box">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <b>DSWD Instructions:</b> {{ $r->decline_reason }}
                                    </div>
                                @endif
                                <div class="detail-grid">
                                    <div class="dg-item"><div class="dl">Format No</div><div class="dv">{{ $r->format_no }}</div></div>
                                    <div class="dg-item"><div class="dl">Household Head</div><div class="dv">{{ $r->household_head }}</div></div>
                                    <div class="dg-item"><div class="dl">Disaster Type</div><div class="dv">{{ $r->disaster_type }}</div></div>
                                    <div class="dg-item"><div class="dl">Family Members</div><div class="dv">{{ $r->family_members }}</div></div>
                                    <div class="dg-item"><div class="dl">Full Address</div><div class="dv">{{ $r->full_address }}</div></div>
                                    <div class="dg-item"><div class="dl">Housing Type</div><div class="dv">{{ $r->housing_type }}</div></div>
                                    <div class="dg-item"><div class="dl">Damage Extent</div><div class="dv">{{ $r->damage_extent }}</div></div>
                                    <div class="dg-item"><div class="dl">Submitted</div><div class="dv">{{ $r->date_formatted }} {{ $r->time_formatted }}</div></div>
                                </div>
                                @if ($r->description)
                                    <div class="dg-item"><div class="dl">Description / Remarks</div><div class="dv">{{ $r->description }}</div></div>
                                @endif
                                @php
                                    $photos = array_filter([$r->pic1, $r->pic2, $r->pic3, $r->pic4]);
                                @endphp
                                @if (count($photos) > 0)
                                    <div class="photos-row">
                                        @foreach ($photos as $p)
                                            <a href="{{ uimg($p) }}" target="_blank"><img src="{{ uimg($p) }}" alt="Damage photo"></a>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="sig-row">
                                    <div class="sig-box">
                                        <div class="sig-line"></div>
                                        <div class="sig-label">{{ $details->captain_name ?? 'Punong Barangay' }}</div>
                                        <div class="sig-sub">Punong Barangay</div>
                                    </div>
                                    <div class="sig-box">
                                        <div class="sig-line"></div>
                                        <div class="sig-label">{{ $details->secretary_name ?? 'Barangay Secretary' }}</div>
                                        <div class="sig-sub">Barangay Secretary</div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showReason ? 9 : 8 }}">
                            <div class="empty-state">
                                <i class="fas fa-inbox" style="font-size:2.2rem;color:#c8d3cd;display:block;margin-bottom:10px;"></i>
                                No {{ $pageKey === 'history' ? 'reports' : $pageTitle }} found{{ $search ? ' for "' . e($search) . '"' : '' }}.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleDetail(row) {
    var next = row.nextElementSibling;
    if (!next || next.classList.contains('detail-row') === false) return;
    var show = next.style.display !== 'table-row';
    var allDetails = document.querySelectorAll('.detail-row');
    allDetails.forEach(function (d) { d.style.display = 'none'; });
    document.querySelectorAll('tbody tr.clickable').forEach(function (r) { r.classList.remove('expanded'); });
    if (show) {
        next.style.display = 'table-row';
        row.classList.add('expanded');
        row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
}
</script>
@endpush