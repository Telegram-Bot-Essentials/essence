<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who a bot message with inline buttons was sent for, in a group. Anyone in
 * a group can press any button, so presses on someone else's message are
 * turned away (see MessageOwner and CallbackQueryBus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->bigInteger('message_id');
            $table->foreignId('bot_user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bot_id', 'chat_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_owners');
    }
};
