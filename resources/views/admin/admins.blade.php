@extends('layouts.admin')
@section('title', 'Admin Accounts - Admin Panel')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.page-wrap{max-width:1100px;margin:0 auto;}
.page-grid{display:grid;grid-template-columns:380px 1fr;gap:22px;align-items:start;}
.card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06);}
.card-title{display:flex;align-items:center;gap:9px;font-size:1.02rem;color:#202124;margin-bottom:6px;}
.card-title i{color:#0072C6;width:22px;height:22px;border-radius:7px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.8rem;}
.card-sub{font-size:.8rem;color:#5f6368;margin-bottom:16px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group input,.form-group select{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;transition:border-color .2s;font-family:inherit;}
.form-group input:focus,.form-group select:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.btn{width:100%;padding:12px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.95rem;font-weight:600;cursor:pointer;transition:transform .15s, box-shadow .15s;font-family:inherit;}
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
.role-badge.admin{background:#e8f2fb;color:#0072C6;}
.role-badge.barangay{background:#e8f7ee;color:#28a745;}
.role-badge.user{background:#fff3cd;color:#856404;}
.role-badge i{color:inherit;}
.actions{display:flex;gap:8px;align-items:center;white-space:nowrap;}
.btn-edit,.btn-del{display:inline-flex;align-items:center;gap:6px;border:none;border-radius:8px;padding:7px 13px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-edit{background:#e8f2fb;color:#0072C6;}
.btn-edit:hover{background:#0072C6;color:#fff;}
.btn-del{background:#fdeaea;color:#dc3545;}
.btn-del:hover{background:#dc3545;color:#fff;}
.empty{text-align:center;color:#8a97a8;padding:24px;}
.empty i{font-size:2rem;margin-bottom:8px;display:block;}
.modal{display:none;position:fixed;inset:0;background:rgba(15,30,50,.5);z-index:1100;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.modal.show{display:flex;}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:560px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);animation:popIn .25s ease;max-height:90vh;overflow-y:auto;}
@keyframes popIn{from{opacity:0;transform:translateY(-12px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}
.modal-head{display:flex;align-items:center;gap:9px;font-size:1.05rem;font-weight:700;color:#202124;margin-bottom:18px;}
.modal-head i{color:#0072C6;width:28px;height:28px;border-radius:8px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
.modal-actions{display:flex;gap:10px;margin-top:4px;}
.btn-cancel{flex:1;padding:11px;border:2px solid #e0e0e0;background:#fff;color:#5f6368;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-cancel:hover{background:#f5f5f5;}
.btn-save{flex:1.4;padding:11px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-save:hover{box-shadow:0 4px 14px rgba(0,114,198,.3);}
.perms-section{margin-top:18px;border-top:1px solid #eef0f3;padding-top:14px;}
.perms-title{font-weight:700;font-size:.88rem;color:#202124;margin-bottom:4px;}
.perms-sub{font-size:.75rem;color:#8a97a8;margin-bottom:10px;}
.perm-group{margin-bottom:12px;}
.perm-group-title{font-size:.78rem;font-weight:700;color:#0072C6;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;padding-bottom:4px;border-bottom:1px dashed #e0e0e0;}
.perm-item{display:flex;align-items:center;gap:10px;padding:5px 8px;border-radius:6px;margin-bottom:2px;}
.perm-item:hover{background:#f8fafc;}
.perm-item input[type="checkbox"]{width:16px;height:16px;accent-color:#0072C6;cursor:pointer;flex-shrink:0;}
.perm-item label{font-size:.85rem;color:#333;cursor:pointer;display:flex;align-items:center;gap:8px;flex:1;}
.required-star{color:#dc3545;font-weight:800;}
.perm-state{font-size:.68rem;padding:2px 8px;border-radius:10px;font-weight:600;}
.perm-state.required{background:#fdeaea;color:#dc3545;}
.perm-state.optional{background:#e8f2fb;color:#0072C6;}
.role-select{margin-bottom:16px;}
.radio-group{display:flex;gap:10px;flex-wrap:wrap;}
.radio-option{flex:1;min-width:110px;padding:10px 12px;border:2px solid #e0e0e0;border-radius:10px;cursor:pointer;text-align:center;transition:all .15s;}
.radio-option:hover{border-color:#0072C6;}
.radio-option.selected{border-color:#0072C6;background:#e8f2fb;}
.radio-option input{display:none;}
.radio-option .r-icon{font-size:1.3rem;display:block;margin-bottom:4px;}
.radio-option .r-label{font-size:.78rem;font-weight:600;color:#333;display:block;}
.radio-option .r-desc{font-size:.65rem;color:#8a97a8;display:block;margin-top:2px;}
@media (max-width:820px){.page-grid{grid-template-columns:1fr;}}
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="page-grid">
        <div class="card">
            <div class="card-title"><i class="fas fa-user-plus"></i> Add Admin Account</div>
            <p class="card-sub">Create a new MSWD account with a role.</p>
            <form method="POST" action="{{ route('admin.admins.store') }}">
                @csrf
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter username" autocomplete="off" value="{{ old('username') }}" required>
                    @error('username')<div style="color:#dc3545;font-size:0.8rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min. 6 characters" autocomplete="new-password" required>
                    @error('password')<div style="color:#dc3545;font-size:0.8rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm password" autocomplete="new-password" required>
                </div>
                <div class="form-group role-select">
                    <label>Role</label>
                    <div id="createRoleRadios" class="radio-group">
                        <label class="radio-option" data-value="admin">
                            <input type="radio" name="role" value="admin" checked>
                            <span class="r-icon">🛡️</span>
                            <span class="r-label">Admin</span>
                            <span class="r-desc">Full MSWD access</span>
                        </label>
                        <label class="radio-option" data-value="user">
                            <input type="radio" name="role" value="user">
                            <span class="r-icon">👤</span>
                            <span class="r-label">User</span>
                            <span class="r-desc">Read-only access</span>
                        </label>
                    </div>
                    @error('role')<div style="color:#dc3545;font-size:0.8rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn"><i class="fas fa-save"></i> Save Account</button>
            </form>
        </div>

        <div class="card">
            <div class="card-title"><i class="fas fa-users"></i> Admin Accounts ({{ count($admins) }})</div>
            <p class="card-sub">Manage user accounts of the data management system.</p>
            @if (count($admins) === 0)
                <div class="empty"><i class="fas fa-user-shield"></i>No accounts yet.</div>
            @else
                <table class="adm-table">
                    <thead>
                        <tr><th>#</th><th>Username</th><th>Role</th><th>Permissions</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($admins as $i => $a)
                        @php
                            $roleName = $a->roles->first()->name ?? 'none';
                            $permCount = $a->permissions->count() ?: ($a->roles->first()?->permissions->count() ?? 0);
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <span class="user-cell">
                                    <span class="avatar">{{ strtoupper(substr($a->username, 0, 1)) }}</span>
                                    {{ $a->username }}
                                    @if ((int) $a->id === (int) $myId)<span class="you-badge">You</span>@endif
                                </span>
                            </td>
                            <td>
                                <span class="role-badge {{ $roleName }}"><i class="fas fa-{{ $roleName === 'admin' ? 'user-shield' : ($roleName === 'barangay' ? 'building' : 'user') }}"></i> {{ ucfirst($roleName) }}</span>
                            </td>
                            <td><span style="font-size:.78rem;color:#5f6368;">{{ $permCount }} permission{{ $permCount !== 1 ? 's' : '' }}</span></td>
                            <td>
                                <div class="actions">
                                    <button type="button" class="btn-edit" onclick="openEdit({{ $a->id }}, @json($a->username), @json($roleName), @json($a->permissions->pluck('name')->all()))"><i class="fas fa-pen"></i> Edit</button>
                                    @if ((int) $a->id !== (int) $myId)
                                    <form method="POST" action="{{ route('admin.admins.delete') }}" onsubmit="return confirm('Delete this admin account? This cannot be undone.');">
                                        @csrf
                                        <input type="hidden" name="admin_id" value="{{ $a->id }}">
                                        <button type="submit" class="btn-del"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-head"><i class="fas fa-user-edit"></i> Edit Account &amp; Permissions</div>
        <form method="POST" action="{{ route('admin.admins.update') }}">
            @csrf
            <input type="hidden" name="admin_id" id="editId">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="editUsername" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>New Password <small style="font-weight:400;color:#8a97a8;">(leave blank to keep current)</small></label>
                <input type="password" name="password" placeholder="Min. 6 characters" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password">
            </div>
            <div class="form-group role-select">
                <label>Role</label>
                <select name="role" id="editRole" onchange="updateRolePerms()">
                    @foreach(['admin' => 'Admin', 'user' => 'User'] as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="perms-section">
                <div class="perms-title"><i class="fas fa-key" style="color:#0072C6;margin-right:6px;"></i>Permissions</div>
                <div class="perms-sub">Check the permissions for this account. <span style="color:#dc3545;font-weight:700;">* Required</span> for the selected role (cannot be unchecked).</div>
                @foreach ($groups as $groupName => $permNames)
                    <div class="perm-group">
                        <div class="perm-group-title">{{ $groupName }}</div>
                        @foreach ($permNames as $permName)
                            @php $permLabel = $allPermissions[$permName] ?? $permName; @endphp
                            <div class="perm-item">
                                <input type="checkbox" name="permissions[{{ $permName }}]" value="1" id="edit-perm-{{ $permName }}" data-perm="{{ $permName }}">
                                <label for="edit-perm-{{ $permName }}">
                                    {{ $permLabel }}
                                    @if (in_array($permName, $permissionHelper::requiredFor('admin')))
                                        <span class="required-star">*</span>
                                    @endif
                                </label>
                                <span class="perm-state {{ in_array($permName, $permissionHelper::requiredFor('admin')) ? 'required' : 'optional' }}" id="perm-state-{{ $permName }}">
                                    {{ in_array($permName, $permissionHelper::requiredFor('admin')) ? 'Required' : 'Optional' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const requiredPerms = {
    admin: @json($permissionHelper::requiredFor('admin')),
    barangay: @json($permissionHelper::requiredFor('barangay')),
    user: @json($permissionHelper::requiredFor('user')),
};
const allPermIds = @json(array_keys($allPermissions));

function setRoleRadios() {
    document.querySelectorAll('#createRoleRadios .radio-option').forEach(opt => {
        opt.addEventListener('click', function() {
            document.querySelectorAll('#createRoleRadios .radio-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;
        });
    });
}
setRoleRadios();

function updateRolePerms() {
    const role = document.getElementById('editRole').value;
    const required = requiredPerms[role] || [];
    allPermIds.forEach(perm => {
        const cb = document.getElementById('edit-perm-' + perm);
        const state = document.getElementById('perm-state-' + perm);
        const label = cb.closest('.perm-item').querySelector('.required-star');
        if (required.includes(perm)) {
            cb.checked = true;
            cb.disabled = true;
            if (label) label.style.display = '';
            state.textContent = 'Required';
            state.className = 'perm-state required';
        } else {
            cb.disabled = false;
            if (label) label.style.display = 'none';
            state.textContent = 'Optional';
            state.className = 'perm-state optional';
        }
    });
}

function openEdit(id, username, role, perms) {
    document.getElementById('editId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editRole').value = role;
    allPermIds.forEach(perm => {
        const cb = document.getElementById('edit-perm-' + perm);
        cb.checked = perms.includes(perm);
        cb.disabled = false;
    });
    updateRolePerms();
    document.getElementById('editModal').classList.add('show');
}
function closeEdit(){
    document.getElementById('editModal').classList.remove('show');
}
document.getElementById('editModal').addEventListener('click', function(e){
    if (e.target === this) closeEdit();
});
</script>
@endpush
