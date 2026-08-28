<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('plate_photo_path')->nullable()->after('owner_id');
            $table->string('circulation_card_path')->nullable()->after('plate_photo_path');
            $table->string('front_photo_path')->nullable()->after('circulation_card_path');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'plate_photo_path',
                'circulation_card_path',
                'front_photo_path',
            ]);
        });
    }
};
