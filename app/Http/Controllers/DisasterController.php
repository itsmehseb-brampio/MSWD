<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\DisasterFormatField;
use App\Models\DisasterReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisasterController extends Controller
{
    public function format(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_disaster_format')) {
            abort(403, 'You do not have permission to manage the disaster format.');
        }

        $fields = DisasterFormatField::orderBy('field_order')->get();
        $brgyList = Barangay::orderBy('barangay_name')->get(['barangay_id', 'barangay_name', 'disaster_open']);
        return view('admin.disaster-format', compact('fields', 'brgyList'));
    }

    public function formatStore(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_disaster_format')) {
            abort(403, 'You do not have permission to manage the disaster format.');
        }

        switch ($request->action) {
            case 'add_field':
                $label = trim($request->field_label);
                $name = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $label), '_'));
                $maxOrder = DisasterFormatField::max('field_order') + 1;
                DisasterFormatField::create([
                    'field_label' => $label,
                    'field_name' => $name,
                    'field_type' => $request->field_type,
                    'field_options' => trim($request->field_options ?? ''),
                    'is_required' => $request->has('is_required') ? 1 : 0,
                    'field_order' => $maxOrder,
                ]);
                return redirect()->route('admin.disaster.format')->with('success', 'Field added!');

            case 'update_field':
                $label = trim($request->field_label);
                $name = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $label), '_'));
                DisasterFormatField::where('id', $request->field_id)->update([
                    'field_label' => $label,
                    'field_name' => $name,
                    'field_type' => $request->field_type,
                    'field_options' => trim($request->field_options ?? ''),
                    'is_required' => $request->has('is_required') ? 1 : 0,
                ]);
                return redirect()->route('admin.disaster.format')->with('success', 'Field updated!');

            case 'delete_field':
                DisasterFormatField::where('id', $request->field_id)->delete();
                $this->renumberFields();
                return redirect()->route('admin.disaster.format')->with('success', 'Field deleted!');

            case 'reorder':
                foreach (($request->field_order ?? []) as $idx => $fid) {
                    DisasterFormatField::where('id', $fid)->update(['field_order' => (int) $idx]);
                }
                return redirect()->route('admin.disaster.format')->with('success', 'Fields reordered!');

            case 'toggle_disaster':
                Barangay::where('barangay_id', $request->barangay_id)->update(['disaster_open' => (int) $request->disaster_open]);
                return response()->json(['ok' => true, 'disaster_open' => (int) $request->disaster_open]);
        }

        return redirect()->route('admin.disaster.format');
    }

    private function renumberFields()
    {
        $i = 1;
        foreach (DisasterFormatField::orderBy('field_order')->get() as $f) {
            $f->update(['field_order' => $i]);
            $i++;
        }
    }

    public function pending(Request $request)
    {
        $this->checkReviewPermission();
        $reports = $this->queryReports('pending', $request->search);
        $counts = $this->statusCounts();
        return view('admin.disaster-reports', [
            'reports' => $reports,
            'status' => 'pending',
            'counts' => $counts,
            'search' => $request->search,
            'dchart' => $this->chartData('pending'),
        ]);
    }

    public function approved(Request $request)
    {
        $this->checkReviewPermission();
        $reports = $this->queryReports('approved', $request->search);
        $counts = $this->statusCounts();
        return view('admin.disaster-reports', [
            'reports' => $reports,
            'status' => 'approved',
            'counts' => $counts,
            'search' => $request->search,
            'dchart' => $this->chartData('approved'),
        ]);
    }

    public function declined(Request $request)
    {
        $this->checkReviewPermission();
        $reports = DisasterReport::with('barangay')
            ->whereIn('status', ['declined', 'cancelled'])
            ->search($request->search)
            ->orderByDesc('created_at')
            ->get();
        $counts = $this->statusCounts();
        return view('admin.disaster-reports', [
            'reports' => $reports,
            'status' => 'declined',
            'counts' => $counts,
            'search' => $request->search,
            'dchart' => $this->chartData('declined'),
        ]);
    }

    public function reedit(Request $request)
    {
        $this->checkReviewPermission();
        $reports = $this->queryReports('reedit', $request->search);
        $counts = $this->statusCounts();
        return view('admin.disaster-reports', [
            'reports' => $reports,
            'status' => 'reedit',
            'counts' => $counts,
            'search' => $request->search,
            'dchart' => $this->chartData('reedit'),
        ]);
    }

    public function history(Request $request)
    {
        $this->checkReviewPermission();
        $reports = DisasterReport::with('barangay')
            ->search($request->search)
            ->orderByDesc('created_at')
            ->get();
        $counts = $this->statusCounts();
        return view('admin.disaster-reports', [
            'reports' => $reports,
            'status' => 'history',
            'counts' => $counts,
            'search' => $request->search,
            'dchart' => $this->chartData('history'),
        ]);
    }

    public function review(Request $request)
    {
        $this->checkReviewPermission();
        $request->validate([
            'id' => 'required|exists:disaster_reports,report_id',
            'action' => 'required|in:approved,declined,reedit',
        ]);

        $report = DisasterReport::findOrFail($request->id);

        if ($request->action === 'approved') {
            $report->update(['status' => 'approved', 'decline_reason' => null]);
            $msg = 'Report approved!';
        } elseif ($request->action === 'reedit') {
            $report->update(['status' => 'reedit', 'decline_reason' => $request->decline_reason ?: null]);
            $msg = 'Report sent back for re-edit!';
        } else {
            if ($report->status === 'declined') {
                $report->update(['status' => 'cancelled', 'decline_reason' => $request->decline_reason ?: null]);
                $msg = 'Report cancelled!';
            } else {
                $report->update(['status' => 'declined', 'decline_reason' => $request->decline_reason ?: null]);
                $msg = 'Report declined!';
            }
        }

        return back()->with('success', $msg);
    }

    private function checkReviewPermission(): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('review_disaster_reports')) {
            abort(403, 'You do not have permission to review disaster reports.');
        }
    }

    private function queryReports($status, $search)
    {
        return DisasterReport::with('barangay')
            ->where('status', $status)
            ->search($search)
            ->orderByDesc('created_at')
            ->get();
    }

    private function statusCounts()
    {
        $counts = ['approved' => 0, 'declined' => 0, 'pending' => 0, 'reedit' => 0, 'history' => 0];
        foreach (DisasterReport::selectRaw('status, COUNT(*) as c')->groupBy('status')->get() as $r) {
            $counts['history'] += (int) $r->c;
            if (in_array($r->status, ['approved', 'declined', 'pending', 'reedit'])) {
                $counts[$r->status] = (int) $r->c;
            }
            if ($r->status === 'cancelled') {
                $counts['declined'] += (int) $r->c;
            }
        }
        return $counts;
    }

    private function chartData(?string $statusFilter = null): array
    {
        $base = DisasterReport::query();
        if ($statusFilter === 'declined') {
            $base->whereIn('status', ['declined', 'cancelled']);
        } elseif ($statusFilter && $statusFilter !== 'history') {
            $base->where('status', $statusFilter);
        }

        $status = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'reedit' => 0];
        foreach (DisasterReport::selectRaw('status, COUNT(*) c')->groupBy('status')->get() as $r) {
            if (isset($status[$r->status])) {
                $status[$r->status] = (int) $r->c;
            }
        }

        $months = [];
        $monthly = [];
        $rows = (clone $base)->selectRaw("DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c")->groupBy('ym')->orderBy('ym')->get();
        foreach ($rows as $r) {
            $months[] = date('M y', strtotime($r->ym . '-01'));
            $monthly[] = (int) $r->c;
        }

        $barangays = [];
        $barangayReports = [];
        $statusByBrgy = ['labels' => [], 'pending' => [], 'approved' => [], 'declined' => [], 'cancelled' => [], 'reedit' => []];
        $map = [];
        $rows = (clone $base)->selectRaw('barangay_id, COUNT(*) c')->groupBy('barangay_id')->orderByDesc('c')->get();
        $srows = (clone $base)->with('barangay')->selectRaw('barangay_id, status, COUNT(*) c')->groupBy('barangay_id', 'status')->get();
        foreach ($rows as $r) {
            $name = $r->barangay->barangay_name ?? 'Unknown';
            $barangays[] = $name;
            $barangayReports[] = (int) $r->c;
            $map[$name] = $map[$name] ?? [];
        }
        foreach ($srows as $r) {
            $name = $r->barangay->barangay_name ?? 'Unknown';
            $map[$name][$r->status] = (int) $r->c;
        }
        $statusByBrgy['labels'] = $barangays;
        foreach (['pending', 'approved', 'declined', 'cancelled', 'reedit'] as $s) {
            $statusByBrgy[$s] = array_map(fn ($n) => $map[$n][$s] ?? 0, $barangays);
        }

        $damage = ['Totally' => 0, 'Partially' => 0];
        $phys = (clone $base)->selectRaw("SUM(damage_extent='Totally') t, SUM(damage_extent='Partially') p")->first();
        $damage['Totally'] = (int) ($phys->t ?? 0);
        $damage['Partially'] = (int) ($phys->p ?? 0);

        return [
            'scope' => 'admin',
            'months' => $months,
            'monthly' => $monthly,
            'barangays' => $barangays,
            'barangay_reports' => $barangayReports,
            'status_by_brgy' => $statusByBrgy,
            'status' => $status,
            'damage' => $damage,
        ];
    }
}
