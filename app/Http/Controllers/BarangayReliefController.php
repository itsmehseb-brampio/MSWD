<?php

namespace App\Http\Controllers;

use App\Models\ReliefDistributionReport;
use App\Models\ReliefSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayReliefController extends Controller
{
    public function index()
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('confirm_relief') && !$barangay->hasPerm('view_municipal_contacts')) {
            abort(403, 'You do not have permission to view relief goods.');
        }
        $id = $barangay->barangay_id;

        $schedules = ReliefSchedule::where(function ($q) use ($id) {
            $q->whereDoesntHave('targets')->orWhereHas('targets', function ($t) use ($id) {
                $t->where('barangay_id', $id);
            });
        })
            ->with(['items', 'targets'])
            ->orderByDesc('distribution_date')
            ->get();

        $distributions = ReliefDistributionReport::where('barangay_id', $id)
            ->with('documents')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('schedule_id');

        return view('barangay.relief', compact('barangay', 'schedules', 'distributions'));
    }
}