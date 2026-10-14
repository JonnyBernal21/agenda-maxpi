<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('roles')->where('slug', Role::INSTRUCTOR)->exists()) {
            return;
        }

        $now = now();

        DB::table('roles')->insert([
            'name' => 'Instructor',
            'slug' => Role::INSTRUCTOR,
            'is_admin' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', Role::INSTRUCTOR)->delete();
    }
};
