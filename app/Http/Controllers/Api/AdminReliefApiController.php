<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\ReliefDistributionDocument;
use App\Models\ReliefDistributionReport;
use App\Models\ReliefItem;
use App\Models\ReliefSchedule;
use App\Models\ReliefScheduleTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReliefApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        switch ($action) {
            case 'list':
                $lookup = Barangay::with('detail')->orderBy('barangay_name')->get()->keyBy('barangay_id');

                $schedules = ReliefSchedule::with(['items', 'targets'])
                    ->orderByRaw('distribution_date DESC, distribution_time DESC')
                    ->get()
                    ->map(function ($s) use ($lookup) {
                        $targets = collect();
                        foreach ($s->targets as $t) {
                            $b = $lookup->get($t->barangay_id);
                            $targets->push([
                                'barangay_id' => $t->barangay_id,
                                'barangay_name' => $b->barangay_name ?? 'Barangay',
                                'households' => (int) ($b->detail->households ?? 0),
                            ]);
                        }

                        $targetAll = $s->targets->count() === 0;
                        $totalHouseholds = $targetAll
                            ? (int) $lookup->sum(fn ($b) => (int) ($b->detail->households ?? 0))
                            : (int) $targets->sum('households');

                        $reports = ReliefDistributionReport::with(['documents', 'barangay'])
                            ->where('schedule_id', $s->schedule_id)
                            ->orderByDesc('received_at')
                            ->get()
                            ->map(fn ($r) => [
                                'report_id' => $r->report_id,
                                'barangay_id' => $r->barangay_id,
                                'barangay_name' => $r->barangay->barangay_name ?? 'Barangay',
                                'confirmed_by' => $r->confirmed_by,
                                'narrative' => $r->narrative,
                                'received_at' => $r->received_at ? $r->received_at->format('Y-m-d H:i:s') : null,
                                'created_at' => $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : null,
                                'documents' => $r->documents->map(fn ($d) => [
                                    'document_id' => $d->document_id,
                                    'file_path' => $d->file_path,
                                ]),
                            ]);

                        return [
                            'schedule_id' => $s->schedule_id,
                            'title' => $s->title,
                            'description' => $s->description,
                            'distribution_date' => $s->distribution_date ? $s->distribution_date->format('Y-m-d') : null,
                            'distribution_time' => $s->distribution_time,
                            'location' => $s->location,
                            'status' => $s->status,
                            'items' => $s->items,
                            'target_all' => $targetAll,
                            'total_households' => $totalHouseholds,
                            'targets' => $targets,
                            'reports' => $reports,
                        ];
                    })->values();

                return response()->json(['ok' => true, 'schedules' => $schedules]);

            case 'add':
                DB::transaction(function () use ($request) {
                    $s = ReliefSchedule::create([
                        'title' => $request->title,
                        'description' => $request->description,
                        'distribution_date' => $request->distribution_date,
                        'distribution_time' => $request->distribution_time,
                        'location' => $request->location,
                        'status' => $request->status ?? 'upcoming',
                    ]);

                    foreach ($request->items ?? [] as $item) {
                        if (isset($item['item_name']) && $item['item_name'] !== '') {
                            ReliefItem::create([
                                'schedule_id' => $s->schedule_id,
                                'item_name' => $item['item_name'],
                                'quantity' => $item['quantity'] ?? 0,
                                'unit' => $item['unit'] ?? null,
                            ]);
                        }
                    }

                    if (!$request->boolean('target_all')) {
                        foreach (($request->targets ?? []) as $bid) {
                            ReliefScheduleTarget::create(['schedule_id' => $s->schedule_id, 'barangay_id' => $bid]);
                        }
                    }
                });
                return response()->json(['ok' => true]);

            case 'update_status':
                $status = in_array($request->status, ['upcoming', 'ongoing', 'completed'], true) ? $request->status : 'upcoming';
                ReliefSchedule::where('schedule_id', $request->schedule_id)->update(['status' => $status]);
                return response()->json(['ok' => true]);

            case 'delete':
                $schedule = ReliefSchedule::find($request->schedule_id);
                if (!$schedule) {
                    return response()->json(['ok' => false, 'error' => 'Invalid schedule'], 422);
                }
                $reportIds = ReliefDistributionReport::where('schedule_id', $schedule->schedule_id)->pluck('report_id');
                foreach (ReliefDistributionDocument::whereIn('report_id', $reportIds)->get() as $d) {
                    $full = public_path($d->file_path);
                    if (is_file($full)) {
                        @unlink($full);
                    }
                }
                $schedule->delete();
                return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }
}