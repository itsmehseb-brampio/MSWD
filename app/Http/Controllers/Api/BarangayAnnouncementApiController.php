<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayAnnouncementApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('view_announcements')) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to view announcements.'], 403);
        }
        $id = $barangay->barangay_id;

        switch ($action) {
            case 'list':
                $announcements = Announcement::with('admin')
                    ->where(function ($q) use ($id) {
                        $q->whereDoesntHave('targets')
                            ->orWhereHas('targets', function ($t) use ($id) {
                                $t->where('barangay_id', $id);
                            });
                    })
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($a) => [
                        'announcement_id' => $a->announcement_id,
                        'author' => $a->admin->username ?? 'DSWD',
                        'title' => $a->title,
                        'message' => $a->message,
                        'is_pinned' => (bool) $a->is_pinned,
                        'created_at' => $a->created_at ? $a->created_at->format('Y-m-d H:i:s') : null,
                    ]);
                return response()->json(['ok' => true, 'announcements' => $announcements]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }
}