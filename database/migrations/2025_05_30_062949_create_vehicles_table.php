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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_of');
            $table->foreign('vehicle_of')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('registration_number');
            $table->longText('registration_certificate')->nullable();
            $table->date('insurance_validity')->nullable();
            $table->string('make');
            $table->string('model');
            $table->string('year');
            $table->string('color');
            $table->longText('vehicle_insurance')->nullable();
            $table->longText('photos')->nullable();
            $table->unsignedBigInteger('vehicle_type_rate_id');
            $table->foreign('vehicle_type_rate_id')->references('id')->on('vehicle_type_rates')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('is_driving', ['active', 'inactive'])->default('active');
            $table->enum('is_active', ['active', 'inactive'])->default('active');
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('admins')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
