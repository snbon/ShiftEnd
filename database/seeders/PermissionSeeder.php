<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Reports permissions
            ['name' => 'reports.create', 'display_name' => 'Create Reports', 'description' => 'Can create new shift reports', 'category' => 'reports'],
            ['name' => 'reports.view', 'display_name' => 'View Reports', 'description' => 'Can view shift reports', 'category' => 'reports'],
            ['name' => 'reports.edit', 'display_name' => 'Edit Reports', 'description' => 'Can edit existing reports', 'category' => 'reports'],
            ['name' => 'reports.delete', 'display_name' => 'Delete Reports', 'description' => 'Can delete reports', 'category' => 'reports'],
            ['name' => 'reports.approve', 'display_name' => 'Approve Reports', 'description' => 'Can approve submitted reports', 'category' => 'reports'],
            ['name' => 'reports.submit', 'display_name' => 'Submit Reports', 'description' => 'Can submit reports for approval', 'category' => 'reports'],

            // User management permissions
            ['name' => 'users.invite', 'display_name' => 'Invite Users', 'description' => 'Can invite new users to the location', 'category' => 'users'],
            ['name' => 'users.view', 'display_name' => 'View Users', 'description' => 'Can view team members', 'category' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'description' => 'Can edit user roles and information', 'category' => 'users'],
            ['name' => 'users.remove', 'display_name' => 'Remove Users', 'description' => 'Can remove users from location', 'category' => 'users'],
            ['name' => 'users.assign_locations', 'display_name' => 'Assign to Locations', 'description' => 'Can assign users to multiple locations', 'category' => 'users'],

            // Settings permissions
            ['name' => 'settings.view', 'display_name' => 'View Settings', 'description' => 'Can view location settings', 'category' => 'settings'],
            ['name' => 'settings.edit', 'display_name' => 'Edit Settings', 'description' => 'Can edit location settings', 'category' => 'settings'],
            ['name' => 'settings.permissions', 'display_name' => 'Manage Permissions', 'description' => 'Can manage role permissions', 'category' => 'settings'],

            // Location management permissions
            ['name' => 'locations.create', 'display_name' => 'Create Locations', 'description' => 'Can create new locations', 'category' => 'locations'],
            ['name' => 'locations.edit', 'display_name' => 'Edit Locations', 'description' => 'Can edit location information', 'category' => 'locations'],
            ['name' => 'locations.delete', 'display_name' => 'Delete Locations', 'description' => 'Can delete locations', 'category' => 'locations'],
            ['name' => 'locations.view', 'display_name' => 'View Locations', 'description' => 'Can view location information', 'category' => 'locations'],

            // Analytics permissions
            ['name' => 'analytics.view', 'display_name' => 'View Analytics', 'description' => 'Can view reports and analytics', 'category' => 'analytics'],
            ['name' => 'analytics.export', 'display_name' => 'Export Data', 'description' => 'Can export reports and data', 'category' => 'analytics'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Set default role permissions
        $this->setDefaultRolePermissions();
    }

    private function setDefaultRolePermissions(): void
    {
        $ownerPermissions = [
            'reports.create', 'reports.view', 'reports.edit', 'reports.delete', 'reports.approve', 'reports.submit',
            'users.invite', 'users.view', 'users.edit', 'users.remove', 'users.assign_locations',
            'settings.view', 'settings.edit', 'settings.permissions',
            'locations.create', 'locations.edit', 'locations.delete', 'locations.view',
            'analytics.view', 'analytics.export'
        ];

        $managerPermissions = [
            'reports.create', 'reports.view', 'reports.edit', 'reports.approve', 'reports.submit',
            'users.invite', 'users.view', 'users.edit', 'users.remove',
            'settings.view',
            'locations.view',
            'analytics.view'
        ];

        $employeePermissions = [
            'reports.create', 'reports.view', 'reports.edit', 'reports.submit',
            'users.view',
            'locations.view'
        ];

        // Get all location IDs
        $locationIds = DB::table('locations')->pluck('id');

        foreach ($locationIds as $locationId) {
            // Set owner permissions
            foreach ($ownerPermissions as $permissionName) {
                $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
                if ($permissionId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'location_id' => $locationId,
                        'role' => 'owner',
                        'permission_id' => $permissionId,
                        'granted' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Set manager permissions
            foreach ($managerPermissions as $permissionName) {
                $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
                if ($permissionId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'location_id' => $locationId,
                        'role' => 'manager',
                        'permission_id' => $permissionId,
                        'granted' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Set employee permissions
            foreach ($employeePermissions as $permissionName) {
                $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
                if ($permissionId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'location_id' => $locationId,
                        'role' => 'employee',
                        'permission_id' => $permissionId,
                        'granted' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
