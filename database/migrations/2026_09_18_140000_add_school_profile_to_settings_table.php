<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('timezone')->default('America/Mexico_City')->after('logo_path');
            $table->string('phone')->nullable()->after('timezone');
            $table->string('email')->nullable()->after('phone');
            $table->string('address')->nullable()->after('email');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('zip')->nullable()->after('state');
            $table->string('country')->default('México')->after('zip');
            $table->string('currency', 3)->default('MXN')->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'phone',
                'email',
                'address',
                'city',
                'state',
                'zip',
                'country',
                'currency',
            ]);
        });
    }
};
