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
        Schema::create('vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');

            $table->longText('headlights')->nullable();
            $table->boolean('is_headlights')->default(0);

            $table->longText('airlights')->nullable();
            $table->boolean('is_airlights')->default(0);

            $table->longText('indicators')->nullable();
            $table->boolean('is_indicators')->default(0);

            $table->longText('stop_lights')->nullable();
            $table->boolean('is_stop_lights')->default(0);

            $table->longText('windshield')->nullable();
            $table->boolean('is_windshield')->default(0);

            $table->longText('windshield_wipers')->nullable();
            $table->boolean('is_windshield_wipers')->default(0);

            $table->longText( 'safty_belt')->nullable();
            $table->boolean('is_safty_belt')->default(0);

            $table->longText('tires')->nullable();
            $table->boolean('is_tires')->default(0);

            $table->longText('speedometer')->nullable();
            $table->boolean('is_speedometer')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_inspections');
    }
};
