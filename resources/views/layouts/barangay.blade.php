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
    @include('layouts.barangay-sidebar', ['riskLevel' => $sideRisk])

    <div class="main">
        <header class="header">
            <button class="menu-toggle" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
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
function openSidebar() {
    document.querySelector('.sidebar').classList.add('open');
}
function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('open');
}
</script>
@stack('scripts')
</body>
</html>