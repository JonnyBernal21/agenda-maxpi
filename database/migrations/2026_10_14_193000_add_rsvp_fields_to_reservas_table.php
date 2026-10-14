<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('rsvp_token', 16)->nullable()->unique()->after('status');
            $table->string('rsvp_response', 20)->nullable()->after('rsvp_token');
            $table->timestamp('rsvp_at')->nullable()->after('rsvp_response');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropUnique(['rsvp_token']);
            $table->dropColumn(['rsvp_token', 'rsvp_response', 'rsvp_at']);
        });
    }
};
