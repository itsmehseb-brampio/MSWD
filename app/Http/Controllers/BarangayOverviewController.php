<?php

namespace App\Http\Controllers;

use App\Models\BarangayDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayOverviewController extends Controller
{
    public function index()
    {
        $barangay = Auth::guard('barangay')->user();
        $detail = BarangayDetail::where('barangay_id', $barangay->barangay_id)->first();

        $detailMeta = [
            'barangay_code' => ['Barangay Code', 'fas fa-qrcode', '#7c5cff'],
            'municipality' => ['Municipality', 'fas fa-city', '#0072C6'],
            'province' => ['Province', 'fas fa-map', '#00875a'],
            'region' => ['Region', 'fas fa-globe-asia', '#e8710a'],
            'zip_code' => ['ZIP Code', 'fas fa-hashtag', '#d93025'],
            'captain_name' => ['Punong Barangay / Captain', 'fas fa-user-tie', '#0072C6'],
            'councilors' => ['Sangguniang Barangay Members', 'fas fa-users', '#7c5cff'],
            'secretary_name' => ['Barangay Secretary', 'fas fa-user', '#00875a'],
            'treasurer_name' => ['Barangay Treasurer', 'fas fa-money-bill-alt', '#e8710a'],
            'barangay_hall_address' => ['Barangay Hall Address', 'fas fa-map-marker-alt', '#d93025'],
            'date_established' => ['Date Established', 'fas fa-calendar-alt', '#7c5cff'],
            'land_area' => ['Land Area', 'fas fa-arrows-alt-v', '#0072C6'],
            'population' => ['Population', 'fas fa-users', '#e8710a'],
            'households' => ['Households', 'fas fa-home', '#00875a'],
            'head_of_household' => ['Head of Household', 'fas fa-user-friends', '#7c5cff'],
            'website' => ['Website', 'fas fa-globe', '#0072C6'],
        ];

        $profile = [];
        foreach ($detailMeta as $key => $meta) {
            $value = $detail->$key ?? null;
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            $profile[] = [
                'label' => $meta[0],
                'icon' => $meta[1],
                'color' => $meta[2],
                'value' => $value,
            ];
        }

        $sectionKeys = [
            'hazard_description' => ['Hazard Description', 'fas fa-exclamation-triangle'],
            'evacuation_routes' => ['Evacuation Routes', 'fas fa-route'],
            'affected_areas' => ['Affected Areas', 'fas fa-map-marked-alt'],
            'boundaries' => ['Boundaries', 'fas fa-bezier-curve'],
            'streets' => ['Streets', 'fas fa-road'],
            'gps_coordinates' => ['GPS Coordinates', 'fas fa-satellite-dish'],
            'population_breakdown' => ['Population Breakdown', 'fas fa-chart-pie'],
            'ordinances' => ['Barangay Ordinances', 'fas fa-gavel'],
            'contact_info' => ['Contact Information', 'fas fa-address-book'],
        ];
        $sections = [];
        foreach ($sectionKeys as $key => [$label, $icon]) {
            $value = $detail->$key ?? null;
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            $sections[] = ['label' => $label, 'icon' => $icon, 'value' => $value];
        }

        $initials = '';
        foreach (preg_split('/\s+/', trim($barangay->barangay_name)) as $word) {
            if ($word === '') {
                continue;
            }
            $initials .= strtoupper($word[0]);
            if (strlen($initials) >= 2) {
                break;
            }
        }
        if ($initials === '') {
            $initials = strtoupper(substr($barangay->barangay_name, 0, 1) ?: 'B');
        }

        return view('barangay.overview', compact('barangay', 'detail', 'profile', 'sections', 'initials'));
    }
}