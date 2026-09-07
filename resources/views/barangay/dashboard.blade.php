@extends('layouts.barangay')

@section('title', 'Dashboard')
@section('headerTitle', 'Dashboard')

@section('content')

<style>
.dash-hero {
    background: linear-gradient(120deg, #11998e 0%, #0b6e4f 100%);
    border-radius: 14px;
    color: #fff;
    padding: 24px 26px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}
.dash-hero h2 { font-size: 1.45rem; margin-bottom: 6px; }
.dash-hero p { opacity: 0.9; font-size: 0.92rem; }
.dash-hero .hero-badge {
    background: rgba(255,255,255,0.18);
    padding: 9px 16px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
}
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #fff;
}
.stat-icon.blue { background: #0072C6; }
.stat-icon.green { background: #28a745; }
.stat-icon.orange { background: #fd7e14; }
.stat-icon.red { background: #dc3545; }
.stat-info h3 { font-size: 1.5rem; color: #222; line-height: 1.1; }
.stat-info p { font-size: 0.82rem; color: #777; font-weight: 600; margin-top: 2px; }

.risk-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px; }
@media (max-width: 1000px) { .risk-row { grid-template-columns: 1fr; } }
.risk-panel {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.risk-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.risk-head h3 { font-size: 1rem; color: #333; }
.risk-badge {
    padding: 5px 12px;
    border-radius: 20px;
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
}
.risk-chart-wrap { position: relative; display: flex; justify-content: center; }
.risk-chart-wrap canvas { width: 230px !important; height: 150px !important; }
.risk-gauge-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}
.risk-gauge-center .rg-ico {
    font-size: 1.6rem;
    color: inherit;
    display: block;
}
.risk-gauge-center small { display: block; font-size: 0.7rem; color: #888; font-weight: 700; }
.risk-desc { margin-top: 12px; font-size: 0.85rem; color: #666; }

.charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px; }
@media (max-width: 1000px) { .charts-row { grid-template-columns: 1fr; } }
.chart-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.chart-card h3 { font-size: 0.95rem; color: #333; margin-bottom: 16px; }

.contacts-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.contacts-card h3 { font-size: 1rem; color: #333; margin-bottom: 16px; }
.contact-item {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f1f1;
}
.contact-item:last-child { border-bottom: none; }
.contact-item i {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #eafaf6;
    color: #11998e;
    display: flex;
    align-items: center;
    justify-content: center;
}
.contact-item .c-label { font-size: 0.85rem; color: #555; flex: 1; }
.contact-item .c-phone { font-weight: 700; color: #222; font-size: 0.95rem; }
.contact-empty {
    text-align: center;
    color: #999;
    font-size: 0.9rem;
    padding: 18px 0;
}
</style>

<div class="dash-hero">
    <div>
        <h2>Welcome, {{ $barangay->barangay_name }}!</h2>
        <p><i class="fas fa-calendar-day"></i> {{ now()->format('F j, Y') }} &nbsp;|&nbsp; Barangay Disaster Monitoring &amp; Management</p>
    </div>
    <div class="hero-badge"><i class="fas fa-shield-halved"></i> Disaster Risk Level: {{ $riskCur['level'] }}</div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <h3>{{ number_format($reportCount) }}</h3>
            <p>Total Reports</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-phone"></i></div>
        <div class="stat-info">
            <h3>{{ $emergencyActive ? 'Active' : 'None' }}</h3>
            <p>Emergency Contacts</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>{{ number_format((int) ($detail->population ?? 0)) }}</h3>
            <p>Population</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-home"></i></div>
        <div class="stat-info">
            <h3>{{ number_format((int) ($detail->households ?? 0)) }}</h3>
            <p>Households</p>
        </div>
    </div>
</div>

<div class="risk-row">
    <div class="risk-panel">
        <div class="risk-head">
            <h3><i class="fas fa-exclamation-triangle"></i> Risk Level</h3>
            <span class="risk-badge" style="background:{{ $riskCur['color'] }};">{{ $riskCur['level'] }}</span>
        </div>
        <div class="risk-chart-wrap">
            <canvas id="riskGauge" width="220" height="140"></canvas>
            <div class="risk-gauge-center" style="color:{{ $riskCur['color'] }};">
                <i class="fas {{ $riskCur['icon'] }} rg-ico"></i>
                <small>{{ $riskCur['pct'] }}%</small>
            </div>
        </div>
        <p class="risk-desc">{{ $riskCur['desc'] }}</p>
    </div>

    <div class="risk-panel">
        <div class="risk-head">
            <h3><i class="fas fa-tasks"></i> Disaster Reports ({{ number_format($reportCount) }})</h3>
            <a href="{{ route('barangay.disaster.history') }}" style="font-size:0.8rem;color:#11998e;font-weight:600;">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        @foreach ($status as $key => $count)
            @php
                $label = ucfirst($key);
                $colorMap = ['pending' => '#fd7e14', 'approved' => '#28a745', 'declined' => '#dc3545', 'cancelled' => '#6c757d', 'reedit' => '#6f42c1'];
                $iconMap = ['pending' => 'fa-clock', 'approved' => 'fa-check-circle', 'declined' => 'fa-times-circle', 'cancelled' => 'fa-ban', 'reedit' => 'fa-redo'];
            @endphp
            <div class="contact-item">
                <i style="background:{{ $colorMap[$key] }}22;color:{{ $colorMap[$key] }};"><i class="fas {{ $iconMap[$key] }}"></i></i>
                <span class="c-label">{{ $label }} Reports</span>
                <span class="c-phone">{{ number_format($count) }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="charts-row">
    <div class="chart-card">
        <h3><i class="fas fa-chart-bar"></i> Damage Reports</h3>
        <canvas id="damageChart" height="200"></canvas>
    </div>
    <div class="chart-card">
        <h3><i class="fas fa-chart-pie"></i> Population Overview</h3>
        <canvas id="popChart" height="200"></canvas>
    </div>
</div>

<div class="contacts-card">
    <h3><i class="fas fa-phone-alt"></i> Emergency Hotlines</h3>
    @forelse ($contacts as $c)
        <div class="contact-item">
            <i><i class="fas {{ $c['icon'] }}"></i></i>
            <span class="c-label">{{ $c['label'] }}</span>
            <span class="c-phone">{{ $c['phone'] }}</span>
        </div>
    @empty
        <div class="contact-empty">No emergency contacts saved yet. <a href="{{ route('barangay.info') }}" style="color:#11998e;">Add contacts</a></div>
    @endforelse
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartInfo = @json($chart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

const riskData = @json($riskCur, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

new Chart(document.getElementById('riskGauge'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [riskData.pct, 100 - riskData.pct],
            backgroundColor: [riskData.color, riskData.track],
            borderWidth: 0,
        }]
    },
    options: {
        cutout: '72%',
        rotation: 270,
        circumference: 180,
        plugins: { legend: { display: false } },
        maintainAspectRatio: false,
    }
});

new Chart(document.getElementById('damageChart'), {
    type: 'bar',
    data: {
        labels: ['Totally Damaged', 'Partially Damaged'],
        datasets: [{
            label: 'Reports',
            data: [chartInfo.damage.Totally, chartInfo.damage.Partially],
            backgroundColor: ['#dc3545', '#fd7e14'],
            borderRadius: 8,
            maxBarThickness: 70,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
        }
    }
});

const popHead = chartInfo.head !== null ? [chartInfo.population - chartInfo.head, chartInfo.head] : [chartInfo.population];
new Chart(document.getElementById('popChart'), {
    type: 'bar',
    data: {
        labels: ['Households', 'Population'],
        datasets: [{
            label: 'Count',
            data: [chartInfo.households, chartInfo.population],
            backgroundColor: ['#28a745', '#0072C6'],
            borderRadius: 8,
            maxBarThickness: 70,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush