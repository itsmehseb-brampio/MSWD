<?php
session_start();
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? 'admin';
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $loggedIn = false;

    $attempts = ($role == 'barangay') ? ['barangay', 'admin'] : ['admin', 'barangay'];

    foreach ($attempts as $attemptRole) {
        if ($attemptRole == 'admin') {
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $loggedIn = true;
                    header("Location: admin_dashboard.php");
                    exit;
                }
            }
        } else {
            $stmt = $conn->prepare("SELECT * FROM barangays WHERE barangay_name = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows >= 1) {
                $barangay = $result->fetch_assoc();
                if (password_verify($password, $barangay['password'])) {
                    $_SESSION['barangay_id'] = $barangay['barangay_id'];
                    $_SESSION['barangay_name'] = $barangay['barangay_name'];
                    $loggedIn = true;
                    header("Location: barangay_dashboard.php");
                    exit;
                }
            }
        }
    }

    if (!$loggedIn) {
        $message = "Incorrect username or password!";
    }
}
?>
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

.login-left img {
    width: 120px;
    margin-bottom: 30px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
}

.login-left h1 {
    font-size: 2rem;
    margin-bottom: 10px;
}

.login-left p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.login-right {
    flex: 1;
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.login-right h2 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 10px;
}

.login-right p {
    color: #666;
    margin-bottom: 30px;
}

.role-toggle {
    display: flex;
    background: #f1f1f1;
    border-radius: 10px;
    padding: 4px;
    margin-bottom: 25px;
    gap: 4px;
}

.role-toggle label {
    flex: 1;
    text-align: center;
    padding: 12px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    color: #555;
    cursor: pointer;
    transition: all 0.3s;
}

.role-toggle label.active {
    background: linear-gradient(90deg, #0072C6, #005999);
    color: white;
    box-shadow: 0 3px 10px rgba(0,114,198,0.3);
}

.role-toggle input {
    display: none;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
}

.form-group input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #0072C6;
    box-shadow: 0 0 0 3px rgba(0,114,198,0.1);
}

.login-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(90deg, #0072C6, #005999);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,114,198,0.4);
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #dc3545;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 25px;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
}

.back-link:hover {
    color: #0072C6;
}

.suggestions-box {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e0e0e0;
    border-top: none;
    border-radius: 0 0 10px 10px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 100;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.suggestion-item {
    padding: 12px 16px;
    cursor: pointer;
    font-size: 0.95rem;
    color: #333;
    transition: background 0.2s;
    border-bottom: 1px solid #f0f0f0;
}
.suggestion-item:last-child { border-bottom: none; }
.suggestion-item:hover { background: #e8f5e9; color: #0072C6; }
.suggestion-item.no-match { color: #999; cursor: default; }
.suggestion-item.no-match:hover { background: none; }

@media (max-width: 900px) {
    .login-container {
        flex-direction: column;
        margin: 20px;
        max-width: none;
    }
    .login-left {
        padding: 40px 30px;
    }
    .login-right {
        padding: 40px 30px;
    }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-left">
        <img src="mapa.png" alt="Logo">
        <h1>Data Management System</h1>
        <p>Municipality of Malilipot</p>
        <p style="margin-top: 20px; font-size: 0.9rem;">Municipal Social Welfare Development</p>
        <p style="margin-top: 8px; font-size: 0.85rem; opacity: 0.8;">Partnered with MDRRMO</p>
    </div>
    
    <div class="login-right">
        <h2>MSWD System Login</h2>
        <p>Sign in with your MSWD employee or barangay account</p>
        
        <?php if($message != ''): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php
        $barResult = $conn->query("SELECT barangay_name FROM barangays ORDER BY barangay_name");
        $barNames = [];
        if ($barResult) {
            while ($row = $barResult->fetch_assoc()) {
                $barNames[] = $row['barangay_name'];
            }
        }
        $barNames = array_unique($barNames);
        ?>
        
        <form method="POST">
            <div class="role-toggle">
                <label class="active" id="labelAdmin">
                    <input type="radio" name="role" value="admin" checked onchange="switchRole('admin')">
                    <i class="fas fa-user-tie"></i> MSWD Staff
                </label>
                <label id="labelBarangay">
                    <input type="radio" name="role" value="barangay" onchange="switchRole('barangay')">
                    <i class="fas fa-building"></i> Barangay Staff
                </label>
            </div>

            <div class="form-group" style="position:relative;">
                <label id="usernameLabel"><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" id="usernameInput" placeholder="Enter your username" required autocomplete="off" oninput="showSuggestions()" onfocus="showSuggestions()">
                <div id="suggestions" class="suggestions-box"></div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

    </div>
</div>

<script>
const barangays = <?php echo json_encode(array_values($barNames)); ?>;
const isBarangayMode = () => document.querySelector('input[name="role"]:checked').value === 'barangay';

function switchRole(role) {
    const adminLabel = document.getElementById('labelAdmin');
    const barangayLabel = document.getElementById('labelBarangay');
    const usernameLabel = document.getElementById('usernameLabel');
    const input = document.getElementById('usernameInput');

    adminLabel.classList.toggle('active', role === 'admin');
    barangayLabel.classList.toggle('active', role === 'barangay');

    if (role === 'barangay') {
        usernameLabel.innerHTML = '<i class="fas fa-building"></i> Barangay Account Name';
        input.placeholder = 'Start typing barangay name...';
    } else {
        usernameLabel.innerHTML = '<i class="fas fa-user"></i> Employee Username';
        input.placeholder = 'Enter your employee username';
    }
    document.getElementById('suggestions').style.display = 'none';
}

function showSuggestions() {
    if (!isBarangayMode()) {
        document.getElementById('suggestions').style.display = 'none';
        return;
    }

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
    if (!e.target.closest('.form-group') && !e.target.closest('.role-toggle')) {
        document.getElementById('suggestions').style.display = 'none';
    }
});
</script>

</body>
</html>