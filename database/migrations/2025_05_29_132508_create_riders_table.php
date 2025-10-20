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
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->longText('stripe_account_id')->nullable();
            $table->boolean('is_stripe_verified')->default(0);
            $table->longText('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->longText('license_photo')->nullable();
            $table->bigInteger('total_rides')->default(0);
            $table->bigInteger('driving_experience')->nullable();
            $table->integer('current_rating')->default(0);
            $table->boolean('background_verfied')->default(0);
            $table->enum('is_online', ['online', 'offline'])->default('offline');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
