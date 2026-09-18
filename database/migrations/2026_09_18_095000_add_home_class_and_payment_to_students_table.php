<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_home_class')->default(false)->after('country');
            $table->string('meeting_point')->nullable()->after('is_home_class');
            $table->decimal('meeting_lat', 10, 7)->nullable()->after('meeting_point');
            $table->decimal('meeting_lng', 10, 7)->nullable()->after('meeting_lat');
            $table->decimal('payment_subtotal', 10, 2)->default(0)->after('meeting_lng');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('payment_subtotal');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent');
            $table->decimal('payment_total', 10, 2)->default(0)->after('discount_amount');
            $table->string('payment_method')->nullable()->after('payment_total');
            $table->unsignedTinyInteger('payment_plan')->default(1)->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'is_home_class',
                'meeting_point',
                'meeting_lat',
                'meeting_lng',
                'payment_subtotal',
                'discount_percent',
                'discount_amount',
                'payment_total',
                'payment_method',
                'payment_plan',
            ]);
        });
    }
};
