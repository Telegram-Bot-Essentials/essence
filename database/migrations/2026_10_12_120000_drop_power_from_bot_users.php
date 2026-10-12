<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Access is decided by named permissions (see AuthorizesAccess), so the
 * numeric power level is gone. An app that granted admin through it moves
 * those users to its own access model in a migration that runs before this.
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
