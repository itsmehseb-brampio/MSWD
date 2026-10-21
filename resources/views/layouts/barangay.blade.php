<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>@yield('title', 'Barangay Portal') - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('responsive.css') }}">
@stack('head')
</head>
<body>
<div class="layout">
    <!-- Sidebar Wrapper with non-overlapping logic -->
    <div class="sidebar-wrapper" id="sidebar-wrapper">
        @include('layouts.barangay-sidebar', ['riskLevel' => $sideRisk])

        <div class="main" id="main-content">
            <header class="header">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1 class="header-title">@yield('headerTitle')</h1>
                <div class="profile-area" onclick="toggleDropdown(event)">
                    <span class="profile-name">{{ $barangayProfile->barangay_name }}</span>
                    <img src="{{ uimg($barangayProfile->detail->logo ?? null) }}" alt="Barangay logo">
                    <i class="fas fa-chevron-down caret"></i>
                    <div id="dropdownMenu" class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('barangay.logout') }}"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </header>

            @if (session('success'))
                <div id="toast" class="toast toast-ok">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                    <span class="toast-close" onclick="closeToast()">&times;</span>
                </div>
            @endif
            @if (session('error'))
                <div id="toast" class="toast toast-err">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                    <span class="toast-close" onclick="closeToast()">&times;</span>
                </div>
            @endif

            <div class="content">
                @yield('content')
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown(e) {
    e.stopPropagation();
    document.getElementById('dropdownMenu').classList.toggle('show');
}
window.onclick = function (e) {
    if (!e.target.closest('.profile-area')) {
        var d = document.getElementById('dropdownMenu');
        if (d) d.classList.remove('show');
    }
};
function closeToast() {
    var t = document.getElementById('toast');
    if (t) t.style.display = 'none';
}
setTimeout(function () {
    var t = document.getElementById('toast');
    if (t) t.style.display = 'none';
}, 4000);
</script>

<script>
/* Sidebar toggle with non-overlapping logic */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.getElementById('main-content');
    const wrapper = document.getElementById('sidebar-wrapper');
    const isOpen = !sidebar.classList.contains('sidebar-open');

    if (isOpen) {
        sidebar.classList.add('sidebar-open');
        sidebar.classList.remove('hide');
        mainContent.classList.add('content-expanded');
        wrapper.style.marginLeft = '0';
        mainContent.style.marginLeft = '260px';
    } else {
        sidebar.classList.remove('sidebar-open');
        mainContent.classList.remove('content-expanded');
        wrapper.style.marginLeft = '-260px';
        mainContent.style.marginLeft = '0';
    }
}

// Keep existing openSidebar/closeSidebar for backward compatibility
function openSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.getElementById('main-content');
    sidebar.classList.add('sidebar-open');
    sidebar.classList.remove('hide');
    mainContent.classList.add('content-expanded');
    wrapper.style.marginLeft = '0';
    mainContent.style.marginLeft = '260px';
}
function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.getElementById('main-content');
    sidebar.classList.remove('sidebar-open');
    mainContent.classList.remove('content-expanded');
    sidebar.classList.add('hide');
    wrapper.style.marginLeft = '-260px';
    mainContent.style.marginLeft = '0';
}
</script>

@stack('scripts')
</body>
</html>