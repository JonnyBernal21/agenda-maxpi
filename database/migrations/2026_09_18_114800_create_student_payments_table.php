<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('paid_at')->index();
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('students')
            ->where('payment_total', '>', 0)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function ($students) use ($now): void {
                foreach ($students as $student) {
                    DB::table('student_payments')->insert([
                        'student_id' => $student->id,
                        'amount' => $student->payment_total,
                        'paid_at' => \Illuminate\Support\Carbon::parse($student->created_at)->toDateString(),
                        'payment_method' => $student->payment_method,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
