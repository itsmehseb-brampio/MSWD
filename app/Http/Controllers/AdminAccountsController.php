<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Support\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminAccountsController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_admin_accounts')) {
            abort(403, 'You do not have permission to manage admin accounts.');
        }

        $admins = Admin::with('roles', 'permissions')->orderBy('id')->get();
        $myId = Auth::guard('admin')->id();
        $roles = Role::whereIn('name', ['admin', 'barangay', 'user'])->get();
        $permissions = Permission::where('guard_name', 'admin')->orderBy('name')->get();
        $permissionHelper = PermissionHelper::class;
        $groups = PermissionHelper::GROUPS;
        $allPermissions = PermissionHelper::PERMISSIONS;
        return view('admin.admins', compact('admins', 'myId', 'roles', 'permissions', 'permissionHelper', 'groups', 'allPermissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:admins,username',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
            'role' => 'required|in:admin,user',
        ], ['username.unique' => 'Username already exists!']);

        $admin = Admin::create([
            'username' => $request->username,
            'password' => $request->password,
        ]);

        $role = Role::where('name', $request->role)->where('guard_name', 'admin')->first();
        if (!$role) {
            $role = Role::where('name', 'user')->where('guard_name', 'admin')->first();
        }
        if ($role) {
            $admin->syncRoles([$role]);
            $admin->syncPermissions($role->permissions->pluck('name')->all());
        }

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

        if ($request->filled('role')) {
            $role = Role::where('name', $request->role)->where('guard_name', 'admin')->first();
            if (!$role) {
                $role = Role::where('name', 'user')->where('guard_name', 'admin')->first();
            }
            if ($role) {
                $admin->syncRoles([$role]);
            }
            $roleName = $request->role;
        } else {
            $roleName = $admin->roles->first()->name ?? 'user';
        }

        $required = PermissionHelper::requiredFor($roleName);
        $custom = $request->has('permissions') ? array_keys($request->permissions) : [];
        $admin->syncPermissions(array_values(array_unique(array_merge($required, $custom))));

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

        $admin = Admin::find($request->admin_id);
        if ($admin) {
            $admin->roles()->detach();
            $admin->permissions()->detach();
            $admin->delete();
        }

        return back()->with('success', 'Admin account deleted successfully!');
    }
}
