<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Your Account - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; display: flex; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.invite-container { width: 100%; max-width: 460px; margin: auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.invite-head { background: linear-gradient(135deg, #0072C6 0%, #005999 100%); padding: 30px; text-align: center; color: white; }
.invite-head img { width: 74px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.4); margin-bottom: 12px; }
.invite-head h1 { font-size: 1.3rem; margin-bottom: 4px; }
.invite-head p { font-size: 0.85rem; opacity: 0.9; }
.invite-body { padding: 30px; }
.invite-body h2 { font-size: 1.2rem; color: #333; margin-bottom: 6px; }
.invite-body .sub { color: #666; font-size: 0.9rem; margin-bottom: 22px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 0.9rem; }
.form-group input { width: 100%; padding: 13px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 0.95rem; transition: border-color 0.3s, box-shadow 0.3s; font-family: inherit; }
.form-group input:focus { outline: none; border-color: #0072C6; box-shadow: 0 0 0 3px rgba(0,114,198,0.1); }
.form-group input[readonly] { background: #f4f6f8; color: #5f6368; }
.err { background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; border-left: 4px solid #dc3545; font-size: 0.85rem; }
.btn { width: 100%; padding: 15px; background: linear-gradient(90deg, #0072C6, #005999); color: white; border: none; border-radius: 10px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; font-family: inherit; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,114,198,0.4); }
.hint { font-size: 0.8rem; color: #888; margin-top: 16px; line-height: 1.5; }
</style>
</head>
<body>
<div class="invite-container">
    <div class="invite-head">
        <img src="{{ asset('mapa.png') }}" alt="Logo">
        <h1>Data Management System</h1>
        <p>Municipality of Malilipot, Albay</p>
    </div>
    <div class="invite-body">
        <h2>Create your account</h2>
        <p class="sub">You were invited to view this system. Choose your own username and password to finish your sign-up.</p>

        @if ($errors->any())
            <div class="err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('invite.complete', $account->invite_token) }}">
            @csrf
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name</label>
                <input type="text" name="name" value="{{ old('name', $account->name) }}" required autocomplete="off">
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" value="{{ $account->email }}" readonly>
            </div>
            <div class="form-group">
                <label><i class="fas fa-user-tag"></i> Username</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="Choose a login username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="At least 6 characters" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat your password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn"><i class="fas fa-check-circle"></i> Create Account</button>
        </form>
        <p class="hint"><i class="fas fa-info-circle"></i> After creating your account, you can sign in with your new username and password.</p>
    </div>
</div>
</body>
</html>