@extends('layouts.admin')
@section('title', 'Contact Info - Admin Panel')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.page-wrap{max-width:1250px;margin:0 auto;}
.view-grid{display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start;}
.card{background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);}
.list-card{padding:18px;}
.list-title{display:flex;align-items:center;gap:8px;font-size:1rem;color:#202124;margin-bottom:14px;}
.list-title i{color:#0072C6;}
.search-box{position:relative;margin-bottom:14px;}
.search-box input{width:100%;padding:10px 14px 10px 38px;border:2px solid #e4e7ec;border-radius:12px;font-size:.88rem;outline:none;transition:border-color .2s;background:#f8f9fb;font-family:inherit;}
.search-box input:focus{border-color:#0072C6;background:#fff;}
.search-box i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9aa0a6;font-size:.9rem;}
.brgy-list{list-style:none;max-height:600px;overflow-y:auto;padding-right:4px;}
.brgy-list li{display:flex;align-items:center;gap:11px;padding:9px 10px;margin-bottom:5px;background:#f6f8fb;border-radius:12px;font-size:.87rem;color:#202124;font-weight:500;cursor:pointer;transition:background .15s, color .15s;border-left:4px solid transparent;}
.brgy-list li:hover{background:#eef6ff;}
.brgy-list li.selected{background:#0072C6;color:#fff;border-left-color:#ffd700;}
.brgy-list li .av{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:#fff;flex-shrink:0;background:#7c5cff;}
.brgy-list li.selected .av{background:rgba(255,255,255,.3);}
.hero{background:linear-gradient(120deg,#0072C6 0%,#005999 60%,#003a63 100%);border-radius:18px;padding:28px;color:#fff;display:flex;align-items:center;gap:22px;box-shadow:0 8px 24px rgba(0,114,198,.25);position:relative;overflow:hidden;}
.hero::after{content:'';position:absolute;right:-60px;top:-60px;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.06);}
.hero .big-av{width:84px;height:84px;border-radius:50%;border:4px solid rgba(255,255,255,.7);background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,.2);}
.hero .big-av img{width:100%;height:100%;object-fit:cover;}
.hero .big-av span{font-size:1.7rem;font-weight:700;color:#0072C6;}
.hero h2{font-size:1.55rem;margin-bottom:6px;font-weight:700;}
.hero .hero-loc{display:flex;gap:16px;flex-wrap:wrap;font-size:.85rem;opacity:.92;}
.hero .hero-loc span{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);padding:5px 12px;border-radius:20px;}
.hero .badge-acc{margin-left:auto;align-self:flex-start;background:#ffd700;color:#003a63;font-size:.72rem;font-weight:700;padding:5px 12px;border-radius:20px;}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:20px;}
.stat{background:#fff;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 4px 16px rgba(0,0,0,.05);}
.stat .ic{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;color:#fff;flex-shrink:0;}
.stat .lbl{font-size:.75rem;color:#5f6368;font-weight:600;text-transform:uppercase;letter-spacing:.4px;}
.stat .val{font-size:1.05rem;font-weight:700;color:#202124;margin-top:2px;word-break:break-word;}
.sections{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px;align-items:start;}
.panel{padding:22px;}
.panel-title{display:flex;align-items:center;gap:8px;font-size:1rem;color:#202124;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #f0f2f5;}
.panel-title i{color:#0072C6;}
.cat-block{margin-bottom:16px;}
.cat-head{width:100%;display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:700;color:#0072C6;text-transform:uppercase;letter-spacing:.5px;padding:12px 14px;background:#eef4fb;border:none;border-radius:12px;cursor:pointer;transition:background .2s;text-align:left;font-family:inherit;}
.cat-head:hover{background:#e0edfa;}
.cat-head .bar{width:4px;height:16px;border-radius:2px;background:linear-gradient(180deg,#0072C6,#005999);}
.cat-head .chev{margin-left:auto;transition:transform .2s;color:#5f6368;font-size:.8rem;}
.cat-head.open{background:#0072C6;color:#fff;}
.cat-head.open .chev{transform:rotate(180deg);color:#fff;}
.cat-head.open .bar{background:linear-gradient(180deg,#ffd700,#ffb300);}
.cat-body{display:none;background:#f8f9fb;border-radius:0 0 12px 12px;border:1px solid #eef1f5;border-top:none;margin-top:-6px;padding:12px 0 6px;}
.cat-body.open{display:block;animation:fadeIn .2s ease;}
@keyframes fadeIn{from{opacity:0;transform:translateY(-4px);}to{opacity:1;transform:translateY(0);}}
.info-row{display:flex;align-items:flex-start;gap:12px;padding:9px 14px;border-bottom:1px dashed #e4e7ec;}
.info-row:last-child{border-bottom:none;}
.info-row .fi{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.85rem;color:#fff;flex-shrink:0;}
.info-row .fl{font-size:.76rem;color:#5f6368;font-weight:600;text-transform:uppercase;letter-spacing:.3px;}
.info-row .fv{font-size:.9rem;color:#202124;font-weight:500;margin-top:1px;line-height:1.45;word-break:break-word;}
.info-row .fv-time{flex:0 0 auto;display:inline-flex;align-items:center;gap:5px;font-size:.68rem;font-weight:600;padding:3px 10px;border-radius:12px;white-space:nowrap;margin-top:1px;}
.info-row .fv-time.edited{background:#e6f4ea;color:#188038;}
.info-row .fv-time.never{background:#f1f3f4;color:#80868b;}
.no-data{text-align:center;padding:22px;color:#9aa0a6;font-size:.88rem;}
.no-data i{display:block;font-size:1.8rem;margin-bottom:8px;opacity:.5;}
.phone-grid{display:grid;grid-template-columns:1fr;gap:12px;}
.phone{display:flex;align-items:center;gap:14px;padding:12px 14px;background:#f8f9fb;border-radius:12px;border:1px solid #eef1f5;transition:transform .15s, box-shadow .15s;text-decoration:none;color:inherit;}
.phone:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.08);}
.phone .pi{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;flex-shrink:0;}
.phone .pl{font-size:.78rem;color:#5f6368;font-weight:600;}
.phone .pn{font-size:1rem;font-weight:700;color:#202124;letter-spacing:.5px;margin-top:1px;}
.phone .call{margin-left:auto;width:34px;height:34px;border-radius:50%;background:#0072C6;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;}
.empty-state{text-align:center;padding:70px 30px;color:#9aa0a6;}
.empty-state i{font-size:3.6rem;display:block;margin-bottom:16px;opacity:.5;color:#c3cad6;}
.empty-state h3{color:#5f6368;font-size:1.1rem;margin-bottom:6px;}
.empty-state p{font-size:.88rem;}
@media (max-width:1000px){.view-grid{grid-template-columns:1fr;}.stats{grid-template-columns:1fr 1fr;}.sections{grid-template-columns:1fr;}}
@media (max-width:560px){.stats{grid-template-columns:1fr;}.hero{flex-direction:column;text-align:center;}.hero .badge-acc{margin:0;}}
</style>
@endpush

@php
$detail_meta = [
    'barangay_code' => ['Barangay Code', 'fas fa-qrcode', '#7c5cff'],
    'municipality' => ['Municipality', 'fas fa-city', '#0072C6'],
    'province' => ['Province', 'fas fa-map', '#00875a'],
    'region' => ['Region', 'fas fa-globe-asia', '#e8710a'],
    'zip_code' => ['ZIP Code', 'fas fa-hashtag', '#d93025'],
    'captain_name' => ['Punong Barangay / Captain', 'fas fa-user-tie', '#0072C6'],
    'councilors' => ['Sangguniang Barangay Members', 'fas fa-users', '#7c5cff'],
    'secretary_name' => ['Barangay Secretary', 'fas fa-user', '#00875a'],
    'treasurer_name' => ['Barangay Treasurer', 'fas fa-user', '#e8710a'],
    'contact_info' => ['Contact Information', 'fas fa-phone', '#d93025'],
    'population' => ['Population', 'fas fa-users', '#7c5cff'],
    'households' => ['Households', 'fas fa-home', '#00875a'],
    'head_of_household' => ['Head of Household', 'fas fa-user', '#0072C6'],
    'population_breakdown' => ['Population Breakdown', 'fas fa-chart-pie', '#e8710a'],
    'boundaries' => ['Boundaries', 'fas fa-border-all', '#d93025'],
    'streets' => ['Streets / Puroks', 'fas fa-road', '#0072C6'],
    'land_area' => ['Land Area', 'fas fa-vector-square', '#00875a'],
    'gps_coordinates' => ['GPS Coordinates', 'fas fa-map-marker-alt', '#e8710a'],
    'barangay_hall_address' => ['Barangay Hall Address', 'fas fa-university', '#7c5cff'],
    'health_center' => ['Health Center', 'fas fa-hospital', '#d93025'],
    'daycare_schools' => ['Daycare / Schools', 'fas fa-child', '#e8710a'],
    'community_centers' => ['Community Centers', 'fas fa-building', '#0072C6'],
    'emergency_services' => ['Emergency Services', 'fas fa-ambulance', '#00875a'],
    'date_established' => ['Date Established', 'fas fa-calendar-alt', '#7c5cff'],
    'website' => ['Website', 'fas fa-globe', '#0072C6'],
    'ordinances' => ['Barangay Ordinances', 'fas fa-gavel', '#e8710a'],
];
$detail_categories = [
    'Identification' => ['barangay_code', 'municipality', 'province', 'region', 'zip_code'],
    'Leadership' => ['captain_name', 'councilors', 'secretary_name', 'treasurer_name', 'contact_info'],
    'Demographics' => ['population', 'households', 'head_of_household', 'population_breakdown'],
    'Location' => ['boundaries', 'streets', 'land_area', 'gps_coordinates'],
    'Facilities' => ['barangay_hall_address', 'health_center', 'daycare_schools', 'community_centers', 'emergency_services'],
    'Other' => ['date_established', 'website', 'ordinances'],
];
$contact_meta = [
    'barangay_hall_phone' => ['Barangay Hall', 'fas fa-university', '#0072C6'],
    'barangay_chairman_phone' => ['Barangay Chairman', 'fas fa-user-tie', '#00875a'],
    'barangay_secretary_phone' => ['Barangay Secretary', 'fas fa-user', '#00875a'],
    'barangay_tanod_phone' => ['Barangay Tanod', 'fas fa-shield-alt', '#e8710a'],
    'city_hotline' => ['City / Municipal Hotline', 'fas fa-phone', '#0072C6'],
    'drrmo_hotline' => ['DRRMO', 'fas fa-shield-alt', '#7c5cff'],
    'police_hotline' => ['Police / PNP', 'fas fa-lock', '#0072C6'],
    'fire_hotline' => ['Fire Department (BFP)', 'fas fa-fire-extinguisher', '#d93025'],
    'medical_services' => ['Medical / Ambulance', 'fas fa-ambulance', '#00875a'],
    'hospital_emergency' => ['Hospital Emergency', 'fas fa-hospital', '#d93025'],
    'traffic_control' => ['Traffic Control', 'fas fa-traffic-light', '#e8710a'],
    'power_emergency' => ['Power / Electricity', 'fas fa-bolt', '#f9ab00'],
    'water_emergency' => ['Water / Utilities', 'fas fa-tint', '#0097a7'],
    'ngo_relief' => ['NGOs / Relief Services', 'fas fa-hands-helping', '#e91e63'],
    'fire_volunteers' => ['Fire Volunteers / Rescue', 'fas fa-fire', '#d93025'],
    'covid_hotline' => ['COVID-19 / Health', 'fas fa-virus', '#c2185b'],
];
$stat_items = [
    ['population', 'Population', 'fas fa-users', '#7c5cff'],
    ['households', 'Households', 'fas fa-home', '#00875a'],
    ['land_area', 'Land Area', 'fas fa-vector-square', '#0072C6'],
    ['date_established', 'Established', 'fas fa-calendar-alt', '#e8710a'],
];
$contact_blocks = [
    'Barangay Officials' => ['barangay_hall_phone', 'barangay_chairman_phone', 'barangay_secretary_phone', 'barangay_tanod_phone'],
    'Municipal Hotlines' => ['city_hotline', 'drrmo_hotline', 'police_hotline', 'fire_hotline'],
    'Emergency & Utilities' => ['medical_services', 'hospital_emergency', 'traffic_control', 'power_emergency', 'water_emergency'],
    'Support Lines' => ['ngo_relief', 'fire_volunteers', 'covid_hotline'],
];
$municipal_fields = ['city_hotline','drrmo_hotline','police_hotline','fire_hotline','medical_services','hospital_emergency','traffic_control','power_emergency','water_emergency','ngo_relief','fire_volunteers','covid_hotline'];
$palette = ['#7c5cff','#0072C6','#00875a','#e8710a','#d93025','#0097a7','#e91e63'];
function init($text) {
    $i = '';
    foreach (preg_split('/\s+/', trim((string)$text)) as $w) { if ($w !== '') $i .= strtoupper($w[0]); }
    return $i;
}
$selected_name = $name ?? '';
$dr = $selected && $detail ? $detail : null;
$logo_url = '';
if ($dr && !empty($dr->logo)) {
    $logo_url = (strpos($dr->logo, 'uploads/') === 0) ? asset($dr->logo) : $dr->logo;
}
@endphp

@section('content')
<div class="page-wrap">
    <div class="view-grid">
        <div class="card list-card">
            <div class="list-title"><i class="fas fa-map-marker-alt"></i> Barangays of Malilipot</div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="brgySearch" placeholder="Search barangay..." oninput="filterBrgy(this.value)">
            </div>
            <ul class="brgy-list" id="brgyList">
                @foreach ($barangays as $i => $b)
                @php
                    $class = ($b->barangay_name == $selected_name) ? 'selected' : '';
                    $color = $palette[$i % count($palette)];
                @endphp
                <li class="{{ $class }}" onclick="window.location='?barangay_name={{ urlencode($b->barangay_name) }}'">
                    <span class="av" style="background:{{ $color }}">{{ init($b->barangay_name) }}</span>
                    {{ $b->barangay_name }}
                </li>
                @endforeach
            </ul>
        </div>

        <div>
            @if (!$selected_name)
                <div class="card empty-state">
                    <i class="fas fa-map-marked-alt"></i>
                    <h3>Select a Barangay</h3>
                    <p>Click any barangay from the list to view its information and contacts.</p>
                </div>
            @else
                <div class="hero">
                    <div class="big-av">
                        @if ($logo_url)
                            <img src="{{ $logo_url }}" alt="Logo">
                        @else
                            <span>{{ init($selected_name) }}</span>
                        @endif
                    </div>
                    <div>
                        <h2>{{ $selected_name }}</h2>
                        <div class="hero-loc">
                            @if ($dr && !empty($dr->municipality))
                                <span><i class="fas fa-city"></i> {{ $dr->municipality }}</span>
                            @endif
                            @if ($dr && !empty($dr->province))
                                <span><i class="fas fa-map"></i> {{ $dr->province }}</span>
                            @endif
                            @if ($dr && !empty($dr->region))
                                <span><i class="fas fa-globe-asia"></i> {{ $dr->region }}</span>
                            @endif
                            @if ($dr && !empty($dr->zip_code))
                                <span><i class="fas fa-hashtag"></i> {{ $dr->zip_code }}</span>
                            @endif
                            <span>
                                <i class="fas fa-clock"></i> Last edited:
                                @if ($dr && !empty($dr->last_updated))
                                    {{ \Carbon\Carbon::parse($dr->last_updated)->format('F d, Y h:i A') }}
                                @else
                                    Not edited yet
                                @endif
                            </span>
                        </div>
                    </div>
                    <span class="badge-acc"><i class="fas fa-check-circle"></i> Account created</span>
                </div>

                <div class="stats">
                    @foreach ($stat_items as $st)
                    @php
                        $val = $dr ? ($dr->{$st[0]} ?? '') : '';
                        if ($val === '' || $val === '0' || $val === '0000-00-00') $val = '—';
                    @endphp
                    <div class="stat">
                        <div class="ic" style="background:{{ $st[3] }}"><i class="{{ $st[2] }}"></i></div>
                        <div>
                            <div class="lbl">{{ $st[1] }}</div>
                            <div class="val">{{ $val }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="sections">
                    <div class="card panel">
                        <div class="panel-title"><i class="fas fa-info-circle"></i> Barangay Information</div>
                        @php $any_detail = false; $first_detail = true; @endphp
                        @foreach ($detail_categories as $cat => $fields)
                        @php
                            $filled = [];
                            if ($dr) { foreach ($fields as $f) { if (!empty($dr->{$f})) $filled[$f] = $dr->{$f}; } }
                            if (empty($filled)) continue;
                            $any_detail = true;
                            $open_class = $first_detail ? 'open' : '';
                            $first_detail = false;
                        @endphp
                        <div class="cat-block">
                            <button type="button" class="cat-head {{ $open_class }}">
                                <span class="bar"></span> {{ $cat }}
                                <i class="fas fa-chevron-down chev"></i>
                            </button>
                            <div class="cat-body {{ $open_class }}">
                                @foreach ($filled as $f => $v)
                                @php
                                    $meta = $detail_meta[$f] ?? [ucwords(str_replace('_', ' ', $f)), 'fas fa-circle', '#5f6368'];
                                    $ft = isset($editLogs[$f]) ? $editLogs[$f] : null;
                                @endphp
                                <div class="info-row">
                                    <div class="fi" style="background:{{ $meta[2] }}"><i class="{{ $meta[1] }}"></i></div>
                                    <div style="flex:1">
                                        <div class="fl">{{ $meta[0] }}</div>
                                        <div class="fv">{{ $v }}</div>
                                    </div>
                                    <div class="fv-time {{ $ft ? 'edited' : 'never' }}">
                                        @if ($ft)
                                            <i class="fas fa-clock"></i> Edited on {{ \Carbon\Carbon::parse($ft)->format('m/d/y') }}
                                        @else
                                            <i class="fas fa-minus-circle"></i> Not edited
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                        @if (!$any_detail)
                            <div class="no-data"><i class="fas fa-folder-open"></i> No barangay information available yet.</div>
                        @endif
                    </div>

                    <div class="card panel">
                        <div class="panel-title"><i class="fas fa-phone-alt"></i> Emergency & Important Contacts</div>
                        @php $any_contact = false; $first_contact = true; @endphp
                        @foreach ($contact_blocks as $cat => $fields)
                        @php
                            $items = [];
                            foreach ($fields as $f) {
                                $src = in_array($f, $municipal_fields) ? $municipal : $contacts;
                                if ($src && !empty($src->{$f})) $items[] = $f;
                            }
                            if (empty($items)) continue;
                            $any_contact = true;
                            $open_class = $first_contact ? 'open' : '';
                            $first_contact = false;
                        @endphp
                        <div class="cat-block">
                            <button type="button" class="cat-head {{ $open_class }}">
                                <span class="bar"></span> {{ $cat }}
                                <i class="fas fa-chevron-down chev"></i>
                            </button>
                            <div class="cat-body {{ $open_class }}"><div class="phone-grid">
                                @foreach ($items as $f)
                                @php
                                    $meta = $contact_meta[$f] ?? [ucwords(str_replace('_', ' ', $f)), 'fas fa-phone', '#5f6368'];
                                    $src = in_array($f, $municipal_fields) ? $municipal : $contacts;
                                @endphp
                                <a class="phone" href="tel:{{ $src->{$f} }}">
                                    <div class="pi" style="background:{{ $meta[2] }}"><i class="{{ $meta[1] }}"></i></div>
                                    <div>
                                        <div class="pl">{{ $meta[0] }}</div>
                                        <div class="pn">{{ $src->{$f} }}</div>
                                    </div>
                                    <div class="call"><i class="fas fa-phone"></i></div>
                                </a>
                                @endforeach
                            </div>
                            </div>
                        </div>
                        @endforeach
                        @if (!$any_contact)
                            <div class="no-data"><i class="fas fa-phone-slash"></i> No contact numbers available yet.</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function filterBrgy(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#brgyList li').forEach(function(li){
        li.style.display = li.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
document.querySelectorAll('.cat-head').forEach(function(btn){
    btn.addEventListener('click', function(){
        const body = this.nextElementSibling;
        this.classList.toggle('open');
        body.classList.toggle('open');
    });
});
</script>
@endpush