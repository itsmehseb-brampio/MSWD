@extends('layouts.barangay')

@section('title', 'Barangay Overview')
@section('headerTitle', 'Barangay Overview')

@section('content')

<style>
.hero {
    background: linear-gradient(120deg, #0072C6 0%, #005999 55%, #003a63 100%);
    border-radius: 14px;
    color: #fff;
    padding: 28px;
    display: flex;
    align-items: center;
    gap: 22px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}
.big-av {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.45);
    object-fit: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.9rem;
    font-weight: 700;
    background: rgba(255,255,255,0.18);
    color: #fff;
}
.hero h1 { font-size: 1.65rem; margin-bottom: 6px; }
.badge-acc {
    display: inline-block;
    background: #38ef7d;
    color: #0b4d38;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    margin-left: 8px;
    vertical-align: middle;
}
.hero-loc { font-size: 0.92rem; opacity: 0.92; }
.hero-loc span { margin: 0 3px; }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.stat-card i {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: #eafaf6;
    color: #11998e;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}
.stat-card .v { font-size: 1.35rem; font-weight: 700; color: #222; }
.stat-card .l { font-size: 0.8rem; color: #777; font-weight: 600; }

.section { margin-bottom: 22px; }
.section h3 {
    font-size: 1rem;
    color: #333;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section h3 i { color: #11998e; }
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 14px;
}
.info-chip {
    background: #fff;
    border-radius: 10px;
    padding: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border-left: 3px solid var(--chip-color, #11998e);
}
.info-chip .ic-row { display: flex; align-items: center; gap: 9px; margin-bottom: 7px; }
.info-chip .ic-row i { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; }
.info-chip .ic-label { font-size: 0.74rem; color: #888; font-weight: 600; }
.info-chip .ic-value { font-size: 0.93rem; color: #222; font-weight: 600; word-break: break-word; }

.text-block {
    background: #fff;
    border-radius: 10px;
    padding: 16px 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    font-size: 0.92rem;
    color: #555;
    line-height: 1.7;
    white-space: pre-line;
}
.empty-note { color: #999; font-size: 0.88rem; font-style: italic; }
</style>

@php
    $municipality = $detail->municipality ?? 'Malilipot';
    $province = $detail->province ?? 'Albay';
    $hasLogo = !empty($detail->logo ?? null);
@endphp

<div class="hero">
    @if ($hasLogo)
        <img class="big-av" src="{{ uimg($detail->logo) }}" alt="Barangay logo">
    @else
        <div class="big-av">{{ $initials }}</div>
    @endif
    <div>
        <h1>{{ $barangay->barangay_name }} <span class="badge-acc"><i class="fas fa-check-circle"></i> Verified</span></h1>
        <div class="hero-loc">
            <i class="fas fa-map-marker-alt"></i>
            <span>Municipality of {{ $municipality }}</span>
            <span>&bull;</span>
            <span>{{ $province }}</span>
            <span>&bull;</span>
            <span>{{ $detail->region ?? 'Bicol Region (V)' }}</span>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <div><div class="v">{{ number_format((int) ($detail->population ?? 0)) }}</div><div class="l">Population</div></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-home"></i>
        <div><div class="v">{{ number_format((int) ($detail->households ?? 0)) }}</div><div class="l">Households</div></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-arrows-alt-v"></i>
        <div><div class="v">{{ $detail->land_area ?? '—' }}<span style="font-size:0.75rem;color:#888;"> km²</span></div><div class="l">Land Area</div></div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hashtag"></i>
        <div><div class="v">{{ $detail->zip_code ?? '—' }}</div><div class="l">ZIP Code</div></div>
    </div>
</div>

<div class="section">
    <h3><i class="fas fa-id-card"></i> Barangay Profile</h3>
    @if (count($profile) > 0)
        <div class="info-grid">
            @foreach ($profile as $item)
                <div class="info-chip" style="--chip-color:{{ $item['color'] }};">
                    <div class="ic-row">
                        <i style="background:{{ $item['color'] }};"><i class="fas {{ $item['icon'] }}"></i></i>
                        <span class="ic-label">{{ $item['label'] }}</span>
                    </div>
                    <div class="ic-value">{{ $item['value'] }}</div>
                </div>
            @endforeach
        </div>
    @else
        <p class="empty-note">No profile data filled in yet. <a href="{{ route('barangay.info') }}" style="color:#11998e;">Complete your profile</a></p>
    @endif
</div>

@foreach ($sections as $s)
    <div class="section">
        <h3><i class="fas {{ $s['icon'] }}"></i> {{ $s['label'] }}</h3>
        <div class="text-block">{{ $s['value'] }}</div>
    </div>
@endforeach

@endsection