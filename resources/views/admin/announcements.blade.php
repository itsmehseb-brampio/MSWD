@extends('layouts.admin')
@section('title', 'Announcements')

@push('head')
<style>
.ann-wrap{display:grid;grid-template-columns:1fr;gap:16px;}
@media(min-width:1100px){.ann-wrap{grid-template-columns:320px 1fr;align-items:start;}}
.ann-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);padding:18px;margin-bottom:14px;border-left:4px solid #0072C6;}
.ann-card.pinned{border-left-color:#f57c00;background:#fffdf6;}
.ann-title{font-weight:700;color:#222;font-size:1rem;display:flex;align-items:center;gap:8px;}
.pin-badge{background:#f57c00;color:white;font-size:0.65rem;padding:2px 8px;border-radius:10px;font-weight:600;}
.ann-body{color:#555;font-size:0.88rem;line-height:1.5;margin:10px 0;white-space:pre-wrap;}
.ann-meta{font-size:0.75rem;color:#999;display:flex;align-items:center;gap:14px;}
.ann-actions{display:flex;gap:8px;margin-top:10px;}
.target-chip{display:inline-block;background:#e8f1fb;color:#0072C6;font-size:0.72rem;padding:2px 10px;border-radius:12px;margin:3px 3px 0 0;}
</style>
@endpush

@section('content')
<div class="ann-wrap">
    <div class="card" style="position:sticky;top:85px;">
        <div class="card-header"><h2><i class="fas fa-bullhorn"></i> Post Announcement</h2></div>
        <div style="padding:20px;">
            <form onsubmit="event.preventDefault();postAnnouncement();">
                <div style="margin-bottom:14px;">
                    <label>Title</label>
                    <input type="text" id="annTitle" required placeholder="Announcement title">
                </div>
                <div style="margin-bottom:14px;">
                    <label>Message</label>
                    <textarea id="annMessage" required rows="4" placeholder="Announcement details..."></textarea>
                </div>
                <div style="margin-bottom:14px;">
                    <label>Target</label>
                    <select id="annTarget" onchange="toggleTargets()">
                        <option value="all">All Barangays</option>
                        <option value="specific">Specific Barangays</option>
                    </select>
                </div>
                <div id="targetCheckboxes" style="display:none;margin-bottom:14px;max-height:160px;overflow-y:auto;border:1px solid #e0e0e0;border-radius:8px;padding:12px;">
                    @foreach ($barangays as $b)
                    <label style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:0.85rem;font-weight:400;">
                        <input type="checkbox" class="annTargetItem" value="{{ $b->barangay_id }}"> {{ $b->barangay_name }}
                    </label>
                    @endforeach
                </div>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:16px;font-weight:400;">
                    <input type="checkbox" id="annPinned"> Pin announcement
                </label>
                <button type="submit" class="btn btn-blue" style="width:100%;"><i class="fas fa-paper-plane"></i> Post</button>
            </form>
        </div>
    </div>

    <div>
        @foreach ($announcements as $a)
        <div class="ann-card {{ $a->is_pinned ? 'pinned' : '' }}" style="{{ $a->is_pinned ? 'border-left-color:#f57c00;' : '' }}">
            <div class="ann-title">
                <i class="fas fa-bullhorn" style="color:{{ $a->is_pinned ? '#f57c00' : '#0072C6' }};"></i>
                {{ $a->title }} @if($a->is_pinned)<span class="pin-badge"><i class="fas fa-thumbtack"></i> Pinned</span>@endif
            </div>
            <div class="ann-body">{{ $a->message }}</div>
            <div>
                @if ($a->targets->isEmpty())
                    <span class="target-chip">All Barangays</span>
                @else
                    @foreach ($a->targets as $t)
                        <span class="target-chip">{{ $t->barangay->barangay_name ?? '#' . $t->barangay_id }}</span>
                    @endforeach
                @endif
            </div>
            <div class="ann-meta">
                <span><i class="fas fa-user"></i> {{ $a->admin->username ?? 'Admin' }}</span>
                <span><i class="fas fa-clock"></i> {{ $a->created_at->format('M d, Y h:i A') }}</span>
            </div>
            <div class="ann-actions">
                <button class="btn btn-sm {{ $a->is_pinned ? 'btn-green' : 'btn-gray' }}"
                    onclick="togglePin({{ $a->announcement_id }}, {{ $a->is_pinned ? 1 : 0 }}, this)">
                    <i class="fas fa-thumbtack"></i> {{ $a->is_pinned ? 'Unpin' : 'Pin' }}
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement({{ $a->announcement_id }}, this)"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
        @endforeach
        @if ($announcements->isEmpty())
            <div class="empty-state"><i class="fas fa-bullhorn"></i>No announcements posted yet</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name=csrf-token]').content;
function api(action, data) {
    return fetch('{{ url("admin/api/announcements") }}/' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json());
}
function toggleTargets() {
    document.getElementById('targetCheckboxes').style.display =
        document.getElementById('annTarget').value === 'specific' ? 'block' : 'none';
}
async function postAnnouncement() {
    const targetAll = document.getElementById('annTarget').value === 'all';
    const targetIds = targetAll ? [] : Array.from(document.querySelectorAll('.annTargetItem:checked')).map(c => c.value);
    const data = {
        title: document.getElementById('annTitle').value,
        message: document.getElementById('annMessage').value,
        is_pinned: document.getElementById('annPinned').checked ? 1 : 0,
        target_all: targetAll ? 1 : 0,
        target_ids: targetIds
    };
    const d = await api('post', data);
    if (d.ok) location.reload();
}
async function togglePin(id, current, btn) {
    const d = await api('pin', { announcement_id: id, is_pinned: current === 1 ? 0 : 1 });
    if (d.ok) location.reload();
}
async function deleteAnnouncement(id, btn) {
    if (!confirm('Delete this announcement?')) return;
    const d = await api('delete', { announcement_id: id });
    if (d.ok) btn.closest('.ann-card').remove();
}
</script>
@endpush
