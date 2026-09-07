<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>@yield('title', 'Data Management System')</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@stack('head')
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; min-height:100vh; }
.guest-body { margin:0; }

.sidebar {
    position: fixed; left:0; top:0; width:260px; height:100%;
    background: linear-gradient(180deg, #0a6cff 0%, #0056c7 55%, #003d8f 100%);
    color:#fff; transform:translateX(0);
    transition:transform 0.35s cubic-bezier(0.25,0.8,0.25,1);
    overflow-y:auto; overflow-x:hidden; z-index:1000; font-family:'Segoe UI',Roboto,sans-serif;
}
.sidebar::before { content:''; position:absolute; top:-80px; right:-60px; width:210px; height:210px; border-radius:50%; background:rgba(255,255,255,.06); }
.sidebar::after { content:''; position:absolute; top:160px; left:-70px; width:150px; height:150px; border-radius:50%; background:rgba(255,255,255,.05); }
.sidebar.hide { transform:translateX(-100%); }
.sidebar-header { position:relative; text-align:center; padding:30px 20px 22px; border-bottom:1px solid rgba(255,255,255,.15); background:rgba(0,0,0,.12); }
.logo-ring { width:82px; height:82px; margin:0 auto 12px; border-radius:50%; background:linear-gradient(135deg,rgba(255,255,255,.35),rgba(255,255,255,.05)); padding:5px; box-shadow:0 4px 16px rgba(0,0,0,.25); }
.logo-ring img { width:100%; height:100%; border-radius:50%; object-fit:cover; background:#fff; }
.sidebar-header h3 { margin:0; font-size:1.15rem; font-weight:700; }
.sidebar-header p { margin:6px 0 0; font-size:.8rem; opacity:.85; }
.close-btn { position:absolute; top:12px; right:12px; width:30px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:rgba(0,0,0,.2); color:#fff; font-size:13px; cursor:pointer; }
.close-btn:hover { background:rgba(0,0,0,.4); transform:rotate(90deg); }
.menu, .submenu { list-style:none; padding:0; margin:0; }
.menu { position:relative; z-index:1; padding:14px 12px 10px; }
.sidebar-btn { position:relative; display:flex; align-items:center; gap:12px; width:100%; padding:12px 14px; margin-bottom:4px; color:rgba(255,255,255,.92); text-decoration:none; border-radius:12px; font-size:.92rem; font-weight:500; transition:background .2s,color .2s,transform .15s; cursor:pointer; border:none; background:none; text-align:left; font-family:inherit; }
.sidebar-btn > i { width:20px; text-align:center; font-size:1rem; color:rgba(255,255,255,.75); }
.sidebar-btn:hover { background:rgba(255,255,255,.12); color:#fff; transform:translateX(3px); }
.sidebar-btn:hover > i { color:#ffd700; }
.sidebar-btn.active { background:#fff; color:#003d8f; font-weight:600; box-shadow:0 4px 14px rgba(0,0,0,.18); }
.sidebar-btn.active > i { color:#0a6cff; }
.has-submenu > .sidebar-btn::after { content:'\f105'; font-family:'Font Awesome 6 Free'; font-weight:900; margin-left:auto; font-size:.8rem; color:rgba(255,255,255,.7); transition:transform .25s; }
.has-submenu.active > .sidebar-btn::after { transform:rotate(90deg); }
.submenu { display:none; margin:2px 0 6px 14px; padding-left:12px; border-left:2px solid rgba(255,255,255,.25); }
.menu .active > .submenu { display:block; }
.submenu .sidebar-btn { padding:10px 12px; font-size:.87rem; margin-bottom:2px; }
.submenu .sidebar-btn > i { font-size:.85rem; }
.sidebar-footer { position:relative; z-index:1; margin:10px 12px 14px; padding:12px 14px; border-radius:12px; background:rgba(0,0,0,.18); font-size:.78rem; display:flex; align-items:center; gap:8px; color:rgba(255,255,255,.85); }
.sidebar::-webkit-scrollbar { width:5px; }
.sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,.3); border-radius:4px; }

.sidebar-edge { display:none; position:fixed; left:0; top:50%; transform:translateY(-50%); z-index:1001; width:40px; height:72px; border:none; border-radius:0 16px 16px 0; background:linear-gradient(180deg,#0a6cff,#004a9f); color:#fff; font-size:17px; cursor:pointer; flex-direction:column; align-items:center; justify-content:center; box-shadow:3px 0 12px rgba(0,0,0,.3); }
.sidebar.hide + .sidebar-edge { display:flex; }
.sidebar-edge:hover { background:#0a5ed7; }

.main-content { margin-left:260px; min-height:100vh; background:#f5f7fa; transition:margin-left .35s; }
.sidebar.hide ~ .main-content { margin-left:0; }

.header { display:flex; justify-content:space-between; align-items:center; padding:18px 30px; background:linear-gradient(90deg,#0072C6,#005999); color:white; box-shadow:0 2px 10px rgba(0,0,0,0.1); position:relative; z-index:10; }
.header h1 { font-size:1.4rem; }
.header-brand { display:flex; align-items:center; gap:12px; }
.header-brand img { width:44px; height:44px; border-radius:12px; object-fit:cover; border:2px solid rgba(255,255,255,.5); background:#fff; padding:2px; }
.header-brand .hb-txt b { display:block; font-size:1.15rem; line-height:1.1; }
.header-brand .hb-txt span { font-size:0.72rem; color:rgba(255,255,255,.8); }
.admin-profile { display:flex; align-items:center; gap:10px; cursor:pointer; padding:8px 15px; border-radius:25px; background:rgba(255,255,255,0.1); }
.admin-profile:hover { background:rgba(255,255,255,0.2); }
.admin-profile img { width:36px; height:36px; border-radius:50%; border:2px solid white; }
.dropdown { display:none; position:absolute; right:30px; top:65px; background:white; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.15); overflow:hidden; z-index:1002; min-width:150px; }
.dropdown.show { display:block; }
.dropdown a { display:flex; align-items:center; gap:10px; padding:12px 20px; text-decoration:none; color:#333; }
.dropdown a:hover { background:#f5f5f5; color:#0072C6; }

.content { padding:25px 30px; }
.card { background:white; border-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.06); overflow:hidden; margin-bottom:20px; }
.card-header { padding:18px 25px; border-bottom:2px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
.card-header h2 { font-size:1.2rem; color:#333; margin:0; }
table { width:100%; border-collapse:collapse; }
th, td { padding:14px 20px; text-align:left; border-bottom:1px solid #f0f0f0; }
th { color:#888; font-weight:600; font-size:0.8rem; text-transform:uppercase; background:#fafbfc; }
.badge { padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
.badge-blue { background:#d6eaf8; color:#1a5276; }
.badge-orange { background:#fff3cd; color:#856404; }
.badge-green { background:#d4edda; color:#155724; }
.badge-red { background:#f8d7da; color:#721c24; }
.badge-gray { background:#e9ecef; color:#666; }
.badge-purple { background:#e8daef; color:#6f42c1; }
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border:none; border-radius:10px; font-size:0.9rem; font-weight:600; cursor:pointer; color:#fff; text-decoration:none; transition:transform .2s,box-shadow .2s; }
.btn:hover { transform:translateY(-2px); }
.btn-blue { background:linear-gradient(90deg,#0072C6,#005999); }
.btn-green { background:linear-gradient(90deg,#28a745,#1e7e34); }
.btn-red { background:linear-gradient(90deg,#dc3545,#b02a37); }
.btn-orange { background:linear-gradient(90deg,#fd7e14,#e8590c); }
.btn-gray { background:linear-gradient(90deg,#6c757d,#495057); }
.btn-purple { background:linear-gradient(90deg,#6f42c1,#5a32a3); }
.btn-danger { background:linear-gradient(90deg,#dc3545,#b02a37); }
.btn-sm { padding:6px 12px; font-size:0.8rem; border-radius:8px; }
.badge-gray { background:#e9ecef; color:#666; }
.form-group { margin-bottom:18px; }
.form-group label { display:block; margin-bottom:8px; color:#333; font-weight:600; font-size:0.9rem; }
.form-group input, .form-group select, .form-group textarea {
    width:100%; padding:12px 14px; border:2px solid #e0e0e0; border-radius:10px; font-size:0.95rem; font-family:inherit; transition:border-color .3s,box-shadow .3s; background:#fff;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:#0072C6; box-shadow:0 0 0 3px rgba(0,114,198,0.1); }
.alert-success { background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:20px; border-left:4px solid #28a745; }
.alert-error, .alert-danger { background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:8px; margin-bottom:20px; border-left:4px solid #dc3545; }
.empty-state { text-align:center; padding:50px 20px; color:#aaa; }
.empty-state i { font-size:3rem; margin-bottom:15px; display:block; }
@media (max-width: 900px) {
    .sidebar { width:100%; max-width:300px; }
    .sidebar.hide { transform:translateX(-100%); }
    .main-content, .sidebar.hide ~ .main-content { margin-left:0; }
}
</style>
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
