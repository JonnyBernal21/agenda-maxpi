<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });

        DB::table('students')->where('email', '')->update(['email' => null]);
        DB::table('students')->where('phone', '')->update(['phone' => null]);
    }

    public function down(): void
    {
        DB::table('students')->whereNull('email')->update(['email' => '']);
        DB::table('students')->whereNull('phone')->update(['phone' => '']);

        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });
    }
};
