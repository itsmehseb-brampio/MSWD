<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReliefSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayReliefApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        $barangay = Auth::guard('barangay')->user();
        $id = $barangay->barangay_id;

        switch ($action) {
            case 'list':
                $schedules = ReliefSchedule::where(function ($q) use ($id) {
                    $q->whereDoesntHave('targets')->orWhereHas('targets', function ($t) use ($id) {
                        $t->where('barangay_id', $id);
                    });
                })
                    ->with(['items', 'targets'])
                    ->orderByDesc('distribution_date')
                    ->get()
                    ->map(fn ($s) => [
                        'schedule_id' => $s->schedule_id,
                        'title' => $s->title,
                        'description' => $s->description,
                        'distribution_date' => $s->distribution_date ? $s->distribution_date->format('Y-m-d') : null,
                        'distribution_time' => $s->distribution_time,
                        'location' => $s->location,
                        'status' => $s->status,
                        'items' => $s->items->map(fn ($i) => [
                            'item_name' => $i->item_name,
                            'quantity' => $i->quantity,
                            'unit' => $i->unit,
                        ]),
                    ]);
                return response()->json(['ok' => true, 'schedules' => $schedules]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }
}