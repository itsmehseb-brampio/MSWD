<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\ReliefDistributionReport;
use App\Models\ReliefSchedule;
use Illuminate\Http\Request;

class ReliefController extends Controller
{
    public function index()
    {
        $barangays = Barangay::with('detail')->orderBy('barangay_name')->get();
        return view('admin.relief', compact('barangays'));
    }

    public function distributions()
    {
        $reports = ReliefDistributionReport::with(['documents', 'barangay'])->orderByDesc('received_at')->get();
        return view('admin.relief-distributions', compact('reports'));
    }
}
