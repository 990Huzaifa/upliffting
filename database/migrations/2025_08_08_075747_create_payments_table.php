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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_id');
            $table->foreign('ride_id')->references('id')->on('rides')->onUpdate('set null')->onDelete('set null');
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->onUpdate('set null')->onDelete('set null');
            $table->unsignedBigInteger('rider_id');
            $table->foreign('rider_id')->references('id')->on('users')->onUpdate('set null')->onDelete('set null');
            $table->decimal('amount', 18, 2);
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('user_accounts')->onUpdate('set null')->onDelete('set null');
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
