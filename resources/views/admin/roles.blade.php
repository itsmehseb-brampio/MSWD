@extends('layouts.admin')
@section('title', 'Roles & Permissions - Admin Panel')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.page-wrap{max-width:1280px;margin:0 auto;}
.page-grid{display:grid;gap:22px;align-items:start;}
.card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06);}
.card-title{display:flex;align-items:center;gap:9px;font-size:1.02rem;color:#202124;margin-bottom:6px;}
.card-title i{color:#0072C6;width:22px;height:22px;border-radius:7px;background:#e8f2fb;display:none;align-items:center;justify-content:center;font-size:.8rem;}
.card-title .ti{color:#0072C6;width:24px;height:24px;border-radius:7px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.78rem;}
.card-sub{font-size:.8rem;color:#5f6368;margin-bottom:16px;}
.form-group{margin-bottom:16px;position:relative;}
.form-group label{display:block;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group input,.form-group select{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;transition:border-color .2s;font-family:inherit;background:#fff;}
.form-group input:focus,.form-group select:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.form-group input[readonly]{background:#f4f6f8;color:#5f6368;}
.form-field{display:none;}
.form-field.show{display:block;}
.btn{width:100%;padding:12px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.95rem;font-weight:600;cursor:pointer;transition:transform .15s, box-shadow .15s;font-family:inherit;}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,114,198,.3);}
.btn:disabled{opacity:.55;cursor:not-allowed;transform:none;box-shadow:none;}
.alert{padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert.ok{background:#e6f4ea;color:#188038;border-left:4px solid #188038;}
.alert.err{background:#fce8e6;color:#c5221f;border-left:4px solid #c5221f;}
.alert.stack{flex-direction:column;align-items:flex-start;gap:8px;}
.alert.stack .invite-link{word-break:break-all;font-size:.9rem;color:#b02a37;background:#fff;border:1px dashed #dc3545;border-radius:8px;padding:8px 10px;width:100%;}
.request-label{font-size:.78rem;font-weight:700;color:#0072C6;text-transform:uppercase;letter-spacing:.4px;margin:14px 0 6px;}
.radio-group{display:flex;gap:10px;flex-wrap:wrap;}
.radio-option{flex:1;min-width:110px;padding:10px 12px;border:2px solid #e0e0e0;border-radius:10px;cursor:pointer;text-align:center;transition:all .15s;font-family:inherit;}
.radio-option:hover{border-color:#0072C6;}
.radio-option.selected{border-color:#0072C6;background:#e8f2fb;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
.radio-option.disabled{opacity:.45;cursor:not-allowed;}
.radio-option input{display:none;}
.radio-option .r-icon{font-size:1.3rem;display:block;margin-bottom:4px;}
.radio-option .r-label{font-size:.8rem;font-weight:700;color:#333;display:block;}
.radio-option .r-desc{font-size:.65rem;color:#8a97a8;display:block;margin-top:2px;}
.role-note{font-size:.8rem;color:#5f6368;background:#f8f9fa;border-radius:8px;padding:8px 12px;display:flex;align-items:center;gap:8px;margin-bottom:14px;}
.perm-hint{font-size:.72rem;color:#8a97a8;margin:2px 0 10px;}
/* ---- right card / tabs ---- */
.tabs{display:flex;gap:8px;margin-bottom:16px;border-bottom:2px solid #eef0f3;padding-bottom:0;}
.tab-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border:none;background:transparent;font-size:.9rem;font-weight:600;color:#5f6368;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .15s;font-family:inherit;}
.tab-btn:hover{color:#0072C6;}
.tab-btn.active{color:#0072C6;border-bottom-color:#0072C6;}
.tab-btn .cnt{font-size:.7rem;font-weight:700;background:#f1f3f4;color:#5f6368;border-radius:12px;padding:1px 8px;}
.tab-btn.active .cnt{background:#e8f2fb;color:#0072C6;}
.tab-panel{display:none;}
.tab-panel.show{display:block;}
.adm-table{width:100%;border-collapse:collapse;}
.adm-table th{text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#8a97a8;padding:10px 10px;border-bottom:2px solid #eef0f3;font-weight:700;white-space:nowrap;}
.adm-table td{padding:11px 10px;border-bottom:1px solid #f0f2f5;font-size:.88rem;color:#333;vertical-align:middle;}
.adm-table tr:last-child td{border-bottom:none;}
.adm-table tr:hover td{background:#fafcff;}
.user-cell{display:flex;align-items:center;gap:10px;font-weight:600;min-width:130px;}
.avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#0a6cff,#00a3ff);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;}
.avatar.bar{background:linear-gradient(135deg,#28a745,#0a6cff);}
.avatar.usr{background:linear-gradient(135deg,#f9a825,#ef6c00);}
.you-badge{font-size:.62rem;font-weight:700;background:#e8f2fb;color:#0072C6;padding:2px 8px;border-radius:12px;margin-left:4px;white-space:nowrap;}
.mono{font-family:Consolas,monospace;font-size:.82rem;color:#202124;}
.role-badge{display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:600;color:#5f6368;background:#f1f3f4;padding:4px 12px;border-radius:20px;}
.role-badge.admin{background:#e8f2fb;color:#0072C6;}
.role-badge.barangay{background:#e8f7ee;color:#28a745;}
.role-badge.user{background:#fff3cd;color:#856404;}
.status-badge{display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;padding:4px 12px;border-radius:20px;}
.status-badge.active{background:#e6f4ea;color:#188038;}
.status-badge.open{background:#fff3cd;color:#856404;}
.status-badge.closed{background:#f1f3f4;color:#5f6368;}
.status-badge.pending{background:#fdeaea;color:#dc3545;}
.actions{display:flex;gap:8px;align-items:center;white-space:nowrap;justify-content:flex-end;}
.btn-edit,.btn-del,.btn-tgl,.btn-now{display:inline-flex;align-items:center;gap:6px;border:none;border-radius:8px;padding:7px 12px;font-size:.76rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-edit{background:#e8f2fb;color:#0072C6;}
.btn-edit:hover{background:#0072C6;color:#fff;}
.btn-del{background:#fdeaea;color:#dc3545;}
.btn-del:hover{background:#dc3545;color:#fff;}
.btn-tgl{background:#fff8e1;color:#b76e00;}
.btn-tgl.closed{background:#f1f3f4;color:#5f6368;}
.btn-tgl:hover{background:#b76e00;color:#fff;}
.btn-now{background:#e8f7ee;color:#28a745;}
.btn-now:hover{background:#28a745;color:#fff;}
.empty{text-align:center;color:#8a97a8;padding:28px;}
.empty i{font-size:2rem;margin-bottom:8px;display:block;}
/* ---- modal ---- */
.modal{display:none;position:fixed;inset:0;background:rgba(15,30,50,.5);z-index:1100;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.modal.show{display:flex;}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:620px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);animation:popIn .25s ease;max-height:90vh;overflow-y:auto;}
@keyframes popIn{from{opacity:0;transform:translateY(-12px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}
.modal-head{display:flex;align-items:center;gap:9px;font-size:1.05rem;font-weight:700;color:#202124;margin-bottom:18px;}
.modal-head i{color:#0072C6;width:28px;height:28px;border-radius:8px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
.modal-actions{display:flex;gap:10px;margin-top:4px;}
.btn-cancel{flex:1;padding:11px;border:2px solid #e0e0e0;background:#fff;color:#5f6368;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-cancel:hover{background:#f5f5f5;}
.btn-save{flex:1.4;padding:11px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-save:hover{box-shadow:0 4px 14px rgba(0,114,198,.3);}
/* ---- permission checklist ---- */
.perms-section{margin-top:16px;border-top:1px solid #eef0f3;padding-top:14px;}
.perms-title{font-weight:700;font-size:.9rem;color:#202124;margin-bottom:4px;}
.perms-sub{font-size:.75rem;color:#8a97a8;margin-bottom:10px;}
.perm-group{margin-bottom:12px;}
.perm-group-title{font-size:.75rem;font-weight:700;color:#0072C6;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;padding-bottom:4px;border-bottom:1px dashed #e0e0e0;}
.perm-item{display:flex;align-items:center;gap:10px;padding:5px 8px;border-radius:6px;margin-bottom:1px;}
.perm-item:hover{background:#f8fafc;}
.perm-item input[type="checkbox"]{width:16px;height:16px;accent-color:#0072C6;cursor:pointer;flex-shrink:0;}
.perm-item label{font-size:.83rem;color:#333;cursor:pointer;display:flex;align-items:center;gap:8px;flex:1;}
.perm-item input:disabled + label{cursor:not-allowed;}
.perm-item input:disabled + label .perm-name{color:#5f6368;}
.required-star{color:#dc3545;font-weight:800;}
.perm-state{font-size:.66rem;padding:2px 8px;border-radius:10px;font-weight:600;white-space:nowrap;}
.perm-state.required{background:#fdeaea;color:#dc3545;}
.perm-state.optional{background:#e8f2fb;color:#0072C6;}
.create-perms{padding-right:4px;}
</style>
@endpush

@section('content')
@php
    $PH = \App\Support\PermissionHelper::class;
    $groups = $PH::GROUPS;
    $allPermissions = $PH::PERMISSIONS;
    $defaultType = $canAdmin ? 'admin' : ($canBarangay ? 'barangay' : 'user');
@endphp
<div class="page-wrap">
    @if (session('success'))
        <div class="alert ok"><i class="fas fa-info-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert err"><i class="fas fa-info-circle"></i> {{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert err"><i class="fas fa-info-circle"></i>
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif
    @if (session('invite_warning'))
        <div class="alert err stack">
            <span><i class="fas fa-paper-plane"></i> {{ session('invite_warning') }}</span>
            <span class="invite-link">{{ session('invite_link') }}</span>
        </div>
    @endif

    <div class="page-grid">
        <!-- ============ EXISTING ACCOUNTS ============ -->
        <div class="card">
            <div class="card-title"><span class="ti"><i class="fas fa-users"></i></span> Existing Accounts</div>
            <p class="card-sub">View, edit, or delete accounts. Edit to adjust roles and individual permissions.</p>

            <div class="tabs">
                <button type="button" class="tab-btn active" data-tab="admin" onclick="switchTab('admin')">
                    <i class="fas fa-user-shield"></i> Admin <span class="cnt">{{ count($tabs['admin']) }}</span>
                </button>
                <button type="button" class="tab-btn" data-tab="barangay" onclick="switchTab('barangay')">
                    <i class="fas fa-building"></i> Barangay <span class="cnt">{{ count($tabs['barangay']) }}</span>
                </button>
                <button type="button" class="tab-btn" data-tab="user" onclick="switchTab('user')">
                    <i class="fas fa-user"></i> User <span class="cnt">{{ count($tabs['user']) }}</span>
                </button>
            </div>

            @foreach ($tabs as $roleKey => $rows)
            <div class="tab-panel {{ $roleKey === 'admin' ? 'show' : '' }}" id="panel-{{ $roleKey }}">
                @if (count($rows) === 0)
                    <div class="empty"><i class="fas fa-user-shield"></i>No {{ ucfirst($roleKey) }} accounts yet.</div>
                @else
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Barangay</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="text-align:right;">Permissions</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $i => $a)
                        @php
                            $isAdminAcc = $a['type'] === 'admin';
                            $isMe = $isAdminAcc && (int) $a['id'] === (int) $myId;
                            $canEditThis = $isAdminAcc ? $canAdmin : $canBarangay;
                            $canDeleteThis = $canEditThis && !$isMe;
                            $permCount = count($a['perms']);
                            $invitePending = ($a['role'] === 'user' && !empty($a['invite_pending']));
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <span class="user-cell">
                                    <span class="avatar {{ $a['type'] === 'barangay' ? 'bar' : ($a['role'] === 'user' ? 'usr' : '') }}">{{ strtoupper(substr($a['name'], 0, 1)) }}</span>
                                    {{ $a['name'] }}
                                    @if ($isMe)<span class="you-badge">You</span>@endif
                                </span>
                            </td>
                            <td><span class="mono">{{ $a['username'] ?? '— (pending)' }}</span></td>
                            <td>
                                @if ($a['barangay'])
                                    {{ $a['name'] }}
                                @else
                                    <span style="color:#b0b6bd;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="role-badge {{ $a['role'] }}">
                                    <i class="fas fa-{{ $a['role'] === 'admin' ? 'user-shield' : ($a['role'] === 'barangay' ? 'building' : 'user') }}"></i>
                                    {{ ucfirst($a['role']) }}
                                </span>
                            </td>
                            <td>
                                @if ($a['type'] === 'barangay')
                                    <span class="status-badge {{ $a['disaster_open'] ? 'open' : 'closed' }}">
                                        <i class="fas fa-{{ $a['disaster_open'] ? 'house-fire' : 'bed' }}"></i>
                                        {{ $a['status'] }}
                                    </span>
                                @elseif ($invitePending)
                                    <span class="status-badge pending"><i class="fas fa-envelope-open-text"></i> Invited</span>
                                @else
                                    <span class="status-badge active"><i class="fas fa-check-circle"></i> Active</span>
                                @endif
                            </td>
                            <td style="text-align:right;"><span style="font-size:.78rem;color:#5f6368;">{{ $permCount }} perm{{ $permCount !== 1 ? 's' : '' }}</span></td>
                            <td>
                                <div class="actions">
                                    @if ($canEditThis)
                                    <button type="button" class="btn-edit" onclick="openEdit(@json($a))">
                                        <i class="fas fa-pen"></i> Edit
                                    </button>
                                    @endif
                                    @if ($a['type'] === 'barangay' && $canBarangay)
                                    <button type="button" class="btn-tgl {{ $a['disaster_open'] ? '' : 'closed' }}" onclick="toggleDisaster({{ $a['id'] }})" title="Toggle disaster operation status">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $a['disaster_open'] ? 'Close' : 'Open' }}
                                    </button>
                                    @endif
                                    @if ($invitePending && $canAdmin)
                                    <form method="POST" action="{{ route('admin.roles.invite') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="account_id" value="{{ $a['id'] }}">
                                        <button type="submit" class="btn-now" title="Regenerate and resend the sign-up link">
                                            <i class="fas fa-paper-plane"></i> Send Invite
                                        </button>
                                    </form>
                                    @endif
                                    @if ($canDeleteThis)
                                    <form method="POST" action="{{ route('admin.roles.delete') }}" onsubmit="return confirm('Delete this {{ $a['type'] }} account? This cannot be undone.');">
                                        @csrf
                                        <input type="hidden" name="account_type" value="{{ $a['type'] }}">
                                        <input type="hidden" name="account_id" value="{{ $a['id'] }}">
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
            @endforeach
        </div>

        <!-- ============ CREATE ACCOUNT ============ -->
        <div class="card">
            <div class="card-title"><span class="ti"><i class="fas fa-user-plus"></i></span> Create Account</div>
            <p class="card-sub">Create an Admin, Barangay, or read-only User account. Required permissions are locked per role. Users are invited through email and sign up on their own.</p>

            <form method="POST" action="{{ route('admin.roles.store') }}" id="createForm">
                @csrf

                <div class="form-group">
                    <label>Account Type</label>
                    <div class="radio-group">
                        @if ($canAdmin)
                        <label class="radio-option {{ $defaultType === 'admin' ? 'selected' : '' }}" data-type="admin" data-role="admin">
                            <input type="radio" name="account_type" value="admin" {{ $defaultType === 'admin' ? 'checked' : '' }}>
                            <span class="r-icon">🛡️</span>
                            <span class="r-label">Admin</span>
                            <span class="r-desc">Full MSWD access</span>
                        </label>
                        @endif
                        @if ($canBarangay)
                        <label class="radio-option {{ $defaultType === 'barangay' ? 'selected' : '' }}" data-type="barangay" data-role="barangay">
                            <input type="radio" name="account_type" value="barangay" {{ $defaultType === 'barangay' ? 'checked' : '' }}>
                            <span class="r-icon">🏘️</span>
                            <span class="r-label">Barangay</span>
                            <span class="r-desc">Barangay official</span>
                        </label>
                        @endif
                        @if ($canAdmin)
                        <label class="radio-option {{ $defaultType === 'user' ? 'selected' : '' }}" data-type="user" data-role="user">
                            <input type="radio" name="account_type" value="user" {{ $defaultType === 'user' ? 'checked' : '' }}>
                            <span class="r-icon">👤</span>
                            <span class="r-label">User</span>
                            <span class="r-desc">Invite by email (read-only)</span>
                        </label>
                        @endif
                    </div>
                </div>

                <div id="fieldCred" class="form-field {{ in_array($defaultType, ['admin', 'user']) ? 'show' : '' }}">
                    <div class="form-group" id="grpCreateName">
                        <label for="createName">Full Name</label>
                        <input type="text" id="createName" name="name" placeholder="Full name" value="{{ old('name') }}" autocomplete="off">
                    </div>
                    <div class="form-group" id="grpCreateUsername">
                        <label for="createUsername">Username</label>
                        <input type="text" id="createUsername" name="username" placeholder="Login username" value="{{ old('username') }}" autocomplete="off">
                    </div>
                    <div class="form-group" id="grpCreateEmail" style="display:none;">
                        <label for="createEmail">Email <small style="font-weight:400;color:#8a97a8;">(invitation will be sent here)</small></label>
                        <input type="email" id="createEmail" name="email" placeholder="invitee@example.com" value="{{ old('email') }}" autocomplete="off">
                    </div>
                </div>

                <div id="fieldBarangay" class="form-field {{ $defaultType === 'barangay' ? 'show' : '' }}">
                    <div class="form-group">
                        <label for="createBarangay">Barangay of Malilipot <small style="font-weight:400;color:#8a97a8;">(18 official barangays)</small></label>
                        <select id="createBarangay" name="barangay_name">
                            <option value="">-- Select a barangay --</option>
                            @foreach ($availableBarangays as $b)
                                @php $taken = in_array(strtolower($b), $registered); @endphp
                                <option value="{{ $taken ? '' : $b }}" {{ $taken ? 'disabled' : '' }} {{ old('barangay_name') === $b ? 'selected' : '' }}>
                                    {{ $b }}{{ $taken ? ' (already registered)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="createBarangayUsername">Auto-generated Username</label>
                        <input type="text" id="createBarangayUsername" name="barangay_username" readonly placeholder="(auto-generated)">
                    </div>
                    <div class="form-group">
                        <label for="createAddress">Full Address <small style="font-weight:400;color:#8a97a8;">(optional)</small></label>
                        <input type="text" id="createAddress" name="address" placeholder="Enter full address" value="{{ old('address') }}">
                    </div>
                </div>

                <div class="form-group" id="grpCreatePassword">
                    <label for="createPassword">Password <small style="font-weight:400;color:#8a97a8;">(not needed for Users — they set it at sign-up)</small></label>
                    <input type="password" id="createPassword" name="password" placeholder="Min. 6 characters" autocomplete="new-password">
                </div>
                <div class="form-group" id="grpCreateConfirm" style="display:none;">
                    <label for="createConfirm">Confirm Password</label>
                    <input type="password" id="createConfirm" name="confirm_password" placeholder="Confirm password" autocomplete="new-password">
                </div>

                <div class="request-label"><i class="fas fa-key" style="margin-right:6px;"></i> Permissions for this role</div>
                <div class="role-note" id="createRoleNote">
                    <i class="fas fa-shield-alt"></i>
                    <span id="createRoleNoteText">Admin</span>
                </div>
                <div class="perms-section">
                    <div class="perms-title">Permission Checklist</div>
                    <div class="perms-sub" id="createPermsSub">Defaults are pre-checked. <span style="color:#dc3545;font-weight:700;">* Required</span> for the role (cannot be unchecked).</div>
                    <div class="create-perms">
                        @foreach ($groups as $groupName => $permNames)
                            <div class="perm-group">
                                <div class="perm-group-title">{{ $groupName }}</div>
                                @foreach ($permNames as $permName)
                                    <div class="perm-item">
                                        <input type="checkbox" name="permissions[{{ $permName }}]" value="1" id="create-perm-{{ $permName }}" data-perm="{{ $permName }}">
                                        <label for="create-perm-{{ $permName }}">
                                            <span class="perm-name">{{ $allPermissions[$permName] ?? $permName }}</span>
                                            <span class="required-star" id="create-star-{{ $permName }}" style="display:none;">*</span>
                                        </label>
                                        <span class="perm-state optional" id="create-state-{{ $permName }}">Optional</span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn" style="margin-top:14px;"><i class="fas fa-save"></i> Save Account</button>
            </form>
        </div>
    </div>
</div>

<!-- ============ EDIT ACCOUNT + PERMISSIONS MODAL ============ -->
<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-head"><i class="fas fa-user-edit"></i> Edit Account &amp; Permissions</div>
        <form method="POST" action="{{ route('admin.roles.update') }}">
            @csrf
            <input type="hidden" name="account_type" id="editType">
            <input type="hidden" name="account_id" id="editId">

            <div class="form-group" id="fieldEditName">
                <label>Full Name</label>
                <input type="text" name="name" id="editName" autocomplete="off">
            </div>
            <div class="form-group" id="fieldEditUsername">
                <label>Username</label>
                <input type="text" name="username" id="editUsername" autocomplete="off">
            </div>
            <div class="form-group" id="fieldEditEmail" style="display:none;">
                <label>Email</label>
                <input type="email" name="email" id="editEmail" autocomplete="off">
            </div>

            <div class="form-group" id="fieldEditBarangay" style="display:none;">
                <label>Barangay of Malilipot</label>
                <select name="barangay_name" id="editBarangayName"></select>
            </div>
            <div class="form-group" id="fieldEditBrgyUsername" style="display:none;">
                <label>Auto-generated Username</label>
                <input type="text" id="editBrgyUsername" readonly>
            </div>
            <div class="form-group" id="fieldEditAddress" style="display:none;">
                <label>Full Address</label>
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

            <div class="form-group">
                <label for="editRole">Role</label>
                <select name="role" id="editRole" onchange="updateEditPerms()"></select>
            </div>

            <div class="perms-section">
                <div class="perms-title"><i class="fas fa-key" style="color:#0072C6;margin-right:6px;"></i>Permissions</div>
                <div class="perms-sub">Check the permissions for this account. <span style="color:#dc3545;font-weight:700;">* Required</span> for the selected role (cannot be unchecked).</div>
                <div class="create-perms">
                @foreach ($groups as $groupName => $permNames)
                    <div class="perm-group">
                        <div class="perm-group-title">{{ $groupName }}</div>
                        @foreach ($permNames as $permName)
                            <div class="perm-item">
                                <input type="checkbox" name="permissions[{{ $permName }}]" value="1" id="edit-perm-{{ $permName }}" data-perm="{{ $permName }}">
                                <label for="edit-perm-{{ $permName }}">
                                    <span class="perm-name">{{ $allPermissions[$permName] ?? $permName }}</span>
                                    <span class="required-star" id="edit-star-{{ $permName }}" style="display:none;">*</span>
                                </label>
                                <span class="perm-state optional" id="edit-state-{{ $permName }}">Optional</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                </div>
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
const REQUIRED = {
    admin: @json($PH::requiredFor('admin')),
    barangay: @json($PH::requiredFor('barangay')),
    user: @json($PH::requiredFor('user')),
};
const DEFAULTS = @json($roleDefaults);
const ALL_PERMS = @json(array_keys($allPermissions));
const TYPE_ROLE = { admin: 'admin', barangay: 'barangay', user: 'user' };
const ALLOWED_ROLES = { admin: ['admin', 'user'], barangay: ['barangay', 'user'] };
const AVAILABLE_BRGY = @json($availableBarangays);
const REGISTERED_BRGY = @json(array_map('strtolower', $registered));
const isBrgyTaken = (name) => REGISTERED_BRGY.includes(name.toLowerCase());

// ================= CREATE FORM =================
let createType = @json($defaultType);

function applyCreatePerms() {
    const role = TYPE_ROLE[createType];
    const req = REQUIRED[role] || [];
    const def = DEFAULTS[role] || [];
    ALL_PERMS.forEach(perm => {
        const cb = document.getElementById('create-perm-' + perm);
        const star = document.getElementById('create-star-' + perm);
        const state = document.getElementById('create-state-' + perm);
        const isReq = req.includes(perm);
        cb.disabled = isReq;
        cb.checked = isReq || def.includes(perm);
        star.style.display = isReq ? 'inline' : 'none';
        state.textContent = isReq ? 'Required' : 'Optional';
        state.className = isReq ? 'perm-state required' : 'perm-state optional';
    });

    const labels = {
        admin: 'Role: Admin — full MSWD access. Key management permissions are required.',
        barangay: 'Role: Barangay — barangay-level duties. Reporting & disaster permissions are required.',
        user: 'Role: User — invited by email. They sign up with their own username and password, and get read-only/view permissions.',
    };
    document.getElementById('createRoleNoteText').textContent = labels[role] || role;
}

function setCreateType(value) {
    createType = value;
    document.querySelectorAll('#createForm .radio-option').forEach(o => {
        o.classList.toggle('selected', o.dataset.type === value);
    });
    const isCred = value === 'admin' || value === 'user';
    document.getElementById('fieldCred').classList.toggle('show', isCred);
    document.getElementById('fieldBarangay').classList.toggle('show', value === 'barangay');
    document.getElementById('grpCreateName').style.display = isCred ? '' : 'none';
    document.getElementById('grpCreateUsername').style.display = value === 'admin' ? '' : 'none';
    document.getElementById('grpCreateEmail').style.display = value === 'user' ? '' : 'none';
    document.getElementById('grpCreatePassword').style.display = value === 'user' ? 'none' : '';
    document.getElementById('grpCreateConfirm').style.display = value === 'user' ? 'none' : '';
    document.getElementById('createName').required = isCred;
    document.getElementById('createUsername').required = value === 'admin';
    document.getElementById('createEmail').required = value === 'user';
    document.getElementById('createPassword').required = value !== 'user';
    document.getElementById('createConfirm').required = value !== 'user';
    document.getElementById('createBarangay').required = value === 'barangay';
    applyCreatePerms();
}

document.querySelectorAll('#createForm .radio-option').forEach(o => {
    o.addEventListener('click', function () {
        if (o.classList.contains('disabled')) return;
        setCreateType(o.dataset.type);
        const radio = o.querySelector('input');
        if (radio) radio.checked = true;
    });
});

// Barangay auto username
const brgyInput = document.getElementById('createBarangay');
function slugify(name) { return name.replace(/ /g, '_'); }
function setBrgyUsername() {
    if (brgyInput.value) {
        document.getElementById('createBarangayUsername').value = slugify(brgyInput.value);
    }
}
brgyInput.addEventListener('change', setBrgyUsername);

// ================= TABS =================
function switchTab(key) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === key));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('show'));
    document.getElementById('panel-' + key).classList.add('show');
}

// ================= DISASTER TOGGLE =================
async function toggleDisaster(id) {
    if (!confirm('Toggle disaster operation status for this barangay?')) return;
    const fd = new FormData();
    fd.append('barangay_id', id);
    fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    const res = await fetch('{{ route('admin.barangay.toggle_disaster') }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: fd
    });
    if (res.ok) location.reload();
    else alert('Failed to toggle status.');
}

// ================= EDIT MODAL =================
function updateEditPerms() {
    const role = document.getElementById('editRole').value;
    const req = REQUIRED[role] || [];
    ALL_PERMS.forEach(perm => {
        const cb = document.getElementById('edit-perm-' + perm);
        const star = document.getElementById('edit-star-' + perm);
        const state = document.getElementById('edit-state-' + perm);
        const isReq = req.includes(perm);
        if (isReq) {
            cb.checked = true;
            cb.disabled = true;
            star.style.display = 'inline';
            state.textContent = 'Required';
            state.className = 'perm-state required';
        } else {
            cb.disabled = false;
            star.style.display = 'none';
            state.textContent = 'Optional';
            state.className = 'perm-state optional';
        }
    });
}

function openEdit(row) {
    const isBarangay = row.type === 'barangay';
    const isUser = row.type === 'admin' && row.role === 'user';
    document.getElementById('editType').value = row.type;
    document.getElementById('editId').value = row.id;

    document.getElementById('fieldEditName').style.display = isBarangay ? 'none' : '';
    document.getElementById('fieldEditUsername').style.display = isBarangay ? 'none' : '';
    document.getElementById('fieldEditEmail').style.display = isBarangay ? 'none' : '';
    document.getElementById('fieldEditBarangay').style.display = isBarangay ? '' : 'none';
    document.getElementById('fieldEditBrgyUsername').style.display = isBarangay ? '' : 'none';
    document.getElementById('fieldEditAddress').style.display = isBarangay ? '' : 'none';

    if (isBarangay) {
        const sel = document.getElementById('editBarangayName');
        sel.innerHTML = '';
        const opts = AVAILABLE_BRGY.filter(b => !isBrgyTaken(b) || b === row.name);
        opts.forEach(b => {
            const o = document.createElement('option');
            o.value = b; o.textContent = b;
            sel.appendChild(o);
        });
        sel.value = row.name;
        document.getElementById('editBrgyUsername').value = row.username || slugify(row.name);
        document.getElementById('editBrgyUsername').name = 'barangay_username';
        document.getElementById('editAddress').value = row.address || '';
        document.getElementById('editName').value = '';
        document.getElementById('editUsername').value = '';
        document.getElementById('editEmail').value = '';
    } else {
        document.getElementById('editName').value = row.name;
        document.getElementById('editUsername').value = row.username || '';
        document.getElementById('editEmail').value = row.email || '';
        document.getElementById('editEmail').required = isUser;
        document.getElementById('editUsername').required = !(isUser && row.invite_pending);
        document.getElementById('editBarangayName').innerHTML = '';
        document.getElementById('editAddress').value = '';
    }

    const selRole = document.getElementById('editRole');
    selRole.innerHTML = '';
    ALLOWED_ROLES[row.type].forEach(r => {
        const o = document.createElement('option');
        o.value = r; o.textContent = r.charAt(0).toUpperCase() + r.slice(1);
        selRole.appendChild(o);
    });
    selRole.value = ALLOWED_ROLES[row.type].includes(row.role) ? row.role : ALLOWED_ROLES[row.type][0];

    ALL_PERMS.forEach(perm => {
        const cb = document.getElementById('edit-perm-' + perm);
        cb.checked = (row.perms || []).includes(perm);
        cb.disabled = false;
    });
    updateEditPerms();
    document.getElementById('editModal').classList.add('show');
}

document.getElementById('editBarangayName').addEventListener('change', function () {
    document.getElementById('editBrgyUsername').value = slugify(this.value);
});

function closeEdit() {
    document.getElementById('editModal').classList.remove('show');
}
document.getElementById('editModal').addEventListener('click', function (e) {
    if (e.target === this) closeEdit();
});

// init
setCreateType(createType);
switchTab('admin');
</script>
@endpush