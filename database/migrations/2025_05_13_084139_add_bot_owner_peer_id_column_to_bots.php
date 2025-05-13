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
        Schema::table('bots', function (Blueprint $table) {
            $table->bigInteger('bot_owner_peer_id')->nullable();
            $table->foreign('bot_owner_peer_id')->references('peer_id')->on('telegram_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropForeign('bots_bot_owner_peer_id_foreign');
            $table->dropColumn('bot_owner_peer_id');
        });
    }
};
