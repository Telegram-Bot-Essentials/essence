<?php

use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_users', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bot::class)->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('telegram_user_peer_id');
            $table->foreign('telegram_user_peer_id')
                ->references('peer_id')
                ->on('telegram_users')
                ->cascadeOnDelete();
            $table->integer('power')->default(0);
            $table->integer('balance')->default(0);
            $table->string('state')->nullable();
            $table->enum('menu', ['main', 'admin'])->default('main');
            $table->boolean('suspend')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['telegram_user_id', 'bot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_users');
    }
};
