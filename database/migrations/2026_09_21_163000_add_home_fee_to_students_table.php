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
            $table->decimal('home_fee_percent', 5, 2)->default(0)->after('meeting_lng');
            $table->decimal('home_fee_amount', 10, 2)->default(0)->after('home_fee_percent');
        });

        $students = DB::table('students')->where('is_home_class', 1)->get(['id', 'course_id', 'payment_subtotal']);

        foreach ($students as $student) {
            $courseCost = (float) (DB::table('courses')->where('id', $student->course_id)->value('cost') ?? 0);
            $amount = round(max(0, (float) $student->payment_subtotal - $courseCost), 2);
            $percent = $courseCost > 0 ? round(($amount / $courseCost) * 100, 2) : 0.0;

            DB::table('students')->where('id', $student->id)->update([
                'home_fee_amount' => $amount,
                'home_fee_percent' => $percent,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['home_fee_percent', 'home_fee_amount']);
        });
    }
};
