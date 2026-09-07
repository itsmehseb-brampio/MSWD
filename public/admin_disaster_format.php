<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_field') {
        $label = trim($_POST['field_label']);
        $type = $_POST['field_type'];
        $required = isset($_POST['is_required']) ? 1 : 0;
        $options = trim($_POST['field_options'] ?? '');
        $maxOrder = $conn->query("SELECT COALESCE(MAX(field_order),0)+1 AS mx FROM disaster_format_fields")->fetch_assoc()['mx'];
        $name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $label));
        $name = trim($name, '_');

        $stmt = $conn->prepare("INSERT INTO disaster_format_fields (field_label, field_name, field_type, field_options, is_required, field_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $label, $name, $type, $options, $required, $maxOrder);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_disaster_format.php?added=1");
        exit();
    }

    if ($_POST['action'] === 'update_field') {
        $id = intval($_POST['field_id']);
        $label = trim($_POST['field_label']);
        $type = $_POST['field_type'];
        $required = isset($_POST['is_required']) ? 1 : 0;
        $options = trim($_POST['field_options'] ?? '');
        $name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $label));
        $name = trim($name, '_');

        $stmt = $conn->prepare("UPDATE disaster_format_fields SET field_label=?, field_name=?, field_type=?, field_options=?, is_required=? WHERE id=?");
        $stmt->bind_param("ssssii", $label, $name, $type, $options, $required, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_disaster_format.php?updated=1");
        exit();
    }

    if ($_POST['action'] === 'delete_field') {
        $id = intval($_POST['field_id']);
        $conn->query("DELETE FROM disaster_format_fields WHERE id = $id");
        $rows = $conn->query("SELECT id FROM disaster_format_fields ORDER BY field_order");
        $i = 1;
        while ($r = $rows->fetch_assoc()) {
            $conn->query("UPDATE disaster_format_fields SET field_order = $i WHERE id = {$r['id']}");
            $i++;
        }
        header("Location: admin_disaster_format.php?deleted=1");
        exit();
    }

    if ($_POST['action'] === 'reorder') {
        $order = $_POST['field_order'] ?? [];
        foreach ($order as $idx => $fid) {
            $conn->query("UPDATE disaster_format_fields SET field_order = " . intval($idx) . " WHERE id = " . intval($fid));
        }
        header("Location: admin_disaster_format.php?reordered=1");
        exit();
    }

    if ($_POST['action'] === 'toggle_disaster') {
        $bid = intval($_POST['barangay_id']);
        $val = intval($_POST['disaster_open']);
        $conn->query("UPDATE barangays SET disaster_open=$val WHERE barangay_id=$bid");
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'disaster_open' => $val]);
        exit();
    }
}

