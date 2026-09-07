<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Barangay Portal') - Data Management System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7f6;
    color: #333;
    min-height: 100vh;
}
a { text-decoration: none; }
button { font-family: inherit; }

/* Layout shell */
.layout { display: flex; min-height: 100vh; }
.main {
    flex: 1;
    margin-left: 260px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
.content { padding: 25px 30px; flex: 1; }

/* Header */
.header {
    height: 72px;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 25px;
    background: linear-gradient(90deg, #11998e 0%, #2bb673 100%);
    color: #fff;
    position: sticky;
    top: 0;
    z-index: 90;
    box-shadow: 0 2px 10px rgba(0,0,0,0.10);
}
.header-title { font-size: 1.15rem; font-weight: 600; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #fff;
    font-size: 1.25rem;
    cursor: pointer;
}
.profile-area {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.14);
    padding: 6px 14px 6px 8px;
    border-radius: 30px;
    cursor: pointer;
    transition: background 0.2s;
}
.profile-area:hover { background: rgba(255,255,255,0.24); }
.profile-area img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
}
.profile-name { font-size: 0.95rem; font-weight: 600; max-width: 190px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.caret { font-size: 0.7rem; opacity: 0.85; }
.dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: #fff;
    color: #333;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.16);
    min-width: 180px;
    padding: 6px;
    display: none;
    z-index: 200;
}
.dropdown-menu.show { display: block; }
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: #444;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    border: none;
    background: none;
    text-align: left;
}
.dropdown-item:hover { background: #eafaf6; color: #11998e; }
.dropdown-item.danger { color: #dc3545; }
.dropdown-item.danger:hover { background: #fdeaea; }

/* Toasts */
.toast {
    position: fixed;
    top: 86px;
    right: 24px;
    z-index: 300;
    padding: 13px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 380px;
    animation: toastIn 0.3s ease;
}
.toast-ok { background: #e6f7ee; color: #0b6e4f; border-left: 4px solid #28a745; }
.toast-err { background: #fdeaea; color: #a71d1d; border-left: 4px solid #dc3545; }
.toast-close { margin-left: 8px; cursor: pointer; font-size: 1.05rem; opacity: 0.6; }
@keyframes toastIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

/* Sidebar */
.sidebar {
    width: 260px;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    background: linear-gradient(180deg, #11998e 0%, #0b6e4f 60%, #0a5f52 100%);
    color: #fff;
    display: flex;
    flex-direction: column;
    z-index: 100;
    transition: transform 0.3s ease;
}
.side-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
}
.side-logo img {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.4);
}
.side-title { font-weight: 700; font-size: 0.98rem; line-height: 1.2; }
.side-sub { font-size: 0.72rem; opacity: 0.85; }
.side-menu { flex: 1; overflow-y: auto; padding: 8px 0 14px; }
.side-menu::-webkit-scrollbar { width: 5px; }
.side-menu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.25); border-radius: 4px; }
.m-label {
    padding: 12px 16px 6px;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255,255,255,0.55);
}
.m-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 16px;
    color: rgba(255,255,255,0.85);
    font-size: 0.9rem;
    border-left: 3px solid transparent;
    cursor: pointer;
    transition: background 0.15s;
}
.m-item:hover { background: rgba(255,255,255,0.08); color: #fff; }
.m-item.active { background: rgba(255,255,255,0.15); color: #fff; border-left-color: #38ef7d; }
.m-item i:first-child { width: 20px; text-align: center; }
.m-item .m-arrow { margin-left: auto; font-size: 0.7rem; transition: transform 0.25s; }
.sub-list { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
.has-submenu.open .sub-list { max-height: 300px; }
.has-submenu.open .m-arrow { transform: rotate(90deg); }
.s-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px 9px 48px;
    color: rgba(255,255,255,0.72);
    font-size: 0.86rem;
    transition: background 0.15s, color 0.15s;
}
.s-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
.s-item.active { color: #38ef7d; font-weight: 600; }
.side-risk {
    margin: 10px 16px 14px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.22);
    background: rgba(255,255,255,0.08);
    font-size: 0.8rem;
}
.side-risk .sr-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.side-risk .sr-row:last-child { margin-bottom: 0; }
.sr-dot { display: inline-block; width: 26px; height: 26px; border-radius: 50%; color: #fff; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; }

/* Responsive */
@media (max-width: 900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main { margin-left: 0; }
    .menu-toggle { display: block; }
}
</style>
@stack('head')
</head>
<body>
@php
if (!function_exists('uimg')) {
    function uimg($path, $fallback = 'yana.png', $originalDir = 'uploads')
    {
        $path = (string) $path;
        if ($path === '') {
            return asset($fallback);
        }
        if (str_starts_with($path, 'http') || str_starts_with($path, '//')
            || str_starts_with($path, 'data:') || str_starts_with($path, '/')) {
            return $path;
        }
        if (str_starts_with($path, $originalDir) || str_contains($path, 'uploads/')) {
            return asset($path);
        }
        if (in_array($path, ['mapa.png', 'yana.png'], true)) {
            return asset($path);
        }
        return asset($originalDir . '/' . $path);
    }
}
$barangayProfile = Auth::guard('barangay')->user();
$sideRisk = optional($barangayProfile->detail)->risk_level ?? 'Low';
@endphp

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
                    <a class="dropdown-item" href="{{ route('barangay.logout') }}">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
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