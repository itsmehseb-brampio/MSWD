<?php

namespace App\Http\Controllers;

use App\Models\BarangayDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayMessagesController extends Controller
{
    public function index()
    {
        $barangay = Auth::guard('barangay')->user();
        if (!$barangay->hasPerm('manage_messages')) {
            abort(403, 'You do not have permission to access messages.');
        }
        $detail = BarangayDetail::where('barangay_id', $barangay->barangay_id)->first();

        return view('barangay.messages', [
            'my_logo' => $detail->logo ?? null,
            'my_name' => $barangay->barangay_name,
        ]);
    }
}