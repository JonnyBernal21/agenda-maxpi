<?php

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $adminId = DB::table('roles')->where('slug', Role::ADMIN)->value('id');

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
            }

            if (! $adminId) {
                continue;
            }

            $alreadyAssigned = DB::table('permission_role')
                ->where('permission_id', $id)
                ->where('role_id', $adminId)
                ->exists();

            if (! $alreadyAssigned) {
                DB::table('permission_role')->insert([
                    'permission_id' => $id,
                    'role_id' => $adminId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('key', 'students.discount')->value('id');

        if (! $id) {
            return;
        }

        DB::table('permission_role')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();
    }
};
