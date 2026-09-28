<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_dashboard' => 'View Dashboard',
            'manage_admin_accounts' => 'Manage Admin Accounts',
            'manage_barangay_accounts' => 'Manage Barangay Accounts',
            'manage_municipal_info' => 'Manage Municipal Info',
            'view_barangay_info' => 'View Barangay Info',
            'edit_barangay_info' => 'Edit Barangay Info',
            'manage_disaster_format' => 'Manage Disaster Format',
            'review_disaster_reports' => 'Review Disaster Reports',
            'submit_disaster_report' => 'Submit Disaster Report',
            'edit_disaster_report' => 'Edit Disaster Report',
            'view_hazard_map' => 'View Hazard Map',
            'edit_hazard_map' => 'Edit Hazard Map',
            'manage_messages' => 'Manage Messages',
            'manage_announcements' => 'Manage Announcements',
            'view_announcements' => 'View Announcements',
            'manage_relief' => 'Manage Relief',
            'confirm_relief' => 'Confirm Relief',
            'view_municipal_contacts' => 'View Municipal Contacts',
        ];

        foreach ($permissions as $name => $label) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'barangay']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $barangayRole = Role::firstOrCreate(['name' => 'barangay', 'guard_name' => 'barangay']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userAdmin = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'admin']);
        $userBarangay = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'barangay']);

        $adminPermissions = [
            'view_dashboard',
            'manage_admin_accounts',
            'manage_barangay_accounts',
            'manage_municipal_info',
            'view_barangay_info',
            'manage_disaster_format',
            'review_disaster_reports',
            'edit_disaster_report',
            'view_hazard_map',
            'manage_messages',
            'manage_announcements',
            'manage_relief',
            'view_municipal_contacts',
        ];

        $barangayPermissions = [
            'view_dashboard',
            'view_barangay_info',
            'edit_barangay_info',
            'submit_disaster_report',
            'edit_disaster_report',
            'view_hazard_map',
            'edit_hazard_map',
            'manage_messages',
            'view_announcements',
            'confirm_relief',
            'view_municipal_contacts',
        ];

        $userPermissions = [
            'view_dashboard',
            'view_barangay_info',
            'view_hazard_map',
            'view_announcements',
            'view_municipal_contacts',
        ];

        $admin->syncPermissions($adminPermissions);
        $barangayRole->syncPermissions($barangayPermissions);
        $user->syncPermissions($userPermissions);
        $userAdmin->syncPermissions($userPermissions);
        $userBarangay->syncPermissions($userPermissions);
    }
}
