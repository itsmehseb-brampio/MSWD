@extends('layouts.admin')
@section('title', 'Barangay Accounts')

@push('head')
<style>
.suggestions-box{position:absolute;top:100%;left:0;right:0;background:white;border:1px solid #e0e0e0;border-radius:8px;max-height:180px;overflow-y:auto;z-index:20;display:none;box-shadow:0 8px 20px rgba(0,0,0,0.12);}
.suggestion-item{padding:9px 14px;cursor:pointer;font-size:0.88rem;color:#333;border-bottom:1px solid #f5f5f5;}
.suggestion-item:hover{background:#e8f1fb;}
.form-group{position:relative;}
.role-badge{display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:600;padding:4px 12px;border-radius:20px;}
.role-badge.barangay{background:#e8f7ee;color:#28a745;}
.role-badge.user{background:#fff3cd;color:#856404;}
.role-badge.none{background:#f1f3f4;color:#5f6368;}
.btn-edit,.btn-del{display:inline-flex;align-items:center;gap:6px;border:none;border-radius:8px;padding:7px 13px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-edit{background:#e8f2fb;color:#0072C6;}
.btn-edit:hover{background:#0072C6;color:#fff;}
.btn-del{background:#fdeaea;color:#dc3545;}
.btn-del:hover{background:#dc3545;color:#fff;}
.actions{display:flex;gap:8px;align-items:center;white-space:nowrap;}
.radio-group{display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;}
.radio-option{flex:1;min-width:110px;padding:10px 12px;border:2px solid #e0e0e0;border-radius:10px;cursor:pointer;text-align:center;transition:all .15s;}
.radio-option:hover{border-color:#28a745;}
.radio-option.selected{border-color:#28a745;background:#e8f7ee;}
.radio-option input{display:none;}
.radio-option .r-icon{font-size:1.3rem;display:block;margin-bottom:4px;}
.radio-option .r-label{font-size:.78rem;font-weight:600;color:#333;display:block;}
.radio-option .r-desc{font-size:.65rem;color:#8a97a8;display:block;margin-top:2px;}
.perms-section{margin-top:18px;border-top:1px solid #eef0f3;padding-top:14px;}
.perms-title{font-weight:700;font-size:.88rem;color:#202124;margin-bottom:4px;}
.perms-sub{font-size:.75rem;color:#8a97a8;margin-bottom:10px;}
.perm-group{margin-bottom:12px;}
.perm-group-title{font-size:.78rem;font-weight:700;color:#28a745;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;padding-bottom:4px;border-bottom:1px dashed #e0e0e0;}
.perm-item{display:flex;align-items:center;gap:10px;padding:5px 8px;border-radius:6px;margin-bottom:2px;}
.perm-item:hover{background:#f8fafc;}
.perm-item input[type="checkbox"]{width:16px;height:16px;accent-color:#28a745;cursor:pointer;flex-shrink:0;}
.perm-item label{font-size:.85rem;color:#333;cursor:pointer;display:flex;align-items:center;gap:8px;flex:1;}
.required-star{color:#dc3545;font-weight:800;}
.perm-state{font-size:.68rem;padding:2px 8px;border-radius:10px;font-weight:600;}
.perm-state.required{background:#fdeaea;color:#dc3545;}
.perm-state.optional{background:#e8f7ee;color:#28a745;}
.modal{display:none;position:fixed;inset:0;background:rgba(15,30,50,.5);z-index:1100;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.modal.show{display:flex;}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:560px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);animation:popIn .25s ease;max-height:90vh;overflow-y:auto;}
@keyframes popIn{from{opacity:0;transform:translateY(-12px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}
.modal-head{display:flex;align-items:center;gap:9px;font-size:1.05rem;font-weight:700;color:#202124;margin-bottom:18px;}
.modal-head i{color:#28a745;width:28px;height:28px;border-radius:8px;background:#e8f7ee;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
.modal-actions{display:flex;gap:10px;margin-top:4px;}
.btn-cancel{flex:1;padding:11px;border:2px solid #e0e0e0;background:#fff;color:#5f6368;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-cancel:hover{background:#f5f5f5;}
.btn-save{flex:1.4;padding:11px;border:none;border-radius:10px;background:linear-gradient(90deg,#28a745,#1e7e34);color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-save:hover{box-shadow:0 4px 14px rgba(40,167,69,.3);}
.role-select{margin-bottom:16px;}
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header"><h2><i class="fas fa-building"></i> Barangay Account Management</h2></div>
    <div style="padding:25px;">
        <h3 style="margin-bottom:15px;color:#333;"><i class="fas fa-user-plus" style="color:#28a745;"></i> Create Barangay Account</h3>
        <form method="POST" action="{{ route('admin.barangay.store') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px;">
            @csrf
            <div class="form-group">
                <label><i class="fas fa-building"></i> Barangay Name</label>
                <input type="text" name="barangay_name" id="barangayInput" value="{{ old('barangay_name') }}" required autocomplete="off" oninput="showSuggestions()" onfocus="showSuggestions()">
                <div id="suggestions" class="suggestions-box"></div>
                @error('barangay_name')<div style="color:#dc3545;font-size:0.8rem;margin-top:4px;">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Address</label>
                <input type="text" name="address" value="{{ old('address') }}" placeholder="Barangay Hall address">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required>
                @error('password')<div style="color:#dc3545;font-size:0.8rem;margin-top:4px;">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label><i class="fas fa-user-tag"></i> Role</label>
                <div id="createRoleRadios" class="radio-group">
                    <label class="radio-option selected" data-value="barangay">
                        <input type="radio" name="role" value="barangay" checked>
                        <span class="r-icon">🏘️</span>
                        <span class="r-label">Barangay</span>
                        <span class="r-desc">Barangay access</span>
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
            <div class="form-group" style="display:flex;align-items:flex-end;grid-column:1/-1;">
                <button type="submit" class="btn btn-green"><i class="fas fa-plus"></i> Create Account</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i> Existing Barangay Accounts</h2>
        <span style="color:#999;font-size:0.9rem;">{{ count($barangays) }} total</span>
    </div>
    <table>
        <thead><tr>
            <th>Barangay Name</th><th>Address</th><th>Role</th><th>Disaster Submission</th><th>Actions</th>
        </tr></thead>
        <tbody>
            @foreach ($barangays as $b)
            @php $roleName = $b->roles->first()->name ?? 'none'; @endphp
            <tr>
                <td><strong>{{ $b->barangay_name }}</strong></td>
                <td>{{ $b->address ?? 'N/A' }}</td>
                <td><span class="role-badge {{ $roleName }}"><i class="fas fa-{{ $roleName === 'barangay' ? 'building' : 'user' }}"></i> {{ ucfirst($roleName) }}</span></td>
                <td>
                    <button type="button" class="badge {{ $b->disaster_open ? 'badge-green' : 'badge-gray' }}" style="border:none;cursor:pointer;" onclick="toggleDisaster({{ $b->barangay_id }})">
                        {{ $b->disaster_open ? 'Open' : 'Closed' }}
                    </button>
                </td>
                <td>
                    <div class="actions">
                        <button type="button" class="btn-edit" onclick="openEdit({{ $b->barangay_id }}, @json($b->barangay_name), @json($b->address), @json($roleName), @json($b->permissions->pluck('name')->all()))"><i class="fas fa-pen"></i> Edit</button>
                        <form method="POST" action="{{ route('admin.barangay.delete') }}" onsubmit="return confirm('Delete this barangay account? This cannot be undone.');">
                            @csrf
                            <input type="hidden" name="barangay_id" value="{{ $b->barangay_id }}">
                            <button type="submit" class="btn-del"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            @if ($barangays->isEmpty())
                <tr><td colspan="5" class="empty-state"><i class="fas fa-building"></i>No barangay accounts yet</td></tr>
            @endif
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header"><h2><i class="fas fa-check-circle" style="color:#28a745;"></i> Barangays of Malilipot</h2></div>
    <div style="padding:20px;">
        <p style="color:#666;font-size:0.9rem;margin-bottom:12px;">All 18 barangays of Malilipot (accounts may be registered below):</p>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            @foreach ($suggestions as $s)
                <span class="badge {{ in_array(strtolower($s), $registered) ? 'badge-green' : 'badge-gray' }}" style="padding:8px 14px;">
                    <i class="fas {{ in_array(strtolower($s), $registered) ? 'fa-check' : 'fa-building' }}"></i> {{ $s }}
                </span>
            @endforeach
        </div>
    </div>
</div>

<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-head"><i class="fas fa-building-edit"></i> Edit Barangay Account &amp; Permissions</div>
        <form method="POST" action="{{ route('admin.barangay.update') }}">
            @csrf
            <input type="hidden" name="barangay_id" id="editId">
            <div class="form-group">
                <label>Barangay Name</label>
                <input type="text" name="barangay_name" id="editName" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" id="editAddress" autocomplete="off">
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
                    <option value="barangay">Barangay</option>
                    <option value="user">User</option>
                </select>
            </div>
            <div class="perms-section">
                <div class="perms-title"><i class="fas fa-key" style="color:#28a745;margin-right:6px;"></i>Permissions</div>
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
                                    @if (in_array($permName, $permissionHelper::requiredFor('barangay')))
                                        <span class="required-star">*</span>
                                    @endif
                                </label>
                                <span class="perm-state {{ in_array($permName, $permissionHelper::requiredFor('barangay')) ? 'required' : 'optional' }}" id="perm-state-{{ $permName }}">
                                    {{ in_array($permName, $permissionHelper::requiredFor('barangay')) ? 'Required' : 'Optional' }}
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
        if (!cb) return;
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

function openEdit(id, name, address, role, perms) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editAddress').value = address || '';
    document.getElementById('editRole').value = role === 'user' ? 'user' : 'barangay';
    allPermIds.forEach(perm => {
        const cb = document.getElementById('edit-perm-' + perm);
        if (!cb) return;
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

function toggleDisaster(barangayId) {
    const formData = new FormData();
    formData.append('barangay_id', barangayId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    fetch('{{ route('admin.barangay.toggle_disaster') }}', {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) location.reload();
    });
}

const barangays = @json($suggestions);
const registered = @json($registered);
function showSuggestions() {
    const input = document.getElementById('barangayInput').value.toLowerCase();
    const box = document.getElementById('suggestions');
    if (!input) { box.style.display = 'none'; return; }
    const matches = barangays.filter(b => b.toLowerCase().includes(input)).filter(b => !registered.includes(b.toLowerCase()));
    if (matches.length) {
        box.innerHTML = matches.map(b => '<div class="suggestion-item" onclick="selectBarangay(\'' + b.replace(/'/g, "\\'") + '\')">' + b + '</div>').join('');
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}
function selectBarangay(name) {
    document.getElementById('barangayInput').value = name;
    document.getElementById('suggestions').style.display = 'none';
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('#barangayInput') && !e.target.closest('#suggestions')) document.getElementById('suggestions').style.display = 'none';
});
</script>
@endpush
