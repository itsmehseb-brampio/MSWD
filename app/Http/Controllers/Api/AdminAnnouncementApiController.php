<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAnnouncementApiController extends Controller
{
    public function handle(Request $request, $action)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_announcements')) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to manage announcements.'], 403);
        }

        switch ($action) {
            case 'list':
                $announcements = Announcement::with(['targets', 'admin'])
                    ->orderByDesc('is_pinned')->orderByDesc('created_at')->get()
                    ->map(function ($a) {
                        return [
                            'announcement_id' => $a->announcement_id,
                            'title' => $a->title,
                            'message' => $a->message,
                            'is_pinned' => $a->is_pinned,
                            'created_at' => $a->created_at ? $a->created_at->format('M d, Y h:i A') : null,
                            'admin' => $a->admin->username ?? 'Admin',
                            'target_all' => $a->targets->count() === 0,
                            'targets' => $a->targets->pluck('barangay_id'),
                        ];
                    });
                return response()->json(['ok' => true, 'announcements' => $announcements]);

            case 'post':
                $a = Announcement::create([
                    'admin_id' => $admin->id,
                    'title' => $request->title,
                    'message' => $request->message,
                    'is_pinned' => $request->has('is_pinned') && $request->is_pinned ? 1 : 0,
                ]);

                if (!$request->boolean('target_all')) {
                    foreach (($request->target_ids ?? []) as $bid) {
                        AnnouncementTarget::create(['announcement_id' => $a->announcement_id, 'barangay_id' => $bid]);
                    }
                }
                return response()->json(['ok' => true, 'announcement_id' => $a->announcement_id]);

            case 'delete':
                Announcement::where('announcement_id', $request->announcement_id)->delete();
                return response()->json(['ok' => true]);

            case 'pin':
                $ann = Announcement::findOrFail($request->announcement_id);
                $ann->update(['is_pinned' => $request->boolean('is_pinned') ? 1 : 0]);
                return response()->json(['ok' => true, 'is_pinned' => $ann->is_pinned]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid action'], 400);
    }
}
