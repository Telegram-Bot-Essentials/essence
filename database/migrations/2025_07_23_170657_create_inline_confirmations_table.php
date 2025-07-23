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
        Schema::create('inline_confirmations', function (Blueprint $table) {
            $table->id();
            $table->string('confirmation_text')->nullable();
            $table->string('callback_data', 64);
            $table->string('back_callback_data', 64);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inline_confirmations');
    }
};
