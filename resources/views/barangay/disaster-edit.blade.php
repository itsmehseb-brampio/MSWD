@extends('layouts.barangay')

@section('title', 'Edit Report - ' . ($report->format_no ?? ''))
@section('headerTitle', 'Edit Report - ' . ($report->format_no ?? ''))

@section('head')
<style>
.edit-wrap { max-width: 900px; }
.edit-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.edit-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    padding-bottom: 14px;
    border-bottom: 2px solid #eef2ef;
    margin-bottom: 24px;
}
.badge { padding: 4px 13px; border-radius: 18px; font-size: 0.74rem; font-weight: 700; color: #fff; }
.badge-approved { background: #28a745; }
.badge-pending { background: #fd7e14; }
.badge-declined { background: #dc3545; }
.badge-reedit { background: #6f42c1; }
.back-link { font-size: 0.85rem; color: #11998e; font-weight: 600; }
.fg { margin-bottom: 18px; }
.fg label { display: block; font-size: 0.86rem; font-weight: 600; color: #444; margin-bottom: 7px; }
.fg .req { color: #dc3545; }
.fg input[type="text"], .fg input[type="number"], .fg textarea {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #e2e8e4;
    border-radius: 9px;
    font-size: 0.9rem;
    font-family: inherit;
    color: #333;
}
.fg input:focus, .fg textarea:focus { outline: none; border-color: #11998e; box-shadow: 0 0 0 3px rgba(17,153,142,0.12); }
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
.photo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
@media (max-width: 700px) { .photo-grid { grid-template-columns: repeat(2, 1fr); } }
.photo-cell { text-align: center; }
.photo-cell img { width: 100%; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 6px; max-height: 100px; object-fit: cover; }
.photo-cell .no-photo { border: 1px dashed #ccc; border-radius: 6px; padding: 20px; margin-bottom: 6px; color: #ccc; font-size: 11px; }
.photo-cell input[type="file"] { font-size: 11px; width: 100%; }
.section-hr { margin-top: 25px; padding-top: 20px; border-top: 2px solid #e8e8e8; }
.section-hr h4 { font-size: 0.85rem; color: #11998e; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
.b2b-box { text-align: center; }
.b2b-box img { max-width: 150px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 6px; }
.b2b-box .no-photo { border: 1px dashed #ccc; border-radius: 6px; padding: 20px; display: inline-block; color: #ccc; font-size: 12px; margin-bottom: 6px; }
.b2b-box input[type="file"] { font-size: 11px; }
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

<div class="edit-wrap">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <a class="back-link" href="{{ route('barangay.disaster.history') }}"><i class="fas fa-arrow-left"></i> Back to History</a>
        <span class="badge badge-{{ strtolower($report->status ?? 'pending') }}">{{ ucfirst($report->status ?? 'Pending') }}</span>
    </div>

    <div class="edit-card">
        <div class="edit-head">
            <h3 style="font-size:1.05rem;color:#333;display:flex;align-items:center;gap:9px;"><i class="fas fa-edit" style="color:#11998e;"></i> Edit Report</h3>
            @if ($isReedit)
                <span class="badge badge-reedit"><i class="fas fa-redo"></i> For Re-edit</span>
            @endif
        </div>

        <form method="POST" action="{{ $isReedit ? route('barangay.disaster.update', ['report' => $report->report_id]) . '?reedit=1' : route('barangay.disaster.update', ['report' => $report->report_id]) }}" enctype="multipart/form-data">
            @csrf

            <div class="fg">
                <label>Type of Disaster <span class="req">*</span></label>
                <div class="radio-group">
                    @php $disasterTypes = ['Typhoon', 'Flood', 'Earthquake', 'Fire', 'Landslide', 'Volcanic Eruption', 'Storm Surge', 'Other']; @endphp
                    @foreach ($disasterTypes as $dt)
                        <label class="radio-option">
                            <input type="radio" name="disaster_type" value="{{ $dt }}" {{ ($report->disaster_type ?? '') === $dt ? 'checked' : '' }} required>
                            {{ $dt }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="fg">
                <label>Name of Household Head <span class="req">*</span></label>
                <input type="text" name="household_head" value="{{ $report->household_head }}" required>
            </div>

            <div class="fg">
                <label>Number of Family Members <span class="req">*</span></label>
                <input type="number" name="family_members" value="{{ $report->family_members }}" required>
            </div>

            <div class="fg">
                <label>Full Address <span class="req">*</span></label>
                <input type="text" name="full_address" value="{{ $report->full_address }}" required>
            </div>

            <div class="fg">
                <label>Housing Type <span class="req">*</span></label>
                <div class="radio-group">
                    @php $housingTypes = ['Light Materials', 'Semi-Concrete', 'Fully Concrete']; @endphp
                    @foreach ($housingTypes as $ht)
                        <label class="radio-option">
                            <input type="radio" name="housing_type" value="{{ $ht }}" {{ ($report->housing_type ?? '') === $ht ? 'checked' : '' }} required>
                            {{ $ht }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="fg">
                <label>Extent of Damage <span class="req">*</span></label>
                <div class="radio-group">
                    @php $damageExtents = ['Partially Damaged', 'Totally Damaged']; @endphp
                    @foreach ($damageExtents as $de)
                        @php $val = $de === 'Partially Damaged' ? 'Partially' : 'Totally'; @endphp
                        <label class="radio-option">
                            <input type="radio" name="damage_extent" value="{{ $de }}" {{ ($report->damage_extent ?? '') === $val ? 'checked' : '' }} required>
                            {{ $de }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="fg">
                <label>Description / Remarks</label>
                <textarea name="description" rows="4">{{ $report->description }}</textarea>
            </div>

            <div class="section-hr">
                <h4><i class="fas fa-images"></i> Damage Photos (leave blank to keep current)</h4>
                <div class="photo-grid">
                    @for ($i = 1; $i <= 4; $i++)
                        @php $pic = $report->{'pic' . $i} ?? null; @endphp
                        <div class="photo-cell">
                            @if ($pic)
                                <img src="{{ uimg($pic) }}" alt="Photo {{ $i }}">
                            @else
                                <div class="no-photo">No photo</div>
                            @endif
                            <input type="file" name="pic{{ $i }}" accept="image/*">
                        </div>
                    @endfor
                </div>
            </div>

            <div class="section-hr">
                <h4><i class="fas fa-id-card"></i> B2B ID Photo</h4>
                <div class="b2b-box">
                    @if ($report->b2b_id)
                        <img src="{{ uimg($report->b2b_id) }}" alt="B2B ID">
                    @else
                        <div class="no-photo">No B2B ID</div>
                    @endif
                    <div><input type="file" name="b2b_id" accept="image/*"></div>
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

            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>
</div>

@endsection