@extends('layouts.admin')
@section('title', 'Disaster Report Format')

@push('head')
<style>
.toast{position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:14px 24px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:9999;display:flex;align-items:center;gap:10px;animation:slideIn 0.3s ease;}
@keyframes slideIn{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}
.two-panel{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;}
@media(max-width:1100px){.two-panel{grid-template-columns:1fr;}}
.panel{background:white;border-radius:14px;box-shadow:0 4px 12px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
.panel-header{padding:14px 20px;color:white;font-weight:700;font-size:1rem;display:flex;align-items:center;gap:8px;}
.panel-header.builder{background:linear-gradient(90deg,#0072C6,#005999);}
.panel-header.preview{background:linear-gradient(90deg,#28a745,#218838);}
.panel-body{padding:20px;max-height:calc(100vh - 160px);overflow-y:auto;}
.add-form{background:#f8fbff;border:2px solid #e0e0e0;border-radius:10px;padding:16px;margin-bottom:18px;}
.add-form h4{font-size:0.85rem;color:#333;margin-bottom:12px;display:flex;align-items:center;gap:6px;}
.builder-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.fg{margin-bottom:0;}
.fg label{display:block;font-weight:600;color:#555;margin-bottom:3px;font-size:0.75rem;}
.fg input,.fg select,.fg textarea{width:100%;padding:8px 10px;border:2px solid #e0e0e0;border-radius:6px;font-size:0.82rem;font-family:'Segoe UI',sans-serif;transition:border-color 0.3s;}
.fg input:focus,.fg select:focus{outline:none;border-color:#0072C6;}
.fg-full{grid-column:1/-1;}
.fg-check{display:flex;align-items:center;gap:5px;margin-top:6px;}
.fg-check input{accent-color:#0072C6;}
.fg-check label{font-size:0.75rem;color:#555;margin:0;}
.btn-add{padding:8px 18px;background:#28a745;color:white;border:none;border-radius:6px;font-size:0.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all 0.2s;margin-top:10px;width:auto;}
.btn-add:hover{background:#218838;}
.fields-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.fields-header h4{font-size:0.9rem;color:#333;}
.fields-count{background:#0072C6;color:white;padding:2px 10px;border-radius:20px;font-size:0.72rem;font-weight:600;}
.field-list{list-style:none;padding:0;}
.field-item{display:flex;align-items:center;gap:10px;padding:10px 12px;background:white;border:1.5px solid #e8e8e8;border-radius:8px;margin-bottom:6px;transition:all 0.2s;}
.field-item:hover{border-color:#0072C6;box-shadow:0 1px 5px rgba(0,0,0,0.05);}
.field-num{width:24px;height:24px;border-radius:50%;background:#0072C6;color:white;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;flex-shrink:0;}
.field-info{flex:1;min-width:0;}
.field-info .fl{font-weight:600;color:#333;font-size:0.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.field-info .fm{font-size:0.68rem;color:#999;margin-top:1px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.field-type-badge{display:inline-block;padding:1px 6px;border-radius:3px;font-size:0.58rem;font-weight:700;text-transform:uppercase;}
.type-text{background:#e3f2fd;color:#1565c0;}
.type-number{background:#f3e5f5;color:#7b1fa2;}
.type-textarea{background:#e8f5e9;color:#2e7d32;}
.type-select{background:#fff3e0;color:#e65100;}
.type-radio{background:#fce4ec;color:#c62828;}
.type-file{background:#e0f7fa;color:#00695c;}
.type-date{background:#f5f5f5;color:#616161;}
.req-badge{display:inline-block;padding:1px 5px;border-radius:3px;font-size:0.58rem;font-weight:700;background:#dc3545;color:white;}
.opt-badge{display:inline-block;padding:1px 5px;border-radius:3px;font-size:0.58rem;font-weight:700;background:#6c757d;color:white;}
.field-actions{display:flex;gap:4px;flex-shrink:0;}
.btn-icon{width:28px;height:28px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;font-size:0.75rem;}
.btn-edit{background:#e3f2fd;color:#1565c0;}
.btn-edit:hover{background:#bbdefb;}
.btn-delete{background:#fce4ec;color:#c62828;}
.btn-delete:hover{background:#f8bbd0;}
.btn-move{background:#f5f5f5;color:#666;}
.btn-move:hover{background:#e0e0e0;}
.empty-state{text-align:center;padding:30px;color:#999;}
.empty-state i{font-size:2rem;margin-bottom:8px;display:block;}
.preview-note{text-align:center;padding:8px 14px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;font-size:0.78rem;color:#856404;margin-bottom:15px;display:flex;align-items:center;justify-content:center;gap:6px;}
.pf-group{margin-bottom:12px;}
.pf-group label{display:block;font-weight:600;color:#444;margin-bottom:4px;font-size:0.82rem;}
.pf-group label .req{color:#dc3545;margin-left:2px;}
.pf-group input,.pf-group select,.pf-group textarea{width:100%;padding:9px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.85rem;font-family:'Segoe UI',sans-serif;background:#f8f9fa;color:#999;}
.pf-group textarea{resize:none;min-height:60px;}
.pf-radio-group{display:flex;gap:8px;flex-wrap:wrap;}
.pf-radio{display:flex;align-items:center;gap:5px;padding:7px 14px;border:2px solid #e0e0e0;border-radius:8px;font-size:0.8rem;color:#666;background:#f8f9fa;}
.pf-radio input{accent-color:#0072C6;}
.access-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px;}
.access-card{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-radius:10px;border:2px solid #e8e8e8;transition:all 0.2s;}
.access-card.open{border-color:#00D09C;background:#f0fdf4;}
.access-card.closed{border-color:#dc3545;background:#fff5f5;}
.access-name{font-weight:600;font-size:0.9rem;color:#333;display:flex;align-items:center;gap:6px;}
.access-status{font-size:0.75rem;font-weight:700;margin-top:2px;}
.access-card.open .access-status{color:#00D09C;}
.access-card.closed .access-status{color:#dc3545;}
.toggle-switch{position:relative;width:44px;height:24px;flex-shrink:0;}
.toggle-switch input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;border-radius:24px;transition:0.3s;}
.toggle-slider::before{content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:0.3s;}
.toggle-switch input:checked + .toggle-slider{background:#00D09C;}
.toggle-switch input:checked + .toggle-slider::before{transform:translateX(20px);}
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;}
.modal-overlay.active{display:flex;}
.modal{background:white;border-radius:14px;width:100%;max-width:480px;box-shadow:0 10px 40px rgba(0,0,0,0.3);animation:modalIn 0.3s ease;}
@keyframes modalIn{from{transform:scale(0.9);opacity:0;}to{transform:scale(1);opacity:1;}}
.modal-header{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid #e8e8e8;}
.modal-header h3{font-size:0.95rem;color:#333;}
.modal-close{background:none;border:none;font-size:1.2rem;cursor:pointer;color:#999;padding:4px 8px;border-radius:6px;}
.modal-body{padding:18px;}
.modal-body .fg{margin-bottom:10px;}
.modal-footer{display:flex;justify-content:flex-end;gap:8px;padding:12px 20px;border-top:1px solid #e8e8e8;}
.btn-cancel{padding:7px 16px;background:#f8f9fa;color:#666;border:2px solid #ddd;border-radius:6px;font-size:0.82rem;font-weight:600;cursor:pointer;}
.btn-save{padding:7px 16px;background:#0072C6;color:white;border:none;border-radius:6px;font-size:0.82rem;font-weight:600;cursor:pointer;}
.btn-save:hover{background:#005999;}
</style>
@endpush

@section('content')
<div class="two-panel">
    <div class="panel">
        <div class="panel-header builder"><i class="fas fa-tools"></i> Format Builder</div>
        <div class="panel-body">
            <div class="add-form">
                <h4><i class="fas fa-plus-circle" style="color:#28a745;"></i> Add New Field</h4>
                <form method="POST" action="{{ route('admin.disaster.format.store') }}">
                    @csrf
                    <input type="hidden" name="action" value="add_field">
                    <div class="builder-row">
                        <div class="fg">
                            <label>Field Label</label>
                            <input type="text" name="field_label" placeholder="e.g. Name of Household Head" required>
                        </div>
                        <div class="fg">
                            <label>Field Type</label>
                            <select name="field_type" id="newType" onchange="toggleNewOpts()">
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="textarea">Text Area</option>
                                <option value="select">Dropdown</option>
                                <option value="radio">Radio Buttons</option>
                                <option value="file">File/Image</option>
                                <option value="date">Date</option>
                            </select>
                        </div>
                        <div class="fg fg-full" id="newOptsRow" style="display:none;">
                            <label>Options (one per line)</label>
                            <textarea name="field_options" rows="2" placeholder="Option 1&#10;Option 2"></textarea>
                        </div>
                        <div class="fg">
                            <div class="fg-check">
                                <input type="checkbox" name="is_required" checked id="newReq">
                                <label for="newReq">Required</label>
                            </div>
                        </div>
                        <button type="submit" class="btn-add"><i class="fas fa-plus"></i> Add</button>
                    </div>
                </form>
            </div>

            <div class="fields-header">
                <h4><i class="fas fa-list-ol" style="color:#0072C6;"></i> Fields</h4>
                <span class="fields-count">{{ count($fields) }}</span>
            </div>

            @if ($fields->isEmpty())
                <div class="empty-state"><i class="fas fa-inbox"></i><p>No fields yet.</p></div>
            @else
                <ul class="field-list">
                    @foreach ($fields as $idx => $f)
                    <li class="field-item" data-id="{{ $f->id }}">
                        <span class="field-num">{{ $idx + 1 }}</span>
                        <div class="field-info">
                            <div class="fl">{{ $f->field_label }}</div>
                            <div class="fm">
                                <span class="field-type-badge type-{{ $f->field_type }}">{{ $f->field_type }}</span>
                                @if ($f->is_required)<span class="req-badge">Required</span>@else<span class="opt-badge">Optional</span>@endif
                            </div>
                        </div>
                        <div class="field-actions">
                            <button type="button" class="btn-icon btn-move" onclick="moveField({{ $f->id }},'up')" title="Up"><i class="fas fa-arrow-up"></i></button>
                            <button type="button" class="btn-icon btn-move" onclick="moveField({{ $f->id }},'down')" title="Down"><i class="fas fa-arrow-down"></i></button>
                            <button type="button" class="btn-icon btn-edit" onclick="editField({{ $f->id }}, @json($f))" title="Edit"><i class="fas fa-edit"></i></button>
                            <form method="POST" action="{{ route('admin.disaster.format.store') }}" style="display:inline;" onsubmit="return confirm('Delete?')">
                                @csrf
                                <input type="hidden" name="action" value="delete_field">
                                <input type="hidden" name="field_id" value="{{ $f->id }}">
                                <button class="btn-icon btn-delete" type="submit" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="panel">
        <div class="panel-header preview"><i class="fas fa-eye"></i> Format Preview</div>
        <div class="panel-body">
            <div class="preview-note"><i class="fas fa-info-circle"></i> This is a preview. The form is read-only and cannot be submitted here.</div>
            @if ($fields->isEmpty())
                <div class="empty-state"><i class="fas fa-inbox"></i><p>Add fields on the left to see the preview.</p></div>
            @else
                <div style="background:white;border:2px solid #e0e0e0;border-radius:12px;padding:20px;">
                    <div style="background:linear-gradient(135deg,#0072C6,#005999);color:white;border-radius:10px;padding:16px 20px;margin-bottom:20px;text-align:center;">
                        <h3 style="font-size:1.1rem;margin-bottom:4px;"><i class="fas fa-file-invoice"></i> Disaster Report Form</h3>
                        <p style="font-size:0.78rem;opacity:0.8;">Municipality of Malilipot - DSWD</p>
                    </div>
                    @foreach ($fields as $f)
                        @php
                            $opts = $f->field_options ? explode("\n", $f->field_options) : [];
                            $req = $f->is_required ? '<span class="req">*</span>' : '';
                        @endphp
                        @if ($f->field_type === 'radio')
                            <div class="pf-group">
                                <label>{!! $f->field_label !!} {!! $req !!}</label>
                                <div class="pf-radio-group">
                                    @foreach ($opts as $o)
                                        <label class="pf-radio"><input type="radio" disabled> {{ trim($o) }}</label>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($f->field_type === 'textarea')
                            <div class="pf-group">
                                <label>{!! $f->field_label !!} {!! $req !!}</label>
                                <textarea placeholder="{{ $f->field_label }}" disabled></textarea>
                            </div>
                        @elseif ($f->field_type === 'select')
                            <div class="pf-group">
                                <label>{!! $f->field_label !!} {!! $req !!}</label>
                                <select disabled>
                                    <option>-- Select {{ $f->field_label }} --</option>
                                    @foreach ($opts as $o)
                                        <option>{{ trim($o) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif ($f->field_type === 'file')
                            <div class="pf-group">
                                <label>{!! $f->field_label !!} {!! $req !!}</label>
                                <div style="border:2px dashed #ccc;border-radius:8px;padding:12px;text-align:center;background:#f8f9fa;">
                                    <i class="fas fa-cloud-upload-alt" style="font-size:1.2rem;color:#ccc;"></i>
                                    <p style="font-size:0.72rem;color:#aaa;margin-top:4px;">Click to upload</p>
                                </div>
                            </div>
                        @else
                            <div class="pf-group">
                                <label>{!! $f->field_label !!} {!! $req !!}</label>
                                <input type="{{ $f->field_type }}" placeholder="{{ $f->field_label }}" disabled>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header" style="background:linear-gradient(90deg,#6f42c1,#5a32a3);"><i class="fas fa-door-open"></i> Barangay Submission Access</div>
    <div class="panel-body" style="max-height:none;">
        <p style="font-size:0.82rem;color:#666;margin-bottom:14px;">Toggle which barangays can submit disaster reports. <strong>Open</strong> = can apply, <strong>Closed</strong> = cannot apply.</p>
        <div class="access-grid">
            @foreach ($brgyList as $b)
            <div class="access-card {{ $b->disaster_open ? 'open' : 'closed' }}" id="brgy_{{ $b->barangay_id }}">
                <div class="access-info">
                    <div class="access-name"><i class="fas fa-building"></i> {{ $b->barangay_name }}</div>
                    <div class="access-status">{{ $b->disaster_open ? 'Open' : 'Closed' }}</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" {{ $b->disaster_open ? 'checked' : '' }} onchange="toggleAccess({{ $b->barangay_id }}, this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="modal-overlay" id="editModal" onclick="if(event.target===this)closeModal()">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fas fa-edit" style="color:#0072C6;"></i> Edit Field</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.disaster.format.store') }}">
            @csrf
            <input type="hidden" name="action" value="update_field">
            <input type="hidden" name="field_id" id="editId">
            <div class="modal-body">
                <div class="fg"><label>Field Label</label><input type="text" name="field_label" id="editLabel" required></div>
                <div class="fg"><label>Field Type</label>
                    <select name="field_type" id="editType" onchange="toggleEditOpts()">
                        <option value="text">Text</option><option value="number">Number</option><option value="textarea">Text Area</option>
                        <option value="select">Dropdown</option><option value="radio">Radio Buttons</option>
                        <option value="file">File/Image</option><option value="date">Date</option>
                    </select>
                </div>
                <div class="fg" id="editOptsRow" style="display:none;"><label>Options (one per line)</label><textarea name="field_options" rows="3" id="editOptions" placeholder="Option 1&#10;Option 2"></textarea></div>
                <div class="fg"><div class="fg-check"><input type="checkbox" name="is_required" id="editRequired" checked><label for="editRequired">Required</label></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleNewOpts(){document.getElementById('newOptsRow').style.display=document.getElementById('newType').value==='select'||document.getElementById('newType').value==='radio'?'block':'none';}
function toggleEditOpts(){const t=document.getElementById('editType').value;document.getElementById('editOptsRow').style.display=t==='select'||t==='radio'?'block':'none';}
function editField(id,d){
    document.getElementById('editId').value=id;
    document.getElementById('editLabel').value=d.field_label;
    document.getElementById('editType').value=d.field_type;
    document.getElementById('editOptions').value=d.field_options||'';
    document.getElementById('editRequired').checked=!!parseInt(d.is_required);
    toggleEditOpts();
    document.getElementById('editModal').classList.add('active');
}
function closeModal(){document.getElementById('editModal').classList.remove('active');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});
function moveField(id,dir){
    const token=document.querySelector('meta[name=csrf-token]').content;
    const form=document.createElement('form');form.method='POST';
    form.innerHTML='<input type="hidden" name="_token" value="'+token+'"><input type="hidden" name="action" value="reorder">';
    const items=document.querySelectorAll('.field-item');const order=[];
    items.forEach(item=>order.push(parseInt(item.dataset.id)));
    const idx=order.indexOf(id);
    if(dir==='up'&&idx>0)[order[idx],order[idx-1]]=[order[idx-1],order[idx]];
    else if(dir==='down'&&idx<order.length-1)[order[idx],order[idx+1]]=[order[idx+1],order[idx]];
    order.forEach((fid,i)=>{const inp=document.createElement('input');inp.type='hidden';inp.name='field_order['+i+']';inp.value=fid;form.appendChild(inp);});
    document.body.appendChild(form);form.submit();
}
function toggleAccess(bid,isOpen){
    const fd=new FormData();
    fd.append('action','toggle_disaster');
    fd.append('barangay_id',bid);
    fd.append('disaster_open',isOpen?1:0);
    fd.append('_token',document.querySelector('meta[name=csrf-token]')?document.querySelector('meta[name=csrf-token]').content:'{{ csrf_token() }}');
    fetch('{{ route('admin.disaster.format.store') }}',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        const card=document.getElementById('brgy_'+bid);
        if(isOpen){card.classList.remove('closed');card.classList.add('open');}
        else{card.classList.remove('open');card.classList.add('closed');}
        card.querySelector('.access-status').textContent=isOpen?'Open':'Closed';
    });
}
</script>
@endpush
