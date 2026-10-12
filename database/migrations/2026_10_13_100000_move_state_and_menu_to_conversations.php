<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * State and the reply menu belong to a conversation - one user in one chat
 * (and forum topic) - not to the user, so a user's flow in a group no
 * longer runs against their private-chat state. Every user's current state
 * and menu move into their private conversation (chat_id = their peer id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_user_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('chat_id');
            // 0 rather than null outside a forum topic: a unique key treats
            // nulls as distinct, which would let a race mint two rows.
            $table->bigInteger('thread_id')->default(0);
            $table->text('state')->nullable();
            $table->string('menu', 64)->default('main');
            $table->timestamps();

            $table->unique(['bot_user_id', 'chat_id', 'thread_id']);
        });

        DB::table('bot_users')->orderBy('id')->chunkById(500, function ($users) {
            $now = now();

            DB::table('conversations')->insert($users->map(fn ($user) => [
                'bot_id' => $user->bot_id,
                'bot_user_id' => $user->id,
                'chat_id' => $user->telegram_user_peer_id,
                'thread_id' => 0,
                'state' => $user->state,
                'menu' => $user->menu ?? 'main',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        Schema::table('bot_users', function (Blueprint $table) {
            $table->dropColumn(['state', 'menu']);
        });
    }

    /** Back onto the user, from each user's private conversation. */
    public function down(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->text('state')->nullable();
            $table->string('menu', 64)->default('main');
        });

        DB::table('conversations')
            ->join('bot_users', 'bot_users.id', '=', 'conversations.bot_user_id')
            ->whereColumn('conversations.chat_id', 'bot_users.telegram_user_peer_id')
            ->where('conversations.thread_id', 0)
            ->select('conversations.id', 'conversations.bot_user_id', 'conversations.state', 'conversations.menu')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('bot_users')->where('id', $row->bot_user_id)->update([
                        'state' => $row->state,
                        'menu' => $row->menu,
                    ]);
                }
            }, 'conversations.id', 'id');

        Schema::dropIfExists('conversations');
    }
};
