<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barangay_name = trim($_POST['barangay_name']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($barangay_name == '' || $address == '' || $password == '' || $confirm_password == '') {
        $message = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO barangays (barangay_name, address, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $barangay_name, $address, $hashed);
        if ($stmt->execute()) {
            $message = 'Barangay account saved successfully!';
        } else {
            $message = 'Error: ' . $stmt->error;
        }
    }
}

// fetch existing accounts
$result = mysqli_query($conn, "SELECT barangay_name FROM barangays");
$existing = [];
while ($row = mysqli_fetch_assoc($result)) {
    $existing[] = $row['barangay_name'];
}

$all_barangays = [
    'Barangay I', 'Barangay II', 'Barangay III', 'Barangay IV', 'Barangay V',
    'Binitayan', 'Calbayog', 'Canaway', 'Salvacion',
    'San Antonio Santicon', 'San Antonio Sulong', 'San Francisco',
    'San Isidro Ilawod', 'San Isidro Iraya', 'San Jose',
    'San Roque', 'Santa Cruz', 'Santa Teresa'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Barangay Accounts - Admin Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Roboto','Segoe UI',sans-serif;background:#f5f7fa;min-height:100vh;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;min-height:100vh;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.header{display:flex;justify-content:space-between;align-items:center;padding:18px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.header h1{font-size:1.35rem;}
.admin-profile{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.admin-profile:hover{background:rgba(255,255,255,0.25);}
.admin-profile img{width:36px;height:36px;border-radius:50%;border:2px solid white;object-fit:cover;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}

.page-wrap{max-width:1100px;margin:0 auto;padding:28px 30px;}
.page-grid{display:grid;grid-template-columns:340px 1fr;gap:22px;align-items:start;}
.card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06);}
.card h3{display:flex;align-items:center;gap:8px;font-size:1rem;color:#202124;margin-bottom:4px;}
.card h3 i{color:#0072C6;}
.card .card-sub{font-size:.8rem;color:#5f6368;margin-bottom:16px;}

.brgy-list{list-style:none;max-height:600px;overflow-y:auto;}
.brgy-list li{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;margin-bottom:6px;background:#f8f9fa;border-radius:10px;font-size:.88rem;color:#202124;font-weight:500;}
.brgy-list li .st{font-size:.68rem;font-weight:600;padding:3px 10px;border-radius:12px;}
.st.yes{background:#e6f4ea;color:#188038;}
.st.no{background:#f1f3f4;color:#5f6368;}

.form-title{display:flex;align-items:center;gap:8px;font-size:1.05rem;color:#202124;margin-bottom:16px;}
.form-title i{color:#0072C6;}
.alert{padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:16px;}
.alert.ok{background:#e6f4ea;color:#188038;border-left:4px solid #188038;}
.alert.err{background:#fce8e6;color:#c5221f;border-left:4px solid #c5221f;}
.form-group{margin-bottom:16px;position:relative;}
.form-group label{display:block;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group input{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;transition:border-color .2s;}
.form-group input:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.suggestions-list{list-style:none;margin:0;padding:0;border:2px solid #e0e0e0;border-top:none;max-height:140px;overflow-y:auto;position:absolute;width:100%;background:#fff;z-index:10;display:none;border-radius:0 0 10px 10px;box-shadow:0 6px 16px rgba(0,0,0,.1);}
.suggestions-list li{padding:9px 14px;cursor:pointer;font-size:.88rem;}
.suggestions-list li:hover{background:#f0f7ff;color:#0072C6;}
.btn{width:100%;padding:12px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.95rem;font-weight:600;cursor:pointer;transition:transform .15s, box-shadow .15s;}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,114,198,.3);}
@media (max-width:820px){.page-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-building"></i> Barangay Accounts</h1>
            <div style="position:relative;">
                <div class="admin-profile" onclick="toggleDropdown()">
                    <img src="mapa.png" alt="Admin">
                    <span><?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="page-wrap">
            <div class="page-grid">
                <!-- LEFT : BARANGAY LIST -->
                <div class="card">
                    <h3><i class="fas fa-list"></i> Barangays of Malilipot</h3>
                    <p class="card-sub">Accounts already created are marked with a badge.</p>
                    <ul class="brgy-list">
                        <?php foreach ($all_barangays as $b):
                            $has = in_array($b, $existing);
                            $badge = $has ? '<span class="st yes">Account created</span>' : '<span class="st no">No account</span>';
                        ?>
                            <li><?php echo htmlspecialchars($b); ?> <?php echo $badge; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- RIGHT : ADD FORM -->
                <div class="card">
                    <div class="form-title"><i class="fas fa-user-plus"></i> Add Barangay Account</div>
                    <?php if ($message != ''): ?>
                        <div class="alert <?php echo (strpos($message, 'successfully') !== false) ? 'ok' : 'err'; ?>">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Barangay Name</label>
                            <input type="text" id="barangay-input" name="barangay_name" placeholder="Enter barangay name" autocomplete="off">
                            <ul id="suggestions" class="suggestions-list"></ul>
                        </div>
                        <div class="form-group">
                            <label>Full Address</label>
                            <input type="text" name="address" placeholder="Enter full address">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Enter password">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm password">
                        </div>
                        <button type="submit" class="btn"><i class="fas fa-save"></i> Save Barangay</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown(){ document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e){ if(!e.target.closest('.admin-profile')) document.getElementById("dropdownMenu").classList.remove("show"); }

const barangays = <?php echo json_encode(array_diff($all_barangays, $existing)); ?>;
const input = document.getElementById('barangay-input');
const suggestions = document.getElementById('suggestions');

input.addEventListener('input', function(){
    const value = this.value.toLowerCase();
    suggestions.innerHTML = '';
    if(value === '') { suggestions.style.display = 'none'; return; }
    const filtered = barangays.filter(b => b.toLowerCase().includes(value));
    if(filtered.length === 0){ suggestions.style.display = 'none'; return; }
    filtered.forEach(b => {
        const li = document.createElement('li');
        li.textContent = b;
        li.addEventListener('click', function(){
            input.value = b;
            suggestions.innerHTML = '';
            suggestions.style.display = 'none';
        });
        suggestions.appendChild(li);
    });
    suggestions.style.display = 'block';
});

document.addEventListener('click', function(e){
    if(e.target !== input){ suggestions.style.display = 'none'; }
});
</script>
</body>
</html>
