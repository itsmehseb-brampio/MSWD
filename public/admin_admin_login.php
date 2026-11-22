<?php
session_start();
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: admin_dashboard.php");
            exit;
        }
    }

    $message = 'Incorrect username or password!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MSWD Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 20px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .brand { text-align: center; margin-bottom: 30px; }
        .brand img { width: 96px; height: 96px; border-radius: 50%; margin-bottom: 16px; }
        .brand h1 { color: #0072C6; font-size: 1.65rem; margin-bottom: 6px; }
        .brand p { color: #666; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #333; font-weight: 600; margin-bottom: 8px; }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0072C6;
            box-shadow: 0 0 0 3px rgba(0,114,198,.1);
        }
        .login-btn {
            width: 100%;
            padding: 16px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: linear-gradient(90deg, #0072C6, #005999);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <img src="mapa.png" alt="Municipality of Malilipot">
            <h1>MSWD Admin Login</h1>
            <p>Municipal Social Welfare Development</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username"><i class="fas fa-user-tie"></i> Employee Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
    </div>
</body>
</html>
