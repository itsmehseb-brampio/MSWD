<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MSWD Login - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* CSS Variables from app.css are assumed to be loaded */

/* ============ Design System Tokens ============ */
:root {
    --bs-body-font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
    --bs-body-font-size: 0.9375rem;
    --bs-body-line-height: 1.5;
    --bs-container-max-width: 1200px;
}

/* ============ Base & Reset ============ */
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: var(--bs-body-font-family);
    font-size: var(--bs-body-font-size);
    line-height: var(--bs-body-line-height);
    min-height: 100vh;
    display: flex;
    background: var(--color-background);
    color: var(--color-foreground);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Typography improvements */
h1, h2, h3, h4, h5, h6 {
    font-weight: 600;
    line-height: 1.25;
    color: var(--color-foreground);
}

h1 { font-size: 1.8rem; }
h2 { font-size: 1.6rem; }
p { margin-bottom: 1rem; }

/* Subtle focus-visible style */
:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }

/* ============ Layout Containers ============ */
.login-container {
    display: flex;
    width: 100%;
    max-width: 1200px;
    margin: auto;
    background: var(--color-card);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 
        0 2px 20px rgba(0,0,0,0.08),
        0 0 0 1px rgba(30,64,175,0.03);
    margin: 20px 0;
}

/* Subtle divider between panels */
.login-container::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 1px;
    background: var(--color-border);
    opacity: 0.5;
}

/* ============ Left Panel (Brand/Logo) ============ */
.login-left {
    flex: 1;
    background: var(--color-primary);
    position: relative;
    padding: 50px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    color: white;
    gap: 20px;
}

/* Logo container with subtle framing */
.logo-frame {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: rgba(255,255,255,0.15);
    border: 3px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    padding: 4px;
}

.logo-frame img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: brightness(1.2) drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.login-left h1 {
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin-bottom: 0.5rem;
}

.login-left p:first-of-type {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}

.login-left .subtitle {
    font-size: 0.8125rem;
    opacity: 0.8;
    font-weight: 400;
}

/* ============ Right Panel (Form) ============ */
.login-right {
    flex: 1;
    padding: 50px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 20px;
}

/* Form section heading */
.login-right h2 {
    color: var(--color-foreground);
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.login-right p {
    color: var(--color-muted-foreground);
    font-size: 0.875rem;
    line-height: 1.5;
}

/* Form groups with improved spacing */
.form-group {
    margin-bottom: 0;
    position: relative;
    isolation: isolate;
}

.form-group .form-label {
    display: block;
    margin-bottom: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-foreground);
    letter-spacing: 0.01em;
}

/* Improved form inputs */
.form-group input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--color-border);
    border-radius: 10px;
    font-size: 1rem;
    font-family: inherit;
    background: var(--color-card);
    color: var(--color-foreground);
    transition: all 0.2s ease;
}

.form-group input::placeholder {
    color: var(--color-muted-foreground);
    opacity: 0.8;
}

.form-group input:hover {
    border-color: var(--color-secondary);
}

.form-group input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px rgba(30,64,175,0.15);
}

/::-webkit-input-placeholder { color: var(--color-muted-foreground); opacity: 0.8; }
::-moz-placeholder { color: var(--color-muted-foreground); opacity: 0.8; }
::-ms-input-placeholder { color: var(--color-muted-foreground); opacity: 0.8; }
::-ms-input-placeholder { color: var(--color-muted-foreground); opacity: 0.8; }

/* ============ Login Button ============ */
.login-btn {
    width: 100%;
    padding: 14px 24px;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.login-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.login-btn:hover {
    box-shadow: 0 8px 25px rgba(30,64,175,0.4);
    transform: translateY(-2px);
}

.login-btn:hover::before { left: 100%; }

.login-btn:active {
    transform: translateY(1px);
    box-shadow: 0 4px 10px rgba(30,64,175,0.3);
}

.login-btn:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* ============ Password Toggle ============ */
.password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: var(--color-muted-foreground);
    font-size: 0.8125rem;
    transition: color 0.2s;
    padding: 4px;
    background: none;
    border: none;
}

.password-toggle:hover {
    color: var(--color-primary);
}

/* ============ Error Message ============ */
.error-message {
    background: var(--color-muted);
    color: var(--color-destructive);
    border: none;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid var(--color-destructive);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.error-message i {
    flex-shrink: 0;
    color: var(--color-destructive);
}

/* ============ Form Hint ============ */
.hint {
    font-size: 0.75rem;
    color: var(--color-muted-foreground);
    margin-top: 12px;
    line-height: 1.4;
}

/* ============ Datalist Styling ============ */
.datalist-container {
    position: relative;
}

.datalist-container datalist option {
    padding: 8px 12px;
    font-size: 0.875rem;
    color: var(--color-foreground);
}

.datalist-container datalist option:hover {
    background: var(--color-muted);
    color: var(--color-foreground);
}

/* ============ Responsive Behavior ============ */
@media (max-width: 900px) {
    .login-container {
        border-radius: 12px;
        margin: 15px 0;
    }
    
    .login-left, .login-right {
        padding: 35px 25px;
    }
    
    .login-left {
        padding: 40px 25px;
    }
    
    .logo-frame { width: 70px; height: 70px; margin-bottom: 16px; }
    
    .login-left h1 { font-size: 1.5rem; }
    .login-left p:first-of-type { font-size: 0.875rem; }
    .login-left .subtitle { font-size: 0.75rem; }
}

@media (max-width: 600px) {
    .login-container { margin: 10px 0; }
    
    .login-left, .login-right {
        padding: 25px 20px;
    }
    
    .logo-frame { width: 60px; height: 60px; margin-bottom: 12px; }
    
    .login-left h1 { font-size: 1.3rem; }
    .login-left p:first-of-type { font-size: 0.8125rem; }
    .login-left .subtitle { font-size: 0.7rem; }
    
    .login-right h2 { font-size: 1.3rem; }
    .login-right p { font-size: 0.8125rem; }
    
    .login-btn { padding: 12px 20px; font-size: 0.9375rem; }
    
    .error-message { padding: 10px 12px; font-size: 0.75rem; }
    .hint { font-size: 0.7rem; }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-left">
        <div class="logo-frame">
            <img src="{{ asset('mapa.png') }}" alt="MSWD Logo">
        </div>
        <h1>Data Management System</h1>
        <p>Municipality of Malilipot</p>
        <p class="subtitle">Municipal Social Welfare Development</p>
        <p class="subtitle">Partnered with MDRRMO</p>
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
                <label class="form-label"><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" placeholder="Enter employee username or barangay username" value="{{ old('username') }}" list="barangay-names" autocomplete="off" required>
                <datalist id="barangay-names">
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required
                    id="passwordField">
                <button type="button" class="password-toggle" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
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