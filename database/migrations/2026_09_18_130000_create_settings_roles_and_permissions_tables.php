<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('group');
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->unique(['permission_id', 'role_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Agenda MaxPi');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        $now = now();

        $adminId = DB::table('roles')->insertGetId([
            'name' => 'Admin',
            'slug' => 'admin',
            'is_admin' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $receptionistId = DB::table('roles')->insertGetId([
            'name' => 'Recepcionista',
            'slug' => 'recepcionista',
            'is_admin' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $accountantId = DB::table('roles')->insertGetId([
            'name' => 'Contador',
            'slug' => 'contador',
            'is_admin' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionIds = [];

        foreach (PermissionCatalog::all() as $permission) {
            $permissionIds[$permission['key']] = DB::table('permissions')->insertGetId([
                'key' => $permission['key'],
                'name' => $permission['name'],
                'group' => $permission['group'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $adminId,
            ]);
        }

        foreach ([
            'students.manage',
            'instructors.manage',
            'reservas.manage',
            'emails.view',
        ] as $key) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionIds[$key],
                'role_id' => $receptionistId,
            ]);
        }

        foreach (['expenses.manage', 'reports.view'] as $key) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionIds[$key],
                'role_id' => $accountantId,
            ]);
        }

        DB::table('settings')->insert([
            'company_name' => 'Agenda MaxPi',
            'logo_path' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        DB::table('users')->update(['role_id' => $adminId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('settings');
    }
};
