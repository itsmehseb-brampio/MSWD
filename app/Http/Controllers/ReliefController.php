<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\ReliefDistributionReport;
use App\Models\ReliefSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReliefController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_relief')) {
            abort(403, 'You do not have permission to manage relief goods.');
        }

        $barangays = Barangay::with('detail')->orderBy('barangay_name')->get();
        return view('admin.relief', compact('barangays'));
    }

    public function distributions()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_relief')) {
            abort(403, 'You do not have permission to manage relief goods.');
        }

        $reports = ReliefDistributionReport::with(['documents', 'barangay'])->orderByDesc('received_at')->get();
        return view('admin.relief-distributions', compact('reports'));
    }
}
