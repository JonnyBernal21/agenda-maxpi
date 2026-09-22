<?php

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        Schema::table('student_payments', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
        });

        $now = now();
        $adminId = DB::table('roles')->where('slug', Role::ADMIN)->value('id');
        $salesId = null;

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

            if ($permission['key'] === 'sales.view') {
                $salesId = $id;
            }

            if ($adminId && ! DB::table('permission_role')->where('permission_id', $id)->where('role_id', $adminId)->exists()) {
                DB::table('permission_role')->insert([
                    'permission_id' => $id,
                    'role_id' => $adminId,
                ]);
            }
        }

        if ($salesId === null) {
            return;
        }

        $reportsId = DB::table('permissions')->where('key', 'reports.view')->value('id');

        if (! $reportsId) {
            return;
        }

        $roleIds = DB::table('permission_role')
            ->where('permission_id', $reportsId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $alreadyAssigned = DB::table('permission_role')
                ->where('permission_id', $salesId)
                ->where('role_id', $roleId)
                ->exists();

            if (! $alreadyAssigned) {
                DB::table('permission_role')->insert([
                    'permission_id' => $salesId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $salesId = DB::table('permissions')->where('key', 'sales.view')->value('id');

        if ($salesId) {
            DB::table('permission_role')->where('permission_id', $salesId)->delete();
            DB::table('permissions')->where('id', $salesId)->delete();
        }

        Schema::table('student_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
