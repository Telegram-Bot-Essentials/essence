<?php

use Elyar\TelegramBotEssentials\Models\PaymentAttempt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('by_wallet_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PaymentAttempt::class)->constrained()->cascadeOnDelete();
            $table->decimal('amount', 20, 2);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('by_wallet_attempts');
    }
};
