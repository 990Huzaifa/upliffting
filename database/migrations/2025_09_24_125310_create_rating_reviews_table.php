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
        Schema::create('rating_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_id');
            $table->foreign('ride_id')->references('id')->on('rides')->onDelete('cascade');
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('rider_id');
            $table->foreign('rider_id')->references('id')->on('users')->onDelete('cascade');
            $table->tinyInteger('rating')->default(0);
            $table->enum('send_by', ['customer_to_rider', 'rider_to_customer']);
            $table->text('review')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating_reviews');
    }
};
