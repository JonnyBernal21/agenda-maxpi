<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->string('vehicle_type', 20)->nullable();
            $table->string('vehicle_label')->nullable();
            $table->json('scores')->nullable();
            $table->decimal('average', 3, 1)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();

            $table->unique('reserva_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_evaluations');
    }
};
