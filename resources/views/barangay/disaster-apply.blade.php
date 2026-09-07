@extends('layouts.barangay')

@section('title', 'Apply Disaster Report')
@section('headerTitle', 'Apply Disaster Report')

@section('head')
<style>
.apply-wrap { max-width: 900px; }
.closed-banner {
    background: #fdeaea;
    border: 1px solid #f5c2c2;
    color: #a71d1d;
    border-radius: 12px;
    padding: 20px 22px;
    font-size: 0.92rem;
    margin-bottom: 18px;
}
.success-banner {
    background: #e6f7ee;
    border: 1px solid #b5e6c7;
    color: #0b6e4f;
    border-radius: 12px;
    padding: 26px 22px;
    text-align: center;
}
.success-banner i { font-size: 2.4rem; display: block; margin-bottom: 10px; color: #28a745; }
.set-card {
    background: #fff;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.set-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    padding-bottom: 14px;
    border-bottom: 2px solid #eef2ef;
    margin-bottom: 22px;
}
.set-head h3 { font-size: 1.05rem; color: #333; display: flex; align-items: center; gap: 9px; }
.set-head h3 i { color: #11998e; }
.set-sub { font-size: 0.84rem; color: #777; margin-top: 6px; display: flex; align-items: center; gap: 6px; }
.fg { margin-bottom: 18px; }
.fg label { display: block; font-size: 0.86rem; font-weight: 600; color: #444; margin-bottom: 7px; }
.fg .req { color: #dc3545; }
.fg input[type="text"], .fg input[type="number"], .fg textarea, .fg select {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #e2e8e4;
    border-radius: 9px;
    font-size: 0.9rem;
    font-family: inherit;
    color: #333;
}
.fg input:focus, .fg textarea:focus, .fg select:focus { outline: none; border-color: #11998e; box-shadow: 0 0 0 3px rgba(17,153,142,0.12); }
.fg textarea { min-height: 100px; resize: vertical; }
.radio-group { display: flex; flex-wrap: wrap; gap: 9px; }
.radio-option {
    display: flex;
    align-items: center;
    gap: 7px;
    border: 1.5px solid #e2e8e4;
    border-radius: 9px;
    padding: 9px 14px;
    font-size: 0.86rem;
    cursor: pointer;
    color: #444;
    transition: all 0.15s;
}
.radio-option:has(input:checked) { border-color: #11998e; background: #eafaf6; color: #0b6e4f; font-weight: 600; }
.hazard-select-note { font-size: 0.76rem; color: #999; margin-top: 5px; }
.upload-box {
    border: 1.5px dashed #cdd8d1;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    color: #888;
    font-size: 0.82rem;
    background: #fbfdfc;
    transition: border-color 0.15s, background 0.15s;
    position: relative;
    min-height: 110px;
}
.upload-box:hover { border-color: #11998e; background: #f0fbf7; }
.upload-box i { font-size: 1.6rem; color: #b6c6bf; display: block; margin-bottom: 6px; }
.upload-box.has-file i { display: none; }
.upload-box .upload-preview {
    max-width: 100%;
    max-height: 150px;
    border-radius: 8px;
    margin-bottom: 8px;
    object-fit: cover;
}
.upload-box input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}
.photo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
@media (max-width: 700px) { .photo-grid { grid-template-columns: repeat(2, 1fr); } }
.form-section-title {
    font-size: 0.9rem;
    color: #11998e;
    border-bottom: 2px solid #eafaf6;
    padding-bottom: 8px;
    margin: 26px 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.b2b-section { margin-top: 22px; }
.b2b-single { max-width: 210px; }
.b2b-id-box {
    border: 1.5px dashed #cdd8d1;
    border-radius: 10px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    background: #fbfdfc;
    position: relative;
}
.b2b-id-box i { font-size: 1.7rem; color: #b6c6bf; display: block; margin-bottom: 6px; }
.b2b-id-box span { font-size: 0.78rem; color: #888; }
.b2b-id-box img { max-width: 150px; border-radius: 8px; }
.b2b-id-box input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.sig-row { display: flex; gap: 30px; justify-content: center; margin: 28px 0 22px; flex-wrap: wrap; }
.sig-approved { text-align: center; min-width: 200px; }
.sig-approved-line { border-bottom: 1.5px solid #999; margin-bottom: 10px; }
.sig-approved-label { font-size: 0.9rem; font-weight: 700; color: #333; }
.sig-approved-sub { font-size: 0.78rem; color: #888; margin-top: 3px; }
.btn-submit {
    display: block;
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(90deg, #11998e, #0b6e4f);
    color: #fff;
    font-size: 0.98rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}
.btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(17,153,142,0.35); }
</style>
@endsection

@section('content')

<div class="apply-wrap">
    @if (!$isOpen)
        <div class="closed-banner">
            <i class="fas fa-exclamation-circle"></i>
            <b>Disaster reporting is currently closed.</b> The Barangay Captain has not opened the submission window for new disaster reports. Please check back later.
        </div>
    @else
        <div class="set-card">
            <div class="set-head">
                <div>
                    <h3><i class="fas fa-file-alt"></i> New Disaster Report</h3>
                    <div class="set-sub">
                        <i class="fas fa-hashtag"></i> Report No: <b>{{ $formatNo }}</b>
                    </div>
                </div>
                <div style="text-align:right;font-size:0.82rem;color:#777;">
                    <div><i class="fas fa-building"></i> {{ $barangay->barangay_name }}</div>
                    <div><i class="fas fa-calendar-day"></i> {{ now()->format('F j, Y') }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('barangay.disaster.store') }}" enctype="multipart/form-data" onsubmit="return validateForm()">
                @csrf

                @foreach ($fields as $f)
                    @php
                        $name = $f->field_name;
                        $label = $f->field_label;
                        $req = $f->is_required ? '<span class="req">*</span>' : '';
                        $reqAttr = $f->is_required ? 'required' : '';
                        $options = array_filter(array_map('trim', explode('|', (string) $f->field_options)));
                        $skip = str_starts_with($name, 'pic') || str_starts_with($name, 'b2b');
                    @endphp
                    @continue($skip)

                    @if ($f->field_type === 'radio')
                        <div class="fg">
                            <label>{{ $label }} {!! $req !!}</label>
                            <div class="radio-group">
                                @foreach ($options as $opt)
                                    <label class="radio-option">
                                        <input type="radio" name="{{ $name }}" value="{{ $opt }}" {{ $reqAttr }}>
                                        {{ $opt }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($f->field_type === 'textarea')
                        <div class="fg">
                            <label>{{ $label }} {!! $req !!}</label>
                            <textarea name="{{ $name }}" placeholder="Enter {{ $label }}..." {{ $reqAttr }}></textarea>
                        </div>
                    @elseif ($f->field_type === 'select')
                        <div class="fg">
                            <label>{{ $label }} {!! $req !!}</label>
                            <select name="{{ $name }}" {{ $reqAttr }}>
                                <option value="">Select {{ $label }}...</option>
                                @foreach ($options as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif ($f->field_type === 'file')
                        <div class="fg">
                            <label>{{ $label }} {!! $req !!}</label>
                            <div class="upload-box" id="preview_{{ $name }}">
                                <i class="fas fa-camera"></i>
                                <span>Upload {{ $label }}...</span>
                                <input type="file" name="{{ $name }}" accept="image/*" {{ $reqAttr }} onchange="previewImg(this, '{{ $name }}')">
                            </div>
                        </div>
                    @else
                        <div class="fg">
                            <label>{{ $label }} {!! $req !!}</label>
                            <input type="{{ $f->field_type }}" name="{{ $name }}" placeholder="Enter {{ $label }}..." {{ $reqAttr }}>
                        </div>
                    @endif
                @endforeach

                <div class="form-section-title"><i class="fas fa-images"></i> Damage Photos</div>
                <div class="photo-grid">
                    @for ($i = 1; $i <= 4; $i++)
                        <div class="upload-box" id="preview_pic{{ $i }}">
                            <i class="fas fa-camera"></i>
                            <span>Upload Photo {{ $i }}</span>
                            <input type="file" name="pic{{ $i }}" accept="image/*" onchange="previewImg(this, 'pic{{ $i }}')">
                        </div>
                    @endfor
                </div>

                <div class="b2b-section">
                    <div class="form-section-title"><i class="fas fa-id-card"></i> B2B ID</div>
                    <div class="b2b-single">
                        <div class="b2b-id-box" id="preview_b2b_id1">
                            <i class="fas fa-camera"></i>
                            <span>Upload ID Photo</span>
                            <input type="file" name="b2b_id1" accept="image/*" onchange="previewB2B(this, 'b2b_id1')">
                        </div>
                    </div>

                    <div class="sig-row">
                        <div class="sig-approved">
                            <div class="sig-approved-line"></div>
                            <div class="sig-approved-label">Approved by: {{ $details->captain_name ?? 'Punong Barangay' }}</div>
                            <div class="sig-approved-sub">Punong Barangay</div>
                        </div>
                        <div class="sig-approved">
                            <div class="sig-approved-line"></div>
                            <div class="sig-approved-label">Processed by: {{ $details->secretary_name ?? 'Barangay Secretary' }}</div>
                            <div class="sig-approved-sub">Barangay Secretary</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Report</button>
            </form>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function previewImg(input, name) {
    var box = document.getElementById('preview_' + name);
    var existing = box.querySelector('.upload-preview');
    if (existing) existing.remove();
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.createElement('img');
            img.className = 'upload-preview';
            img.src = e.target.result;
            box.prepend(img);
            box.classList.add('has-file');
            var icon = box.querySelector('i');
            if (icon) icon.style.display = 'none';
            var span = box.querySelector('span');
            if (span) span.textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function previewB2B(input, id) {
    var box = document.getElementById('preview_' + id);
    var existing = box.querySelector('img');
    if (existing) existing.remove();
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.createElement('img');
            img.src = e.target.result;
            box.prepend(img);
            box.classList.add('has-file');
            var icon = box.querySelector('i');
            if (icon) icon.style.display = 'none';
            var span = box.querySelector('span');
            if (span) span.textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function validateForm() {
    var required = document.querySelectorAll('[required]');
    for (var i = 0; i < required.length; i++) {
        var el = required[i];
        if (el.type === 'radio') {
            var checked = document.querySelectorAll('input[name="' + el.name + '"]:checked');
            if (checked.length === 0) {
                var label = el.closest('.fg') ? el.closest('.fg').querySelector('label') : null;
                alert('Please select ' + (label ? label.textContent.replace('*', '').trim() : 'this option'));
                return false;
            }
        }
    }
    return true;
}
</script>
@endpush