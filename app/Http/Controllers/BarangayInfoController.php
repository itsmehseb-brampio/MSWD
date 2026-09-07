<?php

namespace App\Http\Controllers;

use App\Models\BarangayDetail;
use App\Models\MunicipalContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BarangayInfoController extends Controller
{
    private const LABELS = [
        'barangay_code' => ['Barangay Code', 'fas fa-qrcode', 'text', true],
        'municipality' => ['Municipality', 'fas fa-city', 'text', true],
        'province' => ['Province', 'fas fa-map', 'text', true],
        'region' => ['Region', 'fas fa-globe-asia', 'text', true],
        'zip_code' => ['ZIP Code', 'fas fa-hashtag', 'number', true],
        'captain_name' => ['Punong Barangay / Captain', 'fas fa-user-tie', 'text', true],
        'councilors' => ['Sangguniang Barangay Members', 'fas fa-users', 'text', true],
        'secretary_name' => ['Barangay Secretary', 'fas fa-user', 'text', true],
        'treasurer_name' => ['Barangay Treasurer', 'fas fa-money-bill-alt', 'text', true],
        'contact_info' => ['Contact Information', 'fas fa-address-book', 'textarea', true],
        'population' => ['Population', 'fas fa-users', 'number', true],
        'households' => ['Households', 'fas fa-home', 'number', true],
        'head_of_household' => ['Head of Household', 'fas fa-user-friends', 'number', false],
        'population_breakdown' => ['Population Breakdown', 'fas fa-chart-pie', 'textarea', false],
        'boundaries' => ['Boundaries', 'fas fa-bezier-curve', 'textarea', false],
        'streets' => ['Streets', 'fas fa-road', 'textarea', false],
        'land_area' => ['Land Area', 'fas fa-arrows-alt-v', 'number', false],
        'gps_coordinates' => ['GPS Coordinates', 'fas fa-satellite-dish', 'textarea', false],
        'barangay_hall_address' => ['Barangay Hall Address', 'fas fa-map-marker-alt', 'text', true],
        'health_center' => ['Health Center', 'fas fa-hospital', 'text', false],
        'daycare_schools' => ['Daycare / Schools', 'fas fa-school', 'text', false],
        'community_centers' => ['Community Centers', 'fas fa-people-roof', 'text', false],
        'emergency_services' => ['Emergency Services', 'fas fa-ambulance', 'textarea', false],
        'date_established' => ['Date Established', 'fas fa-calendar-alt', 'text', true],
        'website' => ['Website', 'fas fa-globe', 'text', false],
        'ordinances' => ['Barangay Ordinances', 'fas fa-gavel', 'textarea', false],
        'logo' => ['Barangay Logo', 'fas fa-image', 'file', false],
        'hazard_map' => ['Hazard Map', 'fas fa-map', 'file', false],
        'hazard_description' => ['Hazard Description', 'fas fa-exclamation-triangle', 'textarea', false],
        'risk_level' => ['Risk Level', 'fas fa-shield', 'select', false],
        'evacuation_routes' => ['Evacuation Routes', 'fas fa-route', 'textarea', false],
        'affected_areas' => ['Affected Areas', 'fas fa-map-marked-alt', 'textarea', false],
    ];

    private const CATEGORIES = [
        'Identification' => ['barangay_code', 'municipality', 'province', 'region', 'zip_code'],
        'Leadership' => ['captain_name', 'councilors', 'secretary_name', 'treasurer_name'],
        'Demographics' => ['population', 'households', 'head_of_household', 'population_breakdown'],
        'Location' => ['barangay_hall_address', 'land_area', 'gps_coordinates', 'boundaries', 'streets'],
        'Facilities' => ['health_center', 'daycare_schools', 'community_centers', 'emergency_services'],
        'Other Information' => ['contact_info', 'date_established', 'website', 'ordinances', 'hazard_description', 'risk_level', 'evacuation_routes', 'affected_areas'],
    ];

    private const CONTACT_LABELS = [
        'barangay_hall_phone' => 'Barangay Hall',
        'barangay_chairman_phone' => 'Barangay Chairman',
        'barangay_secretary_phone' => 'Barangay Secretary',
        'barangay_tanod_phone' => 'Barangay Tanod',
        'police_hotline' => 'Police (Barangay)',
        'fire_hotline' => 'Fire (Barangay)',
        'ngo_relief' => 'NGO / Relief',
        'fire_volunteers' => 'Fire Volunteers',
        'covid_hotline' => 'COVID-19 Hotline',
    ];

    private const RISK_OPTIONS = ['Low', 'Medium', 'High', 'Critical'];

    public function index()
    {
        $barangay = Auth::guard('barangay')->user();
        $id = $barangay->barangay_id;

        $detail = BarangayDetail::where('barangay_id', $id)->first();
        $contacts = (array) (DB::table('barangay_contacts')->where('barangay_id', $id)->first() ?? []);
        $muni = MunicipalContact::first();

        $fieldTimes = DB::table('barangay_edit_log')
            ->where('barangay_id', $id)
            ->select('field_name', DB::raw('MAX(edited_at) as last_edit'))
            ->groupBy('field_name')
            ->orderByDesc('last_edit')
            ->get()
            ->pluck('last_edit', 'field_name');

        $recentLog = DB::table('barangay_edit_log')
            ->where('barangay_id', $id)
            ->orderByDesc('edited_at')
            ->limit(12)
            ->get();

        $loadedCount = 0;
        if ($detail) {
            foreach (array_keys(self::LABELS) as $key) {
                $value = $detail->$key ?? null;
                if ($value !== null && trim((string) $value) !== '') {
                    $loadedCount++;
                }
            }
        }
        $totalFields = count(self::LABELS);
        $progress = $totalFields > 0 ? (int) round(($loadedCount / $totalFields) * 100) : 0;

        return view('barangay.info', array_merge(compact(
            'barangay', 'detail', 'contacts', 'muni', 'fieldTimes',
            'recentLog', 'loadedCount', 'totalFields', 'progress'
        ), [
            'labels' => self::LABELS,
            'categories' => self::CATEGORIES,
            'contactLabels' => self::CONTACT_LABELS,
            'riskOptions' => self::RISK_OPTIONS,
        ]));
    }

    public function update(Request $request)
    {
        $barangay = Auth::guard('barangay')->user();
        $id = $barangay->barangay_id;

        $detail = BarangayDetail::firstOrNew(['barangay_id' => $id]);
        $values = $request->input('data', []);

        foreach (['population', 'households', 'land_area'] as $numeric) {
            if (array_key_exists($numeric, $values) && ($values[$numeric] === '' || $values[$numeric] === null)) {
                $values[$numeric] = null;
            }
        }

        if (!empty($values['date_established'])) {
            $values['date_established'] = $this->normalizeDate((string) $values['date_established']);
        }

        foreach (['logo', 'hazard_map'] as $fileField) {
            if ($request->hasFile('data.' . $fileField)) {
                $file = $request->file('data.' . $fileField);
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    $directory = public_path('uploads');
                    if (!is_dir($directory)) {
                        @mkdir($directory, 0755, true);
                    }
                    $fname = $fileField . '_' . $id . '_' . time() . '.' . $ext;
                    $file->move($directory, $fname);
                    $values[$fileField] = 'uploads/' . $fname;
                }
            }
        }

        foreach (array_keys(self::LABELS) as $key) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $new = $values[$key];
            $old = $detail->exists ? $detail->$key : null;
            if ((string) $old !== (string) $new) {
                DB::table('barangay_edit_log')->insert([
                    'barangay_id' => $id,
                    'field_name' => $key,
                    'edited_at' => now(),
                ]);
                $detail->{$key} = $new;
            }
        }

        $detail->last_updated = now();
        $detail->save();

        $contactInput = $request->input('contacts', []);
        $errors = [];
        $row = ['barangay_id' => $id];
        foreach (self::CONTACT_LABELS as $key => $label) {
            $raw = trim((string) ($contactInput[$key] ?? ''));
            $digits = preg_replace('/\D+/', '', $raw) ?? '';
            if ($raw !== '' && !preg_match('/^09\d{9}$/', $digits)) {
                $errors[] = $label;
            }
            $row[$key] = $digits !== '' ? $digits : null;
        }
        if ($errors) {
            return redirect()->back()->withInput()
                ->with('error', 'Invalid phone number for: ' . implode(', ', $errors) . ' (must start with 09 and have 11 digits).');
        }
        $row['last_updated'] = now();
        DB::table('barangay_contacts')->updateOrInsert(['barangay_id' => $id], $row);

        return redirect()->route('barangay.info')
            ->with('success', 'Barangay information and contacts saved successfully!');
    }

    private function normalizeDate(string $value): string
    {
        $formats = ['Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'd/m/Y', 'd-m-Y', 'm.d.Y', 'd.m.Y'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : $value;
    }
}