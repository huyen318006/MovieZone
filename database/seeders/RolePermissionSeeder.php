<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RolePermission::truncate();

        // ── ADMIN: Tất cả permissions ──
        $adminRole = Role::where('name', 'ADMIN')->first();
        if ($adminRole) {
            $allPermissions = Permission::pluck('id');
            foreach ($allPermissions as $permId) {
                RolePermission::create([
                    'role_id'       => $adminRole->id,
                    'permission_id' => $permId,
                ]);
            }
        }

        // ── STAFF: Chỉ các quyền tra cứu booking ──
        $staffRole = Role::where('name', 'STAFF')->first();
        if ($staffRole) {
            $staffPermissions = Permission::whereIn('name', [
                'booking.lookup',
                'movie.view',
                'ticket.checkin',
            ])->pluck('id');

            foreach ($staffPermissions as $permId) {
                RolePermission::create([
                    'role_id'       => $staffRole->id,
                    'permission_id' => $permId,
                ]);
            }
        }
    }
}