$fields = $conn->query("SELECT * FROM disaster_format_fields ORDER BY field_order ASC")->fetch_all(MYSQLI_ASSOC);
$brgyList = $conn->query("SELECT barangay_id, barangay_name, disaster_open FROM barangays ORDER BY barangay_name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Disaster Report Format</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;min-height:100vh;}
.wrapper{display:flex;min-height:100vh;}
.main-content{flex:1;transition:margin-left 0.3s;}
.sidebar:not(.hide)+.main-content{margin-left:260px;}
.header{display:flex;justify-content:space-between;align-items:center;padding:16px 30px;background:linear-gradient(90deg,#0072C6,#005999);color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.header h1{font-size:1.4rem;}
.admin-profile{display:flex;align-items:center;gap:12px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.1);transition:background 0.3s;}
.admin-profile:hover{background:rgba(255,255,255,0.2);}
.admin-profile img{width:40px;height:40px;border-radius:50%;border:2px solid white;}
.dropdown{display:none;position:absolute;right:30px;top:70px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}
.content{padding:20px;}

.toast{position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:14px 24px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:9999;display:flex;align-items:center;gap:10px;animation:slideIn 0.3s ease;}
@keyframes slideIn{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}

/* ============ TWO PANEL LAYOUT ============ */
.two-panel{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;}
@media(max-width:1100px){.two-panel{grid-template-columns:1fr;}}

.panel{background:white;border-radius:14px;box-shadow:0 4px 12px rgba(0,0,0,0.07);overflow:hidden;}
.panel-header{padding:14px 20px;color:white;font-weight:700;font-size:1rem;display:flex;align-items:center;gap:8px;}
.panel-header.builder{background:linear-gradient(90deg,#0072C6,#005999);}
.panel-header.preview{background:linear-gradient(90deg,#28a745,#218838);}
.panel-body{padding:20px;max-height:calc(100vh - 160px);overflow-y:auto;}

/* ============ LEFT: BUILDER ============ */
.add-form{background:#f8fbff;border:2px solid #e0e0e0;border-radius:10px;padding:16px;margin-bottom:18px;}
.add-form h4{font-size:0.85rem;color:#333;margin-bottom:12px;display:flex;align-items:center;gap:6px;}
.builder-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.fg{margin-bottom:0;}
.fg label{display:block;font-weight:600;color:#555;margin-bottom:3px;font-size:0.75rem;}
.fg input,.fg select,.fg textarea{width:100%;padding:8px 10px;border:2px solid #e0e0e0;border-radius:6px;font-size:0.82rem;font-family:'Segoe UI',sans-serif;transition:border-color 0.3s;}
.fg input:focus,.fg select:focus{outline:none;border-color:#0072C6;}
.fg-full{grid-column:1/-1;}
.fg-check{display:flex;align-items:center;gap:5px;margin-top:6px;}
.fg-check input{accent-color:#0072C6;}
.fg-check label{font-size:0.75rem;color:#555;margin:0;}
.btn-add{padding:8px 18px;background:#28a745;color:white;border:none;border-radius:6px;font-size:0.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all 0.2s;margin-top:10px;}
.btn-add:hover{background:#218838;}

/* Field list */
.fields-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.fields-header h4{font-size:0.9rem;color:#333;}
.fields-count{background:#0072C6;color:white;padding:2px 10px;border-radius:20px;font-size:0.72rem;font-weight:600;}
.field-list{list-style:none;padding:0;}
.field-item{display:flex;align-items:center;gap:10px;padding:10px 12px;background:white;border:1.5px solid #e8e8e8;border-radius:8px;margin-bottom:6px;transition:all 0.2s;}
.field-item:hover{border-color:#0072C6;box-shadow:0 1px 5px rgba(0,0,0,0.05);}
.field-num{width:24px;height:24px;border-radius:50%;background:#0072C6;color:white;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;flex-shrink:0;}
.field-info{flex:1;min-width:0;}
.field-info .fl{font-weight:600;color:#333;font-size:0.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.field-info .fm{font-size:0.68rem;color:#999;margin-top:1px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.field-type-badge{display:inline-block;padding:1px 6px;border-radius:3px;font-size:0.58rem;font-weight:700;text-transform:uppercase;}
.type-text{background:#e3f2fd;color:#1565c0;}
.type-number{background:#f3e5f5;color:#7b1fa2;}
.type-textarea{background:#e8f5e9;color:#2e7d32;}
.type-select{background:#fff3e0;color:#e65100;}
.type-radio{background:#fce4ec;color:#c62828;}
.type-file{background:#e0f7fa;color:#00695c;}
.type-date{background:#f5f5f5;color:#616161;}
.req-badge{display:inline-block;padding:1px 5px;border-radius:3px;font-size:0.58rem;font-weight:700;background:#dc3545;color:white;}
.opt-badge{display:inline-block;padding:1px 5px;border-radius:3px;font-size:0.58rem;font-weight:700;background:#6c757d;color:white;}
.field-actions{display:flex;gap:4px;flex-shrink:0;}
.btn-icon{width:28px;height:28px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;font-size:0.75rem;}
.btn-edit{background:#e3f2fd;color:#1565c0;}
.btn-edit:hover{background:#bbdefb;}
.btn-delete{background:#fce4ec;color:#c62828;}
.btn-delete:hover{background:#f8bbd0;}
.btn-move{background:#f5f5f5;color:#666;}
.btn-move:hover{background:#e0e0e0;}

.empty-state{text-align:center;padding:30px;color:#999;}
.empty-state i{font-size:2rem;margin-bottom:8px;display:block;}

/* ============ RIGHT: PREVIEW ============ */
.preview-note{text-align:center;padding:8px 14px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;font-size:0.78rem;color:#856404;margin-bottom:15px;display:flex;align-items:center;justify-content:center;gap:6px;}

.preview-form{pointer-events:none;opacity:1;}
.pf-section{margin-bottom:16px;}
.pf-section-title{font-size:0.72rem;text-transform:uppercase;font-weight:700;color:#0072C6;letter-spacing:0.5px;margin-bottom:10px;padding-bottom:5px;border-bottom:1px solid #e8e8e8;}
.pf-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.pf-row.three{grid-template-columns:1fr 1fr 1fr;}
.pf-group{margin-bottom:12px;}
.pf-group label{display:block;font-weight:600;color:#444;margin-bottom:4px;font-size:0.82rem;}
.pf-group label .req{color:#dc3545;margin-left:2px;}
.pf-group input,.pf-group select,.pf-group textarea{width:100%;padding:9px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.85rem;font-family:'Segoe UI',sans-serif;background:#f8f9fa;color:#999;}
.pf-group textarea{resize:none;min-height:60px;}

.pf-radio-group{display:flex;gap:8px;flex-wrap:wrap;}
.pf-radio{display:flex;align-items:center;gap:5px;padding:7px 14px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.8rem;color:#666;background:#f8f9fa;}
.pf-radio input{accent-color:#0072C6;}

.pf-upload-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.pf-upload-box{border:2px dashed #ccc;border-radius:8px;padding:15px 8px;text-align:center;background:#f8f9fa;}
.pf-upload-box i{font-size:1.3rem;color:#ccc;}
.pf-upload-box span{display:block;font-size:0.68rem;color:#aaa;margin-top:3px;}

@media(max-width:1100px){.pf-row,.pf-row.three,.pf-upload-grid{grid-template-columns:1fr;}}

/* Edit modal */
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;}
.modal-overlay.active{display:flex;}
.modal{background:white;border-radius:14px;width:100%;max-width:480px;box-shadow:0 10px 40px rgba(0,0,0,0.3);animation:modalIn 0.3s ease;}
@keyframes modalIn{from{transform:scale(0.9);opacity:0;}to{transform:scale(1);opacity:1;}}
.modal-header{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid #e8e8e8;}
.modal-header h3{font-size:0.95rem;color:#333;}
.modal-close{background:none;border:none;font-size:1.2rem;cursor:pointer;color:#999;padding:4px 8px;border-radius:6px;}
.modal-close:hover{background:#f0f0f0;color:#333;}
.modal-body{padding:18px;}
.modal-body .fg{margin-bottom:10px;}
.modal-footer{display:flex;justify-content:flex-end;gap:8px;padding:12px 20px;border-top:1px solid #e8e8e8;}
.btn-cancel{padding:7px 16px;background:#f8f9fa;color:#666;border:2px solid #ddd;border-radius:6px;font-size:0.82rem;font-weight:600;cursor:pointer;}
.btn-cancel:hover{background:#e9ecef;}
.btn-save{padding:7px 16px;background:#0072C6;color:white;border:none;border-radius:6px;font-size:0.82rem;font-weight:600;cursor:pointer;}
.btn-save:hover{background:#005999;}

.access-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px;}
.access-card{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-radius:10px;border:2px solid #e8e8e8;transition:all 0.2s;}
.access-card.open{border-color:#00D09C;background:#f0fdf4;}
.access-card.closed{border-color:#dc3545;background:#fff5f5;}
.access-name{font-weight:600;font-size:0.9rem;color:#333;display:flex;align-items:center;gap:6px;}
.access-status{font-size:0.75rem;font-weight:700;margin-top:2px;}
.access-card.open .access-status{color:#00D09C;}
.access-card.closed .access-status{color:#dc3545;}

.toggle-switch{position:relative;width:44px;height:24px;flex-shrink:0;}
.toggle-switch input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;border-radius:24px;transition:0.3s;}
.toggle-slider::before{content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:0.3s;}
.toggle-switch input:checked + .toggle-slider{background:#00D09C;}
.toggle-switch input:checked + .toggle-slider::before{transform:translateX(20px);}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Disaster Report Format</h1>
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
        <div class="content">
            <?php if (isset($_GET['added'])): ?>
            <div class="toast" id="toast"><i class="fas fa-check-circle"></i> Field added!</div>
            <?php elseif (isset($_GET['updated'])): ?>
            <div class="toast" id="toast"><i class="fas fa-check-circle"></i> Field updated!</div>
            <?php elseif (isset($_GET['deleted'])): ?>
            <div class="toast" id="toast"><i class="fas fa-check-circle"></i> Field deleted!</div>
            <?php endif; ?>

            <div class="two-panel">

                <!-- LEFT: BUILDER -->
                <div class="panel">
                    <div class="panel-header builder"><i class="fas fa-tools"></i> Format Builder</div>
                    <div class="panel-body">
                        <!-- Add Field -->
                        <div class="add-form">
                            <h4><i class="fas fa-plus-circle" style="color:#28a745;"></i> Add New Field</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_field">
                                <div class="builder-row">
                                    <div class="fg">
                                        <label>Field Label</label>
                                        <input type="text" name="field_label" placeholder="e.g. Name of Household Head" required>
                                    </div>
                                    <div class="fg">
                                        <label>Field Type</label>
                                        <select name="field_type" id="newType" onchange="toggleNewOpts()">
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="textarea">Text Area</option>
                                            <option value="select">Dropdown</option>
                                            <option value="radio">Radio Buttons</option>
                                            <option value="file">File/Image</option>
                                            <option value="date">Date</option>
                                        </select>
                                    </div>
                                    <div class="fg fg-full" id="newOptsRow" style="display:none;">
                                        <label>Options (one per line)</label>
                                        <textarea name="field_options" rows="2" placeholder="Option 1&#10;Option 2"></textarea>
                                    </div>
                                    <div class="fg">
                                        <div class="fg-check">
                                            <input type="checkbox" name="is_required" checked id="newReq">
                                            <label for="newReq">Required</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-add"><i class="fas fa-plus"></i> Add</button>
                                </div>
                            </form>
                        </div>

                        <!-- Field List -->
                        <div class="fields-header">
                            <h4><i class="fas fa-list-ol" style="color:#0072C6;"></i> Fields</h4>
                            <span class="fields-count"><?php echo count($fields); ?></span>
                        </div>

                        <?php if (empty($fields)): ?>
                        <div class="empty-state"><i class="fas fa-inbox"></i><p>No fields yet.</p></div>
                        <?php else: ?>
                        <ul class="field-list">
                            <?php foreach ($fields as $idx => $f): ?>
                            <li class="field-item" data-id="<?php echo $f['id']; ?>">
                                <span class="field-num"><?php echo $idx + 1; ?></span>
                                <div class="field-info">
                                    <div class="fl"><?php echo htmlspecialchars($f['field_label']); ?></div>
                                    <div class="fm">
                                        <span class="field-type-badge type-<?php echo $f['field_type']; ?>"><?php echo $f['field_type']; ?></span>
                                        <?php if ($f['is_required']): ?><span class="req-badge">Required</span><?php else: ?><span class="opt-badge">Optional</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="field-actions">
                                    <button class="btn-icon btn-move" onclick="moveField(<?php echo $f['id']; ?>,'up')" title="Up"><i class="fas fa-arrow-up"></i></button>
                                    <button class="btn-icon btn-move" onclick="moveField(<?php echo $f['id']; ?>,'down')" title="Down"><i class="fas fa-arrow-down"></i></button>
                                    <button class="btn-icon btn-edit" onclick="editField(<?php echo $f['id']; ?>,<?php echo htmlspecialchars(json_encode($f), ENT_QUOTES); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                                        <input type="hidden" name="action" value="delete_field">
                                        <input type="hidden" name="field_id" value="<?php echo $f['id']; ?>">
                                        <button class="btn-icon btn-delete" type="submit" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT: PREVIEW -->
                <div class="panel">
                    <div class="panel-header preview"><i class="fas fa-eye"></i> Format Preview</div>
                    <div class="panel-body">
                        <div class="preview-note"><i class="fas fa-info-circle"></i> This is a preview. The form is read-only and cannot be submitted here.</div>

                        <?php if (empty($fields)): ?>
                        <div class="empty-state"><i class="fas fa-inbox"></i><p>Add fields on the left to see the preview.</p></div>
                        <?php else: ?>
                        <div style="background:white;border:2px solid #e0e0e0;border-radius:12px;padding:20px;">
                            <!-- Format header -->
                            <div style="background:linear-gradient(135deg,#0072C6,#005999);color:white;border-radius:10px;padding:16px 20px;margin-bottom:20px;text-align:center;">
                                <h3 style="font-size:1.1rem;margin-bottom:4px;"><i class="fas fa-file-invoice"></i> Disaster Report Form</h3>
                                <p style="font-size:0.78rem;opacity:0.8;">Municipality of Malilipot - DSWD</p>
                            </div>

                            <form class="preview-form" onsubmit="return false;">
                                <?php
                                $hasRadio = false;
                                foreach ($fields as $f):
                                    $opts = !empty($f['field_options']) ? explode("\n", $f['field_options']) : [];
                                    $req = $f['is_required'] ? '<span class="req">*</span>' : '';
                                    $ph = $f['field_type'] === 'file' ? '' : htmlspecialchars($f['field_label']);
                                ?>
                                <?php if ($f['field_type'] === 'radio'): ?>
                                    <div class="pf-group">
                                        <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                        <div class="pf-radio-group">
                                            <?php foreach ($opts as $o): ?>
                                            <label class="pf-radio"><input type="radio" name="<?php echo $f['field_name']; ?>" disabled> <?php echo htmlspecialchars(trim($o)); ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php elseif ($f['field_type'] === 'textarea'): ?>
                                    <div class="pf-group">
                                        <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                        <textarea placeholder="<?php echo htmlspecialchars($f['field_label']); ?>" disabled></textarea>
                                    </div>
                                <?php elseif ($f['field_type'] === 'select'): ?>
                                    <div class="pf-group">
                                        <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                        <select disabled>
                                            <option>-- Select <?php echo htmlspecialchars($f['field_label']); ?> --</option>
                                            <?php foreach ($opts as $o): ?>
                                            <option><?php echo htmlspecialchars(trim($o)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php elseif ($f['field_type'] === 'file'): ?>
                                    <div class="pf-group">
                                        <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                        <div style="border:2px dashed #ccc;border-radius:8px;padding:12px;text-align:center;background:#f8f9fa;">
                                            <i class="fas fa-cloud-upload-alt" style="font-size:1.2rem;color:#ccc;"></i>
                                            <p style="font-size:0.72rem;color:#aaa;margin-top:4px;">Click to upload</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="pf-group">
                                        <label><?php echo htmlspecialchars($f['field_label']); ?> <?php echo $req; ?></label>
                                        <input type="<?php echo $f['field_type']; ?>" placeholder="<?php echo $ph; ?>" disabled>
                                    </div>
                                <?php endif; ?>
                                <?php endforeach; ?>

                                <div style="text-align:center;margin-top:20px;">
                                    <button type="submit" disabled style="padding:10px 30px;background:#ccc;color:white;border:none;border-radius:8px;font-size:0.9rem;font-weight:600;cursor:not-allowed;"><i class="fas fa-save"></i> Submit Report</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Barangay Access Panel -->
        <div style="padding:0 30px 30px;">
            <div class="panel">
                <div class="panel-header" style="background:linear-gradient(90deg,#6f42c1,#5a32a3);"><i class="fas fa-door-open"></i> Barangay Submission Access</div>
                <div class="panel-body">
                    <p style="font-size:0.82rem;color:#666;margin-bottom:14px;">Toggle which barangays can submit disaster reports. <strong>Open</strong> = can apply, <strong>Closed</strong> = cannot apply.</p>
                    <div class="access-grid">
                        <?php foreach ($brgyList as $b): ?>
                        <div class="access-card <?php echo $b['disaster_open'] ? 'open' : 'closed'; ?>" id="brgy_<?php echo $b['barangay_id']; ?>">
                            <div class="access-info">
                                <div class="access-name"><i class="fas fa-building"></i> <?php echo htmlspecialchars($b['barangay_name']); ?></div>
                                <div class="access-status"><?php echo $b['disaster_open'] ? 'Open' : 'Closed'; ?></div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" <?php echo $b['disaster_open'] ? 'checked' : ''; ?> onchange="toggleAccess(<?php echo $b['barangay_id']; ?>, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal" onclick="closeModalOutside(event)">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fas fa-edit" style="color:#0072C6;"></i> Edit Field</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_field">
            <input type="hidden" name="field_id" id="editId">
            <div class="modal-body">
                <div class="fg"><label>Field Label</label><input type="text" name="field_label" id="editLabel" required></div>
                <div class="fg"><label>Field Type</label>
                    <select name="field_type" id="editType" onchange="toggleEditOpts()">
                        <option value="text">Text</option><option value="number">Number</option><option value="textarea">Text Area</option>
                        <option value="select">Dropdown</option><option value="radio">Radio Buttons</option>
                        <option value="file">File/Image</option><option value="date">Date</option>
                    </select>
                </div>
                <div class="fg" id="editOptsRow"><label>Options (one per line)</label><textarea name="field_options" rows="3" id="editOptions" placeholder="Option 1&#10;Option 2"></textarea></div>
                <div class="fg"><div class="fg-check"><input type="checkbox" name="is_required" id="editRequired" checked><label for="editRequired">Required</label></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDropdown(){document.getElementById("dropdownMenu").classList.toggle("show");}
window.onclick=function(e){if(!e.target.closest('.admin-profile'))document.getElementById("dropdownMenu").classList.remove("show");}
setTimeout(()=>{const t=document.getElementById('toast');if(t)t.style.display='none';},3000);

function toggleNewOpts(){document.getElementById('newOptsRow').style.display=document.getElementById('newType').value==='select'||document.getElementById('newType').value==='radio'?'block':'none';}
function toggleEditOpts(){const t=document.getElementById('editType').value;document.getElementById('editOptsRow').style.display=t==='select'||t==='radio'?'block':'none';}

function editField(id,d){
    document.getElementById('editId').value=id;
    document.getElementById('editLabel').value=d.field_label;
    document.getElementById('editType').value=d.field_type;
    document.getElementById('editOptions').value=d.field_options||'';
    document.getElementById('editRequired').checked=!!parseInt(d.is_required);
    toggleEditOpts();
    document.getElementById('editModal').classList.add('active');
}
function closeModal(){document.getElementById('editModal').classList.remove('active');}
function closeModalOutside(e){if(e.target===document.getElementById('editModal'))closeModal();}

function moveField(id,dir){
    const form=document.createElement('form');form.method='POST';
    form.innerHTML='<input type="hidden" name="action" value="reorder">';
    const items=document.querySelectorAll('.field-item');const order=[];
    items.forEach(item=>order.push(parseInt(item.dataset.id)));
    const idx=order.indexOf(id);
    if(dir==='up'&&idx>0)[order[idx],order[idx-1]]=[order[idx-1],order[idx]];
    else if(dir==='down'&&idx<order.length-1)[order[idx],order[idx+1]]=[order[idx+1],order[idx]];
    order.forEach((fid,i)=>{const inp=document.createElement('input');inp.type='hidden';inp.name='field_order['+i+']';inp.value=fid;form.appendChild(inp);});
    document.body.appendChild(form);form.submit();
}

document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});

function toggleAccess(bid, isOpen){
    const fd=new FormData();
    fd.append('action','toggle_disaster');
    fd.append('barangay_id',bid);
    fd.append('disaster_open',isOpen?1:0);
    fetch('admin_disaster_format.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        const card=document.getElementById('brgy_'+bid);
        if(isOpen){card.classList.remove('closed');card.classList.add('open');}
        else{card.classList.remove('open');card.classList.add('closed');}
        card.querySelector('.access-status').textContent=isOpen?'Open':'Closed';
    });
}
</script>
</body>
</html>
