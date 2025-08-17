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
        Schema::create('rides_drop_offs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_id');
            $table->foreign('ride_id')->references('id')->on('rides')->onUpdate('cascade')->onDelete('cascade');
            $table->longText('drop_location');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rides_drop_offs');
    }
};
