<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Support\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class BarangayManagementController extends Controller
{
    const MALILIPOT_BARANGAYS = [
        'Barangay I', 'Barangay II', 'Barangay III', 'Barangay IV', 'Barangay V',
        'Binitayan', 'Calbayog', 'Canaway', 'Salvacion',
        'San Antonio Santicon', 'San Antonio Sulong', 'San Francisco',
        'San Isidro Ilawod', 'San Isidro Iraya', 'San Jose',
        'San Roque', 'Santa Cruz', 'Santa Teresa',
    ];

    public function index()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_barangay_accounts')) {
            abort(403, 'You do not have permission to manage barangay accounts.');
        }

        $barangays = Barangay::with('roles', 'permissions')->orderBy('barangay_name')->get();
        $registered = $barangays->pluck('barangay_name')->map(fn ($n) => strtolower($n))->all();
        $suggestions = self::MALILIPOT_BARANGAYS;
        $roles = Role::where('guard_name', 'barangay')->whereIn('name', ['barangay', 'user'])->get();
        $groups = PermissionHelper::GROUPS;
        $allPermissions = PermissionHelper::PERMISSIONS;
        return view('admin.barangay', compact('barangays', 'suggestions', 'registered', 'roles', 'groups', 'allPermissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'barangay_name' => 'required|string|unique:barangays,barangay_name',
            'address' => 'nullable|string',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
            'role' => 'required|in:barangay,user',
        ], ['barangay_name.unique' => 'This barangay account already exists!']);

        $barangay = Barangay::create([
            'barangay_name' => $request->barangay_name,
            'address' => $request->address,
            'password' => $request->password,
            'last_active' => null,
            'disaster_open' => 0,
        ]);

        $role = Role::where('name', $request->role)->where('guard_name', 'barangay')->first();
        if ($role) {
            $barangay->syncRoles([$role]);
            $barangay->syncPermissions($role->permissions->pluck('name')->all());
        } else {
            $barangay->syncPermissions(PermissionHelper::requiredFor($request->role));
        }

        return back()->with('success', 'Barangay account created successfully!');
    }

    public function update(Request $request)
    {
        $request->validate([
            'barangay_id' => 'required|exists:barangays,barangay_id',
            'barangay_name' => 'required|string',
        ]);

        $barangay = Barangay::findOrFail($request->barangay_id);
        $barangay->barangay_name = $request->barangay_name;

        if ($request->filled('address')) {
            $barangay->address = $request->address;
        }

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|min:6',
                'confirm_password' => 'required|same:password',
            ]);
            $barangay->password = $request->password;
        }
        $barangay->save();

        if ($request->filled('role')) {
            $role = Role::where('name', $request->role)->where('guard_name', 'barangay')->first();
            if ($role) {
                $barangay->syncRoles([$role]);
            }
            $roleName = $request->role;
        } else {
            $roleName = $barangay->roles->first()->name ?? 'barangay';
        }

        $required = PermissionHelper::requiredFor($roleName);
        $custom = $request->has('permissions') ? array_keys($request->permissions) : [];
        $barangay->syncPermissions(array_values(array_unique(array_merge($required, $custom))));

        return back()->with('success', 'Barangay account updated successfully!');
    }

    public function destroy(Request $request)
    {
        $request->validate(['barangay_id' => 'required|exists:barangays,barangay_id']);

        $barangay = Barangay::find($request->barangay_id);
        if ($barangay) {
            $barangay->roles()->detach();
            $barangay->permissions()->detach();
            $barangay->delete();
        }

        return back()->with('success', 'Barangay account deleted successfully!');
    }

    public function toggleDisaster(Request $request)
    {
        $request->validate(['barangay_id' => 'required|exists:barangays,barangay_id']);

        $barangay = Barangay::findOrFail($request->barangay_id);
        $barangay->disaster_open = !$barangay->disaster_open;
        $barangay->save();

        return response()->json(['ok' => true, 'disaster_open' => (int) $barangay->disaster_open]);
    }
}
