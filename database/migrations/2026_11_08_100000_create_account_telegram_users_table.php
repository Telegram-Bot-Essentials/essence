<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which Telegram accounts a web account (the app's own user model) signs in
 * for. One account may link many; a Telegram account links to one account at
 * most. No foreign key to the account: its table is the app's to name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_telegram_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('telegram_user_peer_id')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_telegram_users');
    }
};
