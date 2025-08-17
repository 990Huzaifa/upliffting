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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('username');
            $table->string('email',225);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->bigInteger('phone');
            $table->string('nationality');
            $table->bigInteger('nat_id')->nullable();
            $table->longText('nat_id_photo')->nullable();
            $table->longText('avatar')->nullable();
            $table->longText('lat')->nullable();
            $table->longText('lng')->nullable();
            $table->longText('device_id');
            $table->enum('role', ['customer', 'rider'])->default('customer');
            $table->enum('status', ['active', 'inactive','suspended','pending'])->default('active');
            $table->enum('is_approved', ['unknown','approved','suspended','pending'])->default('unknown');
            $table->bigInteger('social_security_number')->nullable();
            $table->longText('google_id')->nullable();
            $table->longText('apple_id')->nullable();
            $table->longText('fcm_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
