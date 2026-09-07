<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = '';
$msgType = 'err';
$myId = intval($_SESSION['admin_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if ($username === '' || $password === '') {
            $message = 'Username and password are required.';
        } elseif ($password !== $confirm) {
            $message = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $message = 'Username already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $hashed);
                if ($stmt->execute()) {
                    $message = 'Admin account created successfully!';
                    $msgType = 'ok';
                } else {
                    $message = 'Error: ' . $stmt->error;
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['admin_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if ($id <= 0 || $username === '') {
            $message = 'Username is required.';
        } elseif ($password !== $confirm) {
            $message = 'Passwords do not match.';
        } elseif ($password !== '' && strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id <> ?");
            $stmt->bind_param("si", $username, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $message = 'Username is already used by another admin.';
            } else {
                if ($password !== '') {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $username, $hashed, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
                    $stmt->bind_param("si", $username, $id);
                }
                if ($stmt->execute()) {
                    if ($id === $myId) $_SESSION['admin_username'] = $username;
                    $message = 'Admin account updated successfully!';
                    $msgType = 'ok';
                } else {
                    $message = 'Error: ' . $stmt->error;
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['admin_id'] ?? 0);
        if ($id === $myId) {
            $message = 'You cannot delete your own account.';
        } else {
            $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Admin account deleted.';
                $msgType = 'ok';
            } else {
                $message = 'Error: ' . $stmt->error;
            }
        }
    }
}

$admins = $conn->query("SELECT id, username FROM admins ORDER BY id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Accounts - Admin Panel</title>
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
.header h1 i{margin-right:8px;}
.admin-profile{display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 15px;border-radius:25px;background:rgba(255,255,255,0.15);}
.admin-profile:hover{background:rgba(255,255,255,0.25);}
.admin-profile img{width:36px;height:36px;border-radius:50%;border:2px solid white;object-fit:cover;}
.dropdown{display:none;position:absolute;right:30px;top:65px;background:white;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.15);overflow:hidden;z-index:1002;min-width:150px;}
.dropdown.show{display:block;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:12px 20px;text-decoration:none;color:#333;}
.dropdown a:hover{background:#f5f5f5;color:#0072C6;}

.page-wrap{max-width:1050px;margin:0 auto;padding:28px 30px;}
.page-grid{display:grid;grid-template-columns:360px 1fr;gap:22px;align-items:start;}
.card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06);}
.card-title{display:flex;align-items:center;gap:9px;font-size:1.02rem;color:#202124;margin-bottom:6px;}
.card-title i{color:#0072C6;width:22px;height:22px;border-radius:7px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.8rem;}
.card-sub{font-size:.8rem;color:#5f6368;margin-bottom:16px;}

.alert{padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert.ok{background:#e6f4ea;color:#188038;border-left:4px solid #188038;}
.alert.err{background:#fce8e6;color:#c5221f;border-left:4px solid #c5221f;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group label small{font-weight:400;color:#8a97a8;}
.form-group input{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;transition:border-color .2s;}
.form-group input:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.btn{width:100%;padding:12px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.95rem;font-weight:600;cursor:pointer;transition:transform .15s, box-shadow .15s;}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,114,198,.3);}

.adm-table{width:100%;border-collapse:collapse;}
.adm-table th{text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#8a97a8;padding:10px 12px;border-bottom:2px solid #eef0f3;font-weight:700;}
.adm-table td{padding:12px;border-bottom:1px solid #f0f2f5;font-size:.9rem;color:#333;}
.adm-table tr:last-child td{border-bottom:none;}
.adm-table tr:hover td{background:#fafcff;}
.user-cell{display:flex;align-items:center;gap:10px;font-weight:600;}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#0a6cff,#00a3ff);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0;}
.you-badge{font-size:.65rem;font-weight:700;background:#e8f2fb;color:#0072C6;padding:2px 9px;border-radius:12px;margin-left:4px;}
.role-badge{display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:600;color:#5f6368;background:#f1f3f4;padding:4px 12px;border-radius:20px;}
.role-badge i{color:#0072C6;}
.actions{display:flex;gap:8px;align-items:center;white-space:nowrap;}
.btn-edit,.btn-del{display:inline-flex;align-items:center;gap:6px;border:none;border-radius:8px;padding:7px 13px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s;}
.btn-edit{background:#e8f2fb;color:#0072C6;}
.btn-edit:hover{background:#0072C6;color:#fff;}
.btn-del{background:#fdeaea;color:#dc3545;}
.btn-del:hover{background:#dc3545;color:#fff;}
.empty{text-align:center;color:#8a97a8;padding:24px;}
.empty i{font-size:2rem;margin-bottom:8px;display:block;}

.modal{display:none;position:fixed;inset:0;background:rgba(15,30,50,.5);z-index:1100;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.modal.show{display:flex;}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:400px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);animation:popIn .25s ease;}
@keyframes popIn{from{opacity:0;transform:translateY(-12px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}
.modal-head{display:flex;align-items:center;gap:9px;font-size:1.05rem;font-weight:700;color:#202124;margin-bottom:18px;}
.modal-head i{color:#0072C6;width:28px;height:28px;border-radius:8px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
.modal-actions{display:flex;gap:10px;margin-top:4px;}
.btn-cancel{flex:1;padding:11px;border:2px solid #e0e0e0;background:#fff;color:#5f6368;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;}
.btn-cancel:hover{background:#f5f5f5;}
.btn-save{flex:1.4;padding:11px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;}
.btn-save:hover{box-shadow:0 4px 14px rgba(0,114,198,.3);}

@media (max-width:820px){.page-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-user-cog"></i> Admin Accounts</h1>
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
                <!-- LEFT : ADD FORM -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-user-plus"></i> Add Admin</div>
                    <p class="card-sub">Create a new MSWD admin account.</p>
                    <?php if ($message != ''): ?>
                        <div class="alert <?php echo $msgType; ?>">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="Enter username" autocomplete="off" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Min. 6 characters" autocomplete="new-password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm password" autocomplete="new-password" required>
                        </div>
                        <button type="submit" class="btn"><i class="fas fa-save"></i> Save Admin</button>
                    </form>
                </div>

                <!-- RIGHT : ADMIN LIST -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-users"></i> Admin Accounts (<?php echo count($admins); ?>)</div>
                    <p class="card-sub">Manage admin users of the data management system.</p>
                    <?php if (count($admins) === 0): ?>
                        <div class="empty"><i class="fas fa-user-shield"></i>No admin accounts yet.</div>
                    <?php else: ?>
                        <table class="adm-table">
                            <thead>
                                <tr><th>#</th><th>Username</th><th>Role</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $i => $a): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td>
                                        <span class="user-cell">
                                            <span class="avatar"><?php echo htmlspecialchars(strtoupper(substr($a['username'], 0, 1))); ?></span>
                                            <?php echo htmlspecialchars($a['username']); ?>
                                            <?php if ($a['id'] == $myId): ?><span class="you-badge">You</span><?php endif; ?>
                                        </span>
                                    </td>
                                    <td><span class="role-badge"><i class="fas fa-user-shield"></i> MSWD Admin</span></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn-edit" onclick="openEdit(<?php echo $a['id']; ?>, '<?php echo htmlspecialchars($a['username'], ENT_QUOTES); ?>')"><i class="fas fa-pen"></i> Edit</button>
                                            <?php if ($a['id'] != $myId): ?>
                                            <form method="POST" action="" onsubmit="return confirm('Delete this admin account? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="admin_id" value="<?php echo $a['id']; ?>">
                                                <button type="submit" class="btn-del"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-head"><i class="fas fa-user-edit"></i> Edit Admin</div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="admin_id" id="editId">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="editUsername" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>New Password <small>(leave blank to keep current)</small></label>
                <input type="password" name="password" placeholder="Min. 6 characters" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDropdown(){ document.getElementById("dropdownMenu").classList.toggle("show"); }
window.onclick = function(e){ if(!e.target.closest('.admin-profile')) document.getElementById("dropdownMenu").classList.remove("show"); }

function openEdit(id, username){
    document.getElementById('editId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editModal').classList.add('show');
}
function closeEdit(){
    document.getElementById('editModal').classList.remove('show');
}
document.getElementById('editModal').addEventListener('click', function(e){
    if (e.target === this) closeEdit();
});
</script>
</body>
</html>
