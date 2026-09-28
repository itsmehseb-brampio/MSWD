<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReliefDistributionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminReliefDistributionApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_relief')) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to manage relief goods.'], 403);
        }

        switch ($action) {
            case 'list':
                $reports = ReliefDistributionReport::with(['documents', 'barangay'])
                    ->when($request->schedule_id, fn ($q) => $q->where('schedule_id', $request->schedule_id))
                    ->orderByDesc('received_at')->get();
                return response()->json(['ok' => true, 'reports' => $reports]);

            case 'delete_report':
                $report = ReliefDistributionReport::with('documents')->find($request->report_id);
                if (!$report) {
                    return response()->json(['ok' => false, 'error' => 'Invalid report'], 422);
                }
                foreach ($report->documents as $d) {
                    $full = public_path($d->file_path);
                    if (is_file($full)) {
                        @unlink($full);
                    }
                }
                $report->delete();
                return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }
}