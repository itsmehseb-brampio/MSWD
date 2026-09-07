<?php
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header("Location: barangay_login.php");
    exit();
}
require 'db.php';

$barangay_id = $_SESSION['barangay_id'];
$barangay_name = $_SESSION['barangay_name'] ?? 'Barangay';

$isOpen = $conn->query("SELECT disaster_open FROM barangays WHERE barangay_id=$barangay_id")->fetch_assoc()['disaster_open'] ?? 1;

$words = array_filter(explode(' ', trim($barangay_name)));
$brgyCode = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
$formatPrefix = 'D' . $brgyCode;

$maxFormat = $conn->query("SELECT MAX(report_id) AS mx FROM disaster_reports WHERE barangay_id=$barangay_id")->fetch_assoc()['mx'] ?? 0;
$nextNum = $maxFormat + 1;
$formatNo = $formatPrefix . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

$fields = $conn->query("SELECT * FROM disaster_format_fields WHERE field_name != 'format_no' ORDER BY field_order ASC")->fetch_all(MYSQLI_ASSOC);

$details = $conn->query("SELECT captain_name, secretary_name FROM barangay_details WHERE barangay_id=$barangay_id")->fetch_assoc() ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isOpen) {
    header("Location: barangay_disaster_apply.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = [];
    foreach ($fields as $f) {
        $name = $f['field_name'];
        $data[$name] = trim($_POST[$name] ?? '');
    }

    $uploaded = [];
    foreach ($fields as $f) {
        if ($f['field_type'] !== 'file') continue;
        $name = $f['field_name'];
        if (isset($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                $error = "File for \"$f[field_label]\" must be an image (jpg, png, gif, webp).";
                break;
            }
            $fname = 'report_' . $barangay_id . '_' . $name . '_' . time() . '.' . $ext;
            $dest = 'uploads/' . $fname;
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            if (move_uploaded_file($_FILES[$name]['tmp_name'], $dest)) {
                $uploaded[$name] = $fname;
            } else {
                $error = "Failed to upload file for \"$f[field_label]\".";
                break;
            }
        } else {
            if ($f['is_required']) {
                $error = "Please upload \"$f[field_label]\".";
                break;
            }
        }
    }

    if (empty($error)) {
        $recheck = $conn->query("SELECT MAX(report_id) AS mx FROM disaster_reports WHERE barangay_id=$barangay_id")->fetch_assoc()['mx'] ?? 0;
        $finalNum = $recheck + 1;
        $finalFormatNo = $formatPrefix . '-' . str_pad($finalNum, 3, '0', STR_PAD_LEFT);

        $b2b_uploaded = null;
        if (isset($_FILES['b2b_id1']) && $_FILES['b2b_id1']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['b2b_id1']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $b2b_uploaded = 'b2b_' . $barangay_id . '_' . time() . '.' . $ext;
                if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                move_uploaded_file($_FILES['b2b_id1']['tmp_name'], 'uploads/' . $b2b_uploaded);
            }
        }

        $ti = $data['title'] ?? '';
        $dt = $data['disaster_type'] ?? '';
        $hh = $data['household_head'] ?? '';
        $fm = intval($data['family_members'] ?? 0);
        $fa = $data['full_address'] ?? '';
        $ht = $data['housing_type'] ?? '';
        $de = $data['damage_extent'] ?? '';
        if ($de === 'Partially Damaged') $de = 'Partially';
        if ($de === 'Totally Damaged') $de = 'Totally';
        $desc = $data['description'] ?? '';
        $p1 = $uploaded['pic1'] ?? null;
        $p2 = $uploaded['pic2'] ?? null;
        $p3 = $uploaded['pic3'] ?? null;
        $p4 = $uploaded['pic4'] ?? null;

        $stmt = $conn->prepare("INSERT INTO disaster_reports 
            (barangay_id, format_no, title, disaster_type, household_head, family_members, full_address, housing_type, damage_extent, description, pic1, pic2, pic3, pic4, b2b_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

        $stmt->bind_param("issssisssssssss", $barangay_id, $finalFormatNo, $ti, $dt, $hh, $fm, $fa, $ht, $de, $desc, $p1, $p2, $p3, $p4, $b2b_uploaded);
        $stmt->execute();
        $stmt->close();

        header("Location: barangay_disaster_apply.php?success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Apply - Disaster Report</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;min-height:100vh;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;min-height:100vh;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.content{padding:25px 30px;display:flex;flex-direction:column;align-items:center;}

.header{display:flex;justify-content:space-between;align-items:center;padding:18px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);width:100%;}
.header h1{font-size:1.4rem;}
.profile-area{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.profile-area:hover{background:rgba(255,255,255,0.25);}
.profile-area img{width:36px;height:36px;border-radius:50%;border:2px solid white;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}

.toast{position:fixed;top:20px;right:20px;background:#0072C6;color:white;padding:14px 24px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:9999;display:flex;align-items:center;gap:10px;animation:slideIn 0.3s ease;}
.toast.error{background:#dc3545;}
@keyframes slideIn{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}

.form-card{background:white;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,0.06);overflow:hidden;width:100%;max-width:860px;}
.form-card-header{padding:18px 25px;background:linear-gradient(90deg,#0072C6,#005999);color:white;display:flex;align-items:center;gap:10px;}
.form-card-header h2{font-size:1.1rem;}
.form-card-body{padding:25px 30px;}

.format-no-bar{display:flex;align-items:center;gap:10px;background:#f0fff4;border:2px solid #0072C6;border-radius:10px;padding:12px 18px;margin-bottom:20px;}
.format-no-bar i{font-size:1.2rem;color:#0072C6;}
.format-no-bar span{font-weight:700;color:#0072C6;font-size:1.1rem;letter-spacing:1px;}

.fg{margin-bottom:15px;}
.fg label{display:block;font-weight:600;color:#444;margin-bottom:5px;font-size:0.88rem;}
.fg label .req{color:#dc3545;margin-left:2px;}
.fg input[type="text"],
.fg input[type="number"],
.fg input[type="date"],
.fg select,
.fg textarea{width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.9rem;font-family:'Segoe UI',sans-serif;transition:border-color 0.3s;}
.fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:#0072C6;}
.fg textarea{resize:vertical;min-height:80px;}

.radio-group{display:flex;gap:10px;flex-wrap:wrap;}
.radio-option{display:flex;align-items:center;gap:6px;padding:9px 16px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;font-size:0.88rem;color:#555;transition:all 0.2s;}
.radio-option:hover{border-color:#0072C6;background:#f0fff4;}
.radio-option input{accent-color:#0072C6;}

.upload-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;}
@media(max-width:700px){.upload-grid{grid-template-columns:1fr 1fr;}}
.upload-box{border:2px dashed #ccc;border-radius:10px;padding:18px 10px;text-align:center;cursor:pointer;transition:all 0.2s;position:relative;}
.upload-box:hover{border-color:#0072C6;background:#f0fff4;}
.upload-box.has-file{border-color:#0072C6;background:#f0fff4;}
.upload-box i{font-size:1.5rem;color:#ccc;display:block;margin-bottom:5px;}
.upload-box.has-file i{color:#0072C6;}
.upload-box span{font-size:0.72rem;color:#aaa;display:block;}
.upload-box.has-file span{color:#0072C6;}
.upload-box input[type="file"]{position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;}
.upload-preview{width:100%;height:60px;object-fit:cover;border-radius:6px;margin-bottom:5px;}

/* B2B ID & Signature Section */
.b2b-section{margin-top:25px;padding-top:20px;border-top:2px solid #e8e8e8;}
.b2b-section-title{font-size:0.72rem;text-transform:uppercase;font-weight:700;color:#0072C6;letter-spacing:0.5px;margin-bottom:15px;display:flex;align-items:center;gap:6px;}

.b2b-id-photos{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:20px;}
@media(max-width:600px){.b2b-id-photos{grid-template-columns:repeat(3,1fr);}}
.b2b-single{max-width:200px;margin:0 auto 20px;}
.b2b-id-item{text-align:center;}
.b2b-id-box{border:2px dashed #ccc;border-radius:12px;padding:15px;text-align:center;cursor:pointer;transition:all 0.2s;position:relative;aspect-ratio:3/4;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.b2b-id-box:hover{border-color:#0072C6;background:#f0fff4;}
.b2b-id-box.has-file{border-style:solid;border-color:#0072C6;}
.b2b-id-box i{font-size:1.8rem;color:#ccc;margin-bottom:6px;}
.b2b-id-box span{font-size:0.72rem;color:#aaa;}
.b2b-id-box input[type="file"]{position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;}
.b2b-id-box img{width:100%;height:100%;object-fit:cover;border-radius:8px;}

.sig-approved{text-align:center;padding:10px 0 5px;}
.sig-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
@media(max-width:600px){.sig-row{grid-template-columns:1fr;}}
.sig-approved-line{width:220px;height:2px;background:#333;margin:0 auto 8px;}
.sig-approved-label{font-size:0.8rem;font-weight:700;color:#444;}
.sig-approved-sub{font-size:0.68rem;color:#888;margin-top:3px;}

.btn-submit{padding:12px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all 0.2s;margin-top:10px;}
.btn-submit:hover{background:linear-gradient(90deg,#218838,#1ba886);transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,114,198,0.3);}

.empty-msg{text-align:center;padding:30px;color:#999;}
.empty-msg i{font-size:2rem;margin-bottom:8px;display:block;}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'barangay_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Apply - Disaster Report</h1>
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
            <?php if (!$isOpen): ?>
            <div class="form-card">
                <div class="form-card-header" style="background:linear-gradient(90deg,#dc3545,#c82333);">
                    <i class="fas fa-lock"></i>
                    <h2>Submissions Currently Closed</h2>
                </div>
                <div class="form-card-body">
                    <div style="text-align:center;padding:30px 20px;">
                        <i class="fas fa-ban" style="font-size:3rem;color:#dc3545;margin-bottom:15px;display:block;"></i>
                        <h3 style="color:#333;margin-bottom:10px;">Disaster Report Submission is Closed</h3>
                        <p style="color:#777;font-size:0.9rem;max-width:400px;margin:0 auto;">The administrator has temporarily closed disaster report submissions for <strong><?php echo htmlspecialchars($barangay_name); ?></strong>. Please check back later or contact the admin for more information.</p>
                    </div>
                </div>
            </div>
            <?php elseif (isset($_GET['success'])): ?>
            <div class="toast" id="toast"><i class="fas fa-check-circle"></i> Disaster report submitted successfully!</div>
            <?php elseif (!empty($error)): ?>
            <div class="toast error" id="toast"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (empty($fields)): ?>
            <div class="form-card">
                <div class="form-card-header"><i class="fas fa-exclamation-triangle"></i> <h2>No Format Available</h2></div>
                <div class="form-card-body">
                    <div class="empty-msg"><i class="fas fa-inbox"></i><p>The disaster report format has not been configured yet. Please contact the administrator.</p></div>
                </div>
            </div>
            <?php else: ?>
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fas fa-file-invoice"></i>
                    <h2>Disaster Report Form</h2>
                    <span style="margin-left:auto;font-size:0.78rem;opacity:0.8;">Municipality of Malilipot - DSWD</span>
                </div>
                <div class="form-card-body">
                    <div class="format-no-bar">
                        <i class="fas fa-hashtag"></i>
                        <div>
                            <div style="font-size:0.7rem;color:#666;">Format No.</div>
                            <span><?php echo htmlspecialchars($formatNo); ?></span>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="reportForm" onsubmit="return validateForm()">
                        <?php foreach ($fields as $idx => $f):
                            $name = $f['field_name'];
                            $opts = !empty($f['field_options']) ? explode("\n", $f['field_options']) : [];
                            $req = $f['is_required'] ? '<span class="req">*</span>' : '';
                            $reqAttr = $f['is_required'] ? 'required' : '';
                        ?>
                        <?php if ($f['field_type'] === 'radio'): ?>
                            <div class="fg">
                                <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                <div class="radio-group">
                                    <?php foreach ($opts as $o): ?>
                                    <label class="radio-option">
                                        <input type="radio" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars(trim($o)); ?>" <?php echo $reqAttr; ?>>
                                        <?php echo htmlspecialchars(trim($o)); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php elseif ($f['field_type'] === 'textarea'): ?>
                            <div class="fg">
                                <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                <textarea name="<?php echo $name; ?>" placeholder="Enter <?php echo htmlspecialchars($f['field_label']); ?>..." <?php echo $reqAttr; ?>></textarea>
                            </div>
                        <?php elseif ($f['field_type'] === 'select'): ?>
                            <div class="fg">
                                <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                <select name="<?php echo $name; ?>" <?php echo $reqAttr; ?>>
                                    <option value="">-- Select <?php echo htmlspecialchars($f['field_label']); ?> --</option>
                                    <?php foreach ($opts as $o): ?>
                                    <option value="<?php echo htmlspecialchars(trim($o)); ?>"><?php echo htmlspecialchars(trim($o)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php elseif ($f['field_type'] === 'file'): ?>
                            <div class="fg">
                                <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                <div class="upload-box" id="preview_<?php echo $name; ?>">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to upload <?php echo htmlspecialchars($f['field_label']); ?></span>
                                    <input type="file" name="<?php echo $name; ?>" accept="image/*" <?php echo $reqAttr; ?> onchange="previewImg(this, '<?php echo $name; ?>')">
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="fg">
                                <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                <input type="<?php echo $f['field_type']; ?>" name="<?php echo $name; ?>" placeholder="Enter <?php echo htmlspecialchars($f['field_label']); ?>..." <?php echo $reqAttr; ?>>
                            </div>
                        <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- B2B ID & Signatures -->
                        <div class="b2b-section">
                            <div class="b2b-section-title"><i class="fas fa-id-card"></i> B2B ID</div>
                            <div class="b2b-single">
                                <div class="b2b-id-box" id="preview_b2b_id1">
                                    <i class="fas fa-camera"></i>
                                    <span>Upload ID Photo</span>
                                    <input type="file" name="b2b_id1" accept="image/*" onchange="previewB2B(this,'b2b_id1')">
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
                        </div>

                        <div style="text-align:center;margin-top:25px;">
                            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Report</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.profile-area'))document.getElementById("dropdownMenu").classList.remove("show");}
setTimeout(()=>{const t=document.getElementById('toast');if(t)t.style.display='none';},4000);

function previewImg(input, name){
    const box = document.getElementById('preview_' + name);
    const existing = box.querySelector('.upload-preview');
    if(existing) existing.remove();
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = function(e){
            const img = document.createElement('img');
            img.className = 'upload-preview';
            img.src = e.target.result;
            box.prepend(img);
            box.classList.add('has-file');
            box.querySelector('i').style.display = 'none';
            box.querySelector('span').textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewB2B(input, id){
    const box = document.getElementById('preview_' + id);
    const existing = box.querySelector('img');
    if(existing) existing.remove();
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = function(e){
            const img = document.createElement('img');
            img.src = e.target.result;
            box.prepend(img);
            box.classList.add('has-file');
            box.querySelector('i').style.display = 'none';
            box.querySelector('span').textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function validateForm(){
    const required = document.querySelectorAll('[required]');
    for(let i = 0; i < required.length; i++){
        const el = required[i];
        if(el.type === 'radio'){
            const checked = document.querySelectorAll('input[name="'+el.name+'"]:checked');
            if(checked.length === 0){
                alert('Please select ' + el.closest('.fg').querySelector('label').textContent.replace('*','').trim());
                return false;
            }
        }
    }
    return true;
}
</script>
</body>
</html>
