<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAccountsController extends Controller
{
    public function index()
    {
        $admins = Admin::orderBy('id')->get();
        $myId = Auth::guard('admin')->id();
        return view('admin.admins', compact('admins', 'myId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:admins,username',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ], ['username.unique' => 'Username already exists!']);

        Admin::create([
            'username' => $request->username,
            'password' => $request->password,
        ]);

        return back()->with('success', 'Admin account added successfully!');
    }

    public function update(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'username' => 'required|string',
        ]);

        $admin = Admin::findOrFail($request->admin_id);
        $admin->username = $request->username;

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|min:6',
                'confirm_password' => 'required|same:password',
            ]);
            $admin->password = $request->password;
        }
        $admin->save();

        if (Auth::guard('admin')->id() === (int) $admin->id) {
            $request->session()->put('admin_username', $admin->username);
        }

        return back()->with('success', 'Admin account updated successfully!');
    }

    public function destroy(Request $request)
    {
        $request->validate(['admin_id' => 'required|exists:admins,id']);

        if ((int) $request->admin_id === (int) Auth::guard('admin')->id()) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        Admin::find($request->admin_id)?->delete();

        return back()->with('success', 'Admin account deleted successfully!');
    }
}
