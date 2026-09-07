@extends('layouts.admin')
@section('title', 'Barangay Accounts')

@push('head')
<style>
.suggestions-box{position:absolute;top:100%;left:0;right:0;background:white;border:1px solid #e0e0e0;border-radius:8px;max-height:180px;overflow-y:auto;z-index:20;display:none;box-shadow:0 8px 20px rgba(0,0,0,0.12);}
.suggestion-item{padding:9px 14px;cursor:pointer;font-size:0.88rem;color:#333;border-bottom:1px solid #f5f5f5;}
.suggestion-item:hover{background:#e8f1fb;}
.form-group{position:relative;}
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
            <div class="form-group" style="display:flex;align-items:flex-end;">
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
            <th>Barangay Name</th><th>Address</th><th>Disaster Submission</th><th>Created</th>
        </tr></thead>
        <tbody>
            @foreach ($barangays as $b)
            <tr>
                <td><strong>{{ $b->barangay_name }}</strong></td>
                <td>{{ $b->address ?? 'N/A' }}</td>
                <td>@if ($b->disaster_open)<span class="badge badge-green">Open</span>@else<span class="badge badge-gray">Closed</span>@endif</td>
                <td>{{ $b->created_at ? $b->created_at->format('M d, Y') : 'N/A' }}</td>
            </tr>
            @endforeach
            @if ($barangays->isEmpty())
                <tr><td colspan="4" class="empty-state"><i class="fas fa-building"></i>No barangay accounts yet</td></tr>
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
@endsection

@push('scripts')
<script>
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
