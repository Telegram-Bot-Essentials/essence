<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Everyone so far came through the private chat, so the default holds
        // for existing rows. Only a user first met elsewhere starts without it.
        Schema::table('bot_users', function (Blueprint $table) {
            $table->boolean('has_private_chat')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->dropColumn('has_private_chat');
        });
    }
};
