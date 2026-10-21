<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Barangay Account Login - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* CSS Variables from app.css are assumed to be loaded */

/* Reset and Base */
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
    min-height: 100vh;
    display: flex;
    background: var(--color-background);
    color: var(--color-foreground);
}

.login-container {
    display: flex;
    width: 100%;
    max-width: 1200px;
    margin: auto;
    background: var(--color-card);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.login-left {
    flex: 1;
    background: var(--color-primary);
    padding: 40px 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    color: white;
}

.login-left img { width: 100px; margin-bottom: 25px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.3); }

.login-left h1 { font-size: 1.8rem; margin-bottom: 8px; }

.login-left p { font-size: 1rem; opacity: 0.9; margin-bottom: 25px; }

.login-left .subtitle { font-size: 0.85rem; opacity: 0.8; margin-bottom: 30px; }

.login-right { flex: 1; padding: 40px 35px; display: flex; flex-direction: column; justify-content: center; }

.login-right h2 { font-size: 1.6rem; color: var(--color-foreground); margin-bottom: 8px; }

.login-right p { color: var(--color-muted-foreground); margin-bottom: 25px; }

/* Form Styles */
.form-group { margin-bottom: 20px; position: relative; }

.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--color-foreground); }

.form-group input {
    width: 100%; padding: 14px 16px; border: 2px solid var(--color-border); border-radius: 10px;
    font-size: 1rem; transition: border-color 0.3s, box-shadow 0.3s;
    background: var(--color-card);
    color: var(--color-foreground);
}

.form-group input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(30,64,175,0.15);
}

.form-group input:focus::placeholder { color: var(--color-muted-foreground); }

/* Barangay-specific: Suggestions box */
.suggestions-box {
    position: absolute; top:100%; left:0; right:0; background: var(--color-card); border:1px solid var(--color-border);
    border-radius:8px; max-height:200px; overflow-y:auto; z-index: 20; display:none; box-shadow:0 8px 20px rgba(0,0,0,0.12);
}

.suggestion-item { padding:10px 14px; cursor:pointer; font-size:0.9rem; color: var(--color-foreground); border-bottom:1px solid var(--color-border); }

.suggestion-item:hover { background: var(--color-muted); }

.suggestion-item.no-match { color: var(--color-muted-foreground); cursor:default; }

.login-btn {
    width: 100%; padding: 16px; background: var(--color-primary); color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600;
    cursor: pointer; transition: box-shadow 0.2s, transform 0.1s;
}

.login-btn:hover { box-shadow: 0 6px 20px rgba(30,64,175,0.4); transform: translateY(-1px); }

.login-btn:active { transform: translateY(1px); }

/* Barangay-specific: Username input with suggestions */
.barangay-input-wrapper { position: relative; }

/* Error Message */
.error-message {
    background: var(--color-muted);
    color: var(--color-destructive);
    padding: 12px 16px; border-radius: 8px;
    margin-bottom: 20px; border-left: 4px solid var(--color-destructive);
}

.error-message i { margin-right: 8px; }

@media (max-width: 900px) {
    .login-container { flex-direction: column; margin: 20px; max-width: none; }
    .login-left, .login-right { padding: 35px 25px; }
}
</style>
</head>
<body>
<div class="login-container">
    <div class="login-left">
        <img src="{{ asset('mapa.png') }}" alt="Logo">
        <h1>Barangay Portal</h1>
        <p>Municipality of Malilipot</p>
        <p class="subtitle">Municipal Social Welfare Development</p>
        <p class="subtitle" style="margin-top:8px; opacity:0.8;">Partnered with MDRRMO</p>
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
            
            <div class="form-group barangay-input-wrapper">
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