<?php

use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\TelegramUser;
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
            $table->foreignIdFor(Bot::class)->constrained();
            $table->unsignedBigInteger('telegram_user_peer_id');
            $table->foreign('telegram_user_peer_id')
                ->references('peer_id')
                ->on('telegram_users');
            $table->integer('power')->default(0);
            $table->decimal('balance', 65, 30)->default(0);
            $table->string('state')->nullable();
            $table->enum('menu', ['main', 'admin'])->default('main');
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();

            $table->unique(['telegram_user_peer_id', 'bot_id']);
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
