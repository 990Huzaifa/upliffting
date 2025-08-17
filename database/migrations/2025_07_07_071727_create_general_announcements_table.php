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
        Schema::create('general_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('message')->nullable();
            $table->longText('image')->nullable();
            $table->longText('attachment')->nullable();
            $table->enum('priority', [1, 2, 3, 4, 5])->default(5);
            $table->enum('audience', ['all', 'riders', 'customers'])->default('all');
            $table->enum('status', ['draft', 'approved','pending'])->default('draft');
            $table->dateTime('scheduled_at')->nullable();
            $table->boolean('is_sent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_announcements');
    }
};
