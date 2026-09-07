<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MSWD Login - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.login-container {
    display: flex;
    width: 100%;
    max-width: 1200px;
    margin: auto;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.login-left {
    flex: 1;
    background: linear-gradient(135deg, #0072C6 0%, #005999 100%);
    padding: 60px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    color: white;
}
.login-left img { width: 120px; margin-bottom: 30px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.3); }
.login-left h1 { font-size: 2rem; margin-bottom: 10px; }
.login-left p { font-size: 1.1rem; opacity: 0.9; }
.login-left .roles { margin-top: 30px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
.login-left .role-badge {
    background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.35);
    padding: 8px 18px; border-radius: 30px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;
}
.login-right { flex: 1; padding: 60px 50px; display: flex; flex-direction: column; justify-content: center; }
.login-right h2 { font-size: 1.8rem; color: #333; margin-bottom: 10px; }
.login-right p { color: #666; margin-bottom: 30px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
.form-group input {
    width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 10px;
    font-size: 1rem; transition: border-color 0.3s, box-shadow 0.3s;
}
.form-group input:focus { outline: none; border-color: #0072C6; box-shadow: 0 0 0 3px rgba(0,114,198,0.1); }
.login-btn {
    width: 100%; padding: 16px; background: linear-gradient(90deg, #0072C6, #005999);
    color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600;
    cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
}
.login-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,114,198,0.4); }
.error-message {
    background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 8px;
    margin-bottom: 20px; border-left: 4px solid #dc3545;
}
.hint { font-size: 0.82rem; color: #888; margin-top: 16px; line-height: 1.5; }
@media (max-width: 900px) {
    .login-container { flex-direction: column; margin: 20px; max-width: none; }
    .login-left, .login-right { padding: 40px 30px; }
}
</style>
</head>
<body>
<div class="login-container">
    <div class="login-left">
        <img src="{{ asset('mapa.png') }}" alt="Logo">
        <h1>Data Management System</h1>
        <p>Municipality of Malilipot</p>
        <p style="margin-top:20px; font-size:0.9rem;">Municipal Social Welfare Development</p>
        <p style="margin-top:8px; font-size:0.85rem; opacity:0.8;">Partnered with MDRRMO</p>
        <div class="roles">
            <span class="role-badge"><i class="fas fa-user-shield"></i> MSWD Employee</span>
            <span class="role-badge"><i class="fas fa-houses"></i> Barangay Official</span>
        </div>
    </div>

    <div class="login-right">
        <h2>Sign In</h2>
        <p>Use your MSWD employee or barangay account to sign in</p>

        @if ($errors->any())
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ $errors->first('message') ?? $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username / Barangay Name</label>
                <input type="text" name="username" placeholder="Enter employee username or barangay name" value="{{ old('username') }}" list="barangay-names" autocomplete="off" required>
                <datalist id="barangay-names">
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>
        <p class="hint"><i class="fas fa-info-circle"></i> The system automatically detects whether you are an MSWD employee or a barangay official.</p>
    </div>
</div>
</body>
</html>