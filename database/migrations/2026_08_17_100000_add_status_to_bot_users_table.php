<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Essence\Models\BotUser;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->enum('status', BotUser::STATUSES)
                ->default(BotUser::STATUS_ACTIVE)
                ->after('menu')
                ->index();
        });

        Schema::table('telegram_users', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->after('last_interaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn('deactivated_at');
        });
    }
};
