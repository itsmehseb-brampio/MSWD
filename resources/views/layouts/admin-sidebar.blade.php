@php
$route = request()->route() ? request()->route()->getName() : '';
$admin = Auth::guard('admin')->user();
function canAdmin($perm) {
    $u = Auth::guard('admin')->user();
    return $u && $u->hasPerm($perm);
}
@endphp
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="close-btn" onclick="closeSidebar()"><i class="fas fa-times"></i></span>
        <div class="logo-ring"><img src="{{ asset('mapa.png') }}" alt="Logo"></div>
        <h3>Data Management System</h3>
        <p><i class="fas fa-user-shield"></i> {{ ucfirst($admin?->roles->first()->name ?? 'Admin') }} Panel</p>
    </div>

    <ul class="menu">
        @if (canAdmin('view_dashboard'))
        <li>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-btn {{ $route === 'admin.dashboard' ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>
        @endif
        @if (canAdmin('manage_admin_accounts') || canAdmin('manage_barangay_accounts'))
        <li>
            <a href="{{ route('admin.roles') }}" class="sidebar-btn {{ in_array($route, ['admin.roles','admin.admins','admin.barangay']) ? 'active' : '' }}">
                <i class="fas fa-user-cog"></i> <span>Roles &amp; Permissions</span>
            </a>
        </li>
        @endif
        @if (canAdmin('manage_municipal_info') || canAdmin('view_barangay_info'))
        <li class="has-submenu {{ in_array($route, ['admin.municipal','admin.contact']) ? 'active' : '' }}">
            <a href="javascript:void(0)" onclick="toggleMenu(this)" class="sidebar-btn">
                <i class="fas fa-building"></i> <span>Barangay Management</span>
            </a>
            <ul class="submenu">
                @if (canAdmin('manage_municipal_info') || canAdmin('view_municipal_contacts'))
                <li><a href="{{ route('admin.municipal') }}" class="sidebar-btn {{ $route === 'admin.municipal' ? 'active' : '' }}"><i class="fas fa-phone-alt"></i> <span>Municipal Contacts</span></a></li>
                @endif
                @if (canAdmin('view_barangay_info') || canAdmin('manage_municipal_info'))
                <li><a href="{{ route('admin.contact') }}" class="sidebar-btn {{ $route === 'admin.contact' ? 'active' : '' }}"><i class="fas fa-phone"></i> <span>Contact Info</span></a></li>
                @endif
            </ul>
        </li>
        @endif
        @if (canAdmin('manage_disaster_format') || canAdmin('review_disaster_reports'))
        <li class="has-submenu {{ in_array($route, ['admin.disaster.format','admin.disaster.approved']) ? 'active' : '' }}">
            <a href="javascript:void(0)" onclick="toggleMenu(this)" class="sidebar-btn">
                <i class="fas fa-file-alt"></i> <span>Disaster Report</span>
            </a>
            <ul class="submenu">
                @if (canAdmin('manage_disaster_format'))
                <li><a href="{{ route('admin.disaster.format') }}" class="sidebar-btn {{ $route === 'admin.disaster.format' ? 'active' : '' }}"><i class="fas fa-wpforms"></i> <span>Disaster Format</span></a></li>
                @endif
                @if (canAdmin('review_disaster_reports'))
                <li><a href="{{ route('admin.disaster.approved') }}" class="sidebar-btn {{ $route === 'admin.disaster.approved' ? 'active' : '' }}"><i class="fas fa-check-circle"></i> <span>Barangay Report</span></a></li>
                @endif
            </ul>
        </li>
        @endif
        @if (canAdmin('view_hazard_map'))
        <li>
            <a href="{{ route('admin.hazard_map') }}" class="sidebar-btn {{ $route === 'admin.hazard_map' ? 'active' : '' }}">
                <i class="fas fa-map-marked-alt"></i> <span>Hazard Map Information</span>
            </a>
        </li>
        @endif
        @if (canAdmin('manage_messages'))
        <li>
            <a href="{{ route('admin.messages') }}" class="sidebar-btn {{ $route === 'admin.messages' ? 'active' : '' }}">
                <i class="fas fa-comments"></i> <span>Messages</span>
            </a>
        </li>
        @endif
        @if (canAdmin('manage_announcements'))
        <li>
            <a href="{{ route('admin.announcements') }}" class="sidebar-btn {{ $route === 'admin.announcements' ? 'active' : '' }}">
                <i class="fas fa-bullhorn"></i> <span>Announcements</span>
            </a>
        </li>
        @endif
        @if (canAdmin('manage_relief'))
        <li>
            <a href="{{ route('admin.relief') }}" class="sidebar-btn {{ $route === 'admin.relief' ? 'active' : '' }}">
                <i class="fas fa-boxes"></i> <span>Relief Goods Distribution</span>
            </a>
        </li>
        @endif
    </ul>
    <div class="sidebar-footer"><i class="fas fa-map-marker-alt"></i> Malilipot, Albay</div>
</div>

<button class="sidebar-edge" onclick="openSidebar()" title="Show menu"><i class="fas fa-bars"></i></button>

<script>
function openSidebar() { document.getElementById("sidebar").classList.remove("hide"); }
function closeSidebar() { document.getElementById("sidebar").classList.add("hide"); }
function toggleMenu(element) {
    const isActive = element.parentElement.classList.contains('active');
    document.querySelectorAll('.has-submenu').forEach(item => item.classList.remove('active'));
    if (!isActive) element.parentElement.classList.add('active');
}
</script>
