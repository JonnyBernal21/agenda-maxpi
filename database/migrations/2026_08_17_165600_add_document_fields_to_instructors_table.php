<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('country');
            $table->string('dni_front_path')->nullable()->after('photo_path');
            $table->string('dni_back_path')->nullable()->after('dni_front_path');
            $table->string('address_proof_path')->nullable()->after('dni_back_path');
        });
    }

    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropColumn([
                'photo_path',
                'dni_front_path',
                'dni_back_path',
                'address_proof_path',
            ]);
        });
    }
};
