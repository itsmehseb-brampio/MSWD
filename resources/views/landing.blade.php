<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MSWD - Data Management System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #0a6cff 0%, #11998e 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #333;
    }
    .wrapper { text-align: center; padding: 30px; width: 100%; max-width: 720px; }
    .brand { margin-bottom: 30px; }
    .brand img { width: 96px; height: 96px; border-radius: 50%; background: #fff; padding: 6px; box-shadow: 0 8px 24px rgba(0,0,0,.2); }
    .brand h1 { color: #fff; margin-top: 16px; font-size: 1.9rem; text-shadow: 0 2px 8px rgba(0,0,0,.2); }
    .brand p { color: rgba(255,255,255,.9); margin-top: 6px; font-size: 1rem; }
    .cards { display: flex; gap: 24px; flex-wrap: wrap; justify-content: center; margin-top: 30px; }
    .card {
        background: #fff; border-radius: 18px; padding: 34px 30px; width: 280px;
        box-shadow: 0 12px 34px rgba(0,0,0,.25); transition: transform .25s, box-shadow .25s; cursor: pointer;
        text-decoration: none; color: #333;
    }
    .card:hover { transform: translateY(-6px); box-shadow: 0 18px 44px rgba(0,0,0,.3); }
    .card .icon { width: 74px; height: 74px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 18px; color: #fff; }
    .card.admin .icon { background: linear-gradient(135deg,#0a6cff,#0056c7); }
    .card.barangay .icon { background: linear-gradient(135deg,#11998e,#2bb673); }
    .card h3 { font-size: 1.25rem; margin-bottom: 8px; }
    .card p { font-size: .88rem; color: #666; line-height: 1.5; }
    .card .btn {
        display: inline-block; margin-top: 18px; padding: 10px 26px; border-radius: 30px;
        color: #fff; font-weight: 600; font-size: .9rem;
    }
    .card.admin .btn { background: linear-gradient(135deg,#0a6cff,#0056c7); }
    .card.barangay .btn { background: linear-gradient(135deg,#11998e,#2bb673); }
    .footer-note { color: rgba(255,255,255,.85); margin-top: 34px; font-size: .85rem; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="brand">
        <img src="{{ asset('mapa.png') }}" alt="MSWD Logo">
        <h1>MSWD Data Management System</h1>
        <p>Municipality of Malilipot, Albay</p>
    </div>
    <div class="cards">
        <a class="card admin" href="{{ route('admin.login') }}">
            <div class="icon"><i class="fas fa-user-tie"></i></div>
            <h3>MSWD Employee</h3>
            <p>Login as Municipal Social Welfare Development staff to manage barangays, review disaster reports, send announcements, and arrange relief.</p>
            <span class="btn">MSWD Login</span>
        </a>
        <a class="card barangay" href="{{ route('barangay.login') }}">
            <div class="icon"><i class="fas fa-house-user"></i></div>
            <h3>Barangay</h3>
            <p>Login as a barangay official to submit disaster reports, update barangay information, draw hazard maps, and confirm relief goods.</p>
            <span class="btn">Barangay Login</span>
        </a>
    </div>
    <div class="footer-note">Choose your portal to sign in</div>
</div>
</body>
</html>
