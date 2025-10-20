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
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->string('bot_token')->nullable();
            $table->string('unique_id')->unique();
            $table->string('secret_token')->nullable();
            $table->unsignedBigInteger('bot_owner_peer_id')->nullable();
            $table->timestamp('activated_until')->nullable()->useCurrent();
            $table->timestamp('suspended_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->json('data')->nullable();

            $table->foreign('bot_owner_peer_id')->references('peer_id')->on('telegram_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
