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
        Schema::create('rider_tips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_id');
            $table->unsignedBigInteger('rider_id');
            $table->decimal('percent', 5, 2)->nullable();
            $table->decimal('amount', 8, 2);
            $table->foreign('ride_id')->references('id')->on('rides')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('rider_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_tips');
    }
};
