@extends('layouts.admin')
@section('title', 'Municipal Contacts')

@php
$labels = [
    'city_hotline' => 'City Hall Hotline',
    'drrmo_hotline' => 'MDRRMO Hotline',
    'police_hotline' => 'Police Hotline',
    'fire_hotline' => 'Fire Department',
    'medical_services' => 'Medical Services',
    'hospital_emergency' => 'Hospital Emergency',
    'traffic_control' => 'Traffic Control',
    'power_emergency' => 'Power Emergency',
    'water_emergency' => 'Water Emergency',
    'ngo_relief' => 'NGO Relief',
    'fire_volunteers' => 'Fire Volunteers',
    'covid_hotline' => 'COVID-19 Hotline',
];
$icons = [
    'city_hotline' => 'fa-city', 'drrmo_hotline' => 'fa-shield-halved', 'police_hotline' => 'fa-shield',
    'fire_hotline' => 'fa-fire-extinguisher', 'medical_services' => 'fa-truck-medical',
    'hospital_emergency' => 'fa-hospital', 'traffic_control' => 'fa-traffic-light',
    'power_emergency' => 'fa-bolt', 'water_emergency' => 'fa-droplet',
    'ngo_relief' => 'fa-hand-holding-heart', 'fire_volunteers' => 'fa-hands-helping',
    'covid_hotline' => 'fa-virus',
];
@endphp

@section('content')
<div class="card">
    <div class="card-header"><h2><i class="fas fa-phone-alt"></i> Municipal Emergency Hotlines</h2></div>
    <div style="padding:25px;">
        <p style="color:#666;font-size:0.9rem;margin-bottom:20px;">These hotlines are displayed on barangay public profiles. Numbers accept 0-9 only.</p>
        <form method="POST" action="{{ route('admin.municipal.update') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;">
                @foreach ($fields as $f)
                <div class="form-group">
                    <label><i class="fas {{ $icons[$f] }}"></i> {{ $labels[$f] }}</label>
                    <input type="tel" name="{{ $f }}" value="{{ old($f, $contact->$f ?? '') }}" maxlength="11" pattern="\d*"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="09XXXXXXXXX">
                </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-blue"><i class="fas fa-save"></i> Save Municipal Contacts</button>
        </form>
    </div>
</div>
@endsection
