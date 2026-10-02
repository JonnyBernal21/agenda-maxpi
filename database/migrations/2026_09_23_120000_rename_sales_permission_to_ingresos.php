<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('key', 'sales.view')
            ->update(['name' => 'Ver ingresos']);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('key', 'sales.view')
            ->update(['name' => 'Ver ventas']);
    }
};
