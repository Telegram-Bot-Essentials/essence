<?php

use Elyar\TelegramBotEssentials\Models\Bot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bot::class)->unique()->constrained();
            $table->boolean('bot_status')->default(true);
            $table->boolean('pay_with_card')->default(false);
            $table->char('language', 2)->default('en');
            $table->string('pay_to_card_number')->nullable();
            $table->string('pay_to_card_name')->nullable();
            $table->bigInteger('transactions_chat_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
