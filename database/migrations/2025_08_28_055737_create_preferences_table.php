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
        Schema::create('preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('email')->unique()->nullable();
            $table->string('display_name')->nullable();
            $table->string('image')->nullable();
            $table->string('bio')->nullable();
            $table->string('visibility')->default('Public');
            $table->boolean('call_alerts')->default(true);
            $table->boolean('new_messages')->default(true);
            $table->boolean('subscription_reminders')->default(true);
            $table->boolean('call_rom_unknown')->default(true);
            $table->boolean('show_online')->default(true);
            $table->boolean('share_profile')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};
