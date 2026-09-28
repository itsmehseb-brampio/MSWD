<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Barangay;
use App\Support\AccountInvitation;
use App\Support\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AccountManagerController extends Controller
{
    const OFFICIAL_BARANGAYS = [
        'Barangay I', 'Barangay II', 'Barangay III', 'Barangay IV', 'Barangay V',
        'Binitayan', 'Calbayog', 'Canaway', 'Salvacion',
        'San Antonio Santicon', 'San Antonio Sulong', 'San Francisco',
        'San Isidro Ilawod', 'San Isidro Iraya', 'San Jose',
        'San Roque', 'Santa Cruz', 'Santa Teresa',
    ];

    public function index()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_admin_accounts') && !$admin->hasPerm('manage_barangay_accounts')) {
            abort(403, 'You do not have permission to manage accounts.');
        }

        $admins = Admin::with('roles', 'permissions')->orderBy('id')->get();
        $barangays = Barangay::with('roles', 'permissions')->orderBy('barangay_name')->get();
        $myId = Auth::guard('admin')->id();

        $accountRows = collect()
            ->merge($admins->map(function ($a) {
                return [
                    'type' => 'admin',
                    'id' => (int) $a->id,
                    'name' => $a->name ?: $a->username,
                    'username' => $a->username,
                    'email' => $a->email,
                    'invite_pending' => $a->hasPendingInvite(),
                    'role' => $a->roles->first()->name ?? 'none',
                    'barangay' => false,
                    'status' => 'Active',
                    'perms' => $a->permissions->pluck('name')->all(),
                ];
            }))
            ->merge($barangays->map(function ($b) {
                return [
                    'type' => 'barangay',
                    'id' => (int) $b->barangay_id,
                    'name' => $b->barangay_name,
                    'username' => (string) $b->username,
                    'role' => $b->roles->first()->name ?? 'none',
                    'barangay' => true,
                    'status' => $b->disaster_open ? 'Open' : 'Closed',
                    'disaster_open' => (bool) $b->disaster_open,
                    'address' => $b->address,
                    'perms' => $b->permissions->pluck('name')->all(),
                ];
            }))
            ->values();

        $tabs = [
            'admin' => $accountRows->where('role', 'admin')->sortBy(fn ($r) => strtolower($r['name']))->values()->all(),
            'barangay' => $accountRows->where('role', 'barangay')->sortBy(fn ($r) => strtolower($r['name']))->values()->all(),
            'user' => $accountRows->where('role', 'user')->sortBy(fn ($r) => strtolower($r['name']))->values()->all(),
        ];

        $registered = $barangays->pluck('barangay_name')->map(fn ($n) => strtolower(trim($n)))->all();
        $availableBarangays = self::OFFICIAL_BARANGAYS;

        $myPerms = $admin->permissions->pluck('name')->all();
        $canAdmin = in_array('manage_admin_accounts', $myPerms, true);
        $canBarangay = in_array('manage_barangay_accounts', $myPerms, true);

        $roles = Role::whereIn('name', ['admin', 'barangay', 'user'])->get();
        $roleGuards = ['admin' => 'admin', 'barangay' => 'barangay', 'user' => 'admin'];
        $roleDefaults = [];
        foreach ($roleGuards as $rn => $g) {
            $r = Role::where('name', $rn)->where('guard_name', $g)->first();
            $roleDefaults[$rn] = $r ? $r->permissions->pluck('name')->all() : [];
        }

        return view('admin.roles', compact(
            'tabs', 'admins', 'barangays', 'myId', 'registered', 'availableBarangays',
            'roles', 'roleDefaults', 'roleGuards', 'canAdmin', 'canBarangay'
        ));
    }

    public function store(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $type = $request->input('account_type');

        $request->validate([
            'account_type' => 'required|in:admin,barangay,user',
        ]);

        if ($type === 'admin' || $type === 'user') {
            if (!$admin->hasPerm('manage_admin_accounts')) {
                abort(403, 'You do not have permission to create admin accounts.');
            }

            if ($type === 'user') {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => ['required', 'email', 'unique:admins,email'],
                ], ['email.unique' => 'An account with that email already exists!']);

                $account = Admin::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'username' => null,
                    'password' => Str::random(40),
                ]);
            } else {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'username' => 'required|string|max:255|unique:admins,username',
                    'password' => 'required|string|min:6',
                    'confirm_password' => 'required|same:password',
                ], ['username.unique' => 'Username already exists!']);

                $account = Admin::create([
                    'name' => $request->name,
                    'username' => $request->username,
                    'password' => $request->password,
                ]);
            }

            $guard = 'admin';
            $roleName = $type; // admin -> admin role, user -> user role
        } else {
            if (!$admin->hasPerm('manage_barangay_accounts')) {
                abort(403, 'You do not have permission to create barangay accounts.');
            }
            $request->validate([
                'barangay_name' => [
                    'required', 'string',
                    Rule::unique('barangays', 'barangay_name'),
                    Rule::in(self::OFFICIAL_BARANGAYS),
                ],
                'password' => 'required|string|min:6',
                'confirm_password' => 'required|same:password',
            ], ['barangay_name.unique' => 'This barangay account already exists!']);

            $username = str_replace(' ', '_', $request->barangay_name);
            $request->validate(['barangay_username' => 'required|string|unique:barangays,username']);

            $account = Barangay::create([
                'barangay_name' => $request->barangay_name,
                'username' => $username,
                'address' => $request->address,
                'password' => $request->password,
                'last_active' => null,
                'disaster_open' => 0,
            ]);
            $guard = 'barangay';
            $roleName = 'barangay';
        }

        $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
        if ($role) {
            $account->syncRoles([$role]);
        }

        $required = PermissionHelper::requiredFor($roleName);
        $custom = $request->has('permissions') ? array_keys($request->permissions) : [];
        $account->syncPermissions(array_values(array_unique(array_merge($required, $custom))));

        if ($type === 'user') {
            $sent = AccountInvitation::invite($account);
            if ($sent) {
                return back()->with('success', 'User invited! A sign-up link was emailed to ' . $account->email . '.');
            }

            return back()
                ->with('success', 'User account created.')
                ->with('invite_warning', 'The invitation email could not be sent (SMTP not configured yet). Share this sign-up link with the invitee instead:')
                ->with('invite_link', AccountInvitation::link($account));
        }

        return back()->with('success', ucfirst($type) . ' account created successfully!');
    }

    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $type = $request->input('account_type');
        $id = (int) $request->input('account_id');

        $request->validate([
            'account_type' => 'required|in:admin,barangay,user',
            'password' => 'nullable|min:6',
            'confirm_password' => 'nullable|same:password',
        ]);

        if ($type === 'admin' || $type === 'user') {
            if (!$admin->hasPerm('manage_admin_accounts')) {
                abort(403, 'You do not have permission to edit admin accounts.');
            }
            $request->validate([
                'account_id' => 'required|exists:admins,id',
                'name' => 'required|string|max:255',
                'username' => ['nullable', 'string', 'max:255', Rule::unique('admins', 'username')->ignore($id)],
                'email' => [
                    Rule::requiredIf($type === 'user'),
                    'nullable',
                    'email',
                    Rule::unique('admins', 'email')->ignore($id),
                ],
            ], ['email.unique' => 'An account with that email already exists!']);
            $account = Admin::findOrFail($id);

            $account->name = $request->name;
            if ($request->filled('username')) {
                $account->username = $request->username;
            } elseif ($type === 'user' && !$account->hasPendingInvite()) {
                return back()->with('error', 'Username is required for this account.')->withInput();
            }
            if ($type === 'user' && $request->filled('email')) {
                $account->email = $request->email;
            }
            if ($request->filled('password')) {
                $account->password = $request->password;
            }
            $account->save();
            $guard = 'admin';
            $roleName = $request->role;
        } else {
            if (!$admin->hasPerm('manage_barangay_accounts')) {
                abort(403, 'You do not have permission to edit barangay accounts.');
            }
            $request->validate([
                'account_id' => 'required|exists:barangays,barangay_id',
                'barangay_name' => [
                    'required', 'string',
                    Rule::unique('barangays', 'barangay_name')->ignore($id, 'barangay_id'),
                    Rule::in(self::OFFICIAL_BARANGAYS),
                ],
            ], ['barangay_name.unique' => 'This barangay account already exists!']);

            $username = str_replace(' ', '_', $request->barangay_name);
            $request->validate(['barangay_username' => 'required|string|unique:barangays,username,' . $id . ',barangay_id']);

            $account = Barangay::findOrFail($id);
            $account->barangay_name = $request->barangay_name;
            $account->username = $username;
            if ($request->filled('address')) {
                $account->address = $request->address;
            }
            if ($request->filled('password')) {
                $account->password = $request->password;
            }
            $account->save();
            $guard = 'barangay';
            $roleName = $request->role;
        }

        $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
        if ($role) {
            $account->syncRoles([$role]);
        }

        $required = PermissionHelper::requiredFor($roleName);
        $custom = $request->has('permissions') ? array_keys($request->permissions) : [];
        $account->syncPermissions(array_values(array_unique(array_merge($required, $custom))));

        if (($type === 'admin' || $type === 'user') && (int) Auth::guard('admin')->id() === (int) $account->id) {
            $request->session()->put('admin_username', $account->username);
        }

        return back()->with('success', ucfirst($type) . ' account updated successfully!');
    }

    public function resendInvite(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_admin_accounts')) {
            abort(403, 'You do not have permission to invite admin accounts.');
        }

        $request->validate(['account_id' => 'required|exists:admins,id']);

        $account = Admin::findOrFail((int) $request->account_id);

        if (!$account->email) {
            return back()->withErrors(['invite_email' => 'This account has no email address yet.']);
        }

        if ($account->username !== null) {
            return back()->withErrors(['invite_email' => 'This account already has a username, so it cannot be re-invited. Use Edit to change its email, or delete it to start over.']);
        }

        $sent = AccountInvitation::invite($account);

        if ($sent) {
            return back()->with('success', 'Invitation email sent to ' . $account->email . '.');
        }

        return back()
            ->with('success', 'Invitation link regenerated.')
            ->with('invite_warning', 'The invitation email could not be sent (SMTP not configured yet). Share this sign-up link with the invitee instead:')
            ->with('invite_link', AccountInvitation::link($account));
    }

    public function destroy(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $type = $request->input('account_type');
        $id = (int) $request->input('account_id');

        $request->validate(['account_type' => 'required|in:admin,barangay,user']);

        if ($type === 'admin' || $type === 'user') {
            if (!$admin->hasPerm('manage_admin_accounts')) {
                abort(403, 'You do not have permission to delete admin accounts.');
            }
            if ($id === (int) Auth::guard('admin')->id()) {
                return back()->with('error', 'You cannot delete your own account!');
            }
            $account = Admin::find($id);
        } else {
            if (!$admin->hasPerm('manage_barangay_accounts')) {
                abort(403, 'You do not have permission to delete barangay accounts.');
            }
            $account = Barangay::find($id);
        }

        if ($account) {
            $account->roles()->detach();
            $account->permissions()->detach();
            $account->delete();
        }

        return back()->with('success', ucfirst($type) . ' account deleted successfully!');
    }
}