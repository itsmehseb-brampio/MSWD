<?php
session_start();
require 'db.php';

if (!isset($_SESSION['barangay_name'])) {
    header("Location: barangay_login.php");
    exit();
}

$barangay_id = $_SESSION['barangay_id'] ?? 0;
$barangay_name = $_SESSION['barangay_name'] ?? '';

// Fetch current details
$details = [];
if ($barangay_id) {
    $stmt = $conn->prepare("SELECT * FROM barangay_details WHERE barangay_id = ?");
    $stmt->bind_param("i", $barangay_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}

// Fetch current contacts
$contacts = [];
if ($barangay_id) {
    $stmt = $conn->prepare("SELECT * FROM barangay_contacts WHERE barangay_id = ?");
    $stmt->bind_param("i", $barangay_id);
    $stmt->execute();
    $contacts = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}

$muni = $conn->query("SELECT * FROM municipal_contacts LIMIT 1")->fetch_assoc() ?? [];

$detail_labels = [
    'barangay_code' => 'Barangay Code',
    'municipality' => 'Municipality',
    'province' => 'Province',
    'region' => 'Region',
    'zip_code' => 'ZIP Code',
    'captain_name' => 'Punong Barangay / Captain',
    'councilors' => 'Sangguniang Barangay Members',
    'secretary_name' => 'Barangay Secretary',
    'treasurer_name' => 'Barangay Treasurer',
    'contact_info' => 'Contact Information',
    'population' => 'Population',
    'households' => 'Households',
    'head_of_household' => 'Head of Household',
    'population_breakdown' => 'Population Breakdown',
    'boundaries' => 'Boundaries',
    'streets' => 'Streets / Puroks',
    'land_area' => 'Land Area',
    'gps_coordinates' => 'GPS Coordinates',
    'barangay_hall_address' => 'Barangay Hall Address',
    'health_center' => 'Health Center',
    'daycare_schools' => 'Daycare / Schools',
    'community_centers' => 'Community Centers',
    'emergency_services' => 'Emergency Services',
    'date_established' => 'Date Established',
    'website' => 'Website',
    'ordinances' => 'Barangay Ordinances',
    'logo' => 'Barangay Logo',
    'hazard_map' => 'Hazard Map'
];
$contact_labels = [
    'barangay_hall_phone' => 'Barangay Hall / Office',
    'barangay_chairman_phone' => 'Barangay Chairman / Captain',
    'barangay_secretary_phone' => 'Barangay Secretary / Treasurer',
    'barangay_tanod_phone' => 'Barangay Tanods / Security',
    'ngo_relief' => 'NGOs / Relief Services',
    'fire_volunteers' => 'Fire Volunteers / Rescue',
    'covid_hotline' => 'COVID-19 / Health Concerns'
];

$detail_categories = [
    'Identification' => ['barangay_code', 'municipality', 'province', 'region', 'zip_code'],
    'Leadership' => ['captain_name', 'councilors', 'secretary_name', 'treasurer_name', 'contact_info'],
    'Demographics' => ['population', 'households', 'head_of_household', 'population_breakdown'],
    'Location' => ['boundaries', 'streets', 'land_area', 'gps_coordinates'],
    'Facilities' => ['barangay_hall_address', 'health_center', 'daycare_schools', 'community_centers', 'emergency_services'],
    'Other Information' => ['date_established', 'website', 'ordinances']
];

function upsertDetails($conn, $barangay_id, $data) {
    $fields = array_keys($data);
    $check = $conn->prepare("SELECT barangay_id FROM barangay_details WHERE barangay_id = ?");
    $check->bind_param("i", $barangay_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $sets = [];
        foreach ($fields as $f) $sets[] = "`$f` = ?";
        $sql = "UPDATE barangay_details SET " . implode(', ', $sets) . ", last_updated = NOW() WHERE barangay_id = ?";
        $types = str_repeat('s', count($fields)) . 'i';
        $params = array_merge(array_values($data), [$barangay_id]);
    } else {
        $cols = '`' . implode('`,`', $fields) . '`';
        $ph = implode(',', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO barangay_details (barangay_id, $cols, last_updated) VALUES (?, $ph, NOW())";
        $types = 'i' . str_repeat('s', count($fields));
        $params = array_merge([$barangay_id], array_values($data));
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
}

function upsertContacts($conn, $barangay_id, $data) {
    $fields = array_keys($data);
    $check = $conn->prepare("SELECT barangay_id FROM barangay_contacts WHERE barangay_id = ?");
    $check->bind_param("i", $barangay_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $sets = [];
        foreach ($fields as $f) $sets[] = "`$f` = ?";
        $sql = "UPDATE barangay_contacts SET " . implode(', ', $sets) . ", last_updated = NOW() WHERE barangay_id = ?";
        $types = str_repeat('s', count($fields)) . 'i';
        $params = array_merge(array_values($data), [$barangay_id]);
    } else {
        $cols = '`' . implode('`,`', $fields) . '`';
        $ph = implode(',', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO barangay_contacts (barangay_id, $cols, last_updated) VALUES (?, $ph, NOW())";
        $types = 'i' . str_repeat('s', count($fields));
        $params = array_merge([$barangay_id], array_values($data));
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
}

function logFieldChange($conn, $barangay_id, $field, $label, $old, $new) {
    $old = $old === null ? '' : (string)$old;
    $new = $new === null ? '' : (string)$new;
    if ($old === $new) return;
    $stmt = $conn->prepare("INSERT INTO barangay_edit_log (barangay_id, field_name, field_label, old_value, new_value, edited_at) VALUES (?,?,?,?,?, NOW())");
    $stmt->bind_param("issss", $barangay_id, $field, $label, $old, $new);
    $stmt->execute();
    $stmt->close();
}

$mode = ($_GET['mode'] ?? 'view') === 'edit' ? 'edit' : 'view';

$flash = '';
$flash_type = 'ok';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    $flash_type = $_SESSION['flash_type'] ?? 'ok';
    unset($_SESSION['flash'], $_SESSION['flash_type']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['certify'])) {
        $flash = 'Please check the certification box to confirm that all information is correct and up to date.';
        $flash_type = 'err';
        $mode = 'edit';
    } else {
        // --- DETAILS ---
        $detail_fields = array_keys($detail_labels);
        $data = [];
        foreach ($detail_fields as $f) {
            $data[$f] = trim($_POST['data'][$f] ?? '');
        }
        // Numeric columns must be NULL when empty
        foreach (['population', 'households', 'land_area'] as $nf) {
            if ($data[$nf] === '' || !is_numeric($data[$nf])) $data[$nf] = null;
        }
        // Date column must be NULL when empty; convert any accepted format to Y-m-d
        $data['date_established'] = normalizeDate($data['date_established']);

        // File uploads (logo + hazard_map)
        $file_fields = ['logo', 'hazard_map'];
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        foreach ($file_fields as $field) {
            if (isset($_FILES['data']['name'][$field]) && $_FILES['data']['error'][$field] == 0) {
                $filename = time() . '_' . basename($_FILES['data']['name'][$field]);
                $target = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['data']['tmp_name'][$field], $target)) {
                    $data[$field] = $target;
                } else {
                    $data[$field] = $details[$field] ?? '';
                }
            } else {
                $data[$field] = $details[$field] ?? '';
            }
        }

        // --- CONTACTS ---
        $contact_data = [];
        $num_error = '';
        foreach (array_keys($contact_labels) as $f) {
            $num = preg_replace('/\D/', '', $_POST['contacts'][$f] ?? '');
            if ($num !== '' && !preg_match('/^09\d{9}$/', $num)) {
                $num_error = 'All phone numbers must start with 09 and be exactly 11 digits.';
                break;
            }
            $contact_data[$f] = $num;
        }

        if ($num_error !== '') {
            $flash = $num_error;
            $flash_type = 'err';
            $mode = 'edit';
        } else {
            // Log changes BEFORE updating so we capture old values
            foreach ($detail_labels as $f => $label) {
                logFieldChange($conn, $barangay_id, $f, $label, $details[$f] ?? '', $data[$f] ?? '');
            }
            foreach ($contact_labels as $f => $label) {
                logFieldChange($conn, $barangay_id, $f, $label, $contacts[$f] ?? '', $contact_data[$f] ?? '');
            }

            upsertDetails($conn, $barangay_id, $data);
            upsertContacts($conn, $barangay_id, $contact_data);

            $_SESSION['flash'] = 'Barangay information and contacts saved successfully!';
            $_SESSION['flash_type'] = 'ok';
            header("Location: barangay_info.php");
            exit;
        }
    }
}

// Latest "edited on" timestamp per field for the view mode
$field_times = [];
$stmt = $conn->prepare("SELECT field_name, MAX(edited_at) AS t FROM barangay_edit_log WHERE barangay_id = ? GROUP BY field_name");
$stmt->bind_param("i", $barangay_id);
$stmt->execute();
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $field_times[$r['field_name']] = $r['t'];
$stmt->close();

// Recent changes timeline
$recent_log = [];
$stmt = $conn->prepare("SELECT * FROM barangay_edit_log WHERE barangay_id = ? ORDER BY edited_at DESC LIMIT 12");
$stmt->bind_param("i", $barangay_id);
$stmt->execute();
$recent_log = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function fmtVal($v) {
    return ($v === null || $v === '') ? '—' : htmlspecialchars((string)$v);
}
function fmtTime($t) {
    return date('M d, Y h:i A', strtotime($t));
}
function fmtDate($v) {
    if (empty($v) || $v === '0000-00-00') return '—';
    $t = strtotime($v);
    return $t ? date('F d, Y', $t) : htmlspecialchars((string)$v);
}
function normalizeDate($v) {
    $v = trim((string)$v);
    if ($v === '') return null;
    $formats = ['Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'd/m/Y', 'd-m-Y', 'm.d.Y', 'd.m.Y'];
    foreach ($formats as $fmt) {
        $d = DateTime::createFromFormat($fmt, $v);
        if ($d) {
            $errs = DateTime::getLastErrors();
            if ($errs && ($errs['warning_count'] > 0 || $errs['error_count'] > 0)) continue;
            return $d->format('Y-m-d');
        }
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Barangay Info &amp; Contacts</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Roboto','Segoe UI',sans-serif;background:#eef1f6;min-height:100vh;}
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

.page-wrap{max-width:1000px;margin:0 auto;padding:28px 30px;}
.card{background:#fff;border-radius:14px;padding:26px;box-shadow:0 2px 10px rgba(0,0,0,.06);}
.card-title{display:flex;align-items:center;gap:8px;font-size:1.1rem;color:#202124;margin-bottom:4px;}
.card-title i{color:#0072C6;}
.card-sub{font-size:.85rem;color:#5f6368;margin-bottom:6px;}
.page-head{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;}
.updated-line{display:inline-flex;align-items:center;gap:7px;font-size:.78rem;color:#188038;background:#e6f4ea;padding:5px 12px;border-radius:20px;margin:14px 0 0;}
.updated-line i{font-size:.8rem;}
.alert{padding:12px 16px;border-radius:10px;font-size:.88rem;margin-top:16px;border-left:4px solid;}
.alert.ok{background:#e6f4ea;color:#188038;border-color:#188038;}
.alert.err{background:#fce8e6;color:#c5221f;border-color:#c5221f;}
.btn{padding:12px 26px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.92rem;font-weight:600;cursor:pointer;transition:transform .15s, box-shadow .15s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,114,198,.3);}
.btn:disabled{opacity:.6;cursor:not-allowed;}
.btn-outline{background:#fff;border:2px solid #0072C6;color:#0072C6;}
.btn-outline:hover{background:#e8f1fb;box-shadow:none;transform:none;}
.btn-sm{padding:9px 18px;font-size:.85rem;border-radius:9px;}

.section-head{display:flex;align-items:center;gap:8px;font-size:.95rem;font-weight:600;color:#202124;margin:22px 0 8px;padding-bottom:8px;border-bottom:2px solid #f0f0f0;}
.section-head i{color:#0072C6;}

/* ---- View mode ---- */
.v-row{display:flex;gap:14px;padding:11px 2px;border-bottom:1px solid #f5f6f8;align-items:flex-start;}
.v-row:last-child{border-bottom:none;}
.v-label{flex:0 0 225px;font-size:.78rem;font-weight:600;color:#5f6368;text-transform:uppercase;letter-spacing:.3px;padding-top:3px;}
.v-value{flex:1;font-size:.94rem;color:#202124;white-space:pre-line;word-break:break-word;}
.v-time{flex:0 0 auto;display:inline-flex;align-items:center;gap:5px;font-size:.7rem;font-weight:600;padding:3px 10px;border-radius:12px;white-space:nowrap;margin-top:1px;}
.v-time.edited{background:#e6f4ea;color:#188038;}
.v-time.never{background:#f1f3f4;color:#80868b;}
.v-img img{max-width:110px;border-radius:8px;border:1px solid #e0e0e0;display:block;}
.v-img img.hazard{max-width:200px;border-radius:10px;}

/* Contact chips in view mode */
.phone-chips{display:flex;flex-direction:column;gap:8px;}
.phone-chip{display:inline-flex;align-items:center;gap:9px;background:#f8f9fb;border:1px solid #e6eaf0;border-radius:10px;padding:7px 12px;font-size:.88rem;font-weight:700;color:#202124;width:fit-content;max-width:100%;}
.phone-chip .pc-ic{width:26px;height:26px;border-radius:8px;background:#0072C6;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;flex-shrink:0;}
.phone-chip a{color:#0072C6;text-decoration:none;}
.phone-chip a:hover{text-decoration:underline;}

/* Municipal hotlines */
.muni-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.muni-item{display:flex;align-items:center;gap:12px;background:#f8f9fb;border:1px solid #eef1f5;border-radius:12px;padding:11px 14px;}
.muni-item .mi{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;flex-shrink:0;}
.muni-item .ml{font-size:.76rem;color:#5f6368;font-weight:600;text-transform:uppercase;letter-spacing:.3px;}
.muni-item .mv{font-size:.95rem;font-weight:700;color:#202124;margin-top:1px;}
.muni-item .lock{margin-left:auto;color:#9aa0a6;font-size:.8rem;}
.muni-empty{color:#9aa0a6;font-size:.88rem;font-style:italic;}

/* Timeline */
.timeline{margin-top:4px;}
.timeline-item{display:flex;gap:14px;padding:10px 2px;border-bottom:1px solid #f5f5f5;align-items:flex-start;}
.timeline-item:last-child{border-bottom:none;}
.timeline-item .tl-dot{width:10px;height:10px;border-radius:50%;background:#0072C6;margin-top:6px;flex-shrink:0;}
.timeline-item .tl-body{flex:1;font-size:.9rem;color:#202124;}
.timeline-item .tl-time{font-size:.72rem;color:#80868b;margin-top:2px;}
.tl-old{color:#c5221f;text-decoration:line-through;}
.tl-new{color:#188038;font-weight:600;}
.timeline-empty{color:#9aa0a6;font-size:.88rem;font-style:italic;padding:8px 2px;}

/* ---- Edit mode ---- */
.back-link{display:inline-flex;align-items:center;gap:7px;font-size:.85rem;color:#0072C6;text-decoration:none;margin-bottom:14px;}
.back-link:hover{text-decoration:underline;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group label{display:flex;align-items:center;gap:8px;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group label i{width:18px;color:#0072C6;text-align:center;}
.form-group input, .form-group textarea{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;transition:border-color .2s;font-family:inherit;resize:vertical;}
.form-group input:focus, .form-group textarea:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.form-group.full{grid-column:1/-1;}
.file-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.file-box{background:#f8f9fb;border:2px dashed #d5d9e0;border-radius:12px;padding:18px;text-align:center;}
.file-box img{max-width:130px;max-height:130px;object-fit:cover;border-radius:50%;border:3px solid #0072C6;margin-bottom:10px;box-shadow:0 4px 12px rgba(0,0,0,.12);}
.file-box img.hazard{max-width:100%;max-height:180px;border-radius:10px;object-fit:contain;border:2px solid #0072C6;}
.file-box label{display:block;font-weight:600;font-size:.85rem;color:#333;margin-bottom:8px;}
.file-box input[type="file"]{font-size:.82rem;color:#5f6368;}
.cert-box{display:flex;align-items:flex-start;gap:12px;background:#e8f1fb;border:2px solid #b7d7f5;border-radius:12px;padding:16px 18px;margin-top:24px;}
.cert-box input[type="checkbox"]{width:20px;height:20px;margin-top:2px;accent-color:#0072C6;cursor:pointer;flex-shrink:0;}
.cert-box .cert-t{font-size:.9rem;color:#1a3f7a;line-height:1.5;}
.cert-box .cert-t strong{display:block;margin-bottom:3px;}
.cert-box .cert-t small{color:#5f6368;font-size:.78rem;}
@media (max-width:700px){.form-grid,.muni-grid,.file-grid{grid-template-columns:1fr;}.v-label{flex-basis:140px;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'barangay_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-info-circle"></i> Barangay Info &amp; Contacts</h1>
            <div style="position:relative;">
                <div class="admin-profile" onclick="toggleDropdown()">
                    <img src="<?php echo htmlspecialchars($header_logo ?? 'yana.png'); ?>" alt="Logo" onerror="this.src='yana.png'">
                    <span><?php echo htmlspecialchars($barangay_name); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="page-wrap">
            <div class="card">
                <div class="page-head">
                    <div>
                        <div class="card-title"><i class="fas fa-building"></i> <?php echo htmlspecialchars($barangay_name); ?></div>
                        <p class="card-sub">Keep your barangay information and contact numbers correct and up to date.</p>
                    </div>
                    <?php if ($mode === 'view'): ?>
                        <a href="?mode=edit" class="btn btn-sm"><i class="fas fa-edit"></i> Edit Information</a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($details['last_updated']) && $details['last_updated'] !== '0000-00-00 00:00:00'): ?>
                    <span class="updated-line"><i class="fas fa-check-circle"></i> Last updated: <?php echo fmtTime($details['last_updated']); ?></span>
                <?php endif; ?>

                <?php if ($flash != ''): ?>
                    <div class="alert <?php echo $flash_type; ?>">
                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($flash); ?>
                    </div>
                <?php endif; ?>

                <?php if ($mode === 'edit'): ?>

                    <a href="?mode=view" class="back-link"><i class="fas fa-arrow-left"></i> Back to Info View</a>

                    <form method="POST" enctype="multipart/form-data" id="infoForm">

                        <!-- IDENTIFICATION -->
                        <div class="section-head"><i class="fas fa-id-card"></i> Identification</div>
                        <div class="form-grid">
                            <?php $g = ['barangay_code'=>['Barangay Code','fas fa-qrcode'],'municipality'=>['Municipality','fas fa-city'],'province'=>['Province','fas fa-map'],'region'=>['Region','fas fa-globe-asia'],'zip_code'=>['ZIP Code','fas fa-hashtag']];
                            foreach ($g as $k => $m): ?>
                                <div class="form-group">
                                    <label><i class="<?php echo $m[1]; ?>"></i> <?php echo $m[0]; ?></label>
                                    <input type="text" name="data[<?php echo $k; ?>]" value="<?php echo htmlspecialchars($details[$k] ?? ''); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- LEADERSHIP -->
                        <div class="section-head"><i class="fas fa-user-tie"></i> Leadership</div>
                        <div class="form-grid">
                            <?php $g = ['captain_name'=>['Punong Barangay / Captain','fas fa-user-tie'],'secretary_name'=>['Barangay Secretary','fas fa-user'],'treasurer_name'=>['Barangay Treasurer','fas fa-user'],'contact_info'=>['Contact Information','fas fa-phone']];
                            foreach ($g as $k => $m): ?>
                                <div class="form-group">
                                    <label><i class="<?php echo $m[1]; ?>"></i> <?php echo $m[0]; ?></label>
                                    <input type="text" name="data[<?php echo $k; ?>]" value="<?php echo htmlspecialchars($details[$k] ?? ''); ?>">
                                </div>
                            <?php endforeach; ?>
                            <div class="form-group full">
                                <label><i class="fas fa-users"></i> Sangguniang Barangay Members</label>
                                <textarea name="data[councilors]" rows="3"><?php echo htmlspecialchars($details['councilors'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- DEMOGRAPHICS -->
                        <div class="section-head"><i class="fas fa-users"></i> Demographics</div>
                        <div class="form-grid">
                            <?php $g = ['population'=>['Population','fas fa-users'],'households'=>['Households','fas fa-home'],'head_of_household'=>['Head of Household','fas fa-user']];
                            foreach ($g as $k => $m): ?>
                                <div class="form-group">
                                    <label><i class="<?php echo $m[1]; ?>"></i> <?php echo $m[0]; ?></label>
                                    <input type="text" name="data[<?php echo $k; ?>]" value="<?php echo htmlspecialchars($details[$k] ?? ''); ?>">
                                </div>
                            <?php endforeach; ?>
                            <div class="form-group full">
                                <label><i class="fas fa-chart-pie"></i> Population Breakdown</label>
                                <textarea name="data[population_breakdown]" rows="3"><?php echo htmlspecialchars($details['population_breakdown'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- LOCATION -->
                        <div class="section-head"><i class="fas fa-map-marker-alt"></i> Location</div>
                        <div class="form-grid">
                            <?php $g = ['land_area'=>['Land Area','fas fa-vector-square'],'gps_coordinates'=>['GPS Coordinates','fas fa-map-marker-alt']];
                            foreach ($g as $k => $m): ?>
                                <div class="form-group">
                                    <label><i class="<?php echo $m[1]; ?>"></i> <?php echo $m[0]; ?></label>
                                    <input type="text" name="data[<?php echo $k; ?>]" value="<?php echo htmlspecialchars($details[$k] ?? ''); ?>">
                                </div>
                            <?php endforeach; ?>
                            <div class="form-group full">
                                <label><i class="fas fa-border-all"></i> Boundaries</label>
                                <textarea name="data[boundaries]" rows="3"><?php echo htmlspecialchars($details['boundaries'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label><i class="fas fa-road"></i> Streets / Puroks</label>
                                <textarea name="data[streets]" rows="3"><?php echo htmlspecialchars($details['streets'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- FACILITIES -->
                        <div class="section-head"><i class="fas fa-hospital"></i> Facilities</div>
                        <div class="form-grid">
                            <?php $g = ['barangay_hall_address'=>['Barangay Hall Address','fas fa-university'],'health_center'=>['Health Center','fas fa-hospital'],'daycare_schools'=>['Daycare / Schools','fas fa-child'],'community_centers'=>['Community Centers','fas fa-building'],'emergency_services'=>['Emergency Services','fas fa-ambulance']];
                            foreach ($g as $k => $m): ?>
                                <div class="form-group">
                                    <label><i class="<?php echo $m[1]; ?>"></i> <?php echo $m[0]; ?></label>
                                    <input type="text" name="data[<?php echo $k; ?>]" value="<?php echo htmlspecialchars($details[$k] ?? ''); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- OTHER -->
                        <div class="section-head"><i class="fas fa-clipboard-list"></i> Other Information</div>
                        <div class="form-grid">
                            <?php $g = ['date_established'=>['Date Established','fas fa-calendar-alt'],'website'=>['Website','fas fa-globe']];
                            foreach ($g as $k => $m): ?>
                                <div class="form-group">
                                    <label><i class="<?php echo $m[1]; ?>"></i> <?php echo $m[0]; ?></label>
                                    <input type="text" name="data[<?php echo $k; ?>]" value="<?php echo htmlspecialchars($details[$k] ?? ''); ?>" placeholder="<?php echo $k === 'date_established' ? 'e.g. 12-01-1987 or 1987-12-01' : ''; ?>">
                                </div>
                            <?php endforeach; ?>
                            <div class="form-group full">
                                <label><i class="fas fa-gavel"></i> Barangay Ordinances</label>
                                <textarea name="data[ordinances]" rows="3"><?php echo htmlspecialchars($details['ordinances'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- LOGO & HAZARD MAP -->
                        <div class="section-head"><i class="fas fa-images"></i> Logo &amp; Hazard Map</div>
                        <div class="file-grid">
                            <div class="file-box">
                                <label><i class="fas fa-image"></i> Barangay Logo</label>
                                <?php if (!empty($details['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($details['logo']); ?>" alt="Logo">
                                <?php endif; ?>
                                <input type="file" name="data[logo]" accept="image/*">
                            </div>
                            <div class="file-box">
                                <label><i class="fas fa-map-marked-alt"></i> Hazard Map</label>
                                <?php if (!empty($details['hazard_map'])): ?>
                                    <img src="<?php echo htmlspecialchars($details['hazard_map']); ?>" alt="Hazard Map" class="hazard">
                                <?php endif; ?>
                                <input type="file" name="data[hazard_map]" accept="image/*">
                            </div>
                        </div>

                        <!-- CONTACT NUMBERS -->
                        <div class="section-head"><i class="fas fa-phone-alt"></i> Barangay Contact Numbers</div>
                        <div class="form-grid">
                            <?php $g = ['barangay_hall_phone'=>['Barangay Hall / Office','fas fa-university'],'barangay_chairman_phone'=>['Barangay Chairman / Captain','fas fa-user-tie'],'barangay_secretary_phone'=>['Barangay Secretary / Treasurer','fas fa-user'],'barangay_tanod_phone'=>['Barangay Tanods / Security','fas fa-shield-alt'],'ngo_relief'=>['NGOs / Relief Services','fas fa-hands-helping'],'fire_volunteers'=>['Fire Volunteers / Rescue','fas fa-fire'],'covid_hotline'=>['COVID-19 / Health Concerns','fas fa-virus']];
                            foreach ($g as $k => $m): ?>
                                <div class="form-group">
                                    <label><i class="<?php echo $m[1]; ?>"></i> <?php echo $m[0]; ?></label>
                                    <input type="text" name="contacts[<?php echo $k; ?>]" value="<?php echo htmlspecialchars($contacts[$k] ?? ''); ?>" maxlength="11" pattern="09\d{9}" placeholder="09XXXXXXXXX" inputmode="numeric" oninput="cleanNumber(this)">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- MUNICIPAL HOTLINES (READ-ONLY) -->
                        <div class="section-head"><i class="fas fa-city"></i> City / Municipal Hotlines <small style="font-weight:400;color:#9aa0a6;">(Managed by MSWD)</small></div>
                        <div class="muni-grid">
                            <?php $g = ['city_hotline'=>['City/Municipal General Hotline','fas fa-phone','#0072C6'],'drrmo_hotline'=>['DRRMO','fas fa-shield-alt','#7c5cff'],'police_hotline'=>['Police / PNP','fas fa-lock','#0072C6'],'fire_hotline'=>['Fire Department (BFP)','fas fa-fire-extinguisher','#d93025'],'medical_services'=>['Medical / Ambulance','fas fa-ambulance','#00875a'],'hospital_emergency'=>['Hospital Emergency','fas fa-hospital','#d93025'],'traffic_control'=>['Traffic Control','fas fa-traffic-light','#e8710a'],'power_emergency'=>['Power / Electricity','fas fa-bolt','#f9ab00'],'water_emergency'=>['Water / Utilities','fas fa-tint','#0097a7']];
                            $any_muni = false;
                            foreach ($g as $k => $m):
                                $v = $muni[$k] ?? '';
                                if (!empty($v)) $any_muni = true; ?>
                                <div class="muni-item">
                                    <div class="mi" style="background:<?php echo $m[2]; ?>"><i class="<?php echo $m[1]; ?>"></i></div>
                                    <div>
                                        <div class="ml"><?php echo $m[0]; ?></div>
                                        <div class="mv"><?php echo htmlspecialchars($v); ?></div>
                                    </div>
                                    <i class="fas fa-lock lock"></i>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$any_muni): ?>
                                <div class="muni-empty">No municipal hotlines set yet by the administrator.</div>
                            <?php endif; ?>
                        </div>

                        <!-- CERTIFICATION -->
                        <div class="cert-box">
                            <input type="checkbox" name="certify" id="certify" required>
                            <label class="cert-t" for="certify">
                                <strong><i class="fas fa-certificate"></i> Certification of Accuracy</strong>
                                I hereby certify that all information and contact numbers provided above are correct, complete, and up to date to the best of my knowledge as an authorized representative of Barangay <?php echo htmlspecialchars($barangay_name); ?>.
                                <small>You must check this box before saving. Information will be reviewed by the Municipal Social Welfare and Development (MSWD) Office.</small>
                            </label>
                        </div>

                        <button type="submit" class="btn"><i class="fas fa-save"></i> Save Information</button>
                    </form>

                <?php else: ?>

                    <?php foreach ($detail_categories as $cat => $fields): ?>
                        <div class="section-head"><i class="fas fa-folder-open"></i> <?php echo $cat; ?></div>
                        <?php foreach ($fields as $f):
                            $edited = isset($field_times[$f]) ? fmtTime($field_times[$f]) : '';
                            $val = ($f === 'date_established') ? fmtDate($details[$f] ?? '') : fmtVal($details[$f] ?? ''); ?>
                            <div class="v-row">
                                <div class="v-label"><?php echo $detail_labels[$f]; ?></div>
                                <div class="v-value"><?php echo $val; ?></div>
                                <div class="v-time <?php echo $edited ? 'edited' : 'never'; ?>">
                                    <?php if ($edited): ?>
                                        <i class="fas fa-clock"></i> Edited <?php echo $edited; ?>
                                    <?php else: ?>
                                        <i class="fas fa-minus-circle"></i> Not yet edited
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                    <!-- LOGO & HAZARD MAP -->
                    <div class="section-head"><i class="fas fa-images"></i> Media</div>
                    <?php foreach (['logo' => 'Barangay Logo', 'hazard_map' => 'Hazard Map'] as $f => $label):
                        $edited = isset($field_times[$f]) ? fmtTime($field_times[$f]) : ''; ?>
                        <div class="v-row">
                            <div class="v-label"><?php echo $label; ?></div>
                            <div class="v-value v-img">
                                <?php if (!empty($details[$f])): ?>
                                    <img src="<?php echo htmlspecialchars($details[$f]); ?>" alt="<?php echo $label; ?>" class="<?php echo $f === 'hazard_map' ? 'hazard' : ''; ?>">
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                            <div class="v-time <?php echo $edited ? 'edited' : 'never'; ?>">
                                <?php if ($edited): ?>
                                    <i class="fas fa-clock"></i> Edited <?php echo $edited; ?>
                                <?php else: ?>
                                    <i class="fas fa-minus-circle"></i> Not yet edited
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- CONTACT NUMBERS -->
                    <div class="section-head"><i class="fas fa-phone-alt"></i> Barangay Contact Numbers</div>
                    <?php foreach ($contact_labels as $f => $label):
                        $edited = isset($field_times[$f]) ? fmtTime($field_times[$f]) : '';
                        $v = $contacts[$f] ?? ''; ?>
                        <div class="v-row">
                            <div class="v-label"><?php echo $label; ?></div>
                            <div class="v-value">
                                <?php if ($v !== ''): ?>
                                    <div class="phone-chips">
                                        <span class="phone-chip"><span class="pc-ic"><i class="fas fa-phone"></i></span> <a href="tel:<?php echo htmlspecialchars($v); ?>"><?php echo htmlspecialchars($v); ?></a></span>
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                            <div class="v-time <?php echo $edited ? 'edited' : 'never'; ?>">
                                <?php if ($edited): ?>
                                    <i class="fas fa-clock"></i> Edited <?php echo $edited; ?>
                                <?php else: ?>
                                    <i class="fas fa-minus-circle"></i> Not yet edited
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- MUNICIPAL HOTLINES (READ-ONLY) -->
                    <div class="section-head"><i class="fas fa-city"></i> City / Municipal Hotlines <small style="font-weight:400;color:#9aa0a6;">(Managed by MSWD)</small></div>
                    <div class="muni-grid">
                        <?php $g = ['city_hotline'=>['City/Municipal General Hotline','fas fa-phone','#0072C6'],'drrmo_hotline'=>['DRRMO','fas fa-shield-alt','#7c5cff'],'police_hotline'=>['Police / PNP','fas fa-lock','#0072C6'],'fire_hotline'=>['Fire Department (BFP)','fas fa-fire-extinguisher','#d93025'],'medical_services'=>['Medical / Ambulance','fas fa-ambulance','#00875a'],'hospital_emergency'=>['Hospital Emergency','fas fa-hospital','#d93025'],'traffic_control'=>['Traffic Control','fas fa-traffic-light','#e8710a'],'power_emergency'=>['Power / Electricity','fas fa-bolt','#f9ab00'],'water_emergency'=>['Water / Utilities','fas fa-tint','#0097a7']];
                        $any_muni = false;
                        foreach ($g as $k => $m):
                            $v = $muni[$k] ?? '';
                            if (!empty($v)) $any_muni = true; ?>
                            <div class="muni-item">
                                <div class="mi" style="background:<?php echo $m[2]; ?>"><i class="<?php echo $m[1]; ?>"></i></div>
                                <div>
                                    <div class="ml"><?php echo $m[0]; ?></div>
                                    <div class="mv"><?php echo htmlspecialchars($v); ?></div>
                                </div>
                                <i class="fas fa-lock lock"></i>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$any_muni): ?>
                            <div class="muni-empty">No municipal hotlines set yet by the administrator.</div>
                        <?php endif; ?>
                    </div>

                    <!-- RECENT CHANGES -->
                    <div class="section-head"><i class="fas fa-history"></i> Recent Changes</div>
                    <?php if (empty($recent_log)): ?>
                        <div class="timeline-empty">No changes have been made yet. Click "Edit Information" to update your barangay details.</div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recent_log as $log): ?>
                                <div class="timeline-item">
                                    <div class="tl-dot"></div>
                                    <div class="tl-body">
                                        <strong><?php echo htmlspecialchars($log['field_label']); ?></strong>
                                        <?php if ($log['old_value'] !== '' || $log['new_value'] !== ''): ?>
                                            : <span class="tl-old"><?php echo htmlspecialchars(mb_strimwidth($log['old_value'] !== '' ? $log['old_value'] : '(empty)', 0, 40, '…')); ?></span>
                                            &rarr; <span class="tl-new"><?php echo htmlspecialchars(mb_strimwidth($log['new_value'] !== '' ? $log['new_value'] : '(empty)', 0, 40, '…')); ?></span>
                                        <?php endif; ?>
                                        <div class="tl-time"><i class="fas fa-clock"></i> Edited on <?php echo fmtTime($log['edited_at']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown(){ document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e){ if(!e.target.closest('.admin-profile')) document.getElementById("dropdownMenu").classList.remove("show"); }

function cleanNumber(input) {
    input.value = input.value.replace(/\D/g, '').slice(0, 11);
    if (input.value.length >= 2 && input.value.substring(0,2) !== '09') {
        input.value = '09' + input.value.slice(2);
    }
}

var infoForm = document.getElementById('infoForm');
if (infoForm) {
    infoForm.addEventListener('submit', function(e){
        var cert = document.getElementById('certify');
        if (!cert.checked) {
            e.preventDefault();
            cert.scrollIntoView({behavior:'smooth', block:'center'});
            alert('Please check the certification box to confirm that all information is correct and up to date.');
        }
    });
}
</script>
</body>
</html>
