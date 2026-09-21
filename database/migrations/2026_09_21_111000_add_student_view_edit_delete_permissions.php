<?php

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<string> */
    private const NEW_KEYS = [
        'students.view',
        'students.edit',
        'students.delete',
    ];

    public function up(): void
    {
        $now = now();
        $adminId = DB::table('roles')->where('slug', Role::ADMIN)->value('id');
        $manageId = DB::table('permissions')->where('key', 'students.manage')->value('id');
        $newIds = [];

        foreach (PermissionCatalog::all() as $permission) {
            $id = DB::table('permissions')->where('key', $permission['key'])->value('id');

            if (! $id) {
                $id = DB::table('permissions')->insertGetId([
                    'key' => $permission['key'],
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('permissions')->where('id', $id)->update([
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'updated_at' => $now,
                ]);
            }

            if (in_array($permission['key'], self::NEW_KEYS, true)) {
                $newIds[] = $id;
            }

            if ($adminId && ! DB::table('permission_role')->where('permission_id', $id)->where('role_id', $adminId)->exists()) {
                DB::table('permission_role')->insert([
                    'permission_id' => $id,
                    'role_id' => $adminId,
                ]);
            }
        }

        if ($manageId === null || $newIds === []) {
            return;
        }

        $roleIds = DB::table('permission_role')
            ->where('permission_id', $manageId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            foreach ($newIds as $permissionId) {
                $alreadyAssigned = DB::table('permission_role')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $alreadyAssigned) {
                    DB::table('permission_role')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('key', self::NEW_KEYS)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
