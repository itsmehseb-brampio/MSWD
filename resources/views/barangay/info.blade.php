@extends('layouts.barangay')

@section('title', 'Barangay Info & Contacts')
@section('headerTitle', 'Barangay Info & Contacts')

@section('content')

@php
    if (!function_exists('fmtVal')) {
        function fmtVal($v) {
            return ($v === null || trim((string) $v) === '') ? '—' : htmlspecialchars((string) $v, ENT_QUOTES);
        }
        function fmtTime($t) {
            if (!$t) return '—';
            $time = $t instanceof \DateTimeInterface ? $t : date_create($t);
            return $time ? $time->format('M d, Y h:i A') : '—';
        }
        function fmtDate($d) {
            if (!$d || (string) $d === '0000-00-00') return '—';
            $date = $d instanceof \DateTimeInterface ? $d : date_create($d);
            return $date ? $date->format('F d, Y') : '—';
        }
    }
    $riskOptions = $riskOptions ?? ['Low', 'Medium', 'High', 'Critical'];
@endphp

<style>
.info-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
@media (max-width: 1100px) { .info-layout { grid-template-columns: 1fr; } }
.panel { background: #fff; border-radius: 12px; padding: 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
.panel-title { font-size: 1.02rem; color: #333; margin-bottom: 18px; display: flex; align-items: center; gap: 9px; }
.panel-title i { color: #11998e; }
.section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    background: #f0fbf7;
    color: #0b6e4f;
    font-weight: 700;
    font-size: 0.9rem;
    margin: 18px 0 14px;
}
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
.fg label { display: block; font-size: 0.83rem; font-weight: 600; color: #444; margin-bottom: 7px; }
.fg .req { color: #dc3545; }
.fg input, .fg textarea, .fg select {
    width: 100%;
    padding: 10px 13px;
    border: 1.5px solid #e2e8e4;
    border-radius: 8px;
    font-size: 0.9rem;
    font-family: inherit;
    color: #333;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.fg textarea { min-height: 90px; resize: vertical; }
.fg input:focus, .fg textarea:focus, .fg select:focus {
    outline: none;
    border-color: #11998e;
    box-shadow: 0 0 0 3px rgba(17,153,142,0.12);
}
.fg .hint { font-size: 0.74rem; color: #999; margin-top: 5px; }
.file-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .file-grid { grid-template-columns: 1fr; } }
.file-box {
    border: 1.5px dashed #cdd8d1;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    background: #fbfdfc;
}
.file-box img { max-width: 170px; max-height: 120px; border-radius: 8px; margin-bottom: 10px; object-fit: cover; border: 1px solid #eee; }
.file-box .fb-label { font-size: 0.82rem; font-weight: 600; color: #444; margin-bottom: 8px; }
.file-box .fb-file { font-size: 0.8rem; color: #11998e; }
.btn-submit {
    margin-top: 20px;
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(90deg, #11998e, #0b6e4f);
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(17,153,142,0.35); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.side-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
.side-card h4 { font-size: 0.92rem; color: #333; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.side-card h4 i { color: #11998e; }
.ring-wrap { text-align: center; margin-bottom: 12px; }
.ring-label { font-size: 0.82rem; color: #777; margin-top: 6px; font-weight: 600; }
.log-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f6f4; font-size: 0.85rem; }
.log-row:last-child { border-bottom: none; }
.log-row .lk { color: #555; }
.log-row .lv { color: #11998e; font-weight: 600; font-size: 0.75rem; white-space: nowrap; }
.field-time { display: flex; justify-content: space-between; font-size: 0.8rem; padding: 7px 0; border-bottom: 1px dashed #eef2ef; }
.field-time .ft-name { color: #555; }
.field-time .ft-time { color: #999; font-size: 0.72rem; }
</style>

<div class="info-layout">
    <div>
        <form method="POST" action="{{ route('barangay.info.update') }}" enctype="multipart/form-data" id="infoForm">
            @csrf

            @foreach ($categories as $catName => $fieldKeys)
                <div class="panel">
                    <div class="panel-title"><i class="fas fa-folder-open"></i> {{ $catName }}</div>
                    <div class="form-grid">
                        @foreach ($fieldKeys as $key)
                            @php
                                $label = $labels[$key][0];
                                $icon = $labels[$key][1];
                                $type = $labels[$key][2];
                                $required = $labels[$key][3];
                                $value = $detail->$key ?? '';
                            @endphp
                            @if ($type === 'file')
                                <div class="fg fg-file">
                                    <label><i class="fas {{ $icon }}"></i> {{ $label }}</label>
                                    <div class="file-box">
                                        @if ($value)
                                            <img src="{{ uimg($value) }}" alt="{{ $label }}">
                                        @else
                                            <div style="color:#bbb;font-size:2rem;margin-bottom:8px;"><i class="fas {{ $icon }}"></i></div>
                                        @endif
                                        <div class="fb-label">{{ $value ? 'Current uploaded file' : 'No file uploaded yet' }}</div>
                                        <input type="file" name="data[{{ $key }}]" accept="image/*" class="fb-file">
                                    </div>
                                    <div class="hint">Upload a JPG, PNG, GIF or WEBP image.</div>
                                </div>
                            @elseif ($type === 'select')
                                <div class="fg">
                                    <label><i class="fas {{ $icon }}"></i> {{ $label }}</label>
                                    <select name="data[{{ $key }}]">
                                        @foreach ($riskOptions as $opt)
                                            <option value="{{ $opt }}" {{ (string) $value === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif ($type === 'textarea')
                                <div class="fg {{ in_array($key, ['ordinances', 'contact_info']) ? 'fg-wide' : '' }}" @if (in_array($key, ['ordinances', 'population_breakdown', 'contact_info'])) style="grid-column:1 / -1;" @endif>
                                    <label><i class="fas {{ $icon }}"></i> {{ $label }} @if ($required)<span class="req">*</span>@endif</label>
                                    <textarea name="data[{{ $key }}]" @if ($required) required @endif>{{ old("data.$key", is_string($value) ? $value : '') }}</textarea>
                                </div>
                            @else
                                <div class="fg">
                                    <label><i class="fas {{ $icon }}"></i> {{ $label }} @if ($required)<span class="req">*</span>@endif</label>
                                    <input type="{{ $type === 'number' ? 'number' : 'text' }}"
                                           name="data[{{ $key }}]"
                                           value="{{ old("data.$key", $value ?? '') }}"
                                           @if ($key === 'date_established') placeholder="e.g. 12-01-1987 or 1987-12-01" @endif
                                           @if ($key === 'website') placeholder="https://example.com" @endif
                                           @if ($required) required @endif>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="panel">
                <div class="panel-title"><i class="fas fa-phone-alt"></i> Emergency Contacts</div>
                <div class="form-grid">
                    @foreach ($contactLabels as $key => $label)
                        <div class="fg">
                            <label>{{ $label }}</label>
                            <input type="tel" name="contacts[{{ $key }}]" maxlength="11"
                                   placeholder="09XXXXXXXXX"
                                   value="{{ old("contacts.$key", ($contacts[$key] ?? '') ?: '') }}">
                        </div>
                    @endforeach
                </div>
                <div class="hint" style="margin-top:12px;"><i class="fas fa-info-circle"></i> Phone numbers must start with 09 and have 11 digits.</div>
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>

    <div>
        <div class="side-card">
            <h4><i class="fas fa-chart-ring"></i> Profile Completion</h4>
            <div class="ring-wrap">
                <div class="progress-ring" data-progress="{{ $progress }}">
                    <svg width="130" height="130" viewBox="0 0 130 130">
                        <circle cx="65" cy="65" r="52" fill="none" stroke="#e6efe9" stroke-width="10"></circle>
                        <circle class="ring-fg" cx="65" cy="65" r="52" fill="none" stroke="#11998e" stroke-width="10"
                                stroke-linecap="round" stroke-dasharray="326.7" stroke-dashoffset="326.7"></circle>
                    </svg>
                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <b style="font-size:1.5rem;color:#0b6e4f;">{{ $progress }}%</b>
                        <small style="color:#888;">filled</small>
                    </div>
                </div>
                <div class="ring-label">{{ $loadedCount }} of {{ $totalFields }} fields completed</div>
            </div>
        </div>

        <div class="side-card">
            <h4><i class="fas fa-clock"></i> Last Updated Fields</h4>
            @forelse ($fieldTimes as $field => $time)
                @php $name = $labels[$field][0] ?? $field; @endphp
                <div class="field-time">
                    <span class="ft-name">{{ $name }}</span>
                    <span class="ft-time">{{ fmtTime($time) }}</span>
                </div>
            @empty
                <p class="empty-note" style="color:#999;font-size:0.85rem;">No fields updated yet.</p>
            @endforelse
        </div>

        <div class="side-card">
            <h4><i class="fas fa-history"></i> Recent Activity Log</h4>
            @forelse ($recentLog as $entry)
                @php $name = $labels[$entry->field_name][0] ?? $entry->field_name; @endphp
                <div class="log-row">
                    <span class="lk">{{ $name }}</span>
                    <span class="lv">{{ fmtTime($entry->edited_at) }}</span>
                </div>
            @empty
                <p class="empty-note" style="color:#999;font-size:0.85rem;">No activity recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
var ringFg = document.querySelector('.ring-fg');
if (ringFg) {
    var progress = parseInt(ringFg.parentElement.parentElement.dataset.progress || '0', 10);
    var circ = 326.7;
    ringFg.style.strokeDashoffset = circ - (circ * progress / 100);
}
</script>
@endpush