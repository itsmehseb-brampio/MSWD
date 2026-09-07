@extends('layouts.barangay')

@section('title', 'Announcements')
@section('headerTitle', 'Announcements')

@section('head')
<style>
.ann-cell { max-width: 860px; }
.ann-filter { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }
.ann-filter input {
    flex: 1;
    min-width: 220px;
    padding: 10px 15px;
    border: 1.5px solid #e2e8e4;
    border-radius: 9px;
    font-size: 0.88rem;
}
.ann-filter input:focus { outline: none; border-color: #11998e; }
.ann-list { display: flex; flex-direction: column; gap: 16px; }
.ann-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #11998e;
}
.ann-card.pinned { border-left-color: #f0ad4e; background: #fffdf5; }
.ann-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
.ann-title { font-size: 1.05rem; font-weight: 700; color: #222; }
.ann-pin {
    background: #f0ad4e;
    color: #fff;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 12px;
}
.ann-meta { display: flex; align-items: center; gap: 14px; font-size: 0.76rem; color: #999; margin-bottom: 10px; }
.ann-body { font-size: 0.92rem; color: #555; line-height: 1.7; white-space: pre-line; }
.ann-empty { text-align: center; color: #aaa; padding: 40px 0; font-size: 0.92rem; }
</style>
@endsection

@section('content')

<div class="ann-cell">
    <div class="ann-filter">
        <i class="fas fa-search" style="color:#aaa;"></i>
        <input type="text" id="annFilter" placeholder="Search announcements..." oninput="filterAnnouncements()">
    </div>

    <div class="ann-list" id="annList">
        @forelse ($announcements as $a)
            <div class="ann-card {{ $a->is_pinned ? 'pinned' : '' }}" data-search="{{ strtolower(($a->title ?? '') . ' ' . ($a->message ?? '')) }}">
                <div class="ann-head">
                    <span class="ann-title">{{ $a->title }}</span>
                    @if ($a->is_pinned)
                        <span class="ann-pin"><i class="fas fa-thumbtack"></i> PINNED</span>
                    @endif
                </div>
                <div class="ann-meta">
                    <span><i class="fas fa-user-shield"></i> {{ $a->admin->username ?? 'DSWD' }}</span>
                    <span><i class="fas fa-calendar-day"></i> {{ $a->created_at ? $a->created_at->format('M d, Y') : '' }}</span>
                    <span><i class="fas fa-clock"></i> {{ $a->created_at ? $a->created_at->format('h:i A') : '' }}</span>
                </div>
                <div class="ann-body">{{ $a->message }}</div>
            </div>
        @empty
            <div class="ann-empty">
                <i class="fas fa-bullhorn" style="font-size:2.2rem;color:#c8d3cd;display:block;margin-bottom:10px;"></i>
                No announcements yet.
            </div>
        @endforelse
    </div>
</div>

@endsection

@push('scripts')
<script>
function filterAnnouncements() {
    var key = (document.getElementById('annFilter').value || '').toLowerCase();
    document.querySelectorAll('#annList .ann-card').forEach(function (card) {
        card.style.display = key === '' || (card.dataset.search || '').indexOf(key) > -1 ? '' : 'none';
    });
}
</script>
@endpush