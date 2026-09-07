<?php
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header("Location: barangay_login.php");
    exit();
}
require 'db.php';

$barangay_id = $_SESSION['barangay_id'];
$barangay_name = $_SESSION['barangay_name'] ?? 'Barangay';
$report_id = intval($_GET['id'] ?? 0);

$report = $conn->query("SELECT * FROM disaster_reports WHERE report_id=$report_id AND barangay_id=$barangay_id")->fetch_assoc();
if (!$report) {
    header("Location: barangay_disaster_history.php");
    exit();
}

$fields = $conn->query("SELECT * FROM disaster_format_fields WHERE field_name != 'format_no' ORDER BY field_order ASC")->fetch_all(MYSQLI_ASSOC);
$details = $conn->query("SELECT captain_name, secretary_name FROM barangay_details WHERE barangay_id=$barangay_id")->fetch_assoc() ?? [];

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$allowed = ['jpg','jpeg','png','gif','webp'];

function uploadFile($file, $prefix, $barangay_id, $uploadDir, $allowed) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $fname = $prefix . '_' . $barangay_id . '_' . time() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $uploadDir . $fname);
            return $fname;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ti = trim($_POST['title'] ?? '');
    $dt = trim($_POST['disaster_type'] ?? '');
    $hh = trim($_POST['household_head'] ?? '');
    $fm = intval($_POST['family_members'] ?? 0);
    $fa = trim($_POST['full_address'] ?? '');
    $ht = trim($_POST['housing_type'] ?? '');
    $de = trim($_POST['damage_extent'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    $p1 = uploadFile($_FILES['pic1'] ?? null, 'report_pic1', $barangay_id, $uploadDir, $allowed) ?: $report['pic1'];
    $p2 = uploadFile($_FILES['pic2'] ?? null, 'report_pic2', $barangay_id, $uploadDir, $allowed) ?: $report['pic2'];
    $p3 = uploadFile($_FILES['pic3'] ?? null, 'report_pic3', $barangay_id, $uploadDir, $allowed) ?: $report['pic3'];
    $p4 = uploadFile($_FILES['pic4'] ?? null, 'report_pic4', $barangay_id, $uploadDir, $allowed) ?: $report['pic4'];
    $b2b = uploadFile($_FILES['b2b_id'] ?? null, 'b2b', $barangay_id, $uploadDir, $allowed) ?: $report['b2b_id'];

    $reedit = isset($_GET['reedit']) ? true : false;
    if ($reedit) {
        $stmt = $conn->prepare("UPDATE disaster_reports SET title=?, disaster_type=?, household_head=?, family_members=?, full_address=?, housing_type=?, damage_extent=?, description=?, pic1=?, pic2=?, pic3=?, pic4=?, b2b_id=?, status='pending', decline_reason=NULL WHERE report_id=? AND barangay_id=?");
    } else {
        $stmt = $conn->prepare("UPDATE disaster_reports SET title=?, disaster_type=?, household_head=?, family_members=?, full_address=?, housing_type=?, damage_extent=?, description=?, pic1=?, pic2=?, pic3=?, pic4=?, b2b_id=? WHERE report_id=? AND barangay_id=?");
    }
    $stmt->bind_param("sssisssssssssii", $ti, $dt, $hh, $fm, $fa, $ht, $de, $desc, $p1, $p2, $p3, $p4, $b2b, $report_id, $barangay_id);
    $stmt->execute();
    $stmt->close();

    if ($reedit) {
        header("Location: barangay_reedit.php?success=1");
    } else {
        header("Location: barangay_disaster_edit.php?id=$report_id&updated=1");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Report - <?php echo htmlspecialchars($report['format_no']); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;min-height:100vh;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;min-height:100vh;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.header{display:flex;justify-content:space-between;align-items:center;padding:18px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.header h1{font-size:1.4rem;}
.profile-area{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.profile-area:hover{background:rgba(255,255,255,0.25);}
.profile-area img{width:36px;height:36px;border-radius:50%;border:2px solid white;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}
.content{padding:25px 30px;display:flex;flex-direction:column;align-items:center;}
.toast{position:fixed;top:20px;right:20px;background:#0072C6;color:white;padding:14px 24px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:9999;display:flex;align-items:center;gap:10px;animation:slideIn 0.3s ease;}
.toast.error{background:#dc3545;}
@keyframes slideIn{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}
.form-card{background:white;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;width:100%;max-width:860px;}
.form-card-header{padding:18px 25px;background:linear-gradient(90deg,#0072C6,#005999);color:white;display:flex;align-items:center;gap:10px;}
.form-card-header h2{font-size:1.1rem;}
.form-card-body{padding:25px 30px;}
.format-no-bar{display:flex;align-items:center;gap:10px;background:#f0fff4;border:2px solid #0072C6;border-radius:10px;padding:12px 18px;margin-bottom:20px;}
.format-no-bar i{font-size:1.2rem;color:#0072C6;}
.format-no-bar div{font-size:0.7rem;color:#666;}
.format-no-bar span{font-weight:700;color:#0072C6;font-size:1.1rem;letter-spacing:1px;}
.fg{margin-bottom:15px;}
.fg label{display:block;font-weight:600;color:#444;margin-bottom:5px;font-size:0.88rem;}
.fg label .req{color:#dc3545;margin-left:2px;}
.fg input[type="text"],.fg input[type="number"],.fg select,.fg textarea{width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.9rem;font-family:'Segoe UI',sans-serif;transition:border-color 0.3s;}
.fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:#0072C6;}
.fg textarea{resize:vertical;min-height:80px;}
.radio-group{display:flex;gap:10px;flex-wrap:wrap;}
.radio-option{display:flex;align-items:center;gap:6px;padding:9px 16px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;font-size:0.88rem;color:#555;transition:all 0.2s;}
.radio-option:hover{border-color:#0072C6;background:#f0fff4;}
.radio-option input{accent-color:#0072C6;}
.sig-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:25px;padding-top:20px;border-top:2px solid #e8e8e8;}
.sig-approved{text-align:center;padding:10px 0 5px;}
.sig-approved-line{width:220px;height:2px;background:#333;margin:0 auto 8px;}
.sig-approved-label{font-size:0.8rem;font-weight:700;color:#444;}
.sig-approved-sub{font-size:0.68rem;color:#888;margin-top:3px;}
.btn-submit{padding:12px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all 0.2s;margin-top:10px;}
.btn-submit:hover{background:linear-gradient(90deg,#218838,#1ba886);transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,114,198,0.3);}
.photo-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;}
.photo-grid img{width:100%;border:1px solid #ddd;border-radius:6px;margin-bottom:6px;max-height:100px;object-fit:cover;}
.photo-grid input[type="file"]{font-size:11px;width:100%;}
@media(max-width:600px){.photo-grid{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'barangay_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Edit Report</h1>
            <div style="position:relative;">
                <div class="profile-area" onclick="toggleDropdown()">
                    <span style="font-weight:500;"><?php echo htmlspecialchars($barangay_name); ?></span>
                    <img src="<?php echo htmlspecialchars($header_logo ?? 'yana.png'); ?>" alt="Logo" onerror="this.src='yana.png'">
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
        <div class="content">
            <?php if (isset($_GET['updated'])): ?>
            <div class="toast" id="toast"><i class="fas fa-check-circle"></i> Report updated successfully!</div>
            <?php endif; ?>

            <div class="form-card">
                <div class="form-card-header">
                    <i class="fas fa-edit"></i>
                    <h2>Edit Disaster Report</h2>
                    <span style="margin-left:auto;font-size:0.78rem;opacity:0.8;">Municipality of Malilipot - DSWD</span>
                </div>
                <div class="form-card-body">
                    <div class="format-no-bar">
                        <i class="fas fa-hashtag"></i>
                        <div>
                            <div style="font-size:0.7rem;color:#666;">Format No.</div>
                            <span><?php echo htmlspecialchars($report['format_no']); ?></span>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="fg">
                            <label>Report Title <span class="req">*</span></label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($report['title'] ?? ''); ?>" required>
                        </div>

                        <div class="fg">
                            <label>Type of Disaster <span class="req">*</span></label>
                            <div class="radio-group">
                                <?php
                                $disasterTypes = ['Typhoon','Flood','Earthquake','Fire','Landslide','Volcanic Eruption','Storm Surge','Other'];
                                foreach ($disasterTypes as $dt):
                                    $checked = ($report['disaster_type'] ?? '') === $dt ? 'checked' : '';
                                ?>
                                <label class="radio-option">
                                    <input type="radio" name="disaster_type" value="<?php echo $dt; ?>" <?php echo $checked; ?> required>
                                    <?php echo $dt; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="fg">
                            <label>Name of Household Head <span class="req">*</span></label>
                            <input type="text" name="household_head" value="<?php echo htmlspecialchars($report['household_head'] ?? ''); ?>" required>
                        </div>

                        <div class="fg">
                            <label>Number of Family Members <span class="req">*</span></label>
                            <input type="number" name="family_members" value="<?php echo htmlspecialchars($report['family_members'] ?? ''); ?>" required>
                        </div>

                        <div class="fg">
                            <label>Full Address <span class="req">*</span></label>
                            <input type="text" name="full_address" value="<?php echo htmlspecialchars($report['full_address'] ?? ''); ?>" required>
                        </div>

                        <div class="fg">
                            <label>Housing Type <span class="req">*</span></label>
                            <div class="radio-group">
                                <?php
                                $housingTypes = ['Light Materials','Semi-Concrete','Fully Concrete'];
                                foreach ($housingTypes as $ht):
                                    $checked = ($report['housing_type'] ?? '') === $ht ? 'checked' : '';
                                ?>
                                <label class="radio-option">
                                    <input type="radio" name="housing_type" value="<?php echo $ht; ?>" <?php echo $checked; ?> required>
                                    <?php echo $ht; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="fg">
                            <label>Extent of Damage <span class="req">*</span></label>
                            <div class="radio-group">
                                <?php
                                $damageExtents = ['Partially Damaged','Totally Damaged'];
                                foreach ($damageExtents as $de):
                                    $val = $de === 'Partially Damaged' ? 'Partially' : 'Totally';
                                    $checked = ($report['damage_extent'] ?? '') === $val ? 'checked' : '';
                                ?>
                                <label class="radio-option">
                                    <input type="radio" name="damage_extent" value="<?php echo $de; ?>" <?php echo $checked; ?> required>
                                    <?php echo $de; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="fg">
                            <label>Description / Remarks</label>
                            <textarea name="description" rows="4"><?php echo htmlspecialchars($report['description'] ?? ''); ?></textarea>
                        </div>

                        <div style="margin-top:25px;padding-top:20px;border-top:2px solid #e8e8e8;">
                            <h4 style="font-size:0.85rem;color:#0072C6;margin-bottom:15px;"><i class="fas fa-images"></i> Damage Photos (leave blank to keep current)</h4>
                            <div class="photo-grid">
                                <?php for ($i = 1; $i <= 4; $i++):
                                    $pic = $report["pic$i"] ?? null;
                                ?>
                                <div style="text-align:center;">
                                    <?php if ($pic && file_exists("uploads/$pic")): ?>
                                    <img src="uploads/<?php echo $pic; ?>" style="width:100%;border:1px solid #ddd;border-radius:6px;margin-bottom:6px;max-height:100px;object-fit:cover;">
                                    <?php else: ?>
                                    <div style="border:1px dashed #ccc;border-radius:6px;padding:20px;margin-bottom:6px;color:#ccc;font-size:11px;">No photo</div>
                                    <?php endif; ?>
                                    <input type="file" name="pic<?php echo $i; ?>" accept="image/*" style="font-size:11px;width:100%;">
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div style="margin-top:15px;padding-top:15px;border-top:2px solid #e8e8e8;">
                            <h4 style="font-size:0.85rem;color:#0072C6;margin-bottom:10px;"><i class="fas fa-id-card"></i> B2B ID Photo</h4>
                            <div style="text-align:center;">
                                <?php $b2b = $report['b2b_id'] ?? null; ?>
                                <?php if ($b2b && file_exists("uploads/$b2b")): ?>
                                <img src="uploads/<?php echo $b2b; ?>" style="max-width:150px;border:1px solid #ddd;border-radius:6px;margin-bottom:6px;">
                                <?php else: ?>
                                <div style="border:1px dashed #ccc;border-radius:6px;padding:20px;display:inline-block;color:#ccc;font-size:12px;margin-bottom:6px;">No B2B ID</div>
                                <?php endif; ?>
                                <div><input type="file" name="b2b_id" accept="image/*" style="font-size:11px;"></div>
                            </div>
                        </div>

                        <div class="sig-row">
                            <div class="sig-approved">
                                <div class="sig-approved-line"></div>
                                <div class="sig-approved-label">Approved by: <?php echo htmlspecialchars($details['captain_name'] ?? 'Punong Barangay'); ?></div>
                                <div class="sig-approved-sub">Punong Barangay</div>
                            </div>
                            <div class="sig-approved">
                                <div class="sig-approved-line"></div>
                                <div class="sig-approved-label">Processed by: <?php echo htmlspecialchars($details['secretary_name'] ?? 'Barangay Secretary'); ?></div>
                                <div class="sig-approved-sub">Barangay Secretary</div>
                            </div>
                        </div>

                        <div style="text-align:center;margin-top:25px;">
                            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.profile-area'))document.getElementById("dropdownMenu").classList.remove("show");}
setTimeout(()=>{const t=document.getElementById('toast');if(t)t.style.display='none';},4000);
</script>
</body>
</html>
