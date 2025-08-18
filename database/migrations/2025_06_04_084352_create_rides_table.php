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
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->onUpdate('set null')->onDelete('set null');
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->foreign('rider_id')->references('id')->on('users')->onUpdate('set null')->onDelete('set null');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onUpdate('set null')->onDelete('set null');
            $table->unsignedBigInteger('promo_code_id')->nullable();
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->onUpdate('set null')->onDelete('set null');
            $table->unsignedBigInteger('vehicle_type_rate_id')->nullable();
            $table->foreign('vehicle_type_rate_id')->references('id')->on('vehicle_type_rates')->onUpdate('set null')->onDelete('set null');
            $table->longText('pickup_location');
            $table->decimal('distance', 8, 2)->nullable();
            $table->bigInteger('duration')->nullable();  
            $table->decimal('base_fare', 8, 2);
            $table->decimal('discount_amount', 8, 2)->nullable();
            // pending(for schedule booking), finding, on a way, arrived, started, cancelled, completed
            $table->enum('status', ['pending', 'finding', 'on a way', 'arrived', 'started', 'cancelled', 'completed'])->default('pending');
            $table->decimal('final_fare', 8, 2)->nullable();
            $table->longText('reason')->nullable();
            $table->bigInteger('current_rating')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
