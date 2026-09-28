<aside class="sidebar">
    <div class="side-logo">
        <img src="{{ asset('mapa.png') }}" alt="Portal logo">
        <div>
            <div class="side-title">Barangay Portal</div>
            <div class="side-sub">Municipality of Malilipot</div>
        </div>
    </div>

    <nav class="side-menu">
        @php
            $current = Route::currentRouteName();
            $riskColors = [
                'Low' => '#28a745',
                'Medium' => '#fd7e14',
                'High' => '#dc3545',
                'Critical' => '#7b1a1a',
            ];
            $riskIcons = [
                'Low' => 'fa-smile',
                'Medium' => 'fa-meh',
                'High' => 'fa-frown',
                'Critical' => 'fa-dizzy',
            ];
            $risk = $riskLevel ?? 'Low';
            $riskColor = $riskColors[$risk] ?? '#28a745';
            $riskIcon = $riskIcons[$risk] ?? 'fa-smile';

            $brgy = Auth::guard('barangay')->user();
            $canBrgy = fn ($p) => $brgy && $brgy->hasPerm($p);

            $disasterActive = in_array($current, [
                'barangay.disaster.apply', 'barangay.disaster.approved',
                'barangay.disaster.pending', 'barangay.disaster.declined',
                'barangay.disaster.reedit', 'barangay.disaster.history',
                'barangay.disaster.edit',
            ]);
            $aboutActive = in_array($current, ['barangay.overview', 'barangay.info']);
            $hazardActive = in_array($current, ['barangay.hazard_map', 'barangay.hazard_map_view']);
        @endphp

        <div class="m-label">Main</div>
        <a class="m-item {{ $current === 'barangay.dashboard' ? 'active' : '' }}" href="{{ route('barangay.dashboard') }}">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        @if ($canBrgy('submit_disaster_report') || $canBrgy('edit_disaster_report'))
        <div class="has-submenu {{ $disasterActive ? 'open' : '' }}">
            <div class="m-item {{ $disasterActive ? 'active' : '' }}" onclick="toggleMenu(this)">
                <i class="fas fa-bullhorn"></i> Barangay Disaster Report
                <i class="fas fa-chevron-right m-arrow"></i>
            </div>
            <div class="sub-list">
                @if ($canBrgy('submit_disaster_report'))
                <a class="s-item {{ $current === 'barangay.disaster.apply' ? 'active' : '' }}" href="{{ route('barangay.disaster.apply') }}">
                    <i class="fas fa-plus-circle"></i> Apply
                </a>
                @endif
                @if ($canBrgy('submit_disaster_report') || $canBrgy('edit_disaster_report'))
                <a class="s-item {{ in_array($current, ['barangay.disaster.approved']) ? 'active' : '' }}" href="{{ route('barangay.disaster.approved') }}">
                    <i class="fas fa-check-circle"></i> Barangay Report
                </a>
                @endif
                @if ($canBrgy('edit_disaster_report'))
                <a class="s-item {{ $current === 'barangay.disaster.reedit' ? 'active' : '' }}" href="{{ route('barangay.disaster.reedit') }}">
                    <i class="fas fa-redo"></i> For Re-edit
                </a>
                @endif
            </div>
        </div>
        @endif

        @if ($canBrgy('view_barangay_info') || $canBrgy('edit_barangay_info'))
        <div class="m-label">About Barangay</div>
        <div class="has-submenu {{ $aboutActive ? 'open' : '' }}">
            <div class="m-item {{ $aboutActive ? 'active' : '' }}" onclick="toggleMenu(this)">
                <i class="fas fa-building"></i> About Barangay
                <i class="fas fa-chevron-right m-arrow"></i>
            </div>
            <div class="sub-list">
                @if ($canBrgy('view_barangay_info'))
                <a class="s-item {{ $current === 'barangay.overview' ? 'active' : '' }}" href="{{ route('barangay.overview') }}">
                    <i class="fas fa-eye"></i> Overview
                </a>
                @endif
                @if ($canBrgy('view_barangay_info') || $canBrgy('edit_barangay_info'))
                <a class="s-item {{ $current === 'barangay.info' ? 'active' : '' }}" href="{{ route('barangay.info') }}">
                    <i class="fas fa-info-circle"></i> Info &amp; Contacts
                </a>
                @endif
            </div>
        </div>
        @endif

        @if ($canBrgy('view_hazard_map') || $canBrgy('edit_hazard_map'))
        <div class="has-submenu {{ $hazardActive ? 'open' : '' }}">
            <div class="m-item {{ $hazardActive ? 'active' : '' }}" onclick="toggleMenu(this)">
                <i class="fas fa-map-marked-alt"></i> Hazard Map
                <i class="fas fa-chevron-right m-arrow"></i>
            </div>
            <div class="sub-list">
                @if ($canBrgy('edit_hazard_map'))
                <a class="s-item {{ $current === 'barangay.hazard_map' ? 'active' : '' }}" href="{{ route('barangay.hazard_map') }}">
                    <i class="fas fa-edit"></i> Edit
                </a>
                @endif
                @if ($canBrgy('view_hazard_map'))
                <a class="s-item {{ $current === 'barangay.hazard_map_view' ? 'active' : '' }}" href="{{ route('barangay.hazard_map_view') }}">
                    <i class="fas fa-map"></i> View
                </a>
                @endif
            </div>
        </div>
        @endif

        @if ($canBrgy('manage_messages'))
        <a class="m-item {{ $current === 'barangay.messages' ? 'active' : '' }}" href="{{ route('barangay.messages') }}">
            <i class="fas fa-comments"></i> Messages
        </a>
        @endif
        @if ($canBrgy('view_announcements'))
        <a class="m-item {{ $current === 'barangay.announcements' ? 'active' : '' }}" href="{{ route('barangay.announcements') }}">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        @endif
        @if ($canBrgy('confirm_relief') || $canBrgy('view_municipal_contacts'))
        <a class="m-item {{ $current === 'barangay.relief' ? 'active' : '' }}" href="{{ route('barangay.relief') }}">
            <i class="fas fa-boxes-stacked"></i> Relief Goods Distribution
        </a>
        @endif
    </nav>

    <div class="side-risk">
        <div class="sr-row">
            <span class="sr-dot" style="background:{{ $riskColor }};"><i class="fas {{ $riskIcon }}"></i></span>
            <span>Risk Level: <b>{{ $risk }}</b></span>
        </div>
    </div>
</aside>

@push('scripts')
<script>
function toggleMenu(el) {
    var sub = el.closest('.has-submenu');
    var wasOpen = sub.classList.contains('open');
    document.querySelectorAll('.has-submenu.open').forEach(function (s) {
        s.classList.remove('open');
    });
    if (!wasOpen) sub.classList.add('open');
}
</script>
@endpush
