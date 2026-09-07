@extends('layouts.admin')
@section('title', 'Admin Accounts - Admin Panel')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.page-wrap{max-width:1050px;margin:0 auto;}
.page-grid{display:grid;grid-template-columns:360px 1fr;gap:22px;align-items:start;}
.card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06);}
.card-title{display:flex;align-items:center;gap:9px;font-size:1.02rem;color:#202124;margin-bottom:6px;}
.card-title i{color:#0072C6;width:22px;height:22px;border-radius:7px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.8rem;}
.card-sub{font-size:.8rem;color:#5f6368;margin-bottom:16px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-weight:600;font-size:.85rem;color:#333;margin-bottom:6px;}
.form-group input{width:100%;padding:11px 14px;border:2px solid #e0e0e0;border-radius:10px;font-size:.92rem;outline:none;transition:border-color .2s;font-family:inherit;}
.form-group input:focus{border-color:#0072C6;box-shadow:0 0 0 2px rgba(0,114,198,.12);}
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
.role-badge i{color:#0072C6;}
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
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:400px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);animation:popIn .25s ease;}
@keyframes popIn{from{opacity:0;transform:translateY(-12px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}
.modal-head{display:flex;align-items:center;gap:9px;font-size:1.05rem;font-weight:700;color:#202124;margin-bottom:18px;}
.modal-head i{color:#0072C6;width:28px;height:28px;border-radius:8px;background:#e8f2fb;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
.modal-actions{display:flex;gap:10px;margin-top:4px;}
.btn-cancel{flex:1;padding:11px;border:2px solid #e0e0e0;background:#fff;color:#5f6368;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-cancel:hover{background:#f5f5f5;}
.btn-save{flex:1.4;padding:11px;border:none;border-radius:10px;background:linear-gradient(90deg,#0072C6,#005999);color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.btn-save:hover{box-shadow:0 4px 14px rgba(0,114,198,.3);}
@media (max-width:820px){.page-grid{grid-template-columns:1fr;}}
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="page-grid">
        <div class="card">
            <div class="card-title"><i class="fas fa-user-plus"></i> Add Admin</div>
            <p class="card-sub">Create a new MSWD admin account.</p>
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
                <button type="submit" class="btn"><i class="fas fa-save"></i> Save Admin</button>
            </form>
        </div>

        <div class="card">
            <div class="card-title"><i class="fas fa-users"></i> Admin Accounts ({{ count($admins) }})</div>
            <p class="card-sub">Manage admin users of the data management system.</p>
            @if (count($admins) === 0)
                <div class="empty"><i class="fas fa-user-shield"></i>No admin accounts yet.</div>
            @else
                <table class="adm-table">
                    <thead>
                        <tr><th>#</th><th>Username</th><th>Role</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($admins as $i => $a)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <span class="user-cell">
                                    <span class="avatar">{{ strtoupper(substr($a->username, 0, 1)) }}</span>
                                    {{ $a->username }}
                                    @if ((int) $a->id === (int) $myId)<span class="you-badge">You</span>@endif
                                </span>
                            </td>
                            <td><span class="role-badge"><i class="fas fa-user-shield"></i> MSWD Admin</span></td>
                            <td>
                                <div class="actions">
                                    <button type="button" class="btn-edit" onclick="openEdit({{ $a->id }}, @json($a->username))"><i class="fas fa-pen"></i> Edit</button>
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
        <div class="modal-head"><i class="fas fa-user-edit"></i> Edit Admin</div>
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
@endpush