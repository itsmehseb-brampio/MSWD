<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Barangay;
use App\Models\BarangayContact;
use App\Models\DisasterReport;
use App\Models\ReliefSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('view_dashboard')) {
            abort(403, 'You do not have permission to access the dashboard.');
        }

        $barangays = Barangay::query()
            ->leftJoin('barangay_details', 'barangays.barangay_id', '=', 'barangay_details.barangay_id')
            ->select('barangays.*', 'barangay_details.*')
            ->orderBy('barangays.barangay_name')
            ->get();

        $barangayList = [];
        $riskCounts = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
        $riskBarangays = ['Low' => [], 'Medium' => [], 'High' => [], 'Critical' => []];
        $barangayReports = [];
        $barangayContacts = [];

        foreach ($barangays as $b) {
            $reportCount = DisasterReport::where('barangay_id', $b['barangay_id'])->count();
            $pendingCount = DisasterReport::where('barangay_id', $b['barangay_id'])->where('status', 'pending')->count();
            $approvedCount = DisasterReport::where('barangay_id', $b['barangay_id'])->where('status', 'approved')->count();
            $declinedCount = DisasterReport::where('barangay_id', $b['barangay_id'])->whereIn('status', ['declined', 'cancelled'])->count();
            $contactCount = BarangayContact::where('barangay_id', $b['barangay_id'])->count();

            $barangayReports[$b['barangay_id']] = DisasterReport::where('barangay_id', $b['barangay_id'])
                ->orderByDesc('created_at')
                ->get(['title', 'disaster_type', 'status', 'created_at']);
            $barangayContacts[$b['barangay_id']] = BarangayContact::where('barangay_id', $b['barangay_id'])->first();

            $detail = $b;
            $lvl = $detail['risk_level'] ?? 'Low';
            if (!isset($riskCounts[$lvl])) $lvl = 'Low';
            $riskCounts[$lvl]++;
            $riskBarangays[$lvl][] = $b['barangay_name'];

            $barangayList[] = [
                'id' => $b['barangay_id'],
                'name' => $b['barangay_name'],
                'address' => $b['address'] ?? '',
                'households' => (int) ($b['households'] ?? 0),
                'reports' => $reportCount,
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'declined' => $declinedCount,
                'contacts' => $contactCount,
                'detail' => $detail,
            ];
        }

        $totalBarangays = count($barangayList);
        $totalHouseholdsAll = array_sum(array_column($barangayList, 'households'));
        $highCriticalCount = $riskCounts['High'] + $riskCounts['Critical'];

        $announcements = Announcement::orderByDesc('is_pinned')->orderByDesc('created_at')->limit(3)->get();
        $reliefSchedules = ReliefSchedule::orderByDesc('distribution_date')->limit(3)->get();
        $recentReports = DisasterReport::with('barangay')->orderByDesc('created_at')->limit(10)->get();

        // Chart data - global
        $chartBarangays = [];
        $chartPopulation = [];
        $chartHouseholds = [];
        $chartHead = [];
        $chartReports = [];
        foreach ($barangayList as $b) {
            $chartBarangays[] = $b['name'];
            $chartPopulation[] = (int) ($b['detail']['population'] ?? 0);
            $chartHouseholds[] = (int) ($b['detail']['households'] ?? 0);
            $chartHead[] = (int) ($b['detail']['head_of_household'] ?? 0);
            $chartReports[] = $b['reports'];
        }

        $statusCounts = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'reedit' => 0];
        foreach (DisasterReport::selectRaw('status, COUNT(*) as c')->groupBy('status')->get() as $row) {
            if (isset($statusCounts[$row->status])) $statusCounts[$row->status] = (int) $row->c;
        }

        $damageTotally = DisasterReport::where('damage_extent', 'Totally')->count();
        $damagePartially = DisasterReport::where('damage_extent', 'Partially')->count();

        // Status by barangay
        $sbb = ['labels' => $chartBarangays, 'pending' => [], 'approved' => [], 'declined' => [], 'cancelled' => [], 'reedit' => []];
        $map = [];
        $rows = DisasterReport::selectRaw('barangay_id, status, COUNT(*) as c')
            ->with('barangay')->groupBy('barangay_id', 'status')->get();
        foreach ($rows as $x) {
            $n = $x->barangay ? $x->barangay->barangay_name : 'Unknown';
            if (!isset($map[$n])) $map[$n] = [];
            if (isset($sbb[$x->status])) $map[$n][$x->status] = (int) $x->c;
        }
        foreach (['pending', 'approved', 'declined', 'cancelled', 'reedit'] as $s) {
            $out = [];
            foreach ($chartBarangays as $n) $out[] = $map[$n][$s] ?? 0;
            $sbb[$s] = $out;
        }

        $chart_json = [
            'barangays' => $chartBarangays,
            'population' => $chartPopulation,
            'households' => $chartHouseholds,
            'head' => $chartHead,
            'reports' => $chartReports,
            'status' => $statusCounts,
            'damage' => ['Totally' => $damageTotally, 'Partially' => $damagePartially],
            'risk' => $riskCounts,
            'status_by_brgy' => $sbb,
        ];

        // Per-barangay charts
        $per = [];
        foreach ($barangayList as $b) {
            $bid = $b['id'];
            $bStatus = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'reedit' => 0];
            foreach (DisasterReport::where('barangay_id', $bid)->selectRaw('status, COUNT(*) as c')->groupBy('status')->get() as $x) {
                if (isset($bStatus[$x->status])) $bStatus[$x->status] = (int) $x->c;
            }
            $bMonths = [];
            $bMonthly = [];
            foreach (DisasterReport::where('barangay_id', $bid)
                ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as c")
                ->groupBy('ym')->orderBy('ym')->get() as $x) {
                $bMonths[] = date('M y', strtotime($x->ym . '-01'));
                $bMonthly[] = (int) $x->c;
            }
            $bDamage = DisasterReport::where('barangay_id', $bid)->selectRaw(
                "SUM(damage_extent='Totally') as t, SUM(damage_extent='Partially') as p"
            )->first();
            $per[$bid] = [
                'pop' => [(int) ($b['detail']['population'] ?? 0), (int) ($b['detail']['households'] ?? 0), (int) ($b['detail']['head_of_household'] ?? 0)],
                'months' => $bMonths,
                'monthly' => $bMonthly,
                'status' => $bStatus,
                'damage' => ['Totally' => (int) ($bDamage->t ?? 0), 'Partially' => (int) ($bDamage->p ?? 0)],
            ];
        }

        return view('admin.dashboard', compact(
            'barangayList', 'riskCounts', 'riskBarangays', 'totalBarangays', 'totalHouseholdsAll',
            'highCriticalCount', 'announcements', 'reliefSchedules', 'recentReports', 'chart_json', 'per',
            'barangayReports', 'barangayContacts'
        ));
    }
}
