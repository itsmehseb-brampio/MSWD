<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Barangay;
use Illuminate\Http\Request;

class AnnouncementsController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('targets')->orderByDesc('is_pinned')->orderByDesc('created_at')->get();
        $barangays = Barangay::orderBy('barangay_name')->get();
        return view('admin.announcements', compact('announcements', 'barangays'));
    }
}
