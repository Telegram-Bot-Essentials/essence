<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menus are named in config (tbe-essence.keyboard.menus) rather than fixed
 * to "main" and "admin", so bot_users.menu becomes a plain string. Existing
 * values are already valid menu names and are kept as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->string('menu', 64)->default('main')->change();
        });
    }

    /**
     * Back to the enum. A user on a menu the enum cannot hold is moved to
     * "main": landing on the main menu beats failing the rollback.
     */
    public function down(): void
    {
        DB::table('bot_users')->whereNotIn('menu', ['main', 'admin'])->update(['menu' => 'main']);

        Schema::table('bot_users', function (Blueprint $table) {
            $table->enum('menu', ['main', 'admin'])->default('main')->change();
        });
    }
};
