<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\BarangayContact;
use App\Models\BarangayDetail;
use App\Models\BarangayEditLog;
use App\Models\MunicipalContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('view_barangay_info') && !$admin->hasPerm('manage_municipal_info')) {
            abort(403, 'You do not have permission to view contact information.');
        }

        $barangays = Barangay::orderBy('barangay_name')->get();
        $name = $request->query('barangay_name');
        $selected = null;
        $detail = null;
        $contacts = null;
        $municipal = null;
        $editLogs = [];

        if ($name) {
            $selected = Barangay::where('barangay_name', $name)->first();
        }

        if ($selected) {
            $detail = BarangayDetail::find($selected->barangay_id) ?? new BarangayDetail();
            $contacts = BarangayContact::find($selected->barangay_id);
            $municipal = MunicipalContact::first();
            $editLogs = BarangayEditLog::where('barangay_id', $selected->barangay_id)
                ->selectRaw('MAX(edited_at) as edited_at')->groupBy('field_name')
                ->pluck('edited_at', 'field_name');
        }

        return view('admin.contact', compact(
            'barangays', 'name', 'selected', 'detail', 'contacts', 'municipal', 'editLogs'
        ));
    }
}
