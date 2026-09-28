<?php

namespace App\Support;

class PermissionHelper
{
    public const PERMISSIONS = [
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

    public const REQUIRED_PERMISSIONS = [
        'admin' => [
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
        ],
        'barangay' => [
            'view_dashboard',
            'edit_barangay_info',
            'submit_disaster_report',
            'edit_disaster_report',
            'view_hazard_map',
            'edit_hazard_map',
            'manage_messages',
            'view_announcements',
            'confirm_relief',
        ],
        'user' => [
            'view_dashboard',
        ],
    ];

    public const GROUPS = [
        'Dashboard' => ['view_dashboard'],
        'Account Management' => ['manage_admin_accounts', 'manage_barangay_accounts'],
        'Barangay Info' => ['view_barangay_info', 'edit_barangay_info'],
        'Municipal Info' => ['manage_municipal_info', 'view_municipal_contacts'],
        'Disaster Report' => ['manage_disaster_format', 'review_disaster_reports', 'submit_disaster_report', 'edit_disaster_report'],
        'Hazard Map' => ['view_hazard_map', 'edit_hazard_map'],
        'Messages' => ['manage_messages'],
        'Announcements' => ['manage_announcements', 'view_announcements'],
        'Relief' => ['manage_relief', 'confirm_relief'],
    ];

    public static function requiredFor(string $role): array
    {
        return self::REQUIRED_PERMISSIONS[$role] ?? [];
    }
}
