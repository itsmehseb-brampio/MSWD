<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invitation - Data Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; display: flex; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.box { width: 100%; max-width: 460px; margin: auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; padding: 40px 32px; }
.box i.warn { font-size: 3rem; color: #dc3545; margin-bottom: 16px; display: block; }
.box h1 { font-size: 1.3rem; color: #333; margin-bottom: 10px; }
.box p { color: #666; font-size: 0.95rem; line-height: 1.6; margin-bottom: 24px; }
.btn { display: inline-block; padding: 14px 30px; background: linear-gradient(90deg, #0072C6, #005999); color: white; border-radius: 10px; text-decoration: none; font-size: 1rem; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,114,198,0.4); }
</style>
</head>
<body>
<div class="box">
    <i class="fas fa-hourglass-half warn"></i>
    <h1>Invitation Not Valid</h1>
    <p>This invitation link is invalid or has already expired. Please contact the MSWD office to receive a new invitation.</p>
    <a href="{{ route('login') }}" class="btn"><i class="fas fa-sign-in-alt"></i> Go to Sign In</a>
</div>
</body>
</html>