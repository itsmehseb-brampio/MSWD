@extends('layouts.admin')
@section('title', 'Relief Distribution Reports')

@push('head')
<style>
.rep-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);padding:18px;margin-bottom:14px;border-left:4px solid #28a745;}
.doc-link{padding:8px 12px;border:1px solid #e0e0e0;border-radius:8px;color:#0072C6;text-decoration:none;font-size:0.82rem;display:inline-block;}
</style>
@endpush

@section('content')
<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a class="btn btn-blue" href="{{ route('admin.relief') }}"><i class="fas fa-box-open"></i> Relief Schedules</a>
</div>

@forelse ($reports as $r)
<div class="rep-card" id="rep-{{ $r->report_id }}">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div>
                <div style="font-weight:700;color:#222;">{{ $r->barangay->barangay_name ?? 'Barangay' }}</div>
                <div style="font-size:0.82rem;color:#777;">
                    Relief Distribution Report ·
                    Received {{ $r->received_at ? \Carbon\Carbon::parse($r->received_at)->format('M d, Y h:i A') : 'N/A' }}
                    @if ($r->confirmed_by) · Confirmed by <strong>{{ $r->confirmed_by }}</strong> @endif
            </div>
        </div>
        <button class="btn btn-sm btn-danger" onclick="deleteReport({{ $r->report_id }}, this)"><i class="fas fa-trash"></i></button>
    </div>

    @if ($r->narrative)
        <p style="font-size:0.85rem;color:#666;margin:10px 0;white-space:pre-wrap;">{{ $r->narrative }}</p>
    @endif

    @if ($r->documents->isNotEmpty())
        <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
            @foreach ($r->documents as $d)
                <a class="doc-link" href="{{ asset('uploads/' . $d->file_path) }}" target="_blank">
                    <i class="fas fa-file-alt"></i> {{ $d->document_name ?? $d->file_path }}
                </a>
            @endforeach
        </div>
    @endif
</div>
@empty
    <div class="empty-state"><i class="fas fa-clipboard-check"></i>No distribution reports submitted yet</div>
@endforelse
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name=csrf-token]').content;
async function deleteReport(id, btn) {
    if (!confirm('Delete this report?')) return;
    const res = await fetch('{{ url("admin/api/relief-distribution") }}/delete_report', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ report_id: id })
    });
    const d = await res.json();
    if (d.ok) btn.closest('.rep-card').remove();
}
</script>
@endpush
