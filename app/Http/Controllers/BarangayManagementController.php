<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use Illuminate\Http\Request;

class BarangayManagementController extends Controller
{
    const MALILIPOT_BARANGAYS = [
        'Bagong Bayan', 'Payaw', 'Bariw', 'Bical', 'Buhian', 'Calbayog', 'Canaway',
        'Caranan', 'Corot', 'Guinobatan', 'Macalaya', 'Maoyod', 'Marcos', 'Proper Bariw',
        'San Andres', 'San Isidro', 'San Jose', 'San Rafael',
    ];

    public function index()
    {
        $barangays = Barangay::orderBy('barangay_name')->get();
        $registered = $barangays->pluck('barangay_name')->map(fn ($n) => strtolower($n))->all();
        $suggestions = self::MALILIPOT_BARANGAYS;
        return view('admin.barangay', compact('barangays', 'suggestions', 'registered'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'barangay_name' => 'required|string|unique:barangays,barangay_name',
            'address' => 'nullable|string',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ], ['barangay_name.unique' => 'This barangay account already exists!']);

        Barangay::create([
            'barangay_name' => $request->barangay_name,
            'address' => $request->address,
            'password' => $request->password,
            'last_active' => null,
            'disaster_open' => 0,
        ]);

        return back()->with('success', 'Barangay account created successfully!');
    }
}
