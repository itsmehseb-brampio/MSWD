<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        $barangays = \App\Models\Barangay::query()
            ->orderBy('barangay_name')
            ->pluck('barangay_name')
            ->unique()
            ->values()
            ->all();

        return view('auth.login', ['barangays' => $barangays]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('admin')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('barangay')->attempt([
            'barangay_name' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            $request->session()->regenerate();
            return redirect()->route('barangay.dashboard');
        }

        return back()->withErrors(['message' => 'Incorrect username, barangay name, or password!'])->withInput();
    }

    public function logout(Request $request)
    {
        foreach (['admin', 'barangay'] as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}