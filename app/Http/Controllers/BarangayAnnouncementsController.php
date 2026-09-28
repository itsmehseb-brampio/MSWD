<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayAnnouncementsController extends Controller
{
    public function index()
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('view_announcements')) {
            abort(403, 'You do not have permission to view announcements.');
        }
        $id = $barangay->barangay_id;

        $announcements = Announcement::with('admin')
            ->where(function ($q) use ($id) {
                $q->whereDoesntHave('targets')
                    ->orWhereHas('targets', function ($t) use ($id) {
                        $t->where('barangay_id', $id);
                    });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return view('barangay.announcements', ['announcements' => $announcements]);
    }
}