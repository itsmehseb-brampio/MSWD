<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    private function findPending(string $token): ?Admin
    {
        return Admin::where('invite_token', $token)
            ->where('invite_expires_at', '>', now())
            ->first();
    }

    public function signup(string $token)
    {
        $account = $this->findPending($token);

        if (!$account) {
            return view('auth.invite-invalid');
        }

        return view('auth.invite-signup', ['account' => $account]);
    }

    public function complete(Request $request, string $token)
    {
        $account = $this->findPending($token);

        if (!$account) {
            return view('auth.invite-invalid');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:admins,username'],
            'password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:password'],
        ]);

        $account->update([
            'name' => $request->name,
            'username' => $request->username,
            'password' => $request->password,
            'invite_token' => null,
            'invite_expires_at' => null,
        ]);

        return redirect()->route('login')
            ->with('success', 'Account created! You can now sign in with your username and password.');
    }
}