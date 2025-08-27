<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('surge_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_type_rate_id');
            $table->foreign('vehicle_type_rate_id')->references('id')->on('vehicle_type_rates');
            $table->time('start_time')->nullable();   // e.g. "17:00" (5 PM)
            $table->time('end_time')->nullable();     // e.g. "19:00" (7 PM)
            $table->decimal('surge_rate', 8, 2);
            $table->set('day_of_week', [
                'mon','tue','wed','thu','fri','sat','sun'
            ])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surge_rates');
    }
};
