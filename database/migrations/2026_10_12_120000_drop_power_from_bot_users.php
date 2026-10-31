<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The app's Gate decides who may use a handler, so the numeric level is
 * gone. An app that relied on it moves those users to its own model in a
 * migration that runs before this.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->dropColumn('power');
        });
    }

    /** The column comes back empty: who held which level is not kept. */
    public function down(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->integer('power')->default(0);
        });
    }
};
