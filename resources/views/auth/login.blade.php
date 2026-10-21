<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MSWD Login - Data Management System</title>
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

.login-btn {
    width: 100%; padding: 16px; background: var(--color-primary); color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600;
    cursor: pointer; transition: box-shadow 0.2s, transform 0.1s;
}

.login-btn:hover { box-shadow: 0 6px 20px rgba(30,64,175,0.4); transform: translateY(-1px); }

.login-btn:active { transform: translateY(1px); }

/* Password show/hide */
.password-toggle {
    position: absolute;
    right: 14px; top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: var(--color-muted-foreground);
    font-size: 0.8rem;
    transition: color 0.2s;
}

.password-toggle:hover { color: var(--color-primary); }

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
        <h1>Data Management System</h1>
        <p>Municipality of Malilipot</p>
        <p class="subtitle">Municipal Social Welfare Development</p>
        <p class="subtitle" style="margin-top:8px; opacity:0.8;">Partnered with MDRRMO</p>
    </div>

    <div class="login-right">
        <h2>MSWD Employee Login</h2>
        <p>Sign in with your MSWD account</p>

        @if ($errors->any())
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ $errors->first('message') ?? $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" placeholder="Enter employee username or barangay username" value="{{ old('username') }}" list="barangay-names" autocomplete="off" required>
                <datalist id="barangay-names">
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required
                    id="passwordField">
                <span class="password-toggle" onclick="togglePasswordVisibility()"><i class="fas fa-eye" id="eyeIcon"></i></span>
            </div>

            <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>
        <p class="hint"><i class="fas fa-info-circle"></i> The system automatically detects whether you are an MSWD employee or a barangay official.</p>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passwordField = document.getElementById('passwordField');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}
</script>

</body>
</html>