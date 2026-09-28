<?php

namespace App\Http\Controllers;

use App\Models\BarangayDetail;
use App\Models\DisasterReport;
use App\Models\MunicipalContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BarangayDashboardController extends Controller
{
    public function index()
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('view_dashboard')) {
            abort(403, 'You do not have permission to access the dashboard.');
        }
        $id = $barangay->barangay_id;

        $detail = BarangayDetail::where('barangay_id', $id)->first();

        $reports = DisasterReport::where('barangay_id', $id);
        $reportCount = (clone $reports)->count();
        $statusCounts = (clone $reports)->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')->pluck('total', 'status')->toArray();

        $damageTotally = (clone $reports)->where('damage_extent', 'Totally')->count();
        $damagePartially = (clone $reports)->where('damage_extent', 'Partially')->count();

        $myContacts = (array) (DB::table('barangay_contacts')->where('barangay_id', $id)->first() ?? []);
        $muniArr = MunicipalContact::first()?->toArray() ?? [];

        $hotlines = [
            'barangay_hall_phone' => ['Barangay Hall', 'fas fa-phone'],
            'barangay_chairman_phone' => ['Barangay Chairman', 'fas fa-user-tie'],
            'barangay_secretary_phone' => ['Barangay Secretary', 'fas fa-user'],
            'barangay_tanod_phone' => ['Barangay Tanod', 'fas fa-shield-alt'],
            'police_hotline' => ['Police (Barangay)', 'fas fa-shield'],
            'fire_hotline' => ['Fire (Barangay)', 'fas fa-fire-extinguisher'],
            'ngo_relief' => ['NGO / Relief', 'fas fa-hands-helping'],
            'fire_volunteers' => ['Fire Volunteers', 'fas fa-users'],
            'covid_hotline' => ['COVID-19 Hotline', 'fas fa-kit-medical'],
        ];
        $contacts = [];
        foreach ($hotlines as $key => [$label, $icon]) {
            $phone = trim((string) ($myContacts[$key] ?? ''));
            if ($phone === '') {
                $phone = trim((string) ($muniArr[$key] ?? ''));
            }
            if ($phone !== '') {
                $contacts[] = ['label' => $label, 'icon' => $icon, 'phone' => $phone];
            }
        }
        $emergencyActive = !empty($contacts);

        $riskLevel = $detail->risk_level ?? 'Low';
        $riskMeta = [
            'Low' => ['pct' => 25, 'color' => '#28a745', 'track' => '#d4edda', 'icon' => 'fa-smile', 'desc' => 'Low risk: minimal hazards identified in the barangay.'],
            'Medium' => ['pct' => 50, 'color' => '#fd7e14', 'track' => '#fff3cd', 'icon' => 'fa-meh', 'desc' => 'Medium risk: moderate hazards present that require attention.'],
            'High' => ['pct' => 75, 'color' => '#dc3545', 'track' => '#f8d7da', 'icon' => 'fa-frown', 'desc' => 'High risk: significant hazards present in the barangay.'],
            'Critical' => ['pct' => 100, 'color' => '#7b1a1a', 'track' => '#f5c6c6', 'icon' => 'fa-dizzy', 'desc' => 'Critical risk: severe hazard exposure, evacuate when advised.'],
        ];
        $riskCur = array_merge(['level' => $riskLevel], $riskMeta[$riskLevel] ?? $riskMeta['Low']);

        $status = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'reedit' => 0];
        foreach ($statusCounts as $k => $v) {
            if (array_key_exists($k, $status)) {
                $status[$k] = (int) $v;
            }
        }

        $damage = ['Totally' => $damageTotally, 'Partially' => $damagePartially];

        $chart = [
            'population' => (int) ($detail->population ?? 0),
            'households' => (int) ($detail->households ?? 0),
            'head' => $detail && $detail->head_of_household !== null ? (int) $detail->head_of_household : null,
            'status' => $status,
            'damage' => $damage,
        ];

        return view('barangay.dashboard', compact(
            'barangay', 'detail', 'reportCount', 'status', 'damage',
            'contacts', 'emergencyActive', 'riskCur', 'chart'
        ));
    }
}