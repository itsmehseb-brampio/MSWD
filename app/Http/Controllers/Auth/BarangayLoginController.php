<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayLoginController extends Controller
{
    public function show()
    {
        $barangays = \App\Models\Barangay::query()
            ->orderBy('barangay_name')
            ->pluck('barangay_name')
            ->unique()
            ->values()
            ->all();

        return view('auth.barangay-login', ['barangays' => $barangays]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('barangay')->attempt([
            'barangay_name' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            $request->session()->regenerate();
            return redirect()->route('barangay.dashboard');
        }

        return back()->withErrors(['message' => 'Incorrect barangay name or password!']);
    }

    public function logout(Request $request)
    {
        Auth::guard('barangay')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('barangay.login');
    }
}
