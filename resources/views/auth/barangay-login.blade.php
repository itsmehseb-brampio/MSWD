<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Barangay Account Login - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
    background: linear-gradient(135deg, #11998e 0%, #0b6e4f 100%);
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
.login-right { flex: 1; padding: 60px 50px; display: flex; flex-direction: column; justify-content: center; }
.login-right h2 { font-size: 1.8rem; color: #333; margin-bottom: 10px; }
.login-right p { color: #666; margin-bottom: 30px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
.form-group input {
    width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 10px;
    font-size: 1rem; transition: border-color 0.3s, box-shadow 0.3s;
}
.form-group input:focus { outline: none; border-color: #11998e; box-shadow: 0 0 0 3px rgba(17,153,142,0.1); }
.login-btn {
    width: 100%; padding: 16px; background: linear-gradient(90deg, #11998e, #0b6e4f);
    color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600;
    cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
}
.login-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(17,153,142,0.4); }
.error-message {
    background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 8px;
    margin-bottom: 20px; border-left: 4px solid #dc3545;
}
.suggestions-box {
    position: absolute; top:100%; left:0; right:0; background:white; border:1px solid #e0e0e0;
    border-radius:8px; max-height:200px; overflow-y:auto; z-index: 20; display:none; box-shadow:0 8px 20px rgba(0,0,0,0.12);
}
.suggestion-item { padding:10px 14px; cursor:pointer; font-size:0.9rem; color:#333; border-bottom:1px solid #f5f5f5; }
.suggestion-item:hover { background:#eafaf6; }
.suggestion-item.no-match { color:#999; cursor:default; }
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
        <h1>Barangay Portal</h1>
        <p>Municipality of Malilipot</p>
        <p style="margin-top:20px; font-size:0.9rem;">Municipal Social Welfare Development</p>
        <p style="margin-top:8px; font-size:0.85rem; opacity:0.8;">Partnered with MDRRMO</p>
    </div>

    <div class="login-right">
        <h2>Barangay Account Login</h2>
        <p>Enter your barangay account name and password</p>

        @if ($errors->any())
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ $errors->first('message') ?? $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('barangay.login.post') }}">
            @csrf
            <div class="form-group" style="position:relative;">
                <label><i class="fas fa-building"></i> Barangay Account Name</label>
                <input type="text" name="username" id="usernameInput" placeholder="Start typing barangay name..." value="{{ old('username') }}" required autocomplete="off" oninput="showSuggestions()" onfocus="showSuggestions()">
                <div id="suggestions" class="suggestions-box"></div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>
    </div>
</div>

<script>
const barangays = @json(array_values($barangays));

function showSuggestions() {
    const input = document.getElementById('usernameInput').value.toLowerCase();
    const box = document.getElementById('suggestions');

    if (input.length === 0) {
        box.innerHTML = barangays.map(b => '<div class="suggestion-item" onclick="selectBarangay(\'' + b.replace(/'/g, "\\'") + '\')">' + b + '</div>').join('');
        box.style.display = barangays.length > 0 ? 'block' : 'none';
        return;
    }

    const matches = barangays.filter(b => b.toLowerCase().includes(input));

    if (matches.length > 0) {
        box.innerHTML = matches.map(b => '<div class="suggestion-item" onclick="selectBarangay(\'' + b.replace(/'/g, "\\'") + '\')">' + b + '</div>').join('');
        box.style.display = 'block';
    } else {
        box.innerHTML = '<div class="suggestion-item no-match">No matching barangay found</div>';
        box.style.display = 'block';
    }
}

function selectBarangay(name) {
    document.getElementById('usernameInput').value = name;
    document.getElementById('suggestions').style.display = 'none';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#usernameInput') && !e.target.closest('#suggestions')) {
        document.getElementById('suggestions').style.display = 'none';
    }
});
</script>
</body>
</html>
