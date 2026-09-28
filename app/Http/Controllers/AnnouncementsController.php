<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementsController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_announcements')) {
            abort(403, 'You do not have permission to manage announcements.');
        }

        $announcements = Announcement::with('targets')->orderByDesc('is_pinned')->orderByDesc('created_at')->get();
        $barangays = Barangay::orderBy('barangay_name')->get();
        return view('admin.announcements', compact('announcements', 'barangays'));
    }
}
