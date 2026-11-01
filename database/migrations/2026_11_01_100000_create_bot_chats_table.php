<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->string('type', 16);
            $table->string('title')->nullable();
            $table->string('username')->nullable();
            $table->string('bot_status', 16)->default('member');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['bot_id', 'chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_chats');
    }
};
