<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('state_data', function (Blueprint $table) {
            $table->timestamps();
        });

        // Backfill existing rows with the current time rather than leaving
        // created_at null, so Prunable's age check does not treat every
        // pre-existing row as instantly eligible for deletion.
        DB::table('state_data')->whereNull('created_at')->update([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('state_data', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
