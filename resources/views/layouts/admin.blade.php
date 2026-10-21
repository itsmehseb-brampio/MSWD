<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>@yield('title', 'Data Management System')</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@stack('head')
<link rel="stylesheet" href="{{ asset('responsive.css') }}">
</head>
<body>
<div class="wrapper" style="display:flex; min-height:100vh;">
    <!-- Sidebar Wrapper with non-overlapping logic -->
    <div class="sidebar-wrapper" id="sidebar-wrapper">
        @include('layouts.admin-sidebar')

        <div class="main-content" id="main-content">
            <div class="header">
                <div class="header-brand">
                    <img src="{{ asset('mapa.png') }}" alt="Logo">
                    <div class="hb-txt"><b>DSWD - Municipal Office</b><span>Data Management System</span></div>
                </div>
                <div style="position:relative;">
                    <div class="admin-profile" onclick="toggleDropdown()">
                        <img src="{{ asset('mapa.png') }}" alt="Admin">
                        <span>{{ auth('admin')->user()->username ?? 'Admin' }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown" id="dropdownMenu">
                        <a href="{{ route('admin.logout') }}"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
            <div class="content">
                @if (session('success'))
                    <div class="alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown() { document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e) {
    if (!e.target.closest('.admin-profile')) document.getElementById("dropdownMenu").classList.remove("show");
};
</script>

<script>
/* Sidebar toggle with non-overlapping logic */
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("main-content");
    const wrapper = document.getElementById("sidebar-wrapper");
    const isOpen = !sidebar.classList.contains("sidebar-open");

    if (isOpen) {
        sidebar.classList.add("sidebar-open");
        sidebar.classList.remove("hide");
        mainContent.classList.add("content-expanded");
        wrapper.style.marginLeft = '0';
        mainContent.style.marginLeft = '260px';
    } else {
        sidebar.classList.remove("sidebar-open");
        mainContent.classList.remove("content-expanded");
        wrapper.style.marginLeft = '-260px';
        mainContent.style.marginLeft = '0';
    }
}

function closeSidebar() {
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("main-content");
    sidebar.classList.remove("sidebar-open");
    mainContent.classList.remove("content-expanded");
    sidebar.classList.add("hide");
    wrapper.style.marginLeft = '-260px';
    mainContent.style.marginLeft = '0';
}

// Close on mobile resize if viewport increases beyond 900px
window.addEventListener('resize', () => {
    if (window.innerWidth > 900) {
        toggleSidebar(); // Auto-close on larger screens
    }
});

// Initial state check on load
document.addEventListener('DOMContentLoaded', () => {
    if (window.innerWidth <= 900) {
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("main-content");
        sidebar.classList.add("hide");
        mainContent.style.marginLeft = '0';
    }
});
</script>

@stack('scripts')
</body>
</html>