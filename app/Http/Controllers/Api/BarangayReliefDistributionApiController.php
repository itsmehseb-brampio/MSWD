<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReliefDistributionDocument;
use App\Models\ReliefDistributionReport;
use App\Models\ReliefSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayReliefDistributionApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('confirm_relief')) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to confirm relief.'], 403);
        }
        $id = $barangay->barangay_id;

        switch ($action) {
            case 'list':
                $reports = ReliefDistributionReport::where('barangay_id', $id)
                    ->with('documents')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($r) => [
                        'report_id' => $r->report_id,
                        'schedule_id' => $r->schedule_id,
                        'schedule_title' => ReliefSchedule::find($r->schedule_id)->title ?? 'Relief Distribution',
                        'confirmed_by' => $r->confirmed_by,
                        'narrative' => $r->narrative,
                        'received_at' => $r->received_at ? $r->received_at->format('Y-m-d H:i:s') : null,
                        'documents' => $r->documents->map(fn ($d) => $d->file_path),
                    ]);
                return response()->json(['ok' => true, 'reports' => $reports]);

            case 'confirm':
                $scheduleId = (int) $request->input('schedule_id', 0);
                $schedule = ReliefSchedule::where(function ($q) use ($id) {
                    $q->whereDoesntHave('targets')->orWhereHas('targets', function ($t) use ($id) {
                        $t->where('barangay_id', $id);
                    });
                })->find($scheduleId);
                if (!$schedule) {
                    return response()->json(['ok' => false, 'error' => 'Invalid relief schedule.'], 422);
                }

                $confirmedBy = trim((string) $request->input('confirmed_by', ''));
                if ($confirmedBy === '') {
                    $confirmedBy = $barangay->barangay_name;
                }

                $report = ReliefDistributionReport::create([
                    'schedule_id' => $schedule->schedule_id,
                    'barangay_id' => $id,
                    'confirmed_by' => substr($confirmedBy, 0, 150),
                    'narrative' => trim((string) $request->input('narrative', '')),
                    'received_at' => $this->parseDateTime($request->input('received_at', '')),
                ]);

                $files = $request->file('documents', []);
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if ($file === null || !$file->isValid()) {
                        continue;
                    }
                    $path = $this->storeDocument($file, $id);
                    if ($path !== null) {
                        ReliefDistributionDocument::create([
                            'report_id' => $report->report_id,
                            'file_path' => $path,
                        ]);
                    }
                }

                return response()->json(['ok' => true, 'success' => true, 'report_id' => $report->report_id]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }

    private function parseDateTime($value): ?string
    {
        if (empty($value)) {
            return now();
        }
        $string = trim((string) $value);
        $string = str_replace('T', ' ', $string);
        if (strlen($string) === 16) {
            $string .= ':00';
        }
        $timestamp = strtotime($string);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : now();
    }

    private function storeDocument($file, int $barangayId): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], true)) {
            return null;
        }
        $directory = public_path('uploads/relief_docs');
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        $fname = 'relief_' . $barangayId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $file->move($directory, $fname);

        return 'uploads/relief_docs/' . $fname;
    }
}