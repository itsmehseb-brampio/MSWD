@extends('layouts.admin')
@section('title', 'Admin Dashboard - Data Management System')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:22px;}
.stat-card{background:white;border-radius:16px;padding:20px;display:flex;align-items:center;gap:16px;box-shadow:0 4px 14px rgba(0,0,0,0.07);position:relative;overflow:hidden;transition:transform 0.2s, box-shadow 0.2s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(0,0,0,0.12);}
.stat-card::after{content:'';position:absolute;right:-22px;top:-22px;width:84px;height:84px;border-radius:50%;opacity:.14;background:currentColor;}
.stat-card>i{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;color:#fff;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,0.18);}
.stat-card.sc-b{color:#0072C6;}.stat-card.sc-b>i{background:linear-gradient(135deg,#0a6cff,#00a3ff);}
.stat-card.sc-r{color:#dc3545;}.stat-card.sc-r>i{background:linear-gradient(135deg,#e53935,#ff7043);}
.stat-card.sc-o{color:#f57c00;}.stat-card.sc-o>i{background:linear-gradient(135deg,#f57c00,#ffb300);}
.stat-card.sc-g{color:#28a745;}.stat-card.sc-g>i{background:linear-gradient(135deg,#2e7d32,#66bb6a);}
.stat-card b{font-size:1.65rem;color:#222;display:block;line-height:1.1;}
.stat-card span{font-size:0.78rem;color:#8892a3;font-weight:600;}
.risk-row{display:grid;grid-template-columns:1fr 1.25fr;gap:16px;margin-bottom:22px;}
.risk-panel{background:white;border-radius:16px;box-shadow:0 4px 14px rgba(0,0,0,0.07);overflow:hidden;}
.risk-head{display:flex;align-items:center;gap:9px;padding:15px 20px;border-bottom:2px solid #f0f0f0;font-weight:700;font-size:0.92rem;color:#333;}
.risk-chart-wrap{position:relative;height:250px;padding:16px 14px 8px;}
.risk-donut-center{position:absolute;top:46%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;}
.risk-donut-center b{display:block;font-size:1.9rem;line-height:1;color:#222;}
.risk-donut-center span{font-size:0.68rem;color:#8892a3;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
.risk-list{padding:16px 20px 18px;}
.risk-line{display:flex;align-items:center;gap:11px;margin-bottom:12px;}
.r-ico{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.18);}
.r-ico.r-low{background:linear-gradient(135deg,#43e97b,#28a745);}
.r-ico.r-medium{background:linear-gradient(135deg,#ffb74d,#fd7e14);}
.r-ico.r-high{background:linear-gradient(135deg,#ff7a7a,#dc3545);}
.r-ico.r-critical{background:linear-gradient(135deg,#b34444,#7b1a1a);}
.risk-line .rname{min-width:58px;font-size:0.76rem;font-weight:700;text-align:right;}
.risk-line .rname.r-low{color:#28a745;}
.risk-line .rname.r-medium{color:#fd7e14;}
.risk-line .rname.r-high{color:#dc3545;}
.risk-line .rname.r-critical{color:#7b1a1a;}
.risk-bar{flex:1;height:12px;background:#eef1f4;border-radius:8px;overflow:hidden;}
.risk-bar-fill{height:100%;border-radius:8px;transition:width 0.7s ease;display:flex;align-items:center;justify-content:flex-end;color:#fff;font-size:0.62rem;font-weight:700;padding-right:6px;}
.risk-bar-fill.r-low{background:linear-gradient(90deg,#43e97b,#28a745);}
.risk-bar-fill.r-medium{background:linear-gradient(90deg,#ffb74d,#fd7e14);}
.risk-bar-fill.r-high{background:linear-gradient(90deg,#ff7a7a,#dc3545);}
.risk-bar-fill.r-critical{background:linear-gradient(90deg,#b34444,#7b1a1a);}
.risk-count{font-weight:700;font-size:0.92rem;color:#222;min-width:22px;text-align:right;}
.risk-names{font-size:0.78rem;color:#5a6673;margin:3px 0 12px;padding-left:4px;line-height:1.55;}
.risk-names-lbl{font-weight:700;margin-right:4px;}
.risk-names-lbl.r-critical-txt{color:#7b1a1a;}
.risk-names-lbl.r-high-txt{color:#dc3545;}
.risk-names-lbl.r-medium-txt{color:#fd7e14;}
.risk-names-lbl.r-low-txt{color:#28a745;}
.risk-legend-total{display:flex;align-items:center;justify-content:space-between;margin-top:6px;padding:10px 14px;background:#f8fafc;border-radius:10px;font-size:0.78rem;color:#5a6673;font-weight:600;}
.reminder-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px;}
.reminder-panel{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;}
.reminder-header{padding:12px 18px;font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:8px;border-bottom:2px solid #f0f0f0;}
.reminder-body{padding:12px 18px;max-height:180px;overflow-y:auto;}
.reminder-item{padding:8px 0;border-bottom:1px solid #f5f5f5;display:flex;align-items:flex-start;gap:10px;}
.reminder-item:last-child{border-bottom:none;}
.reminder-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:6px;}
.reminder-dot.red{background:#dc3545;}
.reminder-dot.orange{background:#fd7e14;}
.reminder-dot.blue{background:#0072C6;}
.reminder-dot.green{background:#00D09C;}
.reminder-title{font-weight:600;font-size:0.82rem;color:#333;}
.reminder-sub{font-size:0.73rem;color:#999;margin-top:2px;}
.reminder-empty{padding:15px;text-align:center;color:#bbb;font-size:0.8rem;}
.chart-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:24px;}
.chart-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;}
.chart-card .card-header{padding:12px 18px;border-bottom:2px solid #f0f0f0;font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:8px;}
.chart-wrap{position:relative;height:240px;padding:12px 14px 6px;}
.chart-empty{display:none;text-align:center;color:#a5afbd;padding:45px 10px;font-size:0.8rem;font-weight:600;}
.chart-empty i{display:block;font-size:2.2rem;color:#ccd5e0;margin-bottom:12px;}
.search-box{width:100%;max-width:500px;padding:12px 18px 12px 45px;border:2px solid #e0e0e0;border-radius:12px;font-size:1rem;transition:border-color 0.3s,box-shadow 0.3s;background:white;}
.search-box:focus{outline:none;border-color:#0072C6;box-shadow:0 0 0 3px rgba(0,114,198,0.1);}
.search-wrapper{position:relative;margin-bottom:20px;}
.search-wrapper i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#999;font-size:1rem;}
tr.data-row{cursor:pointer;transition:background 0.2s;}
tr.data-row:hover{background:#f0f7ff;}
.tabs-bar{display:flex;background:#e8ecf0;padding:0;min-height:40px;align-items:flex-end;overflow-x:auto;border-bottom:2px solid #ddd;margin:-25px -30px 22px;}
.tab{display:flex;align-items:center;gap:8px;padding:10px 18px;background:#dde2e8;border-radius:8px 8px 0 0;cursor:pointer;font-size:0.85rem;font-weight:500;color:#555;white-space:nowrap;margin-right:2px;transition:background 0.2s;position:relative;}
.tab:hover{background:#cdd3da;}
.tab.active{background:#f5f7fa;color:#0072C6;font-weight:700;border:2px solid #ddd;border-bottom:2px solid #f5f7fa;margin-bottom:-2px;}
.tab .close-tab{margin-left:8px;width:18px;height:18px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:11px;color:#888;transition:all 0.2s;}
.tab .close-tab:hover{background:#dc3545;color:white;}
.tab-home{padding:10px 18px;background:#0072C6;color:white;border-radius:8px 8px 0 0;cursor:pointer;font-size:0.85rem;font-weight:600;display:flex;align-items:center;gap:6px;}
.tab-home.active{background:#005999;}
.tab-content{display:none;}
.tab-content.active{display:block;}
.detail-header{background:linear-gradient(135deg,#0072C6,#005999);color:white;padding:25px 30px;border-radius:12px;margin-bottom:20px;}
.detail-header h2{font-size:1.6rem;margin-bottom:5px;}
.detail-header p{opacity:0.85;}
.stat-mini{background:white;border-radius:12px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,0.06);text-align:center;cursor:pointer;transition:transform 0.2s, box-shadow 0.2s;}
.stat-mini:hover{transform:translateY(-3px);box-shadow:0 6px 18px rgba(0,0,0,0.1);}
.stat-mini .num{font-size:1.8rem;font-weight:700;color:#0072C6;}
.stat-mini .label{font-size:0.85rem;color:#888;margin-top:4px;}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:15px;margin-bottom:25px;}
.info-card{background:white;border-radius:12px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,0.06);}
.info-card h4{color:#0072C6;margin-bottom:8px;font-size:0.95rem;}
.info-card p{color:#555;font-size:0.9rem;line-height:1.5;}
.report-list{max-height:400px;overflow-y:auto;}
.report-item{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid #f0f0f0;transition:background 0.2s;}
.report-item:hover{background:#f9f9f9;}
.report-item:last-child{border-bottom:none;}
.report-item .info{flex:1;}
.report-item .info strong{color:#333;}
.report-item .info span{display:block;font-size:0.8rem;color:#999;margin-top:2px;}
@media(max-width:900px){.risk-row,.reminder-row{grid-template-columns:1fr;}.tabs-bar{margin:-25px -30px 22px;}}
</style>
@endpush

@section('content')
<div class="tabs-bar" id="tabsBar">
    <div class="tab-home active" id="tabHome" onclick="showHome()">
        <i class="fas fa-home"></i> Dashboard
    </div>
</div>

<div class="tab-content active" id="viewHome">
<div class="stat-row">
    <div class="stat-card sc-b"><i class="fas fa-building"></i><div><b>{{ $totalBarangays }}</b><span>Total Barangays</span></div></div>
    <div class="stat-card sc-r"><i class="fas fa-exclamation-triangle"></i><div><b>{{ $highCriticalCount }}</b><span>High / Critical Risk</span></div></div>
    <div class="stat-card sc-o"><i class="fas fa-file-alt"></i><div><b>{{ array_sum($chart_json['status']) }}</b><span>Total Disaster Reports</span></div></div>
    <div class="stat-card sc-g"><i class="fas fa-home"></i><div><b>{{ number_format($totalHouseholdsAll) }}</b><span>Total Households</span></div></div>
</div>

<div class="risk-row">
    <div class="risk-panel">
        <div class="risk-head"><i class="fas fa-chart-pie" style="color:#7c5cff;"></i> Risk Level Distribution</div>
        <div class="risk-chart-wrap">
            <canvas id="riskChart"></canvas>
            <div class="risk-donut-center"><b>{{ $totalBarangays }}</b><span>Barangays</span></div>
        </div>
    </div>
    <div class="risk-panel">
        <div class="risk-head"><i class="fas fa-list-check" style="color:#0072C6;"></i> Barangay Risk Overview</div>
        <div class="risk-list">
            @php
            $riskLevelsOrder = ['Critical', 'High', 'Medium', 'Low'];
            $riskIcons = ['Critical' => 'fa-skull-crossbones', 'High' => 'fa-triangle-exclamation', 'Medium' => 'fa-exclamation', 'Low' => 'fa-circle-check'];
            @endphp
            @foreach ($riskLevelsOrder as $lvl)
                @php $pct = $totalBarangays ? round($riskCounts[$lvl] / $totalBarangays * 100) : 0; @endphp
                <div class="risk-line">
                    <span class="r-ico r-{{ strtolower($lvl) }}"><i class="fas {{ $riskIcons[$lvl] }}"></i></span>
                    <span class="rname r-{{ strtolower($lvl) }}">{{ $lvl }}</span>
                    <div class="risk-bar"><div class="risk-bar-fill r-{{ strtolower($lvl) }}" style="width:{{ $pct }}%;">{{ $riskCounts[$lvl] ?: '' }}</div></div>
                    <span class="risk-count">{{ $riskCounts[$lvl] }}</span>
                </div>
            @endforeach
            @if ($totalBarangays)
                @foreach ($riskLevelsOrder as $lvl)
                    @if (count($riskBarangays[$lvl]))
                        <div class="risk-names"><span class="risk-names-lbl r-{{ strtolower($lvl) }}-txt">{{ $lvl }}:</span> {{ implode(', ', $riskBarangays[$lvl]) }}</div>
                    @endif
                @endforeach
            @else
                <div class="risk-names">No barangays found.</div>
            @endif
            <div class="risk-legend-total"><span><i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i> High / Critical</span><b style="color:#dc3545;">{{ $highCriticalCount }}</b></div>
        </div>
    </div>
</div>

<div class="reminder-row">
    <div class="reminder-panel">
        <div class="reminder-header"><i class="fas fa-bullhorn" style="color:#dc3545;"></i> Latest Announcements</div>
        <div class="reminder-body">
            @forelse ($announcements as $a)
                <div class="reminder-item">
                    <div class="reminder-dot {{ $a->is_pinned ? 'red' : 'blue' }}"></div>
                    <div>
                        <div class="reminder-title">{{ $a->title }}</div>
                        <div class="reminder-sub">{{ \Illuminate\Support\Str::limit($a->message, 80) }}</div>
                    </div>
                </div>
            @empty
                <div class="reminder-empty"><i class="fas fa-inbox"></i> No announcements yet</div>
            @endforelse
        </div>
    </div>
    <div class="reminder-panel">
        <div class="reminder-header"><i class="fas fa-box-open" style="color:#fd7e14;"></i> Relief Goods Distribution</div>
        <div class="reminder-body">
            @forelse ($reliefSchedules as $s)
                @php $cls = $s->status === 'completed' ? 'green' : ($s->status === 'ongoing' ? 'orange' : 'blue'); @endphp
                <div class="reminder-item">
                    <div class="reminder-dot {{ $cls }}"></div>
                    <div>
                        <div class="reminder-title">{{ $s->title }}</div>
                        <div class="reminder-sub">{{ $s->distribution_date->format('M d, Y') }} · {{ ucfirst($s->status) }}</div>
                    </div>
                </div>
            @empty
                <div class="reminder-empty"><i class="fas fa-calendar"></i> No schedules yet</div>
            @endforelse
        </div>
    </div>
</div>

<div class="chart-row">
    <div class="chart-card">
        <div class="card-header"><i class="fas fa-users" style="color:#7c5cff;"></i> Population, Households &amp; Heads of Household</div>
        <div class="chart-wrap"><canvas id="popChart"></canvas></div>
        <div class="chart-empty" id="popChartEmpty"><i class="fas fa-users"></i>No barangay demographics yet</div>
    </div>
    <div class="chart-card">
        <div class="card-header"><i class="fas fa-clipboard-check" style="color:#0072C6;"></i> Disaster Reports by Status</div>
        <div class="chart-wrap"><canvas id="statusChart"></canvas></div>
        <div class="chart-empty" id="statusChartEmpty"><i class="fas fa-clipboard-check"></i>No disaster reports yet</div>
    </div>
    <div class="chart-card">
        <div class="card-header"><i class="fas fa-house-damage" style="color:#dc3545;"></i> Damage Extent (Totally vs Partially)</div>
        <div class="chart-wrap"><canvas id="damageChart"></canvas></div>
        <div class="chart-empty" id="damageChartEmpty"><i class="fas fa-house-damage"></i>No damage data yet</div>
    </div>
    <div class="chart-card">
        <div class="card-header"><i class="fas fa-file-alt" style="color:#fd7e14;"></i> Disaster Reports by Barangay</div>
        <div class="chart-wrap"><canvas id="reportChart"></canvas></div>
        <div class="chart-empty" id="reportChartEmpty"><i class="fas fa-file-alt"></i>No reports yet</div>
    </div>
    <div class="chart-card">
        <div class="card-header"><i class="fas fa-chart-line" style="color:#6f42c1;"></i> Status by Barangay</div>
        <div class="chart-wrap"><canvas id="statusByBrgyChart"></canvas></div>
        <div class="chart-empty" id="statusByBrgyChartEmpty"><i class="fas fa-chart-line"></i>No reports yet</div>
    </div>
</div>

<div class="search-wrapper">
    <i class="fas fa-search"></i>
    <input type="text" class="search-box" id="searchBox" placeholder="Search barangay..." oninput="filterBarangays()">
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-building"></i> All Barangays</h2>
        <span style="color:#999; font-size:0.9rem;">{{ count($barangayList) }} total</span>
    </div>
    <table>
        <thead><tr>
            <th>Barangay Name</th><th>Households</th><th>Reports</th><th>Pending</th><th>Approved</th><th>Declined</th><th>Contacts</th>
        </tr></thead>
        <tbody id="barangayTable">
            @foreach ($barangayList as $b)
                <tr class="data-row" onclick="openBarangayTab('{{ $b['id'] }}', @js($b['name']))">
                    <td><strong>{{ $b['name'] }}</strong></td>
                    <td><span class="badge badge-purple">{{ number_format($b['households']) }}</span></td>
                    <td><span class="badge badge-blue">{{ $b['reports'] }}</span></td>
                    <td><span class="badge badge-orange">{{ $b['pending'] }}</span></td>
                    <td><span class="badge badge-green">{{ $b['approved'] }}</span></td>
                    <td><span class="badge badge-red">{{ $b['declined'] }}</span></td>
                    <td><span class="badge badge-gray">{{ $b['contacts'] }}</span></td>
                </tr>
            @endforeach
            @if (empty($barangayList))
                <tr><td colspan="7" class="empty-state"><i class="fas fa-building"></i>No barangays found</td></tr>
            @endif
        </tbody>
    </table>
</div>
</div>

@foreach ($barangayList as $b)
@php
    $bid = $b['id'];
    $d = $b['detail'];
    $reportsList = $barangayReports[$bid] ?? collect();
    $contactsRow = $barangayContacts[$bid] ?? null;
@endphp
<div class="tab-content" id="view_{{ $bid }}">
    <div class="detail-header">
        <h2><i class="fas fa-building"></i> {{ $b['name'] }}</h2>
        <p>{{ $b['address'] ?: 'Municipality of Malilipot' }}</p>
    </div>

    <div class="stat-row">
        <div class="stat-mini" onclick="showSection('{{ $bid }}','reports')">
            <div class="num">{{ $b['reports'] }}</div>
            <div class="label">Total Reports</div>
        </div>
        <div class="stat-mini">
            <div class="num" style="color:#fd7e14;">{{ $b['pending'] }}</div>
            <div class="label">Pending</div>
        </div>
        <div class="stat-mini">
            <div class="num" style="color:#28a745;">{{ $b['approved'] }}</div>
            <div class="label">Approved</div>
        </div>
        <div class="stat-mini">
            <div class="num" style="color:#dc3545;">{{ $b['declined'] }}</div>
            <div class="label">Declined</div>
        </div>
        <div class="stat-mini" onclick="showSection('{{ $bid }}','info')">
            <div class="num" style="color:#666;"><i class="fas fa-info-circle"></i></div>
            <div class="label">Barangay Info</div>
        </div>
    </div>

    <div class="chart-row" style="margin-bottom:20px;">
        <div class="chart-card">
            <div class="card-header"><i class="fas fa-users" style="color:#7c5cff;"></i> Population &amp; Households</div>
            <div class="chart-wrap"><canvas id="vpop_{{ $bid }}"></canvas></div>
            <div class="chart-empty" id="vpopE_{{ $bid }}"><i class="fas fa-users"></i>No demographic data yet</div>
        </div>
        <div class="chart-card">
            <div class="card-header"><i class="fas fa-chart-line" style="color:#0072C6;"></i> Disaster Reports Over Time</div>
            <div class="chart-wrap"><canvas id="vtime_{{ $bid }}"></canvas></div>
            <div class="chart-empty" id="vtimeE_{{ $bid }}"><i class="fas fa-chart-line"></i>No reports yet</div>
        </div>
        <div class="chart-card">
            <div class="card-header"><i class="fas fa-clipboard-check" style="color:#28a745;"></i> Reports by Status</div>
            <div class="chart-wrap"><canvas id="vstatus_{{ $bid }}"></canvas></div>
            <div class="chart-empty" id="vstatusE_{{ $bid }}"><i class="fas fa-clipboard-check"></i>No reports yet</div>
        </div>
        <div class="chart-card">
            <div class="card-header"><i class="fas fa-house-damage" style="color:#dc3545;"></i> Damage Extent</div>
            <div class="chart-wrap"><canvas id="vdamage_{{ $bid }}"></canvas></div>
            <div class="chart-empty" id="vdamageE_{{ $bid }}"><i class="fas fa-house-damage"></i>No damage data yet</div>
        </div>
    </div>

    <div id="section_reports_{{ $bid }}" class="card" style="display:block;">
        <div class="card-header">
            <h2><i class="fas fa-file-alt"></i> Disaster Reports</h2>
        </div>
        <div class="report-list">
            @if (count($reportsList))
                @foreach ($reportsList as $rep)
                <div class="report-item">
                    <div class="info">
                        <strong>{{ $rep->title ?? 'Untitled' }}</strong>
                        <span>{{ $rep->disaster_type ?? '' }} | {{ optional($rep->created_at)->format('M d, Y') }}</span>
                    </div>
                    <span class="badge @if($rep->status=='approved')badge-green @elseif($rep->status=='declined')badge-red @else badge-orange @endif">{{ ucfirst($rep->status) }}</span>
                </div>
                @endforeach
            @else
                <div class="empty-state"><i class="fas fa-inbox"></i>No reports yet</div>
            @endif
        </div>
    </div>

    <div id="section_info_{{ $bid }}" class="card" style="display:block;">
        <div class="card-header">
            <h2><i class="fas fa-info-circle"></i> Barangay Information</h2>
        </div>
        <div style="padding:20px;">
            <div class="info-grid">
                <div class="info-card"><h4>Barangay Code</h4><p>{{ $d['barangay_code'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Municipality</h4><p>{{ $d['municipality'] ?? 'Malilipot' }}</p></div>
                <div class="info-card"><h4>Province</h4><p>{{ $d['province'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Region</h4><p>{{ $d['region'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Zip Code</h4><p>{{ $d['zip_code'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Barangay Captain</h4><p>{{ $d['captain_name'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Secretary</h4><p>{{ $d['secretary_name'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Treasurer</h4><p>{{ $d['treasurer_name'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Population</h4><p>{{ $d['population'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Total Households</h4><p>{{ $d['households'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Land Area</h4><p>{{ $d['land_area'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Boundaries</h4><p>{{ $d['boundaries'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Barangay Hall Address</h4><p>{{ $d['barangay_hall_address'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Health Center</h4><p>{{ $d['health_center'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Date Established</h4><p>{{ $d['date_established'] ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Emergency Services</h4><p>{{ $d['emergency_services'] ?? 'N/A' }}</p></div>
            </div>

            @if ($contactsRow)
            <h3 style="margin:20px 0 12px; color:#0072C6;"><i class="fas fa-phone"></i> Emergency Contacts</h3>
            <div class="info-grid">
                <div class="info-card"><h4>Barangay Hall</h4><p>{{ $contactsRow->barangay_hall_phone ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Chairman</h4><p>{{ $contactsRow->barangay_chairman_phone ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Secretary</h4><p>{{ $contactsRow->barangay_secretary_phone ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Tanod</h4><p>{{ $contactsRow->barangay_tanod_phone ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Police</h4><p>{{ $contactsRow->police_hotline ?? 'N/A' }}</p></div>
                <div class="info-card"><h4>Fire Department</h4><p>{{ $contactsRow->fire_hotline ?? 'N/A' }}</p></div>
            </div>
            @endif
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
const CH = @json($chart_json);
const PER = @json($per);
let bCharts = {};

function filterBarangays() {
    const q = document.getElementById('searchBox').value.toLowerCase();
    document.querySelectorAll('#barangayTable .data-row').forEach(row => {
        row.style.display = row.cells[0].textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

const C_COLORS = ['#0072C6','#28a745','#fd7e14','#dc3545','#6f42c1','#20c997','#ffc107','#e83e8c','#17a2b8','#6c757d','#e8710a','#0097a7','#00c853','#5f6368'];
function cColors(n){ return Array.from({length:n},(_,i)=>C_COLORS[i%C_COLORS.length]); }

let openTabs = {};
function showHome() {
    document.querySelectorAll('.tab-content').forEach(v => v.classList.remove('active'));
    document.getElementById('viewHome').classList.add('active');
    document.querySelectorAll('.tab, .tab-home').forEach(t => t.classList.remove('active'));
    document.getElementById('tabHome').classList.add('active');
}
function openBarangayTab(id, name) {
    if (!document.getElementById('view_' + id)) return;
    document.querySelectorAll('.tab-content').forEach(v => v.classList.remove('active'));
    document.getElementById('view_' + id).classList.add('active');
    document.querySelectorAll('.tab, .tab-home').forEach(t => t.classList.remove('active'));
    if (!openTabs[id]) {
        const tab = document.createElement('div');
        tab.className = 'tab active';
        tab.id = 'tab_' + id;
        tab.innerHTML = '<i class="fas fa-building"></i> ' + name + ' <span class="close-tab" onclick="event.stopPropagation(); closeTab(' + id + ')">&times;</span>';
        tab.onclick = function() { openBarangayTab(id, name); };
        document.getElementById('tabsBar').appendChild(tab);
        openTabs[id] = true;
    } else {
        document.getElementById('tab_' + id).classList.add('active');
    }
    document.getElementById('tabHome').classList.remove('active');
    renderBarangayCharts(id);
}
function closeTab(id) {
    const tab = document.getElementById('tab_' + id);
    if (tab) tab.remove();
    delete openTabs[id];
    const view = document.getElementById('view_' + id);
    if (view) view.classList.remove('active');
    const remaining = document.querySelectorAll('.tab');
    if (remaining.length > 0) {
        remaining[remaining.length - 1].click();
    } else {
        showHome();
    }
}
function showSection(bid, section) {
    ['reports','info'].forEach(s => {
        const el = document.getElementById('section_' + s + '_' + bid);
        if (el) el.style.display = (s === section) ? 'block' : (section === 'all' ? 'block' : 'none');
    });
}

function mountChart(canvasId, emptyId, type, labels, datasets, opts){
    const c = document.getElementById(canvasId);
    const has = labels.length > 0 && datasets.some(ds => ds.data.some(v => v > 0));
    if (!has) { c.style.display='none'; document.getElementById(emptyId).style.display='block'; return; }
    new Chart(c, {
        type: type,
        data: { labels: labels, datasets: datasets },
        options: Object.assign({
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position:'bottom', labels:{ boxWidth:12, font:{ size:10 } } } }
        }, opts || {})
    });
}
const statusMeta = [['pending','Processing','#fd7e14'],['approved','Approved','#28a745'],['declined','Declined','#dc3545'],['cancelled','Cancelled','#6c757d'],['reedit','Re-edit','#6f42c1']];
const sLbl=[],sDat=[],sCol=[];
statusMeta.forEach(m=>{ if(CH.status[m[0]]>0){ sLbl.push(m[1]); sDat.push(CH.status[m[0]]); sCol.push(m[2]); } });

mountChart('popChart','popChartEmpty','line', CH.barangays, [
    { label:'Population', data:CH.population, borderColor:'#7c5cff', backgroundColor:'rgba(124,92,255,0.12)', fill:true, tension:0.3, pointRadius:4, borderWidth:2 },
    { label:'Households', data:CH.households, borderColor:'#0072C6', backgroundColor:'rgba(0,114,198,0.12)', fill:true, tension:0.3, pointRadius:4, borderWidth:2 },
    { label:'Head of Household', data:CH.head, borderColor:'#28a745', backgroundColor:'rgba(40,167,69,0.12)', fill:true, tension:0.3, pointRadius:4, borderWidth:2 }
], { scales:{ y:{ beginAtZero:true } } });

mountChart('statusChart','statusChartEmpty','doughnut', sLbl, [{ data:sDat, backgroundColor:sCol, borderWidth:2, borderColor:'#fff' }], { plugins:{ legend:{ position:'bottom' } } });
mountChart('damageChart','damageChartEmpty','doughnut', ['Totally','Partially'], [{ data:[CH.damage.Totally, CH.damage.Partially], backgroundColor:['#dc3545','#fd7e14'], borderWidth:2, borderColor:'#fff' }], { plugins:{ legend:{ position:'bottom' } } });
mountChart('reportChart','reportChartEmpty','line', CH.barangays, [
    { label:'Reports', data:CH.reports, borderColor:'#fd7e14', backgroundColor:'rgba(253,126,20,0.15)', fill:true, tension:0.3, pointRadius:5, borderWidth:2 }
], { scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } }, plugins:{ legend:{ display:false } } });
mountChart('statusByBrgyChart','statusByBrgyChartEmpty','line', CH.status_by_brgy.labels,
    statusMeta.map(m=>({ label:m[1], data:CH.status_by_brgy[m[0]], borderColor:m[2], backgroundColor:m[2]+'22', fill:false, tension:0.3, pointRadius:4, borderWidth:2 })),
    { scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } });

(function initRiskChart(){
    const el=document.getElementById('riskChart');
    if(!el||typeof Chart==='undefined')return;
    const labels=['Critical','High','Medium','Low'];
    const colors=['#7b1a1a','#dc3545','#fd7e14','#28a745'];
    const data=labels.map(l=>CH.risk[l]||0);
    const shown=labels.filter((l,i)=>data[i]>0);
    if(!shown.length){el.style.display='none';return;}
    new Chart(el,{
        type:'doughnut',
        data:{ labels:shown, datasets:[{ data:data.filter(d=>d>0), backgroundColor:colors.filter((c,i)=>data[i]>0), borderWidth:2, borderColor:'#fff', hoverOffset:8 }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ boxWidth:12, padding:12, font:{ size:11, weight:'600' }, color:'#4a5562' } } } }
    });
})();

function bMount(id, canvasId, emptyId, type, labels, datasets, opts){
    const c=document.getElementById(canvasId);
    const has=labels.length>0&&datasets.some(ds=>ds.data.some(v=>v>0));
    if(!has){c.style.display='none';document.getElementById(emptyId).style.display='block';return null;}
    return new Chart(c,{type:type,data:{labels:labels,datasets:datasets},options:Object.assign({responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:10}}}}},opts||{})});
}

const BSTATUS_META = [['pending','Processing','#fd7e14'],['approved','Approved','#28a745'],['declined','Declined','#dc3545'],['cancelled','Cancelled','#6c757d'],['reedit','Re-edit','#6f42c1']];
function renderBarangayCharts(id){
    if (!PER[id]) return;
    (bCharts[id] || []).forEach(c => { if (c) c.destroy(); });
    bCharts[id] = [];
    const d = PER[id];

    const dLab = ['Population','Households','Head of Household'];
    const c1 = bMount(id,'vpop_'+id,'vpopE_'+id,'line', dLab, [{ label: 'Count', data: d.pop, borderColor: '#7c5cff', backgroundColor: 'rgba(124,92,255,0.12)', fill: true, tension: 0.3, pointRadius: 5, borderWidth: 2 }]);
    if (c1) bCharts[id].push(c1);

    const c2 = bMount(id,'vtime_'+id,'vtimeE_'+id,'line', d.months, [{ label: 'Reports', data: d.monthly, borderColor: '#0072C6', backgroundColor: 'rgba(0,114,198,0.15)', fill: true, tension: 0.3, pointRadius: 5, borderWidth: 2 }], { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } });
    if (c2) bCharts[id].push(c2);

    const sl = [], sd = [], sc = [];
    BSTATUS_META.forEach(m => { if (d.status[m[0]] > 0) { sl.push(m[1]); sd.push(d.status[m[0]]); sc.push(m[2]); } });
    const c3 = bMount(id,'vstatus_'+id,'vstatusE_'+id,'doughnut', sl, [{ data: sd, backgroundColor: sc, borderWidth: 2, borderColor: '#fff' }]);
    if (c3) bCharts[id].push(c3);

    const c4 = bMount(id,'vdamage_'+id,'vdamageE_'+id,'doughnut', ['Totally','Partially'], [{ data: [d.damage.Totally, d.damage.Partially], backgroundColor: ['#dc3545','#fd7e14'], borderWidth: 2, borderColor: '#fff' }]);
    if (c4) bCharts[id].push(c4);
}


</script>
@endpush
