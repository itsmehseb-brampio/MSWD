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
    @include('layouts.admin-sidebar')
    <div class="main-content">
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

<script>
function toggleDropdown() { document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e) {
    if (!e.target.closest('.admin-profile')) document.getElementById("dropdownMenu").classList.remove("show");
};
</script>
@stack('scripts')
</body>
</html>