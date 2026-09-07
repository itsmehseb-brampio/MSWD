<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'db.php';

$message = "";

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['city_hotline','drrmo_hotline','police_hotline','fire_hotline','medical_services','hospital_emergency','traffic_control','power_emergency','water_emergency','ngo_relief','fire_volunteers','covid_hotline'];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = preg_replace('/\D/', '', $_POST[$f] ?? '');
    }

    $result = $conn->query("SELECT id FROM municipal_contacts LIMIT 1");
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE municipal_contacts SET city_hotline=?, drrmo_hotline=?, police_hotline=?, fire_hotline=?, medical_services=?, hospital_emergency=?, traffic_control=?, power_emergency=?, water_emergency=?, ngo_relief=?, fire_volunteers=?, covid_hotline=? WHERE id=(SELECT id FROM (SELECT id FROM municipal_contacts LIMIT 1) as t)");
        $stmt->bind_param("ssssssssssss", $data['city_hotline'], $data['drrmo_hotline'], $data['police_hotline'], $data['fire_hotline'], $data['medical_services'], $data['hospital_emergency'], $data['traffic_control'], $data['power_emergency'], $data['water_emergency'], $data['ngo_relief'], $data['fire_volunteers'], $data['covid_hotline']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO municipal_contacts (city_hotline, drrmo_hotline, police_hotline, fire_hotline, medical_services, hospital_emergency, traffic_control, power_emergency, water_emergency, ngo_relief, fire_volunteers, covid_hotline) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssss", $data['city_hotline'], $data['drrmo_hotline'], $data['police_hotline'], $data['fire_hotline'], $data['medical_services'], $data['hospital_emergency'], $data['traffic_control'], $data['power_emergency'], $data['water_emergency'], $data['ngo_relief'], $data['fire_volunteers'], $data['covid_hotline']);
        $stmt->execute();
        $stmt->close();
    }
    $message = "Municipal contacts updated successfully!";
}

// Fetch current
$row = $conn->query("SELECT * FROM municipal_contacts LIMIT 1")->fetch_assoc() ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Municipal Contacts - Admin Panel</title>
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

.page-wrap{max-width:900px;margin:0 auto;padding:28px 30px;}
.card{background:#fff;border-radius:14px;padding:26px;box-shadow:0 2px 10px rgba(0,0,0,.06);}
.card-title{display:flex;align-items:center;gap:8px;font-size:1.1rem;color:#202124;margin-bottom:4px;}
.card-title i{color:#0072C6;}
.card-sub{font-size:.85rem;color:#5f6368;margin-bottom:18px;}
.alert{padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:18px;background:#e6f4ea;color:#188038;border-left:4px solid #188038;}
.section-head{display:flex;align-items:center;gap:8px;font-size:.95rem;font-weight:600;color:#202124;margin:22px 0 14px;padding-bottom:8px;border-bottom:2px solid #f0f0f0;}
.section-head i{color:#0072C6;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group label{display:flex;align-items:center;gap:8px;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group label i{width:18px;color:#0072C6;text-align:center;}
.form-group input{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;transition:border-color .2s;}
.form-group input:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.btn{padding:13px 32px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.95rem;font-weight:600;cursor:pointer;margin-top:20px;transition:transform .15s, box-shadow .15s;}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,114,198,.3);}
@media (max-width:700px){.form-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-phone-alt"></i> Municipal Contacts</h1>
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
            <div class="card">
                <div class="card-title"><i class="fas fa-city"></i> City / Municipal Hotlines</div>
                <p class="card-sub">These numbers are displayed as read-only on all barangay contact pages.</p>

                <?php if ($message): ?>
                    <div class="alert"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="section-head"><i class="fas fa-siren"></i> Emergency Hotlines</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> City/Municipal General Hotline</label>
                            <input type="text" name="city_hotline" value="<?php echo htmlspecialchars($row['city_hotline'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-shield-alt"></i> DRRMO</label>
                            <input type="text" name="drrmo_hotline" value="<?php echo htmlspecialchars($row['drrmo_hotline'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Police / PNP Station</label>
                            <input type="text" name="police_hotline" value="<?php echo htmlspecialchars($row['police_hotline'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-fire-extinguisher"></i> Fire Department (BFP)</label>
                            <input type="text" name="fire_hotline" value="<?php echo htmlspecialchars($row['fire_hotline'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-ambulance"></i> Medical / Ambulance Services</label>
                            <input type="text" name="medical_services" value="<?php echo htmlspecialchars($row['medical_services'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-hospital"></i> Hospital / Medical Emergency</label>
                            <input type="text" name="hospital_emergency" value="<?php echo htmlspecialchars($row['hospital_emergency'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-traffic-light"></i> Traffic Control</label>
                            <input type="text" name="traffic_control" value="<?php echo htmlspecialchars($row['traffic_control'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-bolt"></i> Power / Electricity</label>
                            <input type="text" name="power_emergency" value="<?php echo htmlspecialchars($row['power_emergency'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tint"></i> Water / Utilities</label>
                            <input type="text" name="water_emergency" value="<?php echo htmlspecialchars($row['water_emergency'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                    </div>

                    <div class="section-head"><i class="fas fa-hands-helping"></i> Optional / Useful Numbers</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-hands-helping"></i> NGOs / Relief Services</label>
                            <input type="text" name="ngo_relief" value="<?php echo htmlspecialchars($row['ngo_relief'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-fire"></i> Fire Volunteers / Rescue Teams</label>
                            <input type="text" name="fire_volunteers" value="<?php echo htmlspecialchars($row['fire_volunteers'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-virus"></i> COVID-19 / Health Concerns</label>
                            <input type="text" name="covid_hotline" value="<?php echo htmlspecialchars($row['covid_hotline'] ?? ''); ?>" maxlength="11" placeholder="09XXXXXXXXX">
                        </div>
                    </div>

                    <button type="submit" class="btn"><i class="fas fa-save"></i> Save Municipal Contacts</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown(){ document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e){ if(!e.target.closest('.admin-profile')) document.getElementById("dropdownMenu").classList.remove("show"); }
</script>
</body>
</html>
