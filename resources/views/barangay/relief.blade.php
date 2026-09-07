@extends('layouts.barangay')

@section('title', 'Relief Goods Distribution')
@section('headerTitle', 'Relief Goods Distribution')

@section('head')
<style>
.page-body { max-width: 980px; }
.page-title {
    font-size: 1.25rem;
    color: #222;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.page-title i { color: #11998e; }
.page-sub { color: #999; font-size: 0.88rem; margin-bottom: 22px; }
.sched-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 22px;
    margin-bottom: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #11998e;
}
.sched-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
.sched-title { font-size: 1.05rem; font-weight: 700; color: #222; }
.sched-badge {
    padding: 4px 13px;
    border-radius: 18px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #fff;
    background: #6c757d;
}
.sched-badge.Scheduled { background: #0072C6; }
.sched-badge.Ongoing { background: #fd7e14; }
.sched-badge.Done, .sched-badge.Completed { background: #28a745; }
.sched-meta { display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.82rem; color: #666; margin-bottom: 12px; }
.sched-meta i { color: #11998e; width: 16px; }
.sched-desc { font-size: 0.9rem; color: #555; line-height: 1.6; margin-bottom: 14px; }
.items-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.item-chip {
    background: #eafaf6;
    color: #0b6e4f;
    border-radius: 20px;
    padding: 5px 13px;
    font-size: 0.78rem;
    font-weight: 600;
}
.dist-box {
    margin-top: 14px;
    border-top: 1px dashed #e2e8e4;
    padding-top: 14px;
}
.dist-done {
    background: #edfaf2;
    border: 1px solid #c9ecd4;
    border-radius: 10px;
    padding: 14px 16px;
}
.dist-done h4 { color: #0b6e4f; font-size: 0.88rem; margin-bottom: 8px; }
.dist-done p { font-size: 0.84rem; color: #555; margin-bottom: 5px; }
.dist-done a { color: #11998e; font-weight: 600; }
.confirm-btn {
    background: linear-gradient(90deg, #11998e, #0b6e4f);
    border: none;
    color: #fff;
    padding: 10px 20px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 0.84rem;
    cursor: pointer;
}
.confirm-btn:hover { box-shadow: 0 5px 14px rgba(17,153,142,0.35); }
.sched-empty {
    text-align: center;
    color: #aaa;
    padding: 50px 0;
    font-size: 0.92rem;
}
.modal-mask {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 400;
    align-items: center;
    justify-content: center;
}
.modal-mask.show { display: flex; }
.modal-box {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    width: min(480px, 92vw);
    max-height: 90vh;
    overflow-y: auto;
}
.modal-box h3 { font-size: 1.02rem; color: #222; margin-bottom: 16px; }
.m-field { margin-bottom: 14px; }
.m-field label { display: block; font-size: 0.82rem; font-weight: 600; color: #444; margin-bottom: 6px; }
.m-field input, .m-field textarea {
    width: 100%;
    padding: 10px 13px;
    border: 1.5px solid #e2e8e4;
    border-radius: 8px;
    font-size: 0.88rem;
    font-family: inherit;
}
.m-field input:focus, .m-field textarea:focus { outline: none; border-color: #11998e; }
.m-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
.m-btn { padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 0.84rem; cursor: pointer; border: none; }
.m-cancel { background: #eef1ef; color: #555; }
.m-ok { background: linear-gradient(90deg, #11998e, #0b6e4f); color: #fff; }
.m-ok:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
@endsection

@section('content')

<div class="page-body">
    <h2 class="page-title"><i class="fas fa-boxes-stacked"></i> Relief Goods Distribution</h2>
    <p class="page-sub">Relief schedules assigned to {{ $barangay->barangay_name }}. Confirm receipt once goods are received.</p>

    @forelse ($schedules as $s)
        <div class="sched-card">
            <div class="sched-top">
                <span class="sched-title">{{ $s->title }}</span>
                <span class="sched-badge {{ $s->status }}">{{ $s->status ?? 'Scheduled' }}</span>
            </div>

            <div class="sched-meta">
                <span><i class="fas fa-calendar-day"></i> {{ $s->distribution_date ? $s->distribution_date->format('F j, Y') : '—' }}</span>
                <span><i class="fas fa-clock"></i> {{ $s->distribution_time ?: '—' }}</span>
                <span><i class="fas fa-map-marker-alt"></i> {{ $s->location ?: '—' }}</span>
            </div>

            @if ($s->description)
                <div class="sched-desc">{{ $s->description }}</div>
            @endif

            @if ($s->items->isNotEmpty())
                <div class="items-chips">
                    @foreach ($s->items as $item)
                        <span class="item-chip">{{ $item->item_name }} <b>&times; {{ $item->quantity }} {{ $item->unit }}</b></span>
                    @endforeach
                </div>
            @endif

            <div class="dist-box">
                @if (isset($distributions[$s->schedule_id]) && $distributions[$s->schedule_id]->isNotEmpty())
                    @php $report = $distributions[$s->schedule_id]->first(); @endphp
                    <div class="dist-done">
                        <h4><i class="fas fa-check-circle"></i> Goods Received &amp; Confirmed</h4>
                        <p>Confirmed by: <b>{{ $report->confirmed_by }}</b></p>
                        <p>Received: <b>{{ $report->received_at ? $report->received_at->format('M d, Y h:i A') : '—' }}</b></p>
                        @if ($report->narrative)
                            <p>Notes: {{ $report->narrative }}</p>
                        @endif
                        @if ($report->documents->isNotEmpty())
                            <p>
                                <i class="fas fa-paperclip"></i>
                                @foreach ($report->documents as $doc)
                                    <a href="{{ asset($doc->file_path) }}" target="_blank">{{ basename($doc->file_path) }}</a>
                                @endforeach
                            </p>
                        @endif
                    </div>
                @else
                    <button class="confirm-btn" data-schedule="{{ $s->schedule_id }}" data-title="{{ $s->title }}">
                        <i class="fas fa-hand-holding-heart"></i> Mark as Received
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="sched-empty">
            <i class="fas fa-box-open" style="font-size:2.2rem;color:#c8d3cd;display:block;margin-bottom:10px;"></i>
            No relief schedules assigned to this barangay yet.
        </div>
    @endforelse
</div>

<div class="modal-mask" id="confirmModal">
    <div class="modal-box">
        <h3><i class="fas fa-check-circle"></i> Confirm Relief Receipt</h3>
        <div class="m-field">
            <label>Schedule</label>
            <input type="text" id="cmTitle" disabled>
        </div>
        <div class="m-field">
            <label>Confirmed By</label>
            <input type="text" id="cmName" placeholder="Name of officer accepting goods" value="{{ $barangay->barangay_name }}">
        </div>
        <div class="m-field">
            <label>Date &amp; Time Received</label>
            <input type="datetime-local" id="cmTime">
        </div>
        <div class="m-field">
            <label>Remarks / Narrative</label>
            <textarea id="cmNotes" rows="3" placeholder="Optional notes about the distribution"></textarea>
        </div>
        <div class="m-field">
            <label>Attach Supporting Document (photo/PDF)</label>
            <input type="file" id="cmDoc" accept="image/*,.pdf">
        </div>
        <div class="m-actions">
            <button class="m-btn m-cancel" onclick="closeConfirm()">Cancel</button>
            <button class="m-btn m-ok" id="cmSubmit" onclick="submitConfirm()"><i class="fas fa-check"></i> Confirm Receipt</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const RELIEF_API = '{{ url('barangay/api/relief-distribution') }}';
const CSRFTOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

let activeSchedule = null;

document.querySelectorAll('.confirm-btn').forEach(function (btn) {
    btn.onclick = function () {
        activeSchedule = btn.dataset.schedule;
        document.getElementById('cmTitle').value = btn.dataset.title;
        document.getElementById('cmTime').value = new Date().toISOString().slice(0, 16);
        document.getElementById('cmName').value = {{ Illuminate\Support\Js::from($barangay->barangay_name) }};
        document.getElementById('cmNotes').value = '';
        document.getElementById('cmDoc').value = '';
        document.getElementById('confirmModal').classList.add('show');
    };
});
function closeConfirm() {
    document.getElementById('confirmModal').classList.remove('show');
    activeSchedule = null;
}
function submitConfirm() {
    if (!activeSchedule) return;
    var fd = new FormData();
    fd.append('action', 'confirm');
    fd.append('schedule_id', activeSchedule);
    fd.append('confirmed_by', document.getElementById('cmName').value.trim());
    fd.append('received_at', document.getElementById('cmTime').value);
    fd.append('narrative', document.getElementById('cmNotes').value.trim());
    var doc = document.getElementById('cmDoc').files[0];
    if (doc) fd.append('documents[]', doc);

    var btn = document.getElementById('cmSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    fetch(RELIEF_API + '/confirm', {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': CSRFTOKEN, 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirm Receipt';
            if (data.ok) {
                closeConfirm();
                location.reload();
            } else {
                alert(data.error || 'Failed to confirm receipt.');
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirm Receipt';
            alert('Failed to confirm receipt.');
        });
}
</script>
@endpush